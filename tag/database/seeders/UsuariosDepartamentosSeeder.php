<?php

namespace Database\Seeders;

use App\Models\Personal;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea los usuarios de los 4 departamentos de TAG.
 *
 * - Correo: <nombre>@tag.com (primer nombre, en minúsculas).
 * - Clave por defecto: "123456789".
 * - Privilegios: se mapean a roles Spatie existentes (admin, personal).
 *   Los privilegios que no existen en el sistema se ignoran y se reportan
 *   al final de la ejecución.
 * - Se crea un registro de Personal por cada usuario con su departamento.
 */
class UsuariosDepartamentosSeeder extends Seeder
{
    /**
     * Mapa de "Privilegios" (etiquetas del negocio) → rol Spatie existente.
     *
     * Roles reales en el sistema: admin, user, personal, cliente.
     * null = privilegio no soportado → se ignora y se informa.
     */
    private const MAPA_PRIVILEGIO_ROLE = [
        'ADMIN'             => 'admin',
        // El resto del personal operativo se asigna al rol 'personal':
        'COMERCIAL'          => 'personal',
        'MERCADEO'           => 'personal',
        'ADMINISTRATIVO'     => 'personal',
        'EMISOR RETENCIONES' => null, // no mapea a ningún rol existente
        'FACTURADOR'         => 'personal',
        'ASESOR'             => 'personal',
    ];

    public function run(): void
    {
        $lista = $this->usuarios();
        $ignorados = [];

        foreach ($lista as $item) {
            $rol = self::MAPA_PRIVILEGIO_ROLE[$item['privilegio']] ?? null;

            if ($rol === null) {
                $ignorados[] = "{$item['nombre_completo']} ({$item['privilegio']})";
                // Aun así creamos el usuario sin rol.
            }

            [$nombre, $apellido] = $this->separarNombre($item['nombre_completo']);

            $usuario = Usuario::firstOrCreate(
                ['correo' => $item['correo']],
                [
                    'nombre_usuario' => $item['nombre_completo'],
                    'clave' => Hash::make('123456789'),
                    'esta_activo' => true,
                ]
            );

            if ($rol !== null) {
                $usuario->syncRoles([$rol]);
            }

            // Crear ficha de Personal con el departamento correspondiente
            Personal::firstOrCreate(
                ['usuario_id' => $usuario->id],
                [
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'cedula' => null,
                    'telefono' => null,
                    'correo_institucional' => $item['correo'],
                    'departamento' => $item['departamento'],
                    'cargo' => $item['cargo'],
                    'porcentaje_comision' => 5.00,
                ]
            );
        }

        if (! empty($ignorados)) {
            $this->command->warn('⚠️  Privilegios ignorados (no existen en el sistema):');
            foreach ($ignorados as $ignorado) {
                $this->command->line('   - ' . $ignorado);
            }
        }
    }

