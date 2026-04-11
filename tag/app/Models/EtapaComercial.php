<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtapaComercial extends Model
{
    protected $table = 'etapas_comerciales';

    protected $fillable = [
        'slug',
        'label',
        'color',
    ];
}
