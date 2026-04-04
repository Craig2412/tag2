<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('id_entidad_bancaria')->after('id_tasa_cambio')->constrained('entidades_bancarias');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['id_entidad_bancaria']);
            $table->dropColumn('id_entidad_bancaria');
        });
    }
};
// Descripcion: Agrega la columna id_entidad_bancaria a la tabla pagos y la relaciona con entidades_bancarias.
