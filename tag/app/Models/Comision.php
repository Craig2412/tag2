<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comision extends Model
{
    use HasFactory;

    public const TIPO_PORCENTAJE = 'porcentaje';

    public const TIPO_FIJO = 'fijo';

    public const ESTADO_DEVENGADA = 'devengada';

    public const ESTADO_PAGADA = 'pagada';

    public const ESTADO_ANULADA = 'anulada';

    protected $table = 'comisiones';

    protected $fillable = [
        'id_cotizacion',
        'id_proveedor_comisionista',
        'tipo',
        'valor',
        'base',
        'monto',
        'id_tasa_cambio',
        'moneda',
        'estado',
        'id_cuenta_por_pagar',
        'anulada_at',
        'anulada_por',
        'motivo_anulacion',
    ];

    protected $casts = [
        'valor' => 'decimal:4',
        'base' => 'decimal:2',
        'monto' => 'decimal:2',
        'anulada_at' => 'datetime',
    ];

    // Cotización que originó la comisión.
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    // Proveedor que actúa como comisionista.
    public function comisionista(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor_comisionista');
    }

    // Cuenta por pagar generada por esta comisión.
    public function cuentaPorPagar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorPagar::class, 'id_cuenta_por_pagar');
    }

    // Tasa de cambio congelada al momento de la aprobación.
    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    // Usuario que anuló la comisión.
    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'anulada_por');
    }

    // Comisiones vigentes (devengadas o pagadas).
    public function scopeActivas($query)
    {
        return $query->where('estado', '!=', self::ESTADO_ANULADA);
    }

    public function scopeDeCotizacion($query, $idCotizacion)
    {
        return $query->where('id_cotizacion', $idCotizacion);
    }
}
