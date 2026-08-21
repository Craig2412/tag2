<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use SoftDeletes;

    protected $table = 'personal';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido',
        'cedula',
        'telefono',
        'correo_institucional',
        'departamento',
        'cargo',
        'porcentaje_comision',
    ];

    public function logrosPersonal(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LogroPersonal::class, 'id_personal');
    }

    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
