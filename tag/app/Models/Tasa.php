<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tasa extends Model
{
    use HasFactory;

    protected $table = 'tasas';

    protected $fillable = [
        'codigo',
        'nombre',
        'simbolo',
    ];

    /**
     * Devuelve el historial de cambios de esta moneda.
     */
    public function tasasHistorial(): HasMany
    {
        return $this->hasMany(TasaCambio::class, 'id_tasa');
    }
}
