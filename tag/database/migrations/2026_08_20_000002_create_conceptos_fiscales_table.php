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
        Schema::create('conceptos_fiscales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();          // islr_cliente, retencion_iva_cliente, ...
            $table->string('nombre');                     // ISLR, Alcaldía, Aporte Social...
            $table->enum('tipo_aplicacion', ['retencion', 'impuesto'])->default('retencion');
            $table->enum('aplica_a', ['cliente', 'empresa'])->default('cliente');
            $table->enum('base_calculo', ['base_gravable', 'valor_iva'])->default('base_gravable');
            $table->decimal('porcentaje', 8, 4)->default(0);
            $table->string('excluir_si_contiene')->nullable(); // ej. "boleto" → no aplica el concepto
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conceptos_fiscales');
    }
};
