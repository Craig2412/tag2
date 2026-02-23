<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'fecha_pago',
        'monto_total',
        'id_metodo_pago',
        'nro_comprobante',
        'id_tasa_cambio',
        'estatus',
        'borrado_logico',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'borrado_logico' => 'boolean',
    ];

    // Lista las cotizaciones vinculadas a este pago.
    public function cotizaciones(): HasMany
    {
        return $this->hasMany(PagoCotizacion::class, 'id_pago');
    }

    // Devuelve el metodo de pago usado.
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
    }

    // Devuelve la tasa de cambio aplicada.
    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    // Devuelve el estatus actual del pago.
    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }
}
