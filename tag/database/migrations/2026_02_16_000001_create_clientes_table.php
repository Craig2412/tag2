<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete(); // Opcional, un cliente no requiere usuario para ser cotizado
            $table->string('correo_contacto')->nullable(); // Correo de facturacion/comunicacion
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('cedula')->nullable();
            $table->string('telefono')->nullable();
            $table->foreignId('id_tipo_contribuyente')->nullable(); // Para la factura
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
