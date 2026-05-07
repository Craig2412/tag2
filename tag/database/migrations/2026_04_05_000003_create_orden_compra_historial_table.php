<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orden_compra_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')->constrained('ordenes_compra');
            $table->foreignId('id_estado_anterior')->nullable()->constrained('estados_ordenes_compra');
            $table->foreignId('id_estado_nuevo')->constrained('estados_ordenes_compra');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->text('comentario')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_compra_historial');
    }
};
