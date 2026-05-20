<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TasaCambio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tasas_cambio';

    protected $fillable = [
        'id_tasa',
        'valor_cambio',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
        'valor_cambio' => 'decimal:4',
    ];

    // Lista los servicios que usan esta tasa de cambio.
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_tasa_cambio');
    }

    // Devuelve el tipo de moneda del catálogo (Ej: USD_BCV).
    public function monedaCatalogo(): BelongsTo
    {
        return $this->belongsTo(Tasa::class, 'id_tasa');
    }
}
