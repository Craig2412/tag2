<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EntidadBancaria extends Model
{
    use HasFactory;

    protected $table = 'entidades_bancarias';

    protected $fillable = [
        'entidad',
        'estatus',
    ];

    public function estatus_relation(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    // Lista los métodos de pago que usan esta entidad bancaria.
    public function metodosPago(): BelongsToMany
    {
        return $this->belongsToMany(MetodoPago::class, 'metodo_pago_entidad_bancaria', 'id_entidad_bancaria', 'id_metodo_pago')->withTimestamps();
    }
}
