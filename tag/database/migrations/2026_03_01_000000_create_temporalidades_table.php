<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporalidades', function (Blueprint $table) {
            $table->id();
            $table->string('temporalidad')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporalidades');
    }
};
// Descripcion: Crea la tabla temporalidades.
