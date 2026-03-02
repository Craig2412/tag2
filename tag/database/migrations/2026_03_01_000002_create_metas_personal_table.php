<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_meta')->constrained('metas');
            $table->foreignId('id_personal')->constrained('users');
            $table->timestamps();

            $table->unique(['id_meta', 'id_personal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas_personal');
    }
};
// Descripcion: Crea la tabla metas_personal.
