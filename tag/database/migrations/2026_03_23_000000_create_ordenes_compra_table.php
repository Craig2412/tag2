<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotizacion')->unique()->constrained('cotizaciones');
            $table->foreignId('estatus')->constrained('estatus'); // Estado Operativo (Ej. En Proceso, Anulada)
            $table->string('estado_financiero')->default('POR_PAGAR'); // Enum Mantenido por Observer
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
