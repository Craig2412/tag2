<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_contribuyentes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_contribuyente')->unique();
            $table->decimal('porcentaje_iva', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_contribuyentes');
    }
};
// Descripcion: Crea la tabla tipos_contribuyentes.
