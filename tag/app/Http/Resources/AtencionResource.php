<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AtencionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'id_cliente'          => $this->id_cliente,
            'id_personal'         => $this->id_personal,
            'id_origen_atencion'  => $this->id_origen_atencion,
            'asunto'              => $this->asunto,
            'notas_adicionales'   => $this->notas_adicionales,
            'estatus'             => $this->estatus,
            'id_etapa_comercial'  => $this->id_etapa_comercial,

            // Campos calculados para el frontend
            'id_cotizacion'       => $this->cotizaciones?->first()?->id,
            'id_orden_compra'     => $this->cotizaciones?->first()?->ordenCompra?->id,

            // Relaciones
            'cliente'             => new ClienteResource($this->whenLoaded('cliente')),
            'personal'            => new PersonalResource($this->whenLoaded('personal')),
            'origen'              => new OrigenResource($this->whenLoaded('origen')),
            'etapa_comercial'     => $this->whenLoaded('etapaComercial'),
            'cotizaciones'        => CotizacionResource::collection($this->whenLoaded('cotizaciones')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
