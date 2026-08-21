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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('ciudad')->nullable()->after('razon_comercial');
            $table->string('cargo_contacto')->nullable()->after('nombre_persona_contacto');
            $table->text('caracteristica')->nullable()->after('cargo_contacto');
            $table->string('comision_tag')->nullable()->after('caracteristica');

            // Permitir correos duplicados (sedes de una misma cadena)
            $table->dropUnique(['correo_empresa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['ciudad', 'cargo_contacto', 'caracteristica', 'comision_tag']);
            $table->unique('correo_empresa');
        });
    }
};
