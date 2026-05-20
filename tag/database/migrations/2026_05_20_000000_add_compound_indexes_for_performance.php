<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Hot-path: sincronización de estados ──────────────────

        // sincronizarFaseAtencion: WHERE id_atencion + WHERE id_estado_cotizacion
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->index(['id_atencion', 'id_estado_cotizacion'], 'idx_cotizaciones_atencion_estado');
        });

        // MetricasController + filtros de personal
        Schema::table('atenciones', function (Blueprint $table) {
            $table->index(['id_personal', 'id_estado_atencion'], 'idx_atenciones_personal_estado');
        });

        // sincronizarEstadoOperativo: WHERE id_estado_financiero + id_estado_financiero_egreso
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->index(['id_estado_financiero', 'id_estado_financiero_egreso'], 'idx_ordenes_financiero_egreso');
        });

        // ── Hot-path: agregaciones de montos ─────────────────────

        // GenerarOrdenDesdeCotizacionListener + GenerarCuentasPorPagarListener: WHERE id_cotizacion + SUM
        Schema::table('servicios', function (Blueprint $table) {
            $table->index(['id_cotizacion'], 'idx_servicios_cotizacion');
        });

        // sincronizarEstadoFinanciero: WHERE id_orden_compra + SUM(monto_asignado)
        Schema::table('pagos_ordenes_compra', function (Blueprint $table) {
            $table->index(['id_orden_compra'], 'idx_pagos_oc_orden');
        });

        // sincronizarEstadoEgreso: WHERE id_orden_compra + SUM(saldo_pendiente)
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->index(['id_orden_compra'], 'idx_cxp_orden');
        });

        // ── Métricas y purga ─────────────────────────────────────

        // MetricasController: LAG() OVER (PARTITION BY orden_compra_id ORDER BY created_at)
        Schema::table('orden_compra_historial', function (Blueprint $table) {
            $table->index(['orden_compra_id', 'created_at'], 'idx_oc_historial_orden_fecha');
        });

        // MetricasController + app:prune-logs
        Schema::table('logros_personal', function (Blueprint $table) {
            $table->index(['id_personal', 'created_at'], 'idx_logros_personal_fecha');
        });

        // PagoProveedorObserver::deleting() + PagoProveedorCuentaObserver
        Schema::table('pago_proveedor_cuentas', function (Blueprint $table) {
            $table->index(['id_cuenta_por_pagar', 'id_pago_proveedor'], 'idx_pago_proveedor_cuentas_pivot');
        });
    }

    public function down(): void
    {
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropIndex('idx_cotizaciones_atencion_estado');
        });
        Schema::table('atenciones', function (Blueprint $table) {
            $table->dropIndex('idx_atenciones_personal_estado');
        });
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropIndex('idx_ordenes_financiero_egreso');
        });
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropIndex('idx_servicios_cotizacion');
        });
        Schema::table('pagos_ordenes_compra', function (Blueprint $table) {
            $table->dropIndex('idx_pagos_oc_orden');
        });
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropIndex('idx_cxp_orden');
        });
        Schema::table('orden_compra_historial', function (Blueprint $table) {
            $table->dropIndex('idx_oc_historial_orden_fecha');
        });
        Schema::table('logros_personal', function (Blueprint $table) {
            $table->dropIndex('idx_logros_personal_fecha');
        });
        Schema::table('pago_proveedor_cuentas', function (Blueprint $table) {
            $table->dropIndex('idx_pago_proveedor_cuentas_pivot');
        });
    }
};
