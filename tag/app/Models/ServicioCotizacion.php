<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioCotizacion extends Model
{
    use HasFactory;

    protected $table = 'servicios_cotizaciones';

    protected $fillable = [
        'id_servicio',
        'id_cotizacion',
    ];

    // Devuelve el servicio vinculado.
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    // Devuelve la cotizacion vinculada.
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }
}
