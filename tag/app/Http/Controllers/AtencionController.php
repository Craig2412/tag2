<?php

namespace App\Http\Controllers;

use App\Events\AtencionEstatusActualizado;
use App\Http\Resources\AtencionResource;
use App\Models\Atencion;
use App\Services\AtencionService;
use Illuminate\Http\Request;
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
     * @bodyParam id_cliente int ID de un cliente existente. Alternativo a "cliente". Ejemplo: 1
     * @bodyParam cliente object Datos del cliente a crear si no existe (alternativo a id_cliente).
     * @bodyParam cliente.nombre string Nombre del cliente. Ejemplo: Juan
     * @bodyParam cliente.apellido string Apellido del cliente. Ejemplo: Pérez
     * @bodyParam cliente.cedula string Cédula o RIF. Si ya existe un cliente con esa cédula, se reutiliza. Ejemplo: V-12345678
     * @bodyParam cliente.telefono string Teléfono. Ejemplo: 04121234567
     * @bodyParam cliente.correo_contacto string Correo de contacto. Ejemplo: juan.perez@email.com
     * @bodyParam cliente.id_tipo_contribuyente int ID tipo contribuyente. Ejemplo: 1
     * @bodyParam id_origen_atencion int required ID del origen de la atención (red social / canal). Ejemplo: 1
     * @bodyParam asunto string required Asunto o motivo de la atención. Ejemplo: Consulta sobre pasaporte
     * @bodyParam notas_adicionales string Notas adicionales del operador. Ejemplo: El cliente prefiere contacto por WhatsApp
     */
    public function store(Request $request, AtencionService $service)
    {
        $data = $request->validate([
            'id_cliente' => [
                'nullable',
                'required_without:cliente',
                Rule::exists('clientes', 'id')->whereNull('deleted_at'),
            ],
            'cliente' => ['nullable', 'array', 'required_without:id_cliente'],
            'cliente.nombre' => ['required_with:cliente', 'string', 'max:255'],
            'cliente.apellido' => ['required_with:cliente', 'string', 'max:255'],
            'cliente.cedula' => ['nullable', 'string', 'max:255'],
            'cliente.telefono' => ['nullable', 'string', 'max:255'],
            'cliente.correo_contacto' => ['nullable', 'email', 'max:255'],
            'cliente.id_tipo_contribuyente' => [
                'nullable',
                Rule::exists('tipos_contribuyentes', 'id')->whereNull('deleted_at'),
            ],
            'id_origen_atencion' => ['required', Rule::exists('origenes', 'id')->whereNull('deleted_at')],
            'asunto' => ['required', 'string', 'max:255'],
            'notas_adicionales' => ['nullable', 'string'],
        ]);

        if (! empty($data['cliente'])) {
            abort_unless(
                $request->user()?->can('create:clientes'),
                403,
                'No tienes permiso para crear clientes.'
            );
        }

        try {
            $item = $service->createAtencion($data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

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
}
