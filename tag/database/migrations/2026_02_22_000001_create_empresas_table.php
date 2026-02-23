<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('razon_comercial');
            $table->string('rif')->unique();
            $table->string('numero_telefono')->nullable();
            $table->string('correo_electronico')->nullable();
            $table->string('direccion')->nullable();
            $table->foreignId('id_tipo_contribuyente')->constrained('tipos_contribuyentes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
// Descripcion: Crea la tabla empresas.
