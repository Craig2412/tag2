<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cant_atenciones_aprobadas')->default(0);
            $table->unsignedInteger('cant_cotizaciones_cerradas')->default(0);
            $table->unsignedInteger('cant_cotizaciones_pagadas')->default(0);
            $table->foreignId('id_temporalidad')->constrained('temporalidades');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
// Descripcion: Crea la tabla metas.
