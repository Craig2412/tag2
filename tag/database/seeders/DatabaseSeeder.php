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
            EstatusSeeder::class,
            TiposContribuyentesSeeder::class, // Necesario para Clientes y Proveedores
            EntidadBancariaSeeder::class,
            RoleSeeder::class, // Crea los usuarios base (name, email, pass)
            PersonalSeeder::class, // Cruza usuarios -> Personal
            ClientesSeeder::class, // Cruza usuarios -> Clientes
            TemporalidadesSeeder::class,
            MetasSeeder::class,
            MetasPersonalSeeder::class,
            ConfiguracionesSistemaSeeder::class,
            TiposCotizacionesSeeder::class,
            EmpresasSeeder::class,
            PersonalEmpresasSeeder::class,
            TiposProveedoresSeeder::class,
            ProveedoresSeeder::class,
            CuentasProveedoresSeeder::class,
            TipoServicioSeeder::class,
            TasasSeeder::class,
            TasasCambioSeeder::class,
            OrigenesSeeder::class,
            CatalogosSeeder::class,
            AtencionesSeeder::class,
            CotizacionesSeeder::class,
            ServiciosSeeder::class,
            OrdenesComprasSeeder::class,
            MetodosPagoSeeder::class,
            PagosSeeder::class,
            PagosProveedoresSeeder::class,
            LogrosPersonalSeeder::class,
        ]);
    }
}
