<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orden_compra_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_compra_id');
            $table->unsignedBigInteger('estatus_anterior')->nullable();
            $table->unsignedBigInteger('estatus_nuevo');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->foreign('orden_compra_id')->references('id')->on('ordenes_compra');
            $table->foreign('estatus_anterior')->references('id')->on('estatus');
            $table->foreign('estatus_nuevo')->references('id')->on('estatus');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_historial');
    }
};
