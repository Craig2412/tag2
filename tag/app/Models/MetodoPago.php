<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    // Lista las entidades bancarias asociadas a este método de pago.
    public function entidadesBancarias(): BelongsToMany
    {
        return $this->belongsToMany(EntidadBancaria::class, 'metodo_pago_entidad_bancaria', 'id_metodo_pago', 'id_entidad_bancaria')->withTimestamps();
    }
}
