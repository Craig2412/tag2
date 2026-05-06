<?php

namespace App\Events;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CotizacionEstatusActualizado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Cotizacion $cotizacion,
        public readonly ?int $estatusAnterior,
        public readonly int $estatusNuevo,
        public readonly ?string $comentario = 'Cambio de estatus desde API',
        public ?int $usuarioId = null,
    ) {
        $this->usuarioId = $this->usuarioId ?: Auth::id();
    }
}
