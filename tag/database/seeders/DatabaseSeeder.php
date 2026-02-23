<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EstatusSeeder::class,
            ConfiguracionesSistemaSeeder::class,
            TiposContribuyentesSeeder::class,
            TiposCotizacionesSeeder::class,
            EmpresasSeeder::class,
            PersonalEmpresasSeeder::class,
            CuentasProveedoresSeeder::class,
            TiposProveedoresSeeder::class,
            ProveedoresSeeder::class,
            TipoServicioSeeder::class,
            TasasCambioSeeder::class,
            ServiciosSeeder::class,
            OrigenesSeeder::class,
            AtencionesSeeder::class,
            AtencionesPersonalSeeder::class,
            CotizacionesSeeder::class,
            ServiciosCotizacionesSeeder::class,
            MetodosPagoSeeder::class,
            PagosSeeder::class,
            PagosProveedoresSeeder::class,
        ]);
    }
}
