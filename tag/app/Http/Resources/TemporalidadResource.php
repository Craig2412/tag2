<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TemporalidadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'temporalidad' => $this->temporalidad,
            'slug' => $this->slug,
            'carbon_method' => $this->carbon_method,
        ];
    }
}
