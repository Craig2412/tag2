<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoProveedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_proveedor' => $this->id_proveedor,
            'monto_total' => (float) $this->monto_total,
            'id_tasa_cambio' => $this->id_tasa_cambio,
            'referencia' => $this->referencia,
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d'),
            'id_metodo_pago' => $this->id_metodo_pago,
            'comprobante' => $this->comprobante,
            // Relaciones
            'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),
            'tasa_cambio' => new TasaCambioResource($this->whenLoaded('tasaCambio')),
            'metodo_pago' => new MetodoPagoResource($this->whenLoaded('metodoPago')),
            // Pivot: cuentas saldadas por este pago (con monto_asignado)
            'cuentas_saldadas' => CuentaPorPagarResource::collection($this->whenLoaded('cuentasPorPagar')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
