<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CuentaPorPagar extends Model
{
    use HasFactory, SoftDeletes;

    public const ORIGEN_COMPRA = 'compra';

    public const ORIGEN_COMISION = 'comision';

    public const ORIGEN_MANUAL = 'manual';

    protected $table = 'cuentas_por_pagar';

    protected $fillable = [
        'id_orden_compra',
        'origen',
        'id_cotizacion',
        'id_proveedor',
        'monto_total',
        'saldo_pendiente',
        'id_estado_financiero',
    ];

    protected $attributes = [
        'origen' => self::ORIGEN_COMPRA,
    ];

    public function ordenCompra(): BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class, 'id_orden_compra');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    public function estadoFinanciero(): BelongsTo
    {
        return $this->belongsTo(EstadoFinanciero::class, 'id_estado_financiero');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    public function comision(): HasOne
    {
        return $this->hasOne(Comision::class, 'id_cuenta_por_pagar');
    }

    public function scopeDeComisiones($query)
    {
        return $query->where('origen', self::ORIGEN_COMISION);
    }

    public function pagos()
    {
        return $this->belongsToMany(PagoProveedor::class, 'pago_proveedor_cuentas', 'id_cuenta_por_pagar', 'id_pago_proveedor')
            ->using(PagoProveedorCuenta::class)
            ->withPivot('monto_asignado')
            ->withTimestamps();
    }
}
