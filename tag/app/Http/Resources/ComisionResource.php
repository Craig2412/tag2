<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_cotizacion' => $this->id_cotizacion,
            'id_proveedor_comisionista' => $this->id_proveedor_comisionista,
            'tipo' => $this->tipo,
            'valor' => (float) $this->valor,
            'base' => (float) $this->base,
            'monto' => (float) $this->monto,
            'id_tasa_cambio' => $this->id_tasa_cambio,
            'moneda' => $this->moneda,
            'estado' => $this->estado,
            'id_cuenta_por_pagar' => $this->id_cuenta_por_pagar,

            'comisionista' => new ProveedorResource($this->whenLoaded('comisionista')),
            'cuenta_por_pagar' => new CuentaPorPagarResource($this->whenLoaded('cuentaPorPagar')),

            'anulada_at' => $this->anulada_at,
            'motivo_anulacion' => $this->motivo_anulacion,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
