<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'nombre'              => $this->nombre,
            'tipo_entidad'        => $this->tipo_entidad,
            'id_estatus_objetivo' => $this->id_estatus_objetivo,
            'es_monetario'        => (bool) $this->es_monetario,
            'valor_objetivo'      => (float) $this->valor_objetivo,
            'id_temporalidad'     => $this->id_temporalidad,

            // Relaciones
            'estatus_objetivo'    => new EstatusResource($this->whenLoaded('estatusObjetivo')),
            'temporalidad'        => new TemporalidadResource($this->whenLoaded('temporalidad')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
