<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuraciones_sistema', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('dias_vencimiento')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones_sistema');
    }
};
// Descripcion: Crea la tabla configuraciones_sistema para parametros generales.
