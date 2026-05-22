<?php

namespace App\Http\Controllers;

use App\Events\AtencionEstatusActualizado;
use App\Http\Resources\AtencionResource;
use App\Models\Atencion;
use App\Models\Cliente;
use App\Models\ClienteEmpresa;
use App\Models\EstadoAtencion;
use App\Models\Estatus;
use App\Models\Personal;
use App\Models\PersonalEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AtencionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Atencion::class, 'atencion');
    }

    /**
     * Listar todas las atenciones
     *
     * Devuelve todas las atenciones activas.
     *
     * @queryParam include string Relaciones a incluir. Example: cotizaciones. Values: cotizaciones
     */
    public function index(Request $request)
    {
        $query = Atencion::query();

        // Relaciones base (siempre necesarias para la tabla)
        $query->with([
            'cliente' => fn ($q) => $q->withTrashed(),
            'personal' => fn ($q) => $q->withTrashed(),
            'origen' => fn ($q) => $q->withTrashed(),
            'estadoAtencion',
            'etapaComercial' => fn ($q) => $q->withTrashed(),
        ]);

        // Cotizaciones: solo bajo demanda (consistente con CotizacionController y PersonalController)
        if ($request->has('include')) {
            $allowed = ['cotizaciones'];
            $includes = array_intersect(explode(',', $request->include), $allowed);

            if (in_array('cotizaciones', $includes)) {
                $query->with([
                    'cotizaciones' => fn ($q) => $q->orderByDesc('id'),
                    'cotizaciones.tasaCambio',
                    'cotizaciones.tipoCotizacion',
                    'cotizaciones.estadoCotizacion',
                    'cotizaciones.servicios.tipoServicio',
                    'cotizaciones.servicios.proveedor',
                    'cotizaciones.ordenCompra.estadoOrdenCompra',
                    'cotizaciones.ordenCompra.estadoFinanciero',
                    'cotizaciones.ordenCompra.estadoFinancieroEgreso',
                ]);
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
        if (! $personalId) {
            return response()->json(['message' => 'No hay personal disponible para asignar la atención'], 422);
        }

        $estado = EstadoAtencion::where('slug', 'abierta')->first();
        if (! $estado) {
            return response()->json(['message' => 'Estado "abierta" no configurado en el catálogo'], 500);
        }

        $data['id_personal'] = $personalId;
        $data['id_estado_atencion'] = $estado->id;

        // Asignar etapa comercial por defecto ("atencion")
        $etapaDefault = \App\Models\EtapaComercial::where('slug', 'atencion')->first();
        if ($etapaDefault) {
            $data['id_etapa_comercial'] = $etapaDefault->id;
        }

        $item = Atencion::create($data);

        // Registrar estatus inicial en el historial
        event(new AtencionEstatusActualizado(
            atencion: $item,
            estatusAnterior: null,
            estatusNuevo: $item->id_estado_atencion,
            comentario: 'Atención creada',
        ));

        return new AtencionResource($item->load(['cliente', 'personal', 'origen', 'estadoAtencion', 'etapaComercial']));
    }

    /**
     * Obtener una atención específica
     */
    public function show(Atencion $atencion)
    {
        // Igual que index(): eager load para evitar queries adicionales
        $atencion->load([
            'cliente' => fn ($q) => $q->withTrashed(),
            'personal' => fn ($q) => $q->withTrashed(),
            'origen' => fn ($q) => $q->withTrashed(),
            'estadoAtencion',
            'etapaComercial' => fn ($q) => $q->withTrashed(),
            'cotizaciones' => fn ($q) => $q->orderByDesc('id')->limit(1),
            'cotizaciones.ordenCompra.estadoOrdenCompra',
            'cotizaciones.ordenCompra.estadoFinanciero',
            'cotizaciones.ordenCompra.estadoFinancieroEgreso',
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
     * @bodyParam id_estado_atencion int ID del estado de la atención (catálogo estados-atenciones). Ejemplo: 1
     * @bodyParam id_etapa_comercial int ID de la etapa comercial (catálogo etapas-comerciales). Ejemplo: 1
     */
    public function update(Request $request, Atencion $atencion)
    {
        $data = $request->validate([
            'id_cliente' => ['sometimes', 'required', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'id_personal' => ['sometimes', 'required', Rule::exists('personal', 'id')->whereNull('deleted_at')],
            'id_origen_atencion' => ['sometimes', 'required', Rule::exists('origenes', 'id')->whereNull('deleted_at')],
            'asunto' => ['sometimes', 'required', 'string', 'max:255'],
            'notas_adicionales' => ['sometimes', 'nullable', 'string'],
            'id_estado_atencion' => ['sometimes', 'required', 'exists:estados_atenciones,id'],
            'id_etapa_comercial' => ['sometimes', 'required', 'exists:etapas_comerciales,id'],
        ]);

        $estatusAnterior = $atencion->id_estado_atencion;

        $atencion->update($data);
        $atencion->load(['cliente', 'personal', 'origen', 'estadoAtencion', 'etapaComercial']);

        if (isset($data['id_estado_atencion']) && $data['id_estado_atencion'] != $estatusAnterior) {
            event(new AtencionEstatusActualizado(
                atencion: $atencion,
                estatusAnterior: $estatusAnterior,
                estatusNuevo: $data['id_estado_atencion'],
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

        $estadoCerradoGanado = EstadoAtencion::where('slug', 'cerrada_ganada')->value('id') ?? 0;
        $estadoCerradoPerdido = EstadoAtencion::where('slug', 'cerrada_perdida')->value('id') ?? 0;
        $personalIds = Personal::pluck('id')->all(); // Ya no buscamos roles en Users. Todo Personal es un personal comercial.

        if (empty($personalIds)) {
            return null;
        }

        $conteos = DB::table('atenciones')
            ->select('id_personal', DB::raw('COUNT(*) as total'))
            ->whereNull('deleted_at') // Nativo softDeletes en vez de borrado_logico
            ->whereNotIn('id_estado_atencion', [$estadoCerradoGanado, $estadoCerradoPerdido])
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
