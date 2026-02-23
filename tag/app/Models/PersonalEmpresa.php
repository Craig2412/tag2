<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalEmpresa extends Model
{
    use HasFactory;

    protected $table = 'personal_empresas';

    protected $fillable = [
        'id_personal',
        'id_empresa',
    ];

    // Devuelve el personal asociado.
    public function personal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_personal');
    }

    // Devuelve la empresa asociada.
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }
}
