<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atencion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'atenciones';

    protected $appends = [
        'fase_actual'
    ];

    protected $fillable = [
        'id_cliente',
        'id_personal',
        'id_origen_atencion',
        'asunto',
        'notas_adicionales',
        'estatus',
    ];

    // Devuelve el cliente asociado a la atencion.
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    // Devuelve el personal asignado a la atencion.
    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'id_personal');
    }

    // Devuelve la red o canal de origen.
    public function origen(): BelongsTo
    {
        return $this->belongsTo(Origen::class, 'id_origen_atencion');
    }

    // Devuelve el estatus actual de la atencion.
    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    // Lista las cotizaciones asociadas a la atencion.
    public function cotizaciones(): HasMany
    {
        return $this->hasMany(Cotizacion::class, 'id_atencion');
    }

    /**
     * ESTADO DERIVADO DE MÁQUINA DE ESTADOS:
     * Calcula dinámicamente en qué fase comercial se encuentra este ticket
     * evaluando si existen relaciones descendientes (Cotizaciones/Ordenes).
     */
    public function getFaseActualAttribute(): string
    {
        // Se asume Eager Loading previo de 'cotizaciones.orden_compra'
        if ($this->cotizaciones->isEmpty()) {
            return 'En Atención';
        }

        $tieneOrden = $this->cotizaciones->contains(function ($cotizacion) {
            // Relación cargada o presente físicamente
            return $cotizacion->orden_compra !== null;
        });

        if ($tieneOrden) {
            return 'En Orden de Compra';
        }

        return 'Cotizada';
    }
}
