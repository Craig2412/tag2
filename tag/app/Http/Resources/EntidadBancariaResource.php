<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntidadBancariaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'entidad'  => $this->entidad,
            'estatus'  => $this->estatus,

            // Relaciones
            'estatus_info' => new EstatusResource($this->whenLoaded('estatus_relation')),
        ];
    }
}
