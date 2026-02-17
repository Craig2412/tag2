<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_cambio', function (Blueprint $table) {
            $table->id();
            $table->decimal('tasa_usd', 12, 4);
            $table->decimal('tasa_eur', 12, 4);
            $table->decimal('tasa_binance', 12, 4);
            $table->decimal('tasa_personalizada', 12, 4);
            $table->date('fecha');
            $table->boolean('borrado_logico')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_cambio');
    }
};
