<?php

namespace App\Http\Controllers;

use App\Events\PermissionsUpdated;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * @group Configuración de Seguridad
 *
 * APIs para gestionar los roles del sistema y sus permisos asociados.
 */
class RoleController extends Controller
{
    /**
     * Listar todos los roles
     *
     * Devuelve una lista de todos los roles registrados junto con sus permisos.
     */
    public function index()
    {
        return RoleResource::collection(Role::with('permissions')->get());
    }

    /**
     * Crear un nuevo rol
     *
     * Registra un nuevo rol en el sistema y opcionalmente le asigna permisos iniciales.
     *
     * @bodyParam name string required Nombre único del rol. Ejemplo: Supervisor
     * @bodyParam permissions string[] Lista de nombres de permisos a asignar. Ejemplo: ["view:usuarios", "edit:usuarios"]
     * @bodyParam guard_name string Nombre del guard de Laravel. Por defecto 'web'. Ejemplo: web
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'guard_name' => ['sometimes', 'string'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Obtener un rol específico
     *
     * Muestra los detalles de un rol y todos los permisos que tiene asignados.
     */
    public function show(Role $role)
    {
        return new RoleResource($role->load('permissions'));
    }

    /**
     * Actualizar un rol
     *
     * Permite cambiar el nombre del rol y sincronizar (reemplazar) su lista de permisos.
     *
     * @bodyParam name string Nombre único del rol. Ejemplo: Gerente
     * @bodyParam permissions string[] Lista de nombres de permisos (reemplazará los anteriores). Ejemplo: ["view:atenciones"]
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'unique:roles,name,'.$role->id],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        if (isset($data['name'])) {
            $role->update(['name' => $data['name']]);
        }

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);

            // Notificamos a todos los usuarios que tienen este rol asignado
            // de forma eficiente (de 100 en 100) para no saturar la memoria.
            $role->users()->chunk(100, function ($users) {
                foreach ($users as $user) {
                    broadcast(new PermissionsUpdated($user->id));
                }
            });
        }

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Eliminar un rol
     *
     * Elimina el rol del sistema. Los usuarios que lo tengan asignado perderán los permisos asociados.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json(['data' => ['message' => 'Rol eliminado correctamente']]);
    }
}
