<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptoFiscal extends Model
{
    use HasFactory;

    protected $table = 'conceptos_fiscales';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo_aplicacion',
        'aplica_a',
        'base_calculo',
        'porcentaje',
        'excluir_si_contiene',
        'activo',
        'orden',
    ];

    protected $casts = [
        'porcentaje' => 'float',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden');
    }
}
