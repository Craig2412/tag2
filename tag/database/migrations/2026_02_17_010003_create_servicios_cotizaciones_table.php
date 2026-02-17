<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_servicio')->constrained('servicios');
            $table->foreignId('id_cotizacion')->constrained('cotizaciones');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios_cotizaciones');
    }
};
