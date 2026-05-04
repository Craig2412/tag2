<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'usuario_id'          => $this->usuario_id,
            'nombre'              => $this->nombre,
            'apellido'            => $this->apellido,
            'cedula'              => $this->cedula,
            'telefono'            => $this->telefono,
            'correo_institucional' => $this->correo_institucional,
            'porcentaje_comision' => (float) $this->porcentaje_comision,
            'id_estatus'          => $this->id_estatus,

            // Solo se incluye si la relación fue cargada Y el usuario tiene permiso
            'usuario' => $this->when(
                $this->relationLoaded('usuario') && $request->user()?->can('view:usuarios'),
                fn() => new UsuarioResource($this->usuario)
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
