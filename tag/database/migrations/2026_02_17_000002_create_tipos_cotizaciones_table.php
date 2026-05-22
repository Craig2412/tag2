<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_cotizacion')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cotizaciones');
    }
};
// Descripcion: Crea la tabla tipos_cotizaciones.
