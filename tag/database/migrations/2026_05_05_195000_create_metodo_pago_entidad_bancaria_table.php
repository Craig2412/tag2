<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('metodo_pago_entidad_bancaria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_metodo_pago')->constrained('metodos_pago')->onDelete('cascade');
            $table->foreignId('id_entidad_bancaria')->constrained('entidades_bancarias')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metodo_pago_entidad_bancaria');
    }
};
