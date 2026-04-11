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
        Schema::create('tasas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique(); // Ej: USD_BCV, EUR_BCV, BINANCE
            $table->string('nombre');          // Ej: Dólar BCV, Euro Oficial
            $table->string('simbolo', 10);      // Ej: $, €, Bs.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasas');
    }
};
