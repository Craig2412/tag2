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
            EstadoConciliacionSeeder::class,
            EstadoAtencionSeeder::class,
            EstadoCotizacionSeeder::class,
            EstadoOrdenCompraSeeder::class,
            TiposContribuyentesSeeder::class, // Necesario para Clientes y Proveedores
            EntidadBancariaSeeder::class,
            RoleSeeder::class, // Crea los usuarios base (name, email, pass)
            PersonalSeeder::class, // Cruza usuarios -> Personal
            UsuariosDepartamentosSeeder::class, // Usuarios de los 4 departamentos + Personal
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
            ProveedoresAliadosSeeder::class, // 307 aliados del Maestro de Alianzas tAG
            CuentasProveedoresSeeder::class,
            TipoServicioSeeder::class,
            ProveedorTipoServicioSeeder::class,
            TasasSeeder::class,
            TasasCambioSeeder::class,
            OrigenesSeeder::class,
            CatalogosSeeder::class,
            // AtencionesSeeder::class,
            // CotizacionesSeeder::class,
            ServiciosSeeder::class,
            // OrdenesComprasSeeder::class,
            MetodosPagoSeeder::class,
            MetodoPagoEntidadBancariaSeeder::class,
            PagosSeeder::class,
            PagosProveedoresSeeder::class,
            LogrosPersonalSeeder::class,
            ConceptosFiscalesSeeder::class, // Conceptos de impuestos/retenciones configurables
        ]);

        // Invalidar caché de catálogos para que se refresquen con los nuevos datos
        \Illuminate\Support\Facades\Cache::flush();
    }
}
