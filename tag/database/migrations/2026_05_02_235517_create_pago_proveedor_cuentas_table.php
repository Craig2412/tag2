<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_proveedor_cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pago_proveedor')->constrained('pagos_a_proveedores');
            $table->foreignId('id_cuenta_por_pagar')->constrained('cuentas_por_pagar');
            $table->decimal('monto_asignado', 12, 2);
            $table->timestamps();
            
            // Constraint único para evitar duplicar asignaciones del mismo pago a la misma CxP
            $table->unique(['id_pago_proveedor', 'id_cuenta_por_pagar'], 'pago_cuenta_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_proveedor_cuentas');
    }
};
