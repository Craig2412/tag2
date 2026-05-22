<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_empresa' => $this->nombre_empresa,
            'razon_comercial' => $this->razon_comercial,
            'rif' => $this->rif,
            'correo_empresa' => $this->correo_empresa,
            'telefono_empresa' => $this->telefono_empresa,
            'nombre_persona_contacto' => $this->nombre_persona_contacto,
            'id_tipo_contribuyente' => $this->id_tipo_contribuyente,
            'tipo_proveedor' => $this->tipo_proveedor,

            // Relaciones
            'tipo_contribuyente' => new TipoContribuyenteResource($this->whenLoaded('tipoContribuyente')),
            'tipo_proveedor_info' => new TipoProveedorResource($this->whenLoaded('tipoProveedor')),
            'tipos_servicio' => TipoServicioResource::collection($this->whenLoaded('tiposServicio')),
            'cuentas' => CuentaProveedorResource::collection($this->whenLoaded('cuentas')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
