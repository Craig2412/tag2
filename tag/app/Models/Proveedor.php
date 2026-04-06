<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre_empresa',
        'razon_comercial',
        'rif',
        'correo_empresa',
        'telefono_empresa',
        'nombre_persona_contacto',
        'id_tipo_contribuyente',
        'tipo_proveedor',
        'estatus',
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

    // Devuelve el tipo de contribuyente del proveedor.
    public function tipoContribuyente(): BelongsTo
    {
        return $this->belongsTo(TipoContribuyente::class, 'id_tipo_contribuyente');
    }
}
