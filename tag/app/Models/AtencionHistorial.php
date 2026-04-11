<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtencionHistorial extends Model
{
    use HasFactory;
    protected $table = 'atencion_historial';
    protected $fillable = [
        'atencion_id',
        'estatus_anterior',
        'estatus_nuevo',
        'usuario_id',
        'comentario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function atencion(): BelongsTo
    {
        return $this->belongsTo(Atencion::class, 'atencion_id');
    }

    public function estatusAnteriorObj(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus_anterior');
    }

    public function estatusNuevoObj(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus_nuevo');
    }
}