<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Ej: Cuota de Ventas Mensual, Prospectos Semanales');
            $table->string('tipo_entidad')->comment('atencion, cotizacion o orden_compra');
            $table->foreignId('id_estatus_objetivo')->constrained('estatus')->comment('El estatus que marca el hito logrado');
            $table->boolean('es_monetario')->default(false)->comment('Si es true, sumamos montos; si es false, contamos cantidad');
            $table->decimal('valor_objetivo', 15, 2);
            $table->foreignId('id_temporalidad')->constrained('temporalidades');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metas');
    }
};
// Descripcion: Crea la tabla metas.
