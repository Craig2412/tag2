<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Estatus;
use Illuminate\Http\Request;

class PersonalController extends Controller
{
    /**
     * Listar todo el personal
     *
     * @queryParam include string Relaciones a incluir. Example: usuario. Values: usuario
     */
    public function index(Request $request)
    {
        $query = Personal::query();

        if ($request->has('include')) {
            $allowed = ['usuario'];
            $includes = array_intersect(explode(',', $request->include), $allowed);
            if (!empty($includes)) {
                $query->with($includes);
            }
        }

        return response()->json($query->orderBy('id')->get());
    }

    /**
     * Crear nuevo personal
     *
     * @bodyParam usuario_id int required ID del usuario (auth). Ejemplo: 1
     * @bodyParam nombre string required Nombre. Ejemplo: Maria
     * @bodyParam apellido string required Apellido. Ejemplo: Rodriguez
     * @bodyParam cedula string Cédula. Ejemplo: 87654321
     * @bodyParam telefono string Teléfono. Ejemplo: 04241234567
     * @bodyParam correo_institucional string Correo institucional. Ejemplo: maria.rodriguez@tag.com
     * @bodyParam porcentaje_comision number Porcentaje de comisión. Ejemplo: 5.50
     * @bodyParam id_estatus int ID del estatus. Ejemplo: 1
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id', 'unique:personal,usuario_id'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'correo_institucional' => ['nullable', 'email', 'max:255'],
            'porcentaje_comision' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'id_estatus' => ['nullable', 'exists:estatus,id'],
        ]);

        if (!isset($data['id_estatus'])) {
            $estatusActivo = Estatus::where('estatus', 'activo')->first();
            $data['id_estatus'] = $estatusActivo?->id;
        }

        $item = Personal::create($data);

        return response()->json($item->load(['usuario']), 201);
    }

    /**
     * Obtener un personal específico
     */
    public function show(Personal $personal)
    {
        return response()->json($personal->load(['usuario']));
    }

    /**
     * Actualizar personal
     *
     * @bodyParam usuario_id int ID del usuario. Ejemplo: 1
     * @bodyParam nombre string Nombre. Ejemplo: Maria
     * @bodyParam apellido string Apellido. Ejemplo: Rodriguez
     * @bodyParam cedula string Cédula. Ejemplo: 87654321
     * @bodyParam telefono string Teléfono. Ejemplo: 04241234567
     * @bodyParam correo_institucional string Correo institucional. Ejemplo: maria.rodriguez@tag.com
     * @bodyParam porcentaje_comision number Porcentaje de comisión. Ejemplo: 5.50
     * @bodyParam id_estatus int ID del estatus. Ejemplo: 1
     */
    public function update(Request $request, Personal $personal)
    {
        $data = $request->validate([
            'usuario_id' => ['sometimes', 'required', 'exists:usuarios,id'],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido' => ['sometimes', 'required', 'string', 'max:255'],
            'cedula' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:255'],
            'correo_institucional' => ['sometimes', 'nullable', 'email', 'max:255'],
            'porcentaje_comision' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'id_estatus' => ['sometimes', 'nullable', 'exists:estatus,id'],
        ]);

        $personal->update($data);

        return response()->json($personal->load(['usuario']));
    }

    /**
     * Eliminar personal
     */
    public function destroy(Personal $personal)
    {
        $personal->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
