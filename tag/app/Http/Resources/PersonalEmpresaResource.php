<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalEmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_personal' => $this->id_personal,
            'id_empresa' => $this->id_empresa,

            // Relaciones
            'personal' => new PersonalResource($this->whenLoaded('personal')),
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
