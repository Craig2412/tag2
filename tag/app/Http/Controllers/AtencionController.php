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
    /**
     * Listar todas las atenciones
     *
     * Devuelve todas las atenciones activas (no eliminadas) del sistema.
     */
    public function index()
    {
        // Lista las atenciones activas y las devuelve en JSON.
        $items = Atencion::where('borrado_logico', false)
            ->orderBy('id')
            ->get();

        return response()->json($items);
    }

    /**
     * Crear una nueva atención
     *
     * Registra un nuevo ticket de atención al cliente. El sistema asigna automáticamente al personal disponible.
     *
     * @bodyParam id_cliente int required ID del usuario con rol cliente. Ejemplo: 5
     * @bodyParam id_origen_atencion int required ID del origen de la atención (red social / canal). Ejemplo: 1
     * @bodyParam asunto string required Asunto o motivo de la atención. Ejemplo: Consulta sobre pasaporte
     * @bodyParam notas_adicionales string Notas adicionales del operador. Ejemplo: El cliente prefiere contacto por WhatsApp
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente. Ejemplo: false
     */
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
        $item->load(['cliente', 'personal', 'origen', 'estatus']);

        return response()->json($item, 201);
    }

    /**
     * Obtener una atención específica
     *
     * Devuelve los detalles de una atención por su ID.
     */
    public function show(Atencion $atencion)
    {
        // Muestra una atencion si no esta marcada como borrada.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
        }

        return response()->json($atencion);
    }

    /**
     * Actualizar una atención existente
     *
     * Modifica los datos de una atención activa. Valida el rol del cliente y del personal si se cambian.
     *
     * @bodyParam id_cliente int ID del usuario cliente.
     * @bodyParam id_personal int ID del usuario personal asignado.
     * @bodyParam id_origen_atencion int ID del origen de la atención.
     * @bodyParam asunto string Asunto o motivo de la atención.
     * @bodyParam notas_adicionales string Notas adicionales.
     * @bodyParam estatus int ID del estatus de la atención.
     * @bodyParam borrado_logico boolean Indica si el registro está eliminado lógicamente.
     */
    public function update(Request $request, Atencion $atencion)
    {
        // Actualiza una atencion activa y valida roles cuando cambian.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'No encontrado'], 404);
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
        $atencion->load(['cliente', 'personal', 'origen', 'estatus']);

        return response()->json($atencion);
    }

    /**
     * Eliminar una atención
     *
     * Realiza una eliminación lógica de la atención (no se borra físicamente de la base de datos).
     */
    public function destroy(Atencion $atencion)
    {
        // Marca la atencion como borrada de forma logica.
        if ($atencion->borrado_logico) {
            return response()->json(['message' => 'Ya estaba eliminada']);
        }

        $atencion->update(['borrado_logico' => true]);

        return response()->json(['message' => 'Eliminado correctamente']);
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
