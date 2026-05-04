<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'id_cotizacion'    => $this->id_cotizacion,
            'id_tipo_servicio' => $this->id_tipo_servicio,
            'id_proveedor'     => $this->id_proveedor,
            'descripcion'      => $this->descripcion,
            'costo'            => (float) $this->costo,
            'monto_gravable'   => (float) $this->monto_gravable,
            'monto_no_sujeto'  => (float) $this->monto_no_sujeto,
            'total_servicio'   => (float) $this->total_servicio,
            'iva_establecido'  => (float) $this->iva_establecido,
            'id_tasa_cambio'   => $this->id_tasa_cambio,
            'estatus'          => $this->estatus,

            // Relaciones
            'tipo_servicio'    => new TipoServicioResource($this->whenLoaded('tipoServicio')),
            'proveedor'        => new ProveedorResource($this->whenLoaded('proveedor')),
            'tasa_cambio'      => new TasaCambioResource($this->whenLoaded('tasaCambio')),
            'estatus_info'     => new EstatusResource($this->whenLoaded('estatus')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
