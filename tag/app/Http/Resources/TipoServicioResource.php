<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'tipo_servicio' => $this->tipo_servicio,
            'id_proveedor' => $this->id_proveedor,
            'iva_defecto'  => (float) $this->iva_defecto,

            // Relaciones
            'proveedor'    => new ProveedorResource($this->whenLoaded('proveedor')),
        ];
    }
}
