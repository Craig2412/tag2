<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'servicios';

    protected $fillable = [
        'id_tipo_servicio',
        'id_proveedor',
        'costo',
        'monto_gravable',
        'monto_no_sujeto',
        'total_servicio',
        'id_tasa_cambio',
        'borrado_logico',
    ];

    protected $casts = [
        'borrado_logico' => 'boolean',
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
}
