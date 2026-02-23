<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atenciones_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_atencion')->constrained('atenciones');
            $table->foreignId('id_personal')->constrained('users');
            $table->timestamps();

            $table->unique(['id_atencion', 'id_personal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atenciones_personal');
    }
};
// Descripcion: Crea la tabla atenciones_personal.
