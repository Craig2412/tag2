<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

/**
 * @group Configuración de Seguridad
 * 
 * APIs para gestionar los permisos individuales del sistema.
 */
class PermissionController extends Controller
{
    /**
     * Listar todos los permisos
     * 
     * Devuelve el catálogo completo de permisos disponibles que pueden ser asignados a roles.
     */
    public function index()
    {
        return PermissionResource::collection(Permission::all());
    }

    /**
     * Crear un nuevo permiso
     * 
     * Registra una nueva capacidad o acción que puede ser restringida en el sistema.
     * 
     * @bodyParam name string required Nombre único del permiso. Ejemplo: view:reportes_financieros
     * @bodyParam guard_name string Nombre del guard de Laravel. Por defecto 'web'.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:permissions,name'],
            'guard_name' => ['sometimes', 'string'],
        ]);

        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        return new PermissionResource($permission);
    }

    /**
     * Obtener un permiso específico
     */
    public function show(Permission $permission)
    {
        return new PermissionResource($permission);
    }

    /**
     * Actualizar un permiso
     * 
     * @bodyParam name string required Nuevo nombre único del permiso.
     */
    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:permissions,name,' . $permission->id],
        ]);

        $permission->update(['name' => $data['name']]);

        return new PermissionResource($permission);
    }

    /**
     * Eliminar un permiso
     * 
     * Al eliminar un permiso, se revoca automáticamente de todos los roles que lo tengan.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(['data' => ['message' => 'Permiso eliminado correctamente']]);
    }
}
