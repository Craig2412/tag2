<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_sistema', function (Blueprint $table) {
            $table->boolean('tasas_sync_enabled')->default(true)->after('dias_vencimiento');
            $table->unsignedInteger('tasas_sync_interval_minutes')->default(1440)->after('tasas_sync_enabled');
            $table->timestamp('tasas_sync_last_run_at')->nullable()->after('tasas_sync_interval_minutes');
            $table->string('tasas_sync_last_status', 20)->nullable()->after('tasas_sync_last_run_at');
            $table->string('tasas_sync_last_message')->nullable()->after('tasas_sync_last_status');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_sistema', function (Blueprint $table) {
            $table->dropColumn([
                'tasas_sync_enabled',
                'tasas_sync_interval_minutes',
                'tasas_sync_last_run_at',
                'tasas_sync_last_status',
                'tasas_sync_last_message',
            ]);
        });
    }
};
// Descripcion: Agrega la configuracion de sincronizacion automatica de tasas de cambio.
