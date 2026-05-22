<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->unsignedBigInteger('id_estado_anterior')->nullable();
            $table->unsignedBigInteger('id_estado_nuevo');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones');
            $table->foreign('id_estado_anterior')->references('id')->on('estados_cotizaciones');
            $table->foreign('id_estado_nuevo')->references('id')->on('estados_cotizaciones');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_historial');
    }
};
