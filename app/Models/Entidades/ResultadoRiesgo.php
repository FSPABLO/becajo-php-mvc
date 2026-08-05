<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * La exposición al riesgo de una auditoría en una dimensión (C, I o D).
 *
 * No se construye en PHP a partir de las respuestas: lo calcula y lo persiste
 * pkg_indicadores.calcular_riesgo_auditoria, y aquí solo se lee. Fue un
 * requisito explícito del curso que los indicadores salgan de procedimientos
 * almacenados, así que duplicar la fórmula en PHP sería, además de redundante,
 * una fuente segura de discrepancias entre el informe y la base.
 *
 * promedioMadurez viene normalizado entre 0 y 1 (madurez media / 5).
 */
final class ResultadoRiesgo
{
    public const CONFIDENCIALIDAD = 'CONFIDENCIALIDAD';
    public const INTEGRIDAD = 'INTEGRIDAD';
    public const DISPONIBILIDAD = 'DISPONIBILIDAD';

    public function __construct(
        public readonly string $tipo,
        public readonly ?float $promedioMadurez,
        public readonly ?string $zona,
        public readonly ?string $fechaCalculo = null,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        $promedio = $fila['promedio_madurez'] ?? null;

        return new self(
            tipo:            (string) ($fila['tipo_riesgo'] ?? ''),
            promedioMadurez: $promedio === null ? null : (float) $promedio,
            zona:            isset($fila['zona']) ? (string) $fila['zona'] : null,
            fechaCalculo:    isset($fila['fecha_calculo']) ? (string) $fila['fecha_calculo'] : null,
        );
    }

    /** Nombre corto para las etiquetas del tablero. */
    public function etiqueta(): string
    {
        return match ($this->tipo) {
            self::CONFIDENCIALIDAD => 'Confidencialidad',
            self::INTEGRIDAD       => 'Integridad',
            self::DISPONIBILIDAD   => 'Disponibilidad',
            default                => $this->tipo,
        };
    }

    /**
     * El promedio expresado como porcentaje, para las barras del tablero.
     */
    public function porcentaje(): ?float
    {
        return $this->promedioMadurez === null
            ? null
            : round($this->promedioMadurez * 100, 1);
    }
}
