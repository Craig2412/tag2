<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteEmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'id_cliente'  => $this->id_cliente,
            'id_empresas' => $this->id_empresas,

            // Relaciones
            'cliente'     => new ClienteResource($this->whenLoaded('cliente')),
            'empresa'     => new EmpresaResource($this->whenLoaded('empresa')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
