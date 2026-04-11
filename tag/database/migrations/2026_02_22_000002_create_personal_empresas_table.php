<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_empresas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_personal')->constrained('personal');
            $table->foreignId('id_empresa')->constrained('empresas');
            $table->timestamps();

            $table->unique(['id_personal', 'id_empresa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_empresas');
    }
};
// Descripcion: Crea la tabla personal_empresas.
