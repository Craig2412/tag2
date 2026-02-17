<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Origen extends Model
{
    use HasFactory;

    protected $table = 'origenes';

    protected $fillable = [
        'red',
    ];

    // Lista las atenciones que llegaron por esta red.
    public function atenciones(): HasMany
    {
        return $this->hasMany(Atencion::class, 'id_origen_atencion');
    }
}
