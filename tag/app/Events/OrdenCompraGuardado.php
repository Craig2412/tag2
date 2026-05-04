<?php

namespace App\Events;

use App\Models\OrdenCompra;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdenCompraGuardado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public OrdenCompra $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }
}
