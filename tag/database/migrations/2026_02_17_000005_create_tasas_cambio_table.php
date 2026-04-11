<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas_cambio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tasa')->constrained('tasas')->cascadeOnDelete(); // Apunta al catálogo (Ej: "USD_BCV")
            $table->decimal('valor_cambio', 12, 4); // El multiplicador numérico del día
            $table->date('fecha');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas_cambio');
    }
};
// Descripcion: Crea la tabla tasas_cambio.
