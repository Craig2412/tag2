<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_pago');
            $table->decimal('monto_total', 12, 2);
            $table->foreignId('id_metodo_pago')->constrained('metodos_pago');
            $table->string('nro_comprobante');
            $table->foreignId('id_tasa_cambio')->constrained('tasas_cambio');
            $table->foreignId('id_estado_conciliacion')->constrained('estados_conciliacion');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
// Descripcion: Crea la tabla pagos (cabecera) para registrar pagos unificados.
