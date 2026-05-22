<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogroPersonalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_personal' => $this->id_personal,
            'tipo_entidad' => $this->tipo_entidad,
            'id_entidad' => $this->id_entidad,
            'estatus_anterior' => $this->estatus_anterior,
            'estatus_nuevo' => $this->estatus_nuevo,
            'tiempo_transcurrido_segundos' => $this->tiempo_transcurrido_segundos,

            // Relaciones
            'personal' => new PersonalResource($this->whenLoaded('personal')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
