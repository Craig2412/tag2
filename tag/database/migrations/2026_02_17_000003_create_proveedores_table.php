<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_empresa');
            $table->string('razon_comercial')->nullable();
            $table->string('rif')->unique();
            $table->string('correo_empresa')->unique();
            $table->string('telefono_empresa')->nullable();
            $table->string('nombre_persona_contacto');
            $table->foreignId('id_tipo_contribuyente')->nullable()->constrained('tipos_contribuyentes'); // Traspaso de la responsabilidad fiscal
            $table->foreignId('tipo_proveedor')->constrained('tipos_proveedores');
            $table->foreignId('estatus')->constrained('estatus');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
// Descripcion: Crea la tabla proveedores.
