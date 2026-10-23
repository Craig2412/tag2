<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tasas')
            ->where('codigo', 'BINANCE')
            ->update(['nombre' => 'Binance P2P / Paralelo']);
    }

    public function down(): void
    {
        DB::table('tasas')
            ->where('codigo', 'BINANCE')
            ->update(['nombre' => 'Binance P2P']);
    }
};
// Descripcion: Aclara que la tasa BINANCE representa el dolar paralelo.
