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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura')->unique();
            $table->foreignId('id_orden_compra')->unique()->constrained('ordenes_compra');
            $table->foreignId('id_cliente')->nullable()->constrained('clientes');

            // Datos fiscales del emisor (congelados al emitir)
            $table->string('emisor_rif')->nullable();
            $table->string('emisor_razon_social')->nullable();
            $table->string('timbrado')->nullable();

            // Totales congelados
            $table->decimal('total_gravable', 12, 2)->default(0);
            $table->decimal('total_exento', 12, 2)->default(0);
            $table->decimal('total_iva', 12, 2)->default(0);
            $table->decimal('total_facturado', 12, 2)->default(0);
            $table->decimal('total_retenciones_cliente', 12, 2)->default(0);
            $table->decimal('total_retenciones_empresa', 12, 2)->default(0);
            $table->decimal('total_a_pagar', 12, 2)->default(0);
            $table->decimal('total_neto_empresa', 12, 2)->default(0);

            $table->string('anio')->default('');     // año de emisión (para el correlativo)
            $table->unsignedInteger('correlativo')->default(0);

            $table->foreignId('usuario_emite_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('fecha_emision')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_factura')->constrained('facturas')->onDelete('cascade');
            $table->foreignId('id_servicio')->nullable()->constrained('servicios')->nullOnDelete();
            $table->string('descripcion_servicio')->nullable();
            $table->decimal('base_gravable', 12, 2)->default(0);
            $table->decimal('monto_no_sujeto', 12, 2)->default(0);
            $table->decimal('iva_porcentaje', 5, 2)->default(0);
            $table->decimal('iva_valor', 12, 2)->default(0);
            $table->decimal('total_servicio', 12, 2)->default(0);
            $table->decimal('total_retenciones_servicio', 12, 2)->default(0);
            $table->decimal('total_a_pagar_servicio', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('factura_retenciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_factura')->constrained('facturas')->onDelete('cascade');
            $table->foreignId('id_factura_detalle')->nullable()->constrained('factura_detalles')->onDelete('cascade');
            $table->string('codigo_concepto')->nullable();
            $table->string('nombre_concepto')->nullable();
            $table->enum('aplica_a', ['cliente', 'empresa'])->default('cliente');
            $table->enum('base_calculo', ['base_gravable', 'valor_iva'])->default('base_gravable');
            $table->decimal('porcentaje', 8, 4)->default(0);
            $table->decimal('monto', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_retenciones');
        Schema::dropIfExists('factura_detalles');
        Schema::dropIfExists('facturas');
    }
};
