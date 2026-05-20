<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntidadBancariaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entidad' => $this->entidad,

            // Relaciones
            'metodos_pago' => MetodoPagoResource::collection($this->whenLoaded('metodosPago')),
        ];
    }
}
