<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaPorPagar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cuentas_por_pagar';

    protected $fillable = [
        'id_orden_compra',
        'id_proveedor',
        'monto_total',
        'saldo_pendiente',
        'estatus',
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'id_orden_compra');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function estatusCuenta(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    public function pagos()
    {
        return $this->belongsToMany(PagoProveedor::class, 'pago_proveedor_cuentas', 'id_cuenta_por_pagar', 'id_pago_proveedor')
            ->withPivot('monto_asignado')
            ->withTimestamps();
    }
}
