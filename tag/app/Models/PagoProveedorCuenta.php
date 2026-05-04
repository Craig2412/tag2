<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoProveedorCuenta extends Pivot
{
    protected $table = 'pago_proveedor_cuentas';

    protected $fillable = [
        'id_pago_proveedor',
        'id_cuenta_por_pagar',
        'monto_asignado',
    ];

    public function pagoProveedor(): BelongsTo
    {
        return $this->belongsTo(PagoProveedor::class, 'id_pago_proveedor');
    }

    public function cuentaPorPagar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorPagar::class, 'id_cuenta_por_pagar');
    }
}
