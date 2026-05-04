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
        'nombre',
        'tipo_entidad',
        'id_estatus_objetivo',
        'es_monetario',
        'valor_objetivo',
        'id_temporalidad',
    ];

    public function estatusObjetivo(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'id_estatus_objetivo');
    }

    public function temporalidad(): BelongsTo
    {
        return $this->belongsTo(Temporalidad::class, 'id_temporalidad');
    }

    public function metasPersonal(): HasMany
    {
        return $this->hasMany(MetaPersonal::class, 'id_meta');
    }
}
