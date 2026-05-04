<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoOrdenCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'id_pago'         => $this->id_pago,
            'id_orden_compra' => $this->id_orden_compra,
            'monto_asignado'  => (float) $this->monto_asignado,
            'monto_pagado'    => (float) $this->monto_pagado,

            // Relaciones
            'pago'            => new PagoResource($this->whenLoaded('pago')),
            'orden_compra'    => new OrdenCompraResource($this->whenLoaded('ordenCompra')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
