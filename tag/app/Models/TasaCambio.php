<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TasaCambio extends Model
{
    use HasFactory;

    protected $table = 'tasas_cambio';

    protected $fillable = [
        'tasa_usd',
        'tasa_eur',
        'tasa_binance',
        'tasa_personalizada',
        'fecha',
        'borrado_logico',
    ];

    protected $casts = [
        'fecha' => 'date',
        'borrado_logico' => 'boolean',
    ];

    // Lista los servicios que usan esta tasa de cambio.
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_tasa_cambio');
    }
}
