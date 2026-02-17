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

    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class, 'id_atencion');
    }

    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    public function serviciosCotizaciones(): HasMany
    {
        return $this->hasMany(ServicioCotizacion::class, 'id_cotizacion');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_cotizacion');
    }
}
