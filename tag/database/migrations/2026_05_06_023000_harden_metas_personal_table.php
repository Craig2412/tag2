<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Endurece la tabla metas_personal haciendo que los campos de monto,
     * temporalidad y fechas sean obligatorios (NOT NULL).
     */
    public function up(): void
    {
        // 1. Limpiar datos nulos antes de cambiar a NOT NULL
        // Ponemos valores por defecto para no perder la relación, pero marcándolos como "por completar"
        $primeraTemporalidad = DB::table('temporalidades')->first()?->id ?? 1;

        DB::table('metas_personal')->whereNull('monto')->update(['monto' => 0]);
        DB::table('metas_personal')->whereNull('id_temporalidad')->update(['id_temporalidad' => $primeraTemporalidad]);
        DB::table('metas_personal')->whereNull('fecha_inicio')->update(['fecha_inicio' => now()->startOfMonth()]);
        DB::table('metas_personal')->whereNull('fecha_fin')->update(['fecha_fin' => now()->endOfMonth()]);

        // 2. Aplicar NOT NULL
        Schema::table('metas_personal', function (Blueprint $table) {
            $table->decimal('monto', 12, 2)->nullable(false)->change();
            $table->foreignId('id_temporalidad')->nullable(false)->change();
            $table->date('fecha_inicio')->nullable(false)->change();
            $table->date('fecha_fin')->nullable(false)->change();
        });
    }

    /**
     * Revierte los cambios volviendo a permitir nulos.
     */
    public function down(): void
    {
        Schema::table('metas_personal', function (Blueprint $table) {
            $table->decimal('monto', 12, 2)->nullable()->change();
            $table->foreignId('id_temporalidad')->nullable()->change();
            $table->date('fecha_inicio')->nullable()->change();
            $table->date('fecha_fin')->nullable()->change();
        });
    }
};
