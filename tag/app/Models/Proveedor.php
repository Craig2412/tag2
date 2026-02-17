<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre_empresa',
        'rif',
        'correo_empresa',
        'telefono_empresa',
        'nombre_persona_contacto',
        'tipo_proveedor',
        'estatus',
        'borrado_logico',
    ];

    protected $casts = [
        'borrado_logico' => 'boolean',
    ];

    public function tipoProveedor(): BelongsTo
    {
        return $this->belongsTo(TipoProveedor::class, 'tipo_proveedor');
    }

    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    public function tiposServicio(): HasMany
    {
        return $this->hasMany(TipoServicio::class, 'id_proveedor');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_proveedor');
    }
}
