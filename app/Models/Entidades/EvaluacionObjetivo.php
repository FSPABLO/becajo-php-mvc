<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Capacidad que el auditor declara para un objetivo COBIT en una auditoría.
 *
 * Es juicio profesional, como la madurez en ISO: las prácticas calificadas
 * N/P/L/F dentro del objetivo son la evidencia, no la fórmula.
 */
final class EvaluacionObjetivo
{
    public const JUSTIFICACION_MAXIMA = 2000;

    public function __construct(
        public readonly int $idAuditoria,
        public readonly int $numeroProceso,
        public readonly int $capacidad,
        public readonly string $justificacion,
        public readonly ?string $fechaActualizacion = null,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idAuditoria:        (int) ($fila['id_auditoria'] ?? 0),
            numeroProceso:      (int) ($fila['numero_proceso'] ?? 0),
            capacidad:          (int) ($fila['capacidad'] ?? 0),
            justificacion:      (string) ($fila['justificacion'] ?? ''),
            fechaActualizacion: isset($fila['fecha_actualizacion'])
                ? (string) $fila['fecha_actualizacion']
                : null,
        );
    }
}
