<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'id_cotizacion',
        'id_tasa_cambio',
        'estatus',
        'monto_total',
    ];

    protected $casts = [
        'monto_total' => 'float',
    ];

    // Devuelve la cotizacion origen de la orden.
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    // Devuelve la tasa de cambio aplicada a la orden.
    public function tasaCambio(): BelongsTo
    {
        return $this->belongsTo(TasaCambio::class, 'id_tasa_cambio');
    }

    // Devuelve el estatus actual de la orden.
    public function estatus(): BelongsTo
    {
        return $this->belongsTo(Estatus::class, 'estatus');
    }

    // Lista los pagos asignados a esta orden.
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoOrdenCompra::class, 'id_orden_compra');
    }

    // Recalcula y persiste el monto total segun los servicios de su cotizacion.
    public function recalcularMontoTotal(): void
    {
        $montoTotal = (float) DB::table('servicios_cotizaciones')
            ->join('servicios', 'servicios_cotizaciones.id_servicio', '=', 'servicios.id')
            ->where('servicios_cotizaciones.id_cotizacion', $this->id_cotizacion)
            ->sum('servicios.total_servicio');

        if ((float) $this->monto_total === $montoTotal) {
            return;
        }

        $this->forceFill(['monto_total' => $montoTotal])->save();
    }
}
