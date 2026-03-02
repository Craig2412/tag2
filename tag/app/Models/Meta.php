<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meta extends Model
{
    use HasFactory;

    protected $table = 'metas';

    protected $fillable = [
        'cant_atenciones_aprobadas',
        'cant_cotizaciones_cerradas',
        'cant_cotizaciones_pagadas',
        'id_temporalidad',
    ];

    public function temporalidad(): BelongsTo
    {
        return $this->belongsTo(Temporalidad::class, 'id_temporalidad');
    }

    public function metasPersonal(): HasMany
    {
        return $this->hasMany(MetaPersonal::class, 'id_meta');
    }
}
