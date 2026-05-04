<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntidadBancaria extends Model
{
    use HasFactory;

    protected $table = 'entidades_bancarias';

    protected $fillable = [
        'entidad',
        'estatus',
    ];

    public function estatus_relation(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }
}
