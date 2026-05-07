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
            $table->foreignId('id_cliente')->constrained('clientes');
            $table->foreignId('id_personal')->constrained('personal');
            $table->foreignId('id_origen_atencion')->constrained('origenes');
            $table->string('asunto');
            $table->text('notas_adicionales')->nullable();
            $table->foreignId('id_estado_atencion')->constrained('estados_atenciones');
            $table->foreignId('id_etapa_comercial')->default(1)->constrained('etapas_comerciales');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atenciones');
    }
};
// Descripcion: Crea la tabla atenciones.
