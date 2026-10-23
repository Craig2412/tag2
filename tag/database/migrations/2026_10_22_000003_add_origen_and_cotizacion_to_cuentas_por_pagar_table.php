<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the origin and quote link to accounts payable so commission payables
     * can live in the same table as purchase payables without an order.
     *
     * Commission payables have id_orden_compra = null, origen = 'comision'
     * and id_cotizacion pointing to the approved quote.
     */
    public function up(): void
    {
        // id_orden_compra must be nullable to host commission payables.
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropForeign(['id_orden_compra']);
        });

        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->unsignedBigInteger('id_orden_compra')->nullable()->change();
            $table->enum('origen', ['compra', 'comision', 'manual'])
                ->default('compra')
                ->after('id_orden_compra')
                ->index();
            $table->foreignId('id_cotizacion')
                ->nullable()
                ->after('origen')
                ->constrained('cotizaciones')
                ->nullOnDelete();
        });

        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->foreign('id_orden_compra')->references('id')->on('ordenes_compra');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropForeign(['id_cotizacion']);
            $table->dropForeign(['id_orden_compra']);
        });

        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->dropColumn(['id_cotizacion', 'origen']);
        });

        // Commission payables have no order; they cannot exist in the old schema.
        DB::table('cuentas_por_pagar')->whereNull('id_orden_compra')->delete();

        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->unsignedBigInteger('id_orden_compra')->nullable(false)->change();
        });

        Schema::table('cuentas_por_pagar', function (Blueprint $table) {
            $table->foreign('id_orden_compra')->references('id')->on('ordenes_compra');
        });
    }
};
