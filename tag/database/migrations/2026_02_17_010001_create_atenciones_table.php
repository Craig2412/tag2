<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('users');
            $table->foreignId('id_personal')->constrained('users');
            $table->foreignId('id_origen_atencion')->constrained('origenes');
            $table->string('asunto');
            $table->text('notas_adicionales')->nullable();
            $table->foreignId('estatus')->constrained('estatus');
            $table->boolean('borrado_logico')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atenciones');
    }
};
