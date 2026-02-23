<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCotizacion extends Model
{
    use HasFactory;

    protected $table = 'tipos_cotizaciones';

    protected $fillable = [
        'tipo_cotizacion',
    ];

    // Lista las cotizaciones asociadas a este tipo.
    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'id_tipo_cotizacion');
    }
}
