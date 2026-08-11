<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * El plazo de corrección de un hallazgo puntual (una fila de EvaluacionControl),
 * con re-auditoría programable al vencimiento — el ciclo de mejora continua
 * que piden ISO 9001 §8.5.2 e ISO/IEC 27001 (cláusula 10, Mejora): detectar
 * un hallazgo no basta, hay que darle seguimiento hasta confirmar que se
 * corrigió.
 *
 * El estado 'VENCIDO' nunca se guarda en la base: pkg_indicadores lo calcula
 * al vuelo comparando fecha_limite contra la fecha actual, para no depender
 * de un job que lo actualice. Por eso $estado aquí puede llegar como
 * 'VENCIDO' aunque esa palabra no exista en el CHECK de la columna.
 */
final class Remediacion
{
    public const PENDIENTE = 'PENDIENTE';
    public const EN_PROCESO = 'EN_PROCESO';
    public const CUMPLIDO = 'CUMPLIDO';
    public const VENCIDO = 'VENCIDO';

    public function __construct(
        public readonly int $id,
        public readonly int $idEvaluacionControl,
        public readonly string $fechaLimite,
        public readonly string $estado,
        public readonly ?string $responsable,
        public readonly ?int $idAuditoriaReauditoria,
        /** Llega resuelto desde el JOIN en las consultas de listado. */
        public readonly string $codigoControl = '',
        public readonly string $enunciadoControl = '',
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        $idReauditoria = $fila['id_auditoria_reauditoria'] ?? null;

        return new self(
            id:                      (int) ($fila['id_remediacion'] ?? 0),
            idEvaluacionControl:     (int) ($fila['id_evaluacion_control'] ?? 0),
            fechaLimite:             (string) ($fila['fecha_limite'] ?? ''),
            estado:                  (string) ($fila['estado'] ?? self::PENDIENTE),
            responsable:             isset($fila['responsable']) && $fila['responsable'] !== ''
                ? (string) $fila['responsable']
                : null,
            idAuditoriaReauditoria:  $idReauditoria === null ? null : (int) $idReauditoria,
            codigoControl:           (string) ($fila['codigo_control'] ?? ''),
            enunciadoControl:        (string) ($fila['enunciado'] ?? ''),
        );
    }

    public function estaVencida(): bool
    {
        return $this->estado === self::VENCIDO;
    }

    public function tieneReauditoriaProgramada(): bool
    {
        return $this->idAuditoriaReauditoria !== null;
    }
}
