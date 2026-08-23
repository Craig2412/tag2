<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaRetencion extends Model
{
    use HasFactory;

    protected $table = 'factura_retenciones';

    protected $fillable = [
        'id_factura',
        'id_factura_detalle',
        'codigo_concepto',
        'nombre_concepto',
        'aplica_a',
        'base_calculo',
        'porcentaje',
        'monto',
    ];

    protected $casts = [
        'porcentaje' => 'float',
        'monto' => 'float',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(FacturaDetalle::class, 'id_factura_detalle');
    }
}
