<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'id_cotizacion',
        'id_estado_orden_compra',      // Estado operativo propio catálogo
        'id_estado_financiero',        // Estado financiero (ingresos cliente)
        'id_estado_financiero_egreso', // Estado financiero (egresos proveedor)
        'monto_total',
    ];

    protected $casts = [
        'monto_total' => 'float',
    ];

    // Expone campos calculados en el JSON automáticamente
    protected $appends = ['saldo_pendiente', 'porcentaje_pagado', 'total_pagado'];

    // Devuelve la cotizacion origen de la orden.
    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    // Devuelve el estado operativo actual de la orden.
    public function estadoOrdenCompra(): BelongsTo
    {
        return $this->belongsTo(EstadoOrdenCompra::class, 'id_estado_orden_compra');
    }

    // Devuelve el estado financiero actual.
    public function estadoFinanciero(): BelongsTo
    {
        return $this->belongsTo(EstadoFinanciero::class, 'id_estado_financiero');
    }

    // Devuelve el estado financiero de egresos (pagos a proveedores).
    public function estadoFinancieroEgreso(): BelongsTo
    {
        return $this->belongsTo(EstadoFinanciero::class, 'id_estado_financiero_egreso');
    }

    // Lista las cuentas por pagar asociadas a esta orden.
    public function cuentasPorPagar(): HasMany
    {
        return $this->hasMany(CuentaPorPagar::class, 'id_orden_compra');
    }

    // Lista los pagos asignados a esta orden.
    public function pagos(): HasMany
    {
        return $this->hasMany(PagoOrdenCompra::class, 'id_orden_compra');
    }

    // Recalcula y persiste el monto total segun los servicios de su cotizacion.
    public function recalcularMontoTotal(): void
    {
        $montoTotal = (float) DB::table('servicios')
            ->where('id_cotizacion', $this->id_cotizacion)
            ->whereNull('deleted_at') // Consider soft deletes
            ->sum('total_servicio');

        if ((float) $this->monto_total === $montoTotal) {
            return;
        }

        $this->forceFill(['monto_total' => $montoTotal])->save();
    }

    // Suma todos los abonos activos asignados a esta orden.
    public function getTotalPagadoAttribute(): float
    {
        return (float) $this->pagos()->sum('monto_asignado');
    }

    // Diferencia entre lo facturado y lo abonado. Siempre >= 0.
    public function getSaldoPendienteAttribute(): float
    {
        return max(0, $this->monto_total - $this->total_pagado);
    }

    // Porcentaje de avance de pagos. Util para barras de progreso en el Frontend.
    public function getPorcentajePagadoAttribute(): float
    {
        if ($this->monto_total <= 0) {
            return 0.0;
        }

        return round(($this->total_pagado / $this->monto_total) * 100, 2);
    }
}
