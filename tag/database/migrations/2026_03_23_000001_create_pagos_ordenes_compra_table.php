<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pago')->constrained('pagos');
            $table->foreignId('id_orden_compra')->constrained('ordenes_compra');
            $table->decimal('monto_asignado', 12, 2);
            $table->decimal('monto_pagado', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['id_pago', 'id_orden_compra']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_ordenes_compra');
    }
};
