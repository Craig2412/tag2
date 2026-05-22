<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega los campos que MetaPersonalController valida y persiste
     * pero que no existían en la tabla original:
     * - monto:           Objetivo monetario de la meta personal.
     * - id_temporalidad: FK al catálogo de temporalidades (semanal, mensual, etc.).
     * - fecha_inicio:    Inicio del período de evaluación de la meta.
     * - fecha_fin:       Fin del período de evaluación de la meta.
     */
    public function up(): void
    {
        Schema::table('metas_personal', function (Blueprint $table) {
            // Monto objetivo
            $table->decimal('monto', 12, 2)->after('id_personal')
                ->comment('Monto objetivo de la meta personal');

            // FK a temporalidades
            $table->foreignId('id_temporalidad')
                ->after('monto')
                ->constrained('temporalidades')
                ->comment('Temporalidad de la meta (semanal, mensual, anual)');

            // Rango de fechas del período
            $table->date('fecha_inicio')->after('id_temporalidad')
                ->comment('Inicio del período de evaluación');

            $table->date('fecha_fin')->after('fecha_inicio')
                ->comment('Fin del período de evaluación');
        });
    }

    public function down(): void
    {
        Schema::table('metas_personal', function (Blueprint $table) {
            $table->dropForeign(['id_temporalidad']);
            $table->dropColumn(['monto', 'id_temporalidad', 'fecha_inicio', 'fecha_fin']);
        });
    }
};
