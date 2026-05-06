<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logros_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_personal')->nullable()->constrained('personal');
            $table->string('tipo_entidad', 30);
            $table->unsignedBigInteger('id_entidad');
            $table->foreignId('id_estatus_anterior')->nullable()->constrained('estatus');
            $table->foreignId('id_estatus_nuevo')->constrained('estatus');
            $table->unsignedBigInteger('tiempo_transcurrido_segundos')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_entidad', 'id_entidad']);
            $table->index('id_personal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logros_personal');
    }
};
