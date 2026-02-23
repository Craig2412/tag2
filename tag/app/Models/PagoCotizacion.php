<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCotizacion extends Model
{
    use HasFactory;

    protected $table = 'pagos_cotizaciones';

    protected $fillable = [
        'id_pago',
        'id_cotizacion',
        'monto_asignado',
    ];

    // Devuelve el pago asociado.
    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }

    // Devuelve la cotizacion asociada.
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }
}
