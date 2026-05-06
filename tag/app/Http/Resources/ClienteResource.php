<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'usuario_id'            => $this->usuario_id,
            'nombre'                => $this->nombre,
            'apellido'              => $this->apellido,
            'cedula'                => $this->cedula,
            'telefono'              => $this->telefono,
            'correo_contacto'       => $this->correo_contacto,
            'id_tipo_contribuyente' => $this->id_tipo_contribuyente,

            // Relaciones
            'tipo_contribuyente' => new TipoContribuyenteResource($this->whenLoaded('tipoContribuyente')),

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
