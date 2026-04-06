<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoServicio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_servicio';

    protected $fillable = [
        'tipo_servicio',
        'id_proveedor',
        'iva_defecto',
    ];

    protected $casts = [
        'iva_defecto' => 'float',
    ];

    // Devuelve el proveedor asociado a este tipo.
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    // Lista los servicios que usan este tipo.
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_tipo_servicio');
    }
}
