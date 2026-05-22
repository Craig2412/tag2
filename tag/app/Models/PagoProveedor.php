<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoProveedor extends Model
{
    use HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'pagos_a_proveedores';

    protected $fillable = [
        'id_proveedor',
        'monto_total',
        'id_tasa_cambio',
        'referencia',
        'fecha_pago',
        'id_metodo_pago',
        'comprobante',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
    }

    public function cuentasPorPagar()
    {
        return $this->belongsToMany(CuentaPorPagar::class, 'pago_proveedor_cuentas', 'id_pago_proveedor', 'id_cuenta_por_pagar')
            ->using(PagoProveedorCuenta::class)
            ->withPivot('monto_asignado')
            ->withTimestamps();
    }
}
