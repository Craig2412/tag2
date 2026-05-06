<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogroPersonal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'logros_personal';

    protected $fillable = [
        'id_personal',
        'tipo_entidad',
        'id_entidad',
        'id_estatus_anterior',
        'id_estatus_nuevo',
        'tiempo_transcurrido_segundos',
    ];

    protected $casts = [
        'tiempo_transcurrido_segundos' => 'integer',
    ];

    // Devuelve el personal relacionado con el logro.
    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'id_personal');
    }

    // Devuelve el estatus anterior del cambio.
    public function estatusAnterior(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'id_estatus_anterior');
    }

    // Devuelve el estatus nuevo del cambio.
    public function estatusNuevo(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'id_estatus_nuevo');
    }
}
