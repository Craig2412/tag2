<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoAtencion extends Model
{
    protected $table = 'estados_atenciones';

    protected $fillable = [
        'slug',
        'nombre',
        'color',
    ];

    public $timestamps = false;
}
