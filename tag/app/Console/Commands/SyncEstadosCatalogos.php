<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Atencion;
use App\Models\OrdenCompra;
use App\Services\EstadoFaseService;

class SyncEstadosCatalogos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sincronizar:catalogos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza las fases comerciales y estados financieros de todos los registros históricos con los nuevos catálogos dinámicos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de catálogo...');

        $atenciones = Atencion::all();
        $this->withProgressBar($atenciones, function ($atencion) {
            EstadoFaseService::sincronizarFaseAtencion($atencion);
        });
        $this->newLine();
        $this->info('Atenciones sincronizadas: ' . $atenciones->count());

        $ordenes = OrdenCompra::all();
        $this->withProgressBar($ordenes, function ($orden) {
            EstadoFaseService::sincronizarEstadoFinanciero($orden);
        });
        $this->newLine();
        $this->info('Órdenes de compra sincronizadas: ' . $ordenes->count());

        $this->info('Sincronización completada exitosamente.');
    }
}
