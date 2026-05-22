<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'metas';

    protected $fillable = [
        'nombre',
        'tipo_entidad',
        'estatus_objetivo',
        'es_monetario',
        'valor_objetivo',
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
