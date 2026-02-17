<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'metodo_pago',
    ];

    // Lista los pagos hechos con este metodo.
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_metodo_pago');
    }
}
