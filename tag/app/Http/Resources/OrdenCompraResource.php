<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_cotizacion' => $this->id_cotizacion,
            'id_estado_orden_compra' => $this->id_estado_orden_compra,
            'id_estado_financiero' => $this->id_estado_financiero,
            'id_estado_financiero_egreso' => $this->id_estado_financiero_egreso,
            'facturado_proveedor' => (bool) $this->facturado_proveedor,
            // Campos calculados del modelo: financiero en tiempo real
            'monto_total' => (float) $this->monto_total,
            'total_pagado' => (float) $this->total_pagado,
            'saldo_pendiente' => (float) $this->saldo_pendiente,
            'porcentaje_pagado' => (float) $this->porcentaje_pagado,

            // Relaciones (callback en whenLoaded para evitar MissingValue en Resource constructor)
            'cotizacion' => $this->whenLoaded('cotizacion', fn () => new CotizacionResource($this->cotizacion)),
            'estado_orden_compra' => $this->whenLoaded('estadoOrdenCompra', fn () => new EstadoOrdenCompraResource($this->estadoOrdenCompra)),
            'estado_financiero' => $this->whenLoaded('estadoFinanciero', fn () => new EstadoFinancieroResource($this->estadoFinanciero)),
            'estado_financiero_egreso' => $this->whenLoaded('estadoFinancieroEgreso', fn () => new EstadoFinancieroResource($this->estadoFinancieroEgreso)),
            'pagos' => $this->whenLoaded('pagos', fn () => PagoOrdenCompraResource::collection($this->pagos)),
            'cuentas_por_pagar' => $this->whenLoaded('cuentasPorPagar', fn () => CuentaPorPagarResource::collection($this->cuentasPorPagar)),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
