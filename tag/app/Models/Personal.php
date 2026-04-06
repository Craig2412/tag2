<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personal extends Model
{
    use SoftDeletes;

    protected $table = 'personal';

    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'cedula',
        'telefono',
        'correo_institucional',
        'porcentaje_comision',
        'id_estatus',
    ];

    public function logrosPersonal(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LogroPersonal::class, 'id_personal');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