    /**
     * Separa un nombre completo en nombre y apellido.
     * "FABIAN BERRIO" → ['FABIAN', 'BERRIO'].
     */
    private function separarNombre(string $nombreCompleto): array
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto));
        $nombre = array_shift($partes);
        $apellido = implode(' ', $partes);

        return [$nombre, $apellido !== '' ? $apellido : null];
    }

    /**
     * Lista completa de usuarios por departamento.
     */
    private function usuarios(): array
    {
        return [
            // 1. GERENCIA GENERAL
            ['nombre_completo' => 'FABIAN BERRIO',     'correo' => 'fabian@tag.com',    'departamento' => 'GERENCIA GENERAL',   'cargo' => 'VICEPRESIDENTE / GERENTE GENERAL', 'privilegio' => 'ADMIN'],
            ['nombre_completo' => 'DANILO FARIA',      'correo' => 'danilo@tag.com',    'departamento' => 'GERENCIA GENERAL',   'cargo' => 'GERENTE GENERAL 2',                 'privilegio' => 'ADMIN'],

            // 2. COMERCIAL MERCADEO
            ['nombre_completo' => 'ARBY LONDONO',      'correo' => 'arby@tag.com',      'departamento' => 'COMERCIAL MERCADEO', 'cargo' => 'GERENTE COMERCIAL',                 'privilegio' => 'COMERCIAL'],
            ['nombre_completo' => 'ORIANA BASTOS',     'correo' => 'oriana@tag.com',    'departamento' => 'COMERCIAL MERCADEO', 'cargo' => 'ASISTENTE COMERCIAL',               'privilegio' => 'COMERCIAL'],
            ['nombre_completo' => 'NOEL CLARK',        'correo' => 'noel@tag.com',      'departamento' => 'COMERCIAL MERCADEO', 'cargo' => 'GERENTE OFICINA MARGARITA',         'privilegio' => 'COMERCIAL'],
            ['nombre_completo' => 'ROSELYN GONZALEZ',  'correo' => 'roselyn@tag.com',   'departamento' => 'COMERCIAL MERCADEO', 'cargo' => 'COORDINADORA DE MERCADEO',          'privilegio' => 'MERCADEO'],

            // 3. ADMINISTRACION
            ['nombre_completo' => 'BRENDA HERNANDEZ',  'correo' => 'brenda@tag.com',    'departamento' => 'ADMINISTRACION',     'cargo' => 'GERENTE ADMINISTRACION',           'privilegio' => 'ADMINISTRATIVO'],
            ['nombre_completo' => 'LUIS DIAZ',         'correo' => 'luis@tag.com',      'departamento' => 'ADMINISTRACION',     'cargo' => 'ASISTENTE ADMINISTRATIVO',         'privilegio' => 'EMISOR RETENCIONES'],
            ['nombre_completo' => 'MAYRA MARTIN',      'correo' => 'mayra@tag.com',     'departamento' => 'ADMINISTRACION',     'cargo' => 'FACTURADOR',                       'privilegio' => 'FACTURADOR'],
            ['nombre_completo' => 'MARLYN GUAREPE',    'correo' => 'marlyn@tag.com',    'departamento' => 'ADMINISTRACION',     'cargo' => 'ASISTENTE ADMINISTRATIVO',         'privilegio' => 'ADMINISTRATIVO'],
            ['nombre_completo' => 'ALEXANA BETANCOURT','correo' => 'alexana@tag.com',   'departamento' => 'ADMINISTRACION',     'cargo' => 'ASISTENTE ADMINISTRATIVO',         'privilegio' => 'ADMINISTRATIVO'],
            ['nombre_completo' => 'LORIANA AVARIANO',  'correo' => 'loriana@tag.com',   'departamento' => 'ADMINISTRACION',     'cargo' => 'FACTURADOR',                       'privilegio' => 'FACTURADOR'],

            // 4. OPERACIONES
            ['nombre_completo' => 'MANUEL MADERO',     'correo' => 'manuel@tag.com',    'departamento' => 'OPERACIONES',        'cargo' => 'GERENTE OPERACIONES',              'privilegio' => 'ADMIN'],
            ['nombre_completo' => 'MARIA HERNANDEZ',   'correo' => 'maria@tag.com',     'departamento' => 'OPERACIONES',        'cargo' => 'ASESOR DE VIAJES',                 'privilegio' => 'ASESOR'],
            ['nombre_completo' => 'YRAIN CARRILLO',    'correo' => 'yrain@tag.com',     'departamento' => 'OPERACIONES',        'cargo' => 'ASESOR DE VIAJES',                 'privilegio' => 'ASESOR'],
            ['nombre_completo' => 'GINETTE TORRES',    'correo' => 'ginette@tag.com',   'departamento' => 'OPERACIONES',        'cargo' => 'ASESOR DE VIAJES',                 'privilegio' => 'ASESOR'],
            ['nombre_completo' => 'YESIMAR ADRIAN',    'correo' => 'yesimar@tag.com',   'departamento' => 'OPERACIONES',        'cargo' => 'ASESOR DE VIAJES',                 'privilegio' => 'ASESOR'],
            ['nombre_completo' => 'ANDREINA BLANCO',   'correo' => 'andreina@tag.com',  'departamento' => 'OPERACIONES',        'cargo' => 'ASESOR DE VIAJES',                 'privilegio' => 'ASESOR'],
            ['nombre_completo' => 'LEDA BARRADAS',     'correo' => 'leda@tag.com',      'departamento' => 'OPERACIONES',        'cargo' => 'ASESOR DE VIAJES',                 'privilegio' => 'ASESOR'],
        ];
    }
}
