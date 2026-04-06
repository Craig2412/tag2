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
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Opcional, un cliente no requiere usuario para ser cotizado
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('cedula')->nullable();
            $table->string('telefono')->nullable();
            $table->foreignId('id_tipo_contribuyente')->nullable(); // Para la factura
            $table->foreignId('id_estatus')->nullable(); // Estatus del registro
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
