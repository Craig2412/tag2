<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Roles y permisos solo se exponen a usuarios con permiso explícito.
        // La condición anterior revisaba routeIs('usuarios.*') que no existe en api.php,
        // por lo que roles/permisos NUNCA se incluían a través de esa rama.
        $puedeVerCredenciales = $request->user()?->can('view:usuarios');

        return [
            'id'             => $this->id,
            'nombre_usuario' => $this->nombre_usuario,
            'correo'         => $this->correo,
            'esta_activo'    => $this->esta_activo,
            'roles'          => $this->when($puedeVerCredenciales, $this->role_names),
            'permisos'       => $this->when($puedeVerCredenciales, $this->all_permissions),
            'created_at'     => $this->created_at,
            'updated_at'     => $this->updated_at,
        ];
    }
}
