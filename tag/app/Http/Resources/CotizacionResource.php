<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CotizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'id_atencion'         => $this->id_atencion,
            'id_tipo_cotizacion'  => $this->id_tipo_cotizacion,
            'cant_adultos'        => $this->cant_adultos,
            'cant_menores'        => $this->cant_menores,
            'cant_viejos'         => $this->cant_viejos,
            'id_tasa_cambio'      => $this->id_tasa_cambio,
            'fecha_vencimiento'   => $this->fecha_vencimiento?->format('Y-m-d'),
            // Campo calculado del modelo: indica si la cotizacion ya venció
            'esta_vencida'        => $this->esta_vencida,
            'estatus'             => $this->estatus,

            // Relaciones
            'atencion'            => new AtencionResource($this->whenLoaded('atencion')),
            'tipo_cotizacion'     => new TipoCotizacionResource($this->whenLoaded('tipoCotizacion')),
            'tasa_cambio'         => new TasaCambioResource($this->whenLoaded('tasaCambio')),
            'servicios'           => ServicioResource::collection($this->whenLoaded('servicios')),
            'orden_compra'        => new OrdenCompraResource($this->whenLoaded('ordenCompra')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
