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
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->foreignId('id_estado_financiero_egreso')->nullable()
                  ->after('id_estado_financiero')
                  ->constrained('estados_financieros');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_compra', function (Blueprint $table) {
            $table->dropForeign(['id_estado_financiero_egreso']);
            $table->dropColumn('id_estado_financiero_egreso');
        });
    }
};
