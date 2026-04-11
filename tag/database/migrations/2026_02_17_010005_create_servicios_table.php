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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotizacion')->constrained('cotizaciones')->onDelete('cascade');
            $table->foreignId('id_tipo_servicio')->constrained('tipo_servicio');
            $table->foreignId('id_proveedor')->constrained('proveedores');
            $table->decimal('costo', 12, 2);
            $table->text('descripcion')->nullable();
            $table->decimal('monto_gravable', 12, 2);
            $table->decimal('monto_no_sujeto', 12, 2);
            $table->decimal('total_servicio', 12, 2);
            $table->decimal('iva_establecido', 5, 2)->nullable();
            $table->foreignId('id_tasa_cambio')->constrained('tasas_cambio');
            $table->foreignId('estatus')->nullable()->constrained('estatus');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
