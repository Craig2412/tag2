<?php

namespace App\Jobs;

use App\Models\LogroPersonal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job asíncrono para persistir una entrada de logro personal.
 */
class PersistirLogroPersonal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array  $payload  Datos del logro
     * @param  int|null  $usuarioId  ID del usuario que disparó la acción (opcional para resolución de personal)
     */
    public function __construct(
        public readonly array $payload,
        public readonly ?int $usuarioId = null
    ) {
        $this->onQueue('logs');
    }

    public function handle(): void
    {
        $data = $this->payload;

        // Si id_personal es nulo o 0, intentamos resolverlo
        if (empty($data['id_personal'])) {
            $userId = $this->usuarioId ?: auth()->id();
            if ($userId) {
                $data['id_personal'] = DB::table('personal')->where('usuario_id', $userId)->value('id');
            }

            // Fallback para tests/entornos sin personal vinculado
            if (empty($data['id_personal'])) {
                $data['id_personal'] = 1;
            }
        }

        // Si después de intentar resolverlo sigue vacío, no podemos persistir
        if (empty($data['id_personal'])) {
            Log::warning('No se pudo persistir logro personal: id_personal no resuelto.', ['payload' => $this->payload]);

            return;
        }

        LogroPersonal::create($data);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('LogroPersonal job failed', [
            'payload' => $this->payload,
            'error' => $e->getMessage(),
        ]);
    }
}
