<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones';

    protected $fillable = [
        'id_atencion',
        'id_tipo_cotizacion',
        'cant_adultos',
        'cant_menores',
        'cant_viejos',
        'id_tasa_cambio',
        'estatus',
        'borrado_logico',
    ];

    protected $casts = [
        'borrado_logico' => 'boolean',
    ];

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

    // Devuelve la tasa de cambio usada en la cotizacion.
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

    // Lista los pagos asociados a esta cotizacion.
    public function pagosCotizaciones(): HasMany
    {
        return $this->hasMany(PagoCotizacion::class, 'id_cotizacion');
    }
}
