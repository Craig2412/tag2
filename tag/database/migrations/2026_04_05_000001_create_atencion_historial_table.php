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
            $table->unsignedBigInteger('estatus_anterior')->nullable();
            $table->unsignedBigInteger('estatus_nuevo');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->foreign('atencion_id')->references('id')->on('atenciones');
            $table->foreign('estatus_anterior')->references('id')->on('estatus');
            $table->foreign('estatus_nuevo')->references('id')->on('estatus');
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atencion_historial');
    }
};
