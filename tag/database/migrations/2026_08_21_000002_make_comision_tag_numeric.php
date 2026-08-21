<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Convierte comision_tag de string a decimal (fracción de comisión 0.0 - 1.0).
     * Los valores que no son números (neta, convenio, vacío, etc.) pasan a 0.
     */
    public function up(): void
    {
        // Normalizar los valores no numéricos a 0 antes del cambio de tipo
        DB::statement("UPDATE proveedores SET comision_tag = '0' WHERE comision_tag IS NULL OR comision_tag REGEXP '[^0-9.]'");

        DB::statement('ALTER TABLE proveedores MODIFY comision_tag DECIMAL(8,4) NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE proveedores MODIFY comision_tag VARCHAR(255) NULL');
    }
};
