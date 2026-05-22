<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaProveedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_proveedor' => $this->id_proveedor,
            'numero_cuenta' => $this->numero_cuenta,
            'nombre_banco' => $this->nombre_banco,
            'tipo_cuenta' => $this->tipo_cuenta,
            'moneda' => $this->moneda,

            // Relaciones
            'proveedor' => new ProveedorResource($this->whenLoaded('proveedor')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
