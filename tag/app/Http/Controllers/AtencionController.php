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
use Illuminate\Validation\Rule;

class AtencionController extends Controller
{
    /**
     * Listar todas las atenciones
     *
     * Devuelve todas las atenciones activas.
     */
    public function index(Request $request)
    {
        $query = Atencion::query();

        // Relaciones por defecto
        $query->with([
            'cliente' => fn($q) => $q->withTrashed(),
            'personal' => fn($q) => $q->withTrashed(),
            'origen' => fn($q) => $q->withTrashed(),
            'estatus',
            'etapaComercial' => fn($q) => $q->withTrashed(),
            'cotizaciones' => fn($q) => $q->orderByDesc('id')->limit(1),
            'cotizaciones.ordenCompra',
        ]);

        // Soporte para relaciones adicionales
        if ($request->has('include')) {
            $allowed = ['cliente', 'personal', 'origen', 'estatus', 'etapaComercial', 'cotizaciones'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (!empty($includes)) {
                $query->with($includes);
            }
        }

        $items = $query->orderBy('id')->get();

        return AtencionResource::collection($items);
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
            'id_cliente' => ['required', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'id_origen_atencion' => ['required', Rule::exists('origenes', 'id')->whereNull('deleted_at')],
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
            'cliente' => fn($q) => $q->withTrashed(),
            'personal' => fn($q) => $q->withTrashed(),
            'origen' => fn($q) => $q->withTrashed(),
            'estatus',
            'etapaComercial' => fn($q) => $q->withTrashed(),
            'cotizaciones' => fn($q) => $q->orderByDesc('id')->limit(1),
            'cotizaciones.ordenCompra',
        ]);

        return new AtencionResource($atencion);
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
            'id_cliente' => ['sometimes', 'required', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'id_personal' => ['sometimes', 'required', Rule::exists('personal', 'id')->whereNull('deleted_at')],
            'id_origen_atencion' => ['sometimes', 'required', Rule::exists('origenes', 'id')->whereNull('deleted_at')],
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
            ->where('estatus', '!=', $estatusConcluido)
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
