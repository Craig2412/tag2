<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogroPersonalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                           => $this->id,
            'id_personal'                  => $this->id_personal,
            'tipo_entidad'                 => $this->tipo_entidad,
            'id_entidad'                   => $this->id_entidad,
            'id_estatus_anterior'          => $this->id_estatus_anterior,
            'id_estatus_nuevo'             => $this->id_estatus_nuevo,
            'tiempo_transcurrido_segundos' => $this->tiempo_transcurrido_segundos,

            // Relaciones
            'personal'         => new PersonalResource($this->whenLoaded('personal')),
            'estatus_anterior'  => new EstatusResource($this->whenLoaded('estatusAnterior')),
            'estatus_nuevo'     => new EstatusResource($this->whenLoaded('estatusNuevo')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
