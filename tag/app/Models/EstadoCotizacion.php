<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoCotizacion extends Model
{
    protected $table = 'estados_cotizaciones';

    protected $fillable = [
        'slug',
        'nombre',
        'color',
    ];

    public $timestamps = false;
}
