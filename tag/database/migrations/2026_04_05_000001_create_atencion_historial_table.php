<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('atencion_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('atencion_id');
            $table->unsignedBigInteger('id_estado_anterior')->nullable();
            $table->unsignedBigInteger('id_estado_nuevo')->nullable();
            $table->unsignedBigInteger('id_etapa_anterior')->nullable();
            $table->unsignedBigInteger('id_etapa_nueva')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->foreign('atencion_id')->references('id')->on('atenciones');
            $table->foreign('id_estado_anterior')->references('id')->on('estados_atenciones');
            $table->foreign('id_estado_nuevo')->references('id')->on('estados_atenciones');
            $table->foreign('id_etapa_anterior')->references('id')->on('etapas_comerciales');
            $table->foreign('id_etapa_nueva')->references('id')->on('etapas_comerciales');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atencion_historial');
    }
};
