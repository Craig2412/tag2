<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionSistemaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dias_vencimiento' => $this->dias_vencimiento,
            'tasas_sync_enabled' => (bool) $this->tasas_sync_enabled,
            'tasas_sync_interval_minutes' => (int) $this->tasas_sync_interval_minutes,
            'tasas_sync_last_run_at' => $this->tasas_sync_last_run_at?->toIso8601String(),
            'tasas_sync_last_status' => $this->tasas_sync_last_status,
            'tasas_sync_last_message' => $this->tasas_sync_last_message,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
