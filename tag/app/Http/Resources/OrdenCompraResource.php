<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenCompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'id_cotizacion'               => $this->id_cotizacion,
            'estatus'                     => $this->estatus,
            'id_estado_financiero'        => $this->id_estado_financiero,
            'id_estado_financiero_egreso' => $this->id_estado_financiero_egreso,
            // Campos calculados del modelo: financiero en tiempo real
            'monto_total'                 => (float) $this->monto_total,
            'total_pagado'                => (float) $this->total_pagado,
            'saldo_pendiente'             => (float) $this->saldo_pendiente,
            'porcentaje_pagado'           => (float) $this->porcentaje_pagado,

            // Relaciones
            'cotizacion'                  => new CotizacionResource($this->whenLoaded('cotizacion')),
            'estado_financiero'           => $this->whenLoaded('estadoFinanciero'),
            'estado_financiero_egreso'    => $this->whenLoaded('estadoFinancieroEgreso'),
            'pagos'                       => PagoOrdenCompraResource::collection($this->whenLoaded('pagos')),
            'cuentas_por_pagar'           => CuentaPorPagarResource::collection($this->whenLoaded('cuentasPorPagar')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
