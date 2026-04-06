<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompraHistorial extends Model
{
    use HasFactory;
    protected $table = 'orden_compra_historial';
    protected $fillable = [
        'orden_compra_id',
        'estatus_anterior',
        'estatus_nuevo',
        'usuario_id',
        'comentario',
    ];
}