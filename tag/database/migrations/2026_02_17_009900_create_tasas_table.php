<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tasas')) {
            Schema::create('tasas', function (Blueprint $table) {
                $table->id();
                $table->string('tasa')->unique();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas');
    }
};
// Descripcion: Crea la tabla tasas.
