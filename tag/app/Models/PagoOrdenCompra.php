<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\PagoOrdenCompraGuardado;

class PagoOrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'pagos_ordenes_compra';

    protected $fillable = [
        'id_pago',
        'id_orden_compra',
        'monto_asignado',
        'monto_pagado',
    ];

    protected $dispatchesEvents = [
        'saved'   => PagoOrdenCompraGuardado::class,
        'deleted' => PagoOrdenCompraGuardado::class,
    ];

    // Devuelve el pago asociado.
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }

    // Devuelve la orden de compra asociada.
    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'id_orden_compra');
    }
}
