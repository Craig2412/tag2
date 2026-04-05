<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\ClienteEmpresa;
use App\Models\Estatus;
use App\Models\PersonalEmpresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AtencionController extends Controller
{
    public function index()
    {
        // Lista las atenciones activas y las devuelve en JSON, incluyendo id_cotizacion e id_orden_compra.
        $items = Atencion::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        $result = $items->map(function ($atencion) {
            // Buscar la cotización más reciente (por id mayor)
            $cotizacion = $atencion->cotizaciones()->orderByDesc('id')->first();
            $id_cotizacion = $cotizacion ? $cotizacion->id : null;
            // Buscar la orden de compra asociada a esa cotización
            $id_orden_compra = null;
            if ($cotizacion && $cotizacion->ordenCompra) {
                $id_orden_compra = $cotizacion->ordenCompra->id;
            }
            // Devolver la atención como array + los campos extra
            $arr = $atencion->toArray();
            $arr['id_cotizacion'] = $id_cotizacion;
            $arr['id_orden_compra'] = $id_orden_compra;
            return $arr;
        });

        return response()->json($result);
    }

    public function store(Request $request)
    {
        // Crea una atencion, valida roles y asigna estatus inicial.
        $data = $request->validate([
            'id_cliente' => ['required', 'exists:users,id'],
            'id_origen_atencion' => ['required', 'exists:origenes,id'],
            'asunto' => ['required', 'string', 'max:255'],
            'notas_adicionales' => ['nullable', 'string'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        $cliente = User::find($data['id_cliente']);

        if (!$cliente || !$cliente->hasRole('cliente')) {
            return response()->json(['message' => 'id_cliente debe ser un usuario con rol cliente'], 422);
        }

        $personalId = $this->resolverPersonalAsignado($cliente->id);
        if (!$personalId) {
            return response()->json(['message' => 'No hay personal disponible para asignar la atencion'], 422);
        }

        $estatus = Estatus::firstOrCreate(['estatus' => 'por aprobar']);

        $data['id_personal'] = $personalId;
        $data['estatus'] = $estatus->id;
        $data['borrado_logico'] = $data['borrado_logico'] ?? false;

        $item = Atencion::create($data);

        return response()->json($item, 201);
    }

    public function show(Atencion $atencion)
    {
        // Muestra una atencion si no esta marcada como borrada, incluyendo id_cotizacion e id_orden_compra.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $cotizacion = $atencion->cotizaciones()->orderByDesc('id')->first();
        $id_cotizacion = $cotizacion ? $cotizacion->id : null;
        $id_orden_compra = null;
        if ($cotizacion && $cotizacion->ordenCompra) {
            $id_orden_compra = $cotizacion->ordenCompra->id;
        }
        $arr = $atencion->toArray();
        $arr['id_cotizacion'] = $id_cotizacion;
        $arr['id_orden_compra'] = $id_orden_compra;
        return response()->json($arr);
    }

    public function update(Request $request, Atencion $atencion)
    {
        // Actualiza una atencion activa y valida roles cuando cambian.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'id_cliente' => ['sometimes', 'required', 'exists:users,id'],
            'id_personal' => ['sometimes', 'required', 'exists:users,id'],
            'id_origen_atencion' => ['sometimes', 'required', 'exists:origenes,id'],
            'asunto' => ['sometimes', 'required', 'string', 'max:255'],
            'notas_adicionales' => ['sometimes', 'nullable', 'string'],
            'estatus' => ['sometimes', 'required', 'exists:estatus,id'],
            'borrado_logico' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['id_cliente'])) {
            $cliente = User::find($data['id_cliente']);
            if (!$cliente || !$cliente->hasRole('cliente')) {
                return response()->json(['message' => 'id_cliente debe ser un usuario con rol cliente'], 422);
            }
        }

        if (isset($data['id_personal'])) {
            $personal = User::find($data['id_personal']);
            if (!$personal || !$personal->hasRole('personal')) {
                return response()->json(['message' => 'id_personal debe ser un usuario con rol personal'], 422);
            }
        }

        $atencion->update($data);

        return response()->json($atencion);
    }

    public function destroy(Atencion $atencion)
    {
        // Marca la atencion como borrada de forma logica.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'Already deleted']);
        }

        $atencion->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Deleted']);
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
        $personalIds = User::role('personal')->pluck('id')->all();

        if (empty($personalIds)) {
            return null;
        }

        $conteos = DB::table('atenciones')
            ->select('id_personal', DB::raw('COUNT(*) as total'))
            ->where('borrado_logico', false)
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
