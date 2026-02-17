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

    // Devuelve el tipo de proveedor asociado.
    public function tipoProveedor(): BelongsTo
    {
        return $this->belongsTo(TipoProveedor::class, 'tipo_proveedor');
    }

    // Devuelve el estatus actual del proveedor.
    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    // Lista los tipos de servicio que ofrece este proveedor.
    public function tiposServicio(): HasMany
    {
        return $this->hasMany(TipoServicio::class, 'id_proveedor');
    }

    // Lista los servicios registrados para este proveedor.
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_proveedor');
    }
}
