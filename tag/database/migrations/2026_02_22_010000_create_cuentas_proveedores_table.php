<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_proveedor')->constrained('proveedores');
            $table->string('numero_cuenta');
            $table->string('entidad_financiera');
            $table->string('tipo_cuenta');
            $table->string('moneda');
            $table->foreignId('id_tipo_contribuyente')->constrained('tipos_contribuyentes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_proveedores');
    }
};
// Descripcion: Crea la tabla cuentas_proveedores.
