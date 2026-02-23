<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre')->nullable()->after('id');
            $table->string('apellido')->nullable()->after('nombre');
            $table->string('cedula')->nullable()->after('apellido');
            $table->string('telefono')->nullable()->after('cedula');
            $table->decimal('porcentaje_comision', 5, 2)->nullable()->after('telefono');
            $table->foreignId('id_tipo_contribuyente')
                ->nullable()
                ->after('porcentaje_comision')
                ->constrained('tipos_contribuyentes');
            $table->foreignId('id_rol')->nullable()->after('id_tipo_contribuyente')->constrained('roles')->nullOnDelete();
            $table->foreignId('id_estatus')->nullable()->after('id_rol')->constrained('estatus')->nullOnDelete();
            $table->string('correo_institucional')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_contribuyente']);
            $table->dropForeign(['id_rol']);
            $table->dropForeign(['id_estatus']);
            $table->dropColumn([
                'nombre',
                'apellido',
                'cedula',
                'telefono',
                'porcentaje_comision',
                'id_tipo_contribuyente',
                'id_rol',
                'id_estatus',
                'correo_institucional',
            ]);
        });
    }
};
// Descripcion: Agrega campos de perfil y relaciones a users.
