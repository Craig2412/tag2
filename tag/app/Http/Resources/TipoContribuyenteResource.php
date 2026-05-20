<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoContribuyenteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_contribuyente' => $this->tipo_contribuyente,
            'porcentaje_iva' => (float) $this->porcentaje_iva,
        ];
    }
}
