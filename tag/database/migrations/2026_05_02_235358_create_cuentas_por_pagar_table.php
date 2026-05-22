<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_orden_compra')->constrained('ordenes_compra');
            $table->foreignId('id_proveedor')->constrained('proveedores');
            $table->decimal('monto_total', 12, 2);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->foreignId('id_estado_financiero')->default(1)->constrained('estados_financieros'); // pendiente, parcial, pagado
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
    }
};
