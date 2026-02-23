<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pago')->constrained('pagos');
            $table->foreignId('id_cotizacion')->constrained('cotizaciones');
            $table->decimal('monto_asignado', 12, 2);
            $table->timestamps();

            $table->unique(['id_pago', 'id_cotizacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_cotizaciones');
    }
};
// Descripcion: Crea la tabla pagos_cotizaciones para el detalle de pagos.
