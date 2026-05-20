<?php

namespace App\DTOs;

/**
 * Value object que representa un cambio de estado detectado por los servicios de sincronización.
 * Los callers (listeners/observers) deciden si disparar eventos basados en este DTO.
 */
class CambioEstado
{
    public function __construct(
        public readonly bool $huboCambio,
        public readonly ?int $anterior = null,
        public readonly ?int $nuevo = null,
        public readonly ?string $comentario = null,
    ) {}

    public static function sinCambio(): self
    {
        return new self(false);
    }

    public static function conCambio(int $anterior, int $nuevo, ?string $comentario = null): self
    {
        return new self(true, $anterior, $nuevo, $comentario);
    }
}
