<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtapaComercial extends Model
{
    use SoftDeletes;
    protected $table = 'etapas_comerciales';

    protected $fillable = [
        'slug',
        'label',
        'color',
    ];
}
