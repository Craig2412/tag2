<?php

namespace App\Events;

use App\Models\Atencion;
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
    ) {}
}
