<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cotizacion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'id_atencion',
        'id_tipo_cotizacion',
        'cant_adultos',
        'cant_menores',
        'cant_viejos',
        'id_tasa_cambio',
        'fecha_vencimiento',
        'estatus',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    // Expone campos calculados en el JSON automáticamente
    protected $appends = ['esta_vencida'];

    // Devuelve la atencion a la que pertenece la cotizacion.
    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class, 'id_atencion');
    }

    // Devuelve el tipo de cotizacion asignado.
    public function tipoCotizacion(): BelongsTo
    {
        return $this->belongsTo(TipoCotizacion::class, 'id_tipo_cotizacion');
    }

    // Devuelve la tasa congelada asignada a la cotizacion.
    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    // Devuelve el estatus actual de la cotizacion.
    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    // Lista los servicios asociados a la cotizacion.
    public function serviciosCotizaciones(): HasMany
    {
        return $this->hasMany(ServicioCotizacion::class, 'id_cotizacion');
    }

    // Devuelve la orden de compra asociada a la cotizacion.
    public function ordenCompra(): HasOne
    {
        return $this->hasOne(OrdenCompra::class, 'id_cotizacion');
    }

    // Indica si la proforma ya expiró. Calculado en servidor, nunca persistido.
    public function getEstaVencidaAttribute(): bool
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }

        return $this->fecha_vencimiento->isPast();
    }
}
