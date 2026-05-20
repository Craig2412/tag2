<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AtencionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_cliente' => $this->id_cliente,
            'id_personal' => $this->id_personal,
            'id_origen_atencion' => $this->id_origen_atencion,
            'asunto' => $this->asunto,
            'notas_adicionales' => $this->notas_adicionales,
            'id_estado_atencion' => $this->id_estado_atencion,
            'id_etapa_comercial' => $this->id_etapa_comercial,

            // Campos calculados para el frontend
            'id_cotizacion' => $this->cotizaciones?->first()?->id,
            'id_orden_compra' => $this->cotizaciones?->first()?->ordenCompra?->id,

            // Relaciones (usa callback en whenLoaded para evitar MissingValue en Resource constructor)
            'cliente' => $this->whenLoaded('cliente', fn () => new ClienteResource($this->cliente)),
            'personal' => $this->whenLoaded('personal', fn () => new PersonalResource($this->personal)),
            'origen' => $this->whenLoaded('origen', fn () => new OrigenResource($this->origen)),
            'estado_atencion' => $this->whenLoaded('estadoAtencion', fn () => new EstadoAtencionResource($this->estadoAtencion)),
            'etapa_comercial' => $this->whenLoaded('etapaComercial', fn () => new EtapaComercialResource($this->etapaComercial)),
            'cotizaciones' => $this->whenLoaded('cotizaciones', fn () => CotizacionResource::collection($this->cotizaciones)),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
