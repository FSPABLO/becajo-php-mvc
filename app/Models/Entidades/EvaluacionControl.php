<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * La evaluación de UN control dentro de UNA auditoría.
 *
 * Es la fila que el auditor llena en la plantilla en pantalla: responde la
 * pregunta (SI / NO / NA), califica la madurez, marca a qué dimensiones del
 * riesgo afecta y anota el hallazgo y la recomendación.
 *
 * Casi todo es opcional a propósito. El enunciado exige poder guardar una
 * auditoría a medias y continuarla después, así que una evaluación recién
 * creada tiene el estado en null (= "todavía sin evaluar"), que es distinto de
 * 'NA' (= "evaluado, y no aplica a esta organización"). Confundir ambos casos
 * falsearía el porcentaje de cumplimiento del informe final.
 */
final class EvaluacionControl
{
    public const SI = 'SI';
    public const NO = 'NO';
    public const NO_APLICA = 'NA';

    public function __construct(
        public readonly int $idAuditoria,
        public readonly string $codigoControl,
        public readonly ?string $estado = null,
        public readonly ?int $madurez = null,
        public readonly ?string $criterio = null,
        public readonly bool $afectaConfidencialidad = false,
        public readonly bool $afectaIntegridad = false,
        public readonly bool $afectaDisponibilidad = false,
        public readonly ?int $impacto = null,
        public readonly ?int $probabilidad = null,
        public readonly ?float $nivelRiesgo = null,
        public readonly ?string $hallazgo = null,
        public readonly ?string $recomendacion = null,
        public readonly ?string $preguntaPersonalizada = null,
        public readonly int $id = 0,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idAuditoria:             (int) ($fila['id_auditoria'] ?? 0),
            codigoControl:           (string) ($fila['codigo_control'] ?? ''),
            estado:                  self::textoONulo($fila['estado'] ?? null),
            madurez:                 self::enteroONulo($fila['madurez'] ?? null),
            criterio:                self::textoONulo($fila['criterio'] ?? null),
            afectaConfidencialidad:  (int) ($fila['afecta_confidencialidad'] ?? 0) === 1,
            afectaIntegridad:        (int) ($fila['afecta_integridad'] ?? 0) === 1,
            afectaDisponibilidad:    (int) ($fila['afecta_disponibilidad'] ?? 0) === 1,
            impacto:                 self::enteroONulo($fila['impacto'] ?? null),
            probabilidad:            self::enteroONulo($fila['probabilidad'] ?? null),
            nivelRiesgo:             isset($fila['nivel_riesgo']) ? (float) $fila['nivel_riesgo'] : null,
            hallazgo:                self::textoONulo($fila['hallazgo'] ?? null),
            recomendacion:           self::textoONulo($fila['recomendacion'] ?? null),
            preguntaPersonalizada:   self::textoONulo($fila['pregunta_personalizada'] ?? null),
            id:                      (int) ($fila['id_evaluacion_control'] ?? 0),
        );
    }

    /** ¿El auditor ya se pronunció sobre este control? */
    public function estaEvaluado(): bool
    {
        return $this->estado !== null;
    }

    /**
     * Nivel de riesgo según la metodología acordada en la clase del 27/7:
     * el promedio entre el impacto y la probabilidad, ambos de 1 a 5.
     *
     * Se calcula aquí y no en la base porque el auditor debe verlo actualizarse
     * en la pantalla antes de guardar. El valor guardado en
     * evaluacion_control.nivel_riesgo debe coincidir con este.
     */
    public function nivelRiesgoCalculado(): ?float
    {
        if ($this->impacto === null || $this->probabilidad === null) {
            return null;
        }

        return round(($this->impacto + $this->probabilidad) / 2, 2);
    }

    /** Las dimensiones marcadas, en el formato 'CID' que usa sp_mayor_riesgo. */
    public function dimensiones(): string
    {
        return ($this->afectaConfidencialidad ? 'C' : '')
             . ($this->afectaIntegridad ? 'I' : '')
             . ($this->afectaDisponibilidad ? 'D' : '');
    }

    private static function textoONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }

    private static function enteroONulo(mixed $valor): ?int
    {
        return ($valor === null || $valor === '') ? null : (int) $valor;
    }
}
