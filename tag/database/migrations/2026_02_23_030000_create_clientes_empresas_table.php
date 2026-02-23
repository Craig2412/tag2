<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes_empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('users');
            $table->foreignId('id_empresas')->constrained('empresas');
            $table->timestamps();

            $table->unique(['id_cliente', 'id_empresas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes_empresas');
    }
};
// Descripcion: Crea la tabla clientes_empresas.
