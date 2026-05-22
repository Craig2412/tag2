<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetodoPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'metodo_pago' => $this->metodo_pago,

            // Relaciones
            'entidades_bancarias' => EntidadBancariaResource::collection($this->whenLoaded('entidadesBancarias')),
        ];
    }
}
