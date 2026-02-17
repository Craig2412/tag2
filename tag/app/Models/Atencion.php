<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atencion extends Model
{
    use HasFactory;

    protected $table = 'atenciones';

    protected $fillable = [
        'id_cliente',
        'id_personal',
        'id_origen_atencion',
        'asunto',
        'notas_adicionales',
        'estatus',
        'borrado_logico',
    ];

    protected $casts = [
        'borrado_logico' => 'boolean',
    ];

    // Devuelve el cliente asociado a la atencion.
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_cliente');
    }

    // Devuelve el personal asignado a la atencion.
    public function personal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_personal');
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
}
