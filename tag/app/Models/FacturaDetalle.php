<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaDetalle extends Model
{
    use HasFactory;

    protected $table = 'factura_detalles';

    protected $fillable = [
        'id_factura',
        'id_servicio',
        'descripcion_servicio',
        'base_gravable',
        'monto_no_sujeto',
        'iva_porcentaje',
        'iva_valor',
        'total_servicio',
        'total_retenciones_servicio',
        'total_a_pagar_servicio',
    ];

    protected $casts = [
        'base_gravable' => 'float',
        'monto_no_sujeto' => 'float',
        'iva_porcentaje' => 'float',
        'iva_valor' => 'float',
        'total_servicio' => 'float',
        'total_retenciones_servicio' => 'float',
        'total_a_pagar_servicio' => 'float',
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'id_factura');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }
}
