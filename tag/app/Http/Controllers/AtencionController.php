<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\ClienteEmpresa;
use App\Models\Estatus;
use App\Models\PersonalEmpresa;
use App\Models\Cliente;
use App\Models\Personal;
use App\Models\AtencionHistorial;
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
        // Eloquent maneja SoftDeletes automáticamente
        $items = Atencion::with(['cliente', 'personal', 'origen', 'estatus'])
            ->orderBy('id')
            ->get();

        $result = $items->map(function ($atencion) {
            $cotizacion = $atencion->cotizaciones()->orderByDesc('id')->first();
            $id_cotizacion = $cotizacion ? $cotizacion->id : null;
            $id_orden_compra = ($cotizacion && $cotizacion->ordenCompra) ? $cotizacion->ordenCompra->id : null;

            $arr = $atencion->toArray();
            $arr['id_cotizacion'] = $id_cotizacion;
            $arr['id_orden_compra'] = $id_orden_compra;
            return $arr;
        });

        return response()->json($result);
    }

    /**
     * Crear una nueva atención
     *
     * Registra un nuevo ticket de atención al cliente asignando automáticamente un miembro del personal.
     *
     * @bodyParam id_cliente int required ID del cliente. Ejemplo: 5
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

        $estatus = Estatus::firstOrCreate(['estatus' => 'por aprobar']);

        $data['id_personal'] = $personalId;
        $data['estatus'] = $estatus->id;

        $item = Atencion::create($data);

        return response()->json($item->load(['cliente', 'personal', 'origen', 'estatus']), 201);
    }

    /**
     * Obtener una atención específica
     */
    public function show(Atencion $atencion)
    {
        $cotizacion = $atencion->cotizaciones()->orderByDesc('id')->first();
        $id_cotizacion = $cotizacion ? $cotizacion->id : null;
        $id_orden_compra = ($cotizacion && $cotizacion->ordenCompra) ? $cotizacion->ordenCompra->id : null;
        
        $atencion->load(['cliente', 'personal', 'origen', 'estatus']);
        
        $arr = $atencion->toArray();
        $arr['id_cotizacion'] = $id_cotizacion;
        $arr['id_orden_compra'] = $id_orden_compra;
        return response()->json($arr);
    }

    /**
     * Actualizar una atención existente
     *
     * @bodyParam id_cliente int ID del cliente.
     * @bodyParam id_personal int ID del personal asignado.
     * @bodyParam id_origen_atencion int ID del origen de la atención.
     * @bodyParam asunto string Asunto o motivo de la atención.
     * @bodyParam notas_adicionales string Notas adicionales.
     * @bodyParam estatus int ID del estatus de la atención.
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
        $atencion->load(['cliente', 'personal', 'origen', 'estatus']);

        if (isset($data['estatus']) && $data['estatus'] != $estatusAnterior) {
            AtencionHistorial::create([
                'atencion_id' => $atencion->id,
                'estatus_anterior' => $estatusAnterior,
                'estatus_nuevo' => $data['estatus'],
                'usuario_id' => auth()->id(), // El usuario que muta mantiene auth()->id() (El operador logueado)
                'comentario' => 'Cambio de estatus desde API',
            ]);
        }

        return response()->json($atencion);
    }

    /**
     * Eliminar una atención
     *
     * Usa SoftDeletes nativo.
     */
    public function destroy(Atencion $atencion)
    {
        $atencion->delete();
        return response()->json(['message' => 'Eliminada correctamente']);
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

        $estatusConcluido = Estatus::firstOrCreate(['estatus' => 'aprobado']);
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
