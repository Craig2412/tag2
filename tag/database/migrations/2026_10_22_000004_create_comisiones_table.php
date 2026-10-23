<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commissions agreed on a quote (percentage or fixed amount).
     *
     * A commission is the source of truth for the agreement; its payable is the
     * materialized debt. Both are created atomically when the quote is approved.
     */
    public function up(): void
    {
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotizacion')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('id_proveedor_comisionista')->constrained('proveedores');

            $table->enum('tipo', ['porcentaje', 'fijo']);
            $table->decimal('valor', 12, 4);   // 10.0000 (%) or 250.0000 (fixed)
            $table->decimal('base', 14, 2);    // Quote total at approval time
            $table->decimal('monto', 14, 2);   // Calculated commission

            $table->foreignId('id_tasa_cambio')->nullable()->constrained('tasas_cambio');
            $table->string('moneda', 20)->nullable();

            $table->enum('estado', ['devengada', 'pagada', 'anulada'])
                ->default('devengada')
                ->index();

            $table->foreignId('id_cuenta_por_pagar')->nullable()->constrained('cuentas_por_pagar');

            $table->timestamp('anulada_at')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('usuarios');
            $table->string('motivo_anulacion')->nullable();

            $table->timestamps();

            $table->index(['id_cotizacion', 'estado'], 'idx_comisiones_cotizacion_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');
    }
};
