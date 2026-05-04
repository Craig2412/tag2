<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TasaCambioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'id_tasa'      => $this->id_tasa,
            'valor_cambio' => (float) $this->valor_cambio,
            'fecha'        => $this->fecha?->format('Y-m-d'),

            // Relaciones
            'moneda'       => new TasaResource($this->whenLoaded('monedaCatalogo')),
        ];
    }
}
