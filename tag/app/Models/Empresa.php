<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';

    protected $fillable = [
        'razon_social',
        'razon_comercial',
        'rif',
        'numero_telefono',
        'correo_electronico',
        'direccion',
        'id_tipo_contribuyente',
    ];

    // Devuelve el tipo de contribuyente asociado a la empresa.
    public function tipoContribuyente(): BelongsTo
    {
        return $this->belongsTo(TipoContribuyente::class, 'id_tipo_contribuyente');
    }

    // Lista los enlaces entre personal y la empresa.
    public function personalEmpresas(): HasMany
    {
        return $this->hasMany(PersonalEmpresa::class, 'id_empresa');
    }
}
