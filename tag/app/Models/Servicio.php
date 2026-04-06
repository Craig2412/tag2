<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Servicio extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'servicios';

    protected $fillable = [
        'id_tipo_servicio',
        'id_proveedor',
        'costo',
        'descripcion', // Campo nuevo
        'monto_gravable',
        'monto_no_sujeto',
        'total_servicio',
        'iva_establecido',
        'id_tasa_cambio',
        'estatus',
    ];

    protected $casts = [
        'iva_establecido' => 'float',
    ];

    // Devuelve el tipo de servicio asociado.
    public function tipoServicio(): BelongsTo
    {
        return $this->belongsTo(TipoServicio::class, 'id_tipo_servicio');
    }

    // Devuelve el proveedor que presta el servicio.
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor');
    }

    // Devuelve la tasa de cambio usada en el servicio.
    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    // Devuelve el estatus actual del servicio.
    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    // Lista los pagos hechos a proveedores para este servicio.
    public function pagosProveedores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PagoProveedor::class, 'id_servicio');
    }
}
