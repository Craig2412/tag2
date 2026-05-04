<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Temporalidad extends Model
{
    use HasFactory;

    protected $table = 'temporalidades';

    protected $fillable = [
        'temporalidad',
        'slug',
        'carbon_method',
    ];

    public function metas(): HasMany
    {
        return $this->hasMany(Meta::class, 'id_temporalidad');
    }
}
