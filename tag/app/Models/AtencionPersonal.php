<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtencionPersonal extends Model
{
    use HasFactory;

    protected $table = 'atenciones_personal';

    protected $fillable = [
        'id_atencion',
        'id_personal',
    ];

    // Devuelve la atencion asociada.
    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class, 'id_atencion');
    }

    // Devuelve el personal asignado.
    public function personal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_personal');
    }
}
