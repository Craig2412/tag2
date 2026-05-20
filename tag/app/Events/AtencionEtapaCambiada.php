<?php

namespace App\Events;

use App\Models\Atencion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AtencionEtapaCambiada
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Atencion $atencion;

    public ?int $etapaAnterior;

    public int $etapaNueva;

    public function __construct(Atencion $atencion, ?int $etapaAnterior, int $etapaNueva)
    {
        $this->atencion = $atencion;
        $this->etapaAnterior = $etapaAnterior;
        $this->etapaNueva = $etapaNueva;
    }
}
