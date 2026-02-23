<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_a_proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_servicio')->constrained('servicios');
            $table->decimal('monto', 12, 2);
            $table->string('referencia');
            $table->date('fecha_pago');
            $table->foreignId('id_metodo_pago')->constrained('metodos_pago');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_a_proveedores');
    }
};
// Descripcion: Crea la tabla pagos_a_proveedores.
