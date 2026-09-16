<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Agrupación de alertas concurrentes de una misma instancia (§6.1 del plan).
 *
 * `idAlertaCausa` es la alerta que la tabla `precedencia` señala como causa
 * probable dentro del episodio, cuando la hay — conocimiento declarado, no
 * correlación calculada.
 */
final class Episodio
{
    public function __construct(
        public readonly int $id,
        public readonly string $claveInstancia,
        public readonly ?int $idAlertaCausa,
        public readonly string $abiertoEn,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id:              (int) ($fila['id_episodio'] ?? 0),
            claveInstancia:  (string) ($fila['clave_instancia'] ?? ''),
            idAlertaCausa:   isset($fila['id_alerta_causa']) ? (int) $fila['id_alerta_causa'] : null,
            abiertoEn:       (string) ($fila['abierto_en'] ?? ''),
        );
    }
}
