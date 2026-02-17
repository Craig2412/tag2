<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'id_cotizacion',
        'fecha_pago',
        'monto_abono',
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

    // Devuelve la cotizacion a la que pertenece el pago.
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
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
