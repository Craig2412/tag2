<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoFinanciero extends Model
{
    protected $table = 'estados_financieros';

    protected $fillable = [
        'slug',
        'label',
        'color',
    ];
}
