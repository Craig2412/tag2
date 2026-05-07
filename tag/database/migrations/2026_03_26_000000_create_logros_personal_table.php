<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logros_personal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_personal')->nullable()->constrained('personal');
            $table->string('tipo_entidad', 30);
            $table->unsignedBigInteger('id_entidad');
            $table->string('estatus_anterior', 50)->nullable()->comment('Slug del estado anterior. Ej: por_aprobar');
            $table->string('estatus_nuevo', 50)->comment('Slug del estado nuevo. Ej: aprobado');
            $table->unsignedBigInteger('tiempo_transcurrido_segundos')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_entidad', 'id_entidad']);
            $table->index('id_personal');

            // Índice compuesto para la consulta de MetaPersonal::progreso_actual:
            // WHERE id_personal = ? AND tipo_entidad = ? AND estatus_nuevo = ? AND created_at BETWEEN ? AND ?
            $table->index(
                ['id_personal', 'tipo_entidad', 'estatus_nuevo', 'created_at'],
                'logros_busqueda_metas_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logros_personal');
    }
};
