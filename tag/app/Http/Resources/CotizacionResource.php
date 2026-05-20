<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CotizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_atencion' => $this->id_atencion,
            'id_tipo_cotizacion' => $this->id_tipo_cotizacion,
            'cant_adultos' => $this->cant_adultos,
            'cant_menores' => $this->cant_menores,
            'cant_viejos' => $this->cant_viejos,
            'id_tasa_cambio' => $this->id_tasa_cambio,
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            // Campo calculado del modelo: indica si la cotizacion ya venció
            'esta_vencida' => $this->esta_vencida,

            // Relaciones (callback en whenLoaded para evitar MissingValue en Resource constructor)
            'atencion' => $this->whenLoaded('atencion', fn () => new AtencionResource($this->atencion)),
            'tipo_cotizacion' => $this->whenLoaded('tipoCotizacion', fn () => new TipoCotizacionResource($this->tipoCotizacion)),
            'tasa_cambio' => $this->whenLoaded('tasaCambio', fn () => new TasaCambioResource($this->tasaCambio)),
            'servicios' => $this->whenLoaded('servicios', fn () => ServicioResource::collection($this->servicios)),
            'orden_compra' => $this->whenLoaded('ordenCompra', fn () => new OrdenCompraResource($this->ordenCompra)),

            'id_estado_cotizacion' => $this->id_estado_cotizacion,
            'estado_cotizacion' => $this->whenLoaded('estadoCotizacion', fn () => new EstadoCotizacionResource($this->estadoCotizacion)),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
