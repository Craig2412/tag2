<?php

namespace App\Events;

use App\Models\Atencion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AtencionEstatusActualizado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Atencion $atencion,
        public readonly ?int $estatusAnterior,
        public readonly int $estatusNuevo,
        public readonly ?string $comentario = 'Cambio de estatus desde API',
        public ?int $usuarioId = null,
    ) {
        // Si no se pasó un usuario explícito, intentamos capturar el de la sesión
        $this->usuarioId = $this->usuarioId ?: Auth::id();
    }
}
