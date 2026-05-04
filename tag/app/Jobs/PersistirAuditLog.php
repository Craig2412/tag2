<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job asíncrono para persistir una entrada de auditoría.
 *
 * Al implementar ShouldQueue, Laravel envía este job al worker de cola
 * en vez de ejecutarlo síncronamente en el request HTTP. Esto evita
 * que la escritura en audit_logs bloquee la respuesta al cliente.
 *
 * El job es `tries=3` y `backoff=5s` por defecto de Laravel.
 */
class PersistirAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        AuditLog::create($this->payload);
    }

    /**
     * Manejar fallo del job — registrar en log del sistema sin lanzar excepción
     * para evitar que errores de auditoría afecten el flujo de negocio.
     */
    public function failed(\Throwable $e): void
    {
        \Illuminate\Support\Facades\Log::error('AuditLog job failed', [
            'payload' => $this->payload,
            'error'   => $e->getMessage(),
        ]);
    }
}
