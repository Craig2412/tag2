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
        Schema::create('estados_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->comment('Identificador de sistema: pendiente, aprobada, rechazada');
            $table->string('nombre');
            $table->string('color', 20)->default('#000000');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_cotizaciones');
    }
};
