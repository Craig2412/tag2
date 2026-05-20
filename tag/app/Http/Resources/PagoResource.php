<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d'),
            'monto_total' => (float) $this->monto_total,
            'id_metodo_pago' => $this->id_metodo_pago,
            'nro_comprobante' => $this->nro_comprobante,
            'id_tasa_cambio' => $this->id_tasa_cambio,
            'id_entidad_bancaria' => $this->id_entidad_bancaria,
            'id_estado_conciliacion' => $this->id_estado_conciliacion,

            // Relaciones
            'metodo_pago' => new MetodoPagoResource($this->whenLoaded('metodoPago')),
            'tasa_cambio' => new TasaCambioResource($this->whenLoaded('tasaCambio')),
            'entidad_bancaria' => $this->whenLoaded('entidadBancaria'),
            'estado_conciliacion' => $this->whenLoaded('estadoConciliacion'),
            'ordenes_compra' => PagoOrdenCompraResource::collection($this->whenLoaded('ordenesCompra')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
