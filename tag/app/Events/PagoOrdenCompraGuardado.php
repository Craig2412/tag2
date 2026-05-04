<?php

namespace App\Events;

use App\Models\PagoOrdenCompra;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagoOrdenCompraGuardado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PagoOrdenCompra $pagoOrden;

    public function __construct(PagoOrdenCompra $pagoOrden)
    {
        $this->pagoOrden = $pagoOrden;
    }
}
