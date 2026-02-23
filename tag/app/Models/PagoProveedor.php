<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoProveedor extends Model
{
    use HasFactory;

    protected $table = 'pagos_a_proveedores';

    protected $fillable = [
        'id_servicio',
        'monto',
        'referencia',
        'fecha_pago',
        'id_metodo_pago',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
    ];

    // Devuelve el servicio al que corresponde el pago.
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    // Devuelve el metodo de pago usado.
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
    }
}
