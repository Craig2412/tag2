<?php

namespace App\Events;

use App\Http\Resources\AtencionResource;
use App\Models\Atencion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Request;

/**
 * Transmite en tiempo real los cambios del recurso Atencion.
 *
 * - created: recurso completo con relaciones precargadas
 * - updated: recurso completo con relaciones precargadas
 * - deleted: solo id + action (payload mínimo)
 *
 * El frontend escucha en el canal privado atenciones.
 */
class AtencionBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Atencion  $atencion  Modelo con relaciones ya cargadas (para created/updated)
     * @param  string  $action  'created', 'updated' o 'deleted'
     */
    public function __construct(
        public readonly Atencion $atencion,
        public readonly string $action,
    ) {
        // Precargar relaciones necesarias para el Resource
        // Solo cuando no es deleted (evita queries innecesarias)
        if ($this->action !== 'deleted') {
            $this->atencion->load([
                'cliente' => fn ($q) => $q->withTrashed(),
                'personal' => fn ($q) => $q->withTrashed(),
                'origen' => fn ($q) => $q->withTrashed(),
                'estadoAtencion',
                'etapaComercial' => fn ($q) => $q->withTrashed(),
                'cotizaciones' => fn ($q) => $q->orderByDesc('id')->limit(1),
                'cotizaciones.ordenCompra',
            ]);
        }
    }

    /**
     * Canal privado. Solo usuarios con view:atenciones pueden suscribirse.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('atenciones'),
        ];
    }

    /**
     * Nombre del evento en el frontend: .atencion.created, .atencion.updated, .atencion.deleted
     */
    public function broadcastAs(): string
    {
        return "atencion.{$this->action}";
    }

    /**
     * Payload mínimo. Para deleted solo enviamos el id.
     */
    public function broadcastWith(): array
    {
        if ($this->action === 'deleted') {
            return [
                'action' => 'deleted',
                'id' => $this->atencion->id,
            ];
        }

        return [
            'action' => $this->action,
            'atencion' => (new AtencionResource($this->atencion))->toArray(Request::instance()),
        ];
    }
}
