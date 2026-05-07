<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoOrdenCompra extends Model
{
    protected $table = 'estados_ordenes_compra';

    protected $fillable = [
        'slug',
        'nombre',
        'color',
    ];
}
