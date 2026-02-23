<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_atencion')->constrained('atenciones');
            $table->foreignId('id_tipo_cotizacion')
                ->nullable()
                ->constrained('tipos_cotizaciones');
            $table->unsignedInteger('cant_adultos');
            $table->unsignedInteger('cant_menores');
            $table->unsignedInteger('cant_viejos');
            $table->foreignId('id_tasa_cambio')->constrained('tasas_cambio');
            $table->foreignId('estatus')->constrained('estatus');
            $table->boolean('borrado_logico')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
// Descripcion: Crea la tabla cotizaciones.
