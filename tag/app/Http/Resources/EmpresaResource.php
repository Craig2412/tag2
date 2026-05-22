<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'razon_social' => $this->razon_social,
            'razon_comercial' => $this->razon_comercial,
            'rif' => $this->rif,
            'numero_telefono' => $this->numero_telefono,
            'correo_electronico' => $this->correo_electronico,
            'direccion' => $this->direccion,
            'id_tipo_contribuyente' => $this->id_tipo_contribuyente,

            // Relaciones
            'tipo_contribuyente' => new TipoContribuyenteResource($this->whenLoaded('tipoContribuyente')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
