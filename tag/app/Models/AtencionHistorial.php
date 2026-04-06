<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}