<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntidadBancaria extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'entidades_bancarias';

    protected $fillable = [
        'entidad',
    ];

    // Lista los métodos de pago que usan esta entidad bancaria.
    public function metodosPago(): BelongsToMany
    {
        return $this->belongsToMany(MetodoPago::class, 'metodo_pago_entidad_bancaria', 'id_entidad_bancaria', 'id_metodo_pago')->withTimestamps();
    }
}
