<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoContribuyente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipos_contribuyentes';

    protected $fillable = [
        'tipo_contribuyente',
        'porcentaje_iva',
    ];

    // Lista las empresas asociadas a este tipo de contribuyente.
    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class, 'id_tipo_contribuyente');
    }
}
