<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_servicio', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_servicio');
            $table->decimal('iva_defecto', 5, 2)->nullable();
            $table->foreignId('id_proveedor')->constrained('proveedores');
            $table->boolean('borrado_logico')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_servicio');
    }
};
// Descripcion: Crea la tabla tipo_servicio.
