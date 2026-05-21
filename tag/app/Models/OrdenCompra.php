<?php

namespace App\Models;

use App\Models\CuentaPorPagar;
use App\Models\EstadoFinanciero;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /**
     * Sincroniza las Cuentas por Pagar con los servicios actuales de la cotización.
     * Crea, actualiza o elimina CxP según los proveedores presentes en los servicios.
     */
    public function sincronizarCuentasPorPagar(): void
    {
        $servicios = Servicio::where('id_cotizacion', $this->id_cotizacion)->get();
        $porProveedor = $servicios->groupBy('id_proveedor');

        $estadoPendienteId = EstadoFinanciero::where('slug', 'pendiente')->value('id') ?: 1;

        $proveedoresConServicios = $porProveedor->keys()->toArray();

        foreach ($porProveedor as $idProveedor => $servs) {
            $montoTotal = $servs->sum('costo');

            $cuenta = CuentaPorPagar::where('id_orden_compra', $this->id)
                ->where('id_proveedor', $idProveedor)
                ->first();

            if ($cuenta) {
                // Preservar lo ya pagado: saldo = max(0, nuevo_monto - total_pagado)
                $totalPagado = (float) $cuenta->pagos()->sum('monto_asignado');
                $nuevoSaldo = max(0, $montoTotal - $totalPagado);

                $cuenta->update([
                    'monto_total' => $montoTotal,
                    'saldo_pendiente' => $nuevoSaldo,
                ]);

                $this->actualizarEstadoFinancieroCuenta($cuenta);
            } else {
                $cuenta = CuentaPorPagar::create([
                    'id_orden_compra' => $this->id,
                    'id_proveedor' => $idProveedor,
                    'monto_total' => $montoTotal,
                    'saldo_pendiente' => $montoTotal,
                    'id_estado_financiero' => $estadoPendienteId,
                ]);
            }
        }

        // Soft-delete de CxP cuyos proveedores ya no tienen servicios en la cotización
        $eliminadas = CuentaPorPagar::where('id_orden_compra', $this->id)
            ->whereNotIn('id_proveedor', $proveedoresConServicios)
            ->delete();

        if ($eliminadas > 0) {
            Log::info("{$eliminadas} Cuenta(s) por Pagar eliminada(s) por falta de servicios en OC #{$this->id}");
        }

        // Sincronizar estado de egreso de la OC
        \App\Services\OrdenStateService::sincronizarEgreso($this);
    }

    /**
     * Recalcula el estado financiero de una Cuenta por Pagar según su saldo pendiente.
     */
    private function actualizarEstadoFinancieroCuenta(CuentaPorPagar $cuenta): void
    {
        $slugEstado = 'parcial';
        if ($cuenta->saldo_pendiente <= 0) {
            $slugEstado = 'pagado';
        } elseif ($cuenta->saldo_pendiente >= $cuenta->monto_total) {
            $slugEstado = 'pendiente';
        }

        $estado = EstadoFinanciero::where('slug', $slugEstado)->first();
        if ($estado && $cuenta->id_estado_financiero !== $estado->id) {
            $cuenta->update(['id_estado_financiero' => $estado->id]);
        }
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
