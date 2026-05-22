<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoServicio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_servicio';

    protected $fillable = [
        'tipo_servicio',
        'iva_defecto',
    ];

    protected $casts = [
        'iva_defecto' => 'float',
    ];

    // Lista los proveedores que ofrecen este tipo de servicio.
    public function proveedores(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'proveedor_tipo_servicio', 'id_tipo_servicio', 'id_proveedor')->withTimestamps();
    }

    // Lista los servicios que usan este tipo.
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_tipo_servicio');
    }
}
