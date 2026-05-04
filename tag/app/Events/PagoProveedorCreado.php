<?php

namespace App\Events;

use App\Models\PagoProveedor;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagoProveedorCreado
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PagoProveedor $pago;

    public function __construct(PagoProveedor $pago)
    {
        $this->pago = $pago;
    }
}
