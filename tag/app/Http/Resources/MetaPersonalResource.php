<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaPersonalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'id_meta'        => $this->id_meta,
            'id_personal'    => $this->id_personal,
            'es_recurrente'  => (bool) $this->es_recurrente,
            // Campo calculado del modelo: progreso en el periodo vigente
            'progreso_actual' => $this->progreso_actual,
            // Campo calculado del modelo: arreglo con los últimos N periodos
            'progreso_historico' => $this->progreso_historico,

            // Relaciones
            'meta'           => new MetaResource($this->whenLoaded('meta')),
            'personal'       => new PersonalResource($this->whenLoaded('personal')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
