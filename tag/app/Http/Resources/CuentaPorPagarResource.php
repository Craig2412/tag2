<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaPorPagarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'id_orden_compra'  => $this->id_orden_compra,
            'id_proveedor'     => $this->id_proveedor,
            'monto_total'      => (float) $this->monto_total,
            'saldo_pendiente'  => (float) $this->saldo_pendiente,
            'estatus'          => $this->estatus,

            // Relaciones
            'orden_compra'     => new OrdenCompraResource($this->whenLoaded('ordenCompra')),
            'proveedor'        => new ProveedorResource($this->whenLoaded('proveedor')),
            'estatus_info'     => new EstatusResource($this->whenLoaded('estatusCuenta')),
            'pagos'            => PagoProveedorResource::collection($this->whenLoaded('pagos')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
