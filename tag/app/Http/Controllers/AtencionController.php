<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\ClienteEmpresa;
use App\Models\Estatus;
use App\Models\PersonalEmpresa;
use App\Models\Cliente;
use App\Models\Personal;
use App\Http\Resources\AtencionResource;
use App\Events\AtencionEstatusActualizado;
use App\Services\EstatusResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AtencionController extends Controller
{
    /**
     * Listar todas las atenciones
     *
     * Devuelve todas las atenciones activas.
     */
    public function index()
    {
        // withCount no es suficiente: necesitamos la última cotización y su OC.
        // Usamos eager loading con constraint para traer solo la cotización más reciente
        // por atención, evitando el N+1 que ejecutaba 2 queries por cada atención.
        $items = Atencion::with([
                'cliente',
                'personal',
                'origen',
                'estatus',
                'etapaComercial',
                'cotizaciones' => fn($q) => $q->orderByDesc('id')->limit(1),
                'cotizaciones.ordenCompra',
            ])
            ->orderBy('id')
            ->get();

        $result = $items->map(function (Atencion $atencion) {
            // Sin queries adicionales — todo está en memoria
            $cotizacion      = $atencion->cotizaciones->first();
            $id_cotizacion   = $cotizacion?->id;
            $id_orden_compra = $cotizacion?->ordenCompra?->id;

            $arr = $atencion->toArray();
            $arr['id_cotizacion']   = $id_cotizacion;
            $arr['id_orden_compra'] = $id_orden_compra;
            return $arr;
        });

        return AtencionResource::collection(collect($result));
    }

    /**
     * Crear una nueva atención
     *
     * Registra un nuevo ticket de atención al cliente asignando automáticamente un miembro del personal.
     *
     * @bodyParam id_cliente int required ID del cliente. Ejemplo: 1
     * @bodyParam id_origen_atencion int required ID del origen de la atención (red social / canal). Ejemplo: 1
     * @bodyParam asunto string required Asunto o motivo de la atención. Ejemplo: Consulta sobre pasaporte
     * @bodyParam notas_adicionales string Notas adicionales del operador. Ejemplo: El cliente prefiere contacto por WhatsApp
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_cliente' => ['required', 'exists:clientes,id'],
            'id_origen_atencion' => ['required', 'exists:origenes,id'],
            'asunto' => ['required', 'string', 'max:255'],
            'notas_adicionales' => ['nullable', 'string'],
        ]);

        $cliente = Cliente::find($data['id_cliente']);

        $personalId = $this->resolverPersonalAsignado($cliente->id);
        if (!$personalId) {
            return response()->json(['message' => 'No hay personal disponible para asignar la atención'], 422);
        }

        $estatusId = EstatusResolver::id('por aprobar');
        if (!$estatusId) {
            return response()->json(['message' => 'Estatus "por aprobar" no configurado en el catálogo'], 500);
        }

        $data['id_personal'] = $personalId;
        $data['estatus'] = $estatusId;

        $item = Atencion::create($data);

        // Registrar estatus inicial en el historial
        event(new AtencionEstatusActualizado(
            atencion: $item,
            estatusAnterior: null,
            estatusNuevo: $item->estatus,
            comentario: 'Atención creada',
        ));

        return new AtencionResource($item->load(['cliente', 'personal', 'origen', 'etapaComercial']));
    }

    /**
     * Obtener una atención específica
     */
    public function show(Atencion $atencion)
    {
        // Igual que index(): eager load para evitar queries adicionales
        $atencion->load([
            'cliente',
            'personal',
            'origen',
            'estatus',
            'etapaComercial',
            'cotizaciones' => fn($q) => $q->orderByDesc('id')->limit(1),
            'cotizaciones.ordenCompra',
        ]);

        $cotizacion      = $atencion->cotizaciones->first();
        $id_cotizacion   = $cotizacion?->id;
        $id_orden_compra = $cotizacion?->ordenCompra?->id;

        $arr = $atencion->toArray();
        $arr['id_cotizacion']   = $id_cotizacion;
        $arr['id_orden_compra'] = $id_orden_compra;

        return new AtencionResource((object) $arr);
    }

    /**
     * Actualizar una atención existente
     *
     * @bodyParam id_cliente int ID del cliente. Ejemplo: 1
     * @bodyParam id_personal int ID del personal asignado. Ejemplo: 1
     * @bodyParam id_origen_atencion int ID del origen de la atención. Ejemplo: 1
     * @bodyParam asunto string Asunto o motivo de la atención. Ejemplo: Cambio de itinerario
     * @bodyParam notas_adicionales string Notas adicionales. Ejemplo: Se requiere respuesta urgente
     * @bodyParam estatus int ID del estatus de la atención. Ejemplo: 1
     */
    public function update(Request $request, Atencion $atencion)
    {
        $data = $request->validate([
            'id_cliente' => ['sometimes', 'required', 'exists:clientes,id'],
            'id_personal' => ['sometimes', 'required', 'exists:personal,id'],
            'id_origen_atencion' => ['sometimes', 'required', 'exists:origenes,id'],
            'asunto' => ['sometimes', 'required', 'string', 'max:255'],
            'notas_adicionales' => ['sometimes', 'nullable', 'string'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
        ]);

        $estatusAnterior = $atencion->estatus;
        
        $atencion->update($data);
        $atencion->load(['cliente', 'personal', 'origen', 'estatus', 'etapaComercial']);

        if (isset($data['estatus']) && $data['estatus'] != $estatusAnterior) {
            event(new AtencionEstatusActualizado(
                atencion: $atencion,
                estatusAnterior: $estatusAnterior,
                estatusNuevo: $data['estatus'],
            ));
        }

        return new AtencionResource($atencion);
    }

    /**
     * Eliminar una atención
     *
     * Usa SoftDeletes nativo.
     */
    public function destroy(Atencion $atencion)
    {
        $atencion->delete();
        return response()->json(['data' => ['message' => 'Eliminada correctamente']]);
    }

    private function resolverPersonalAsignado(int $idCliente): ?int
    {
        $clienteEmpresa = ClienteEmpresa::where('id_cliente', $idCliente)
            ->orderBy('id')
            ->first();

        if ($clienteEmpresa) {
            $personalEmpresa = PersonalEmpresa::where('id_empresa', $clienteEmpresa->id_empresas)
                ->inRandomOrder()
                ->first();

            if ($personalEmpresa) {
                return $personalEmpresa->id_personal;
            }
        }

        $estatusConcluido = Estatus::where('estatus', 'aprobado')->value('id') ?? 0;
        $personalIds = Personal::pluck('id')->all(); // Ya no buscamos roles en Users. Todo Personal es un personal comercial.

        if (empty($personalIds)) {
            return null;
        }

        $conteos = DB::table('atenciones')
            ->select('id_personal', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at') // Nativo softDeletes en vez de borrado_logico
            ->where('estatus', '!=', $estatusConcluido->id)
            ->whereIn('id_personal', $personalIds)
            ->groupBy('id_personal')
            ->pluck('total', 'id_personal')
            ->all();

        $minimo = null;
        $candidatos = [];

        foreach ($personalIds as $personalId) {
            $total = (int) ($conteos[$personalId] ?? 0);

            if ($minimo === null || $total < $minimo) {
                $minimo = $total;
                $candidatos = [$personalId];
                continue;
            }

            if ($total === $minimo) {
                $candidatos[] = $personalId;
            }
        }

        if (empty($candidatos)) {
            return null;
        }

        return $candidatos[array_rand($candidatos)];
    }
}
