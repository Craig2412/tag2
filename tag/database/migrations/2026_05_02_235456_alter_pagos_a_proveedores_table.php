<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagos_a_proveedores', function (Blueprint $table) {
            $table->dropForeign(['id_servicio']);
            $table->dropColumn('id_servicio');

            $table->renameColumn('monto', 'monto_total');
            $table->foreignId('id_proveedor')->nullable()->after('id')->constrained('proveedores');
            $table->foreignId('id_tasa_cambio')->nullable()->after('monto_total')->constrained('tasas_cambio');
            $table->string('comprobante')->nullable()->after('id_metodo_pago');
            $table->foreignId('estatus')->nullable()->after('comprobante')->constrained('estatus');
            
            $table->softDeletes();
            $table->unique('referencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_a_proveedores', function (Blueprint $table) {
            $table->foreignId('id_servicio')->nullable()->constrained('servicios');
            $table->renameColumn('monto_total', 'monto');
            $table->dropForeign(['id_proveedor']);
            $table->dropColumn('id_proveedor');
            $table->dropForeign(['id_tasa_cambio']);
            $table->dropColumn('id_tasa_cambio');
            $table->dropColumn('comprobante');
            $table->dropForeign(['estatus']);
            $table->dropColumn('estatus');
            
            $table->dropSoftDeletes();
            $table->dropUnique(['referencia']);
        });
    }
};
