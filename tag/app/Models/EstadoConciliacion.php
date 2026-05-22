<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoConciliacion extends Model
{
    use HasFactory;

    protected $table = 'estados_conciliacion';

    protected $fillable = [
        'nombre',
        'slug',
        'color',
    ];
}
