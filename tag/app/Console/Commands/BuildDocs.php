<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BuildDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:build-docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresca la BD, genera un nuevo token para el admin y regenera la documentación de Scribe.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando proceso de construcción de documentación...');

        // 0. Limpiar cachés iniciales
        $this->warn('--- Paso 0: Limpiando todas las cachés ---');
        $this->call('config:clear');
        $this->call('cache:clear');
        if ($this->getLaravel()->make('files')->exists(base_path('resources/docs'))) {
            $this->warn('Limpiando caché de Scribe...');
            $this->call('scribe:cache:clear');
        }

        // 1. Fresh migrate and seed
        $this->warn('--- Paso 1: Reiniciando Base de Datos y Semillas ---');
        $this->call('migrate:fresh', ['--seed' => true]);

        // 2. Buscar primer admin y generar token
        $this->warn('--- Paso 2: Generando Token de API para Administrador ---');
        $admin = Usuario::role('admin')->first();

        if (!$admin) {
            $this->error('❌ No se encontró un usuario con el rol [admin].');
            return 1;
        }

        // Generamos el token con todas las habilidades (*)
        $token = $admin->createToken('scribe-build-token', ['*'])->plainTextToken;
        $this->info("✅ Token generado: {$token}");

        // 3. Gestionar secretos en .env
        $this->warn('--- Paso 3: Sincronizando variables en .env ---');
        
        // Actualizar token de Scribe (siempre se refresca)
        $this->updateEnvFile('SCRIBE_AUTH_TOKEN', $token);

        // Gestionar secreto interno para Next.js (Solo si no existe)
        if (!env('INTERNAL_CONTRACT_SECRET')) {
            $secret = \Illuminate\Support\Str::random(40);
            $this->updateEnvFile('INTERNAL_CONTRACT_SECRET', $secret);
            $this->info("✅ Nuevo secreto interno generado para Next.js.");
        }

        // 4. Inyectar en la memoria actual para la ejecución de Scribe
        config([
            'scribe.auth.use_value' => $token,
        ]);
        
        // 5. Generar Scribe
        $this->warn('--- Paso 4: Regenerando documentación con Scribe ---');
        $this->call('scribe:generate', ['--force' => true]);

        $this->info('✨ ¡Proceso completado! El token ha sido actualizado y el contrato está securizado.');
        
        return 0;
    }

    /**
     * Actualiza o añade una variable en el archivo .env
     */
    private function updateEnvFile(string $key, string $value): void
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            $this->error('❌ No se encontró el archivo .env');
            return;
        }

        $content = File::get($path);

        if (str_contains($content, "{$key}=")) {
            // Reemplazar valor existente
            $content = preg_replace("/{$key}=.*/", "{$key}={$value}", $content);
        } else {
            // Añadir al final si no existe
            $content .= "\n{$key}={$value}";
        }

        File::put($path, $content);
        $this->info("✅ .env actualizado: {$key}");
    }
}
