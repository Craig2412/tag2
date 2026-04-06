<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CotizacionHistorial extends Model
{
    use HasFactory;
    protected $table = 'cotizacion_historial';
    protected $fillable = [
        'cotizacion_id',
        'estatus_anterior',
        'estatus_nuevo',
        'usuario_id',
        'comentario',
    ];
}