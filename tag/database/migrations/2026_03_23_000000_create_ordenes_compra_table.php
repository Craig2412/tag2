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
            $table->foreignId('id_tasa_cambio')->constrained('tasas_cambio');
            $table->foreignId('estatus')->constrained('estatus');
            $table->decimal('monto_total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};
