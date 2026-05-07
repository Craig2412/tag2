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
        'estatus_anterior',
        'estatus_nuevo',
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
}
