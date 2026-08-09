<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un proceso de administración de bases de datos.
 *
 * Agrupa tres controles y pertenece a un dominio. El número NO coincide con el
 * orden de presentación: los procesos 23, 24 y 25 se ubican por afinidad
 * temática, no al final. Se conserva su número original para poder rastrear
 * cada proceso hasta la tabla del marco de referencia.
 *
 * relacionConfidencialidad/Integridad/Disponibilidad usan la notación de
 * COBIT 4.1 (Apéndice II): 'P' cuando el proceso tiene una relación primaria
 * con esa dimensión, 'S' cuando es solo secundaria, null cuando no aplica.
 * Es la relación DECLARADA del proceso en el catálogo — no reemplaza lo que
 * el auditor marca en cada evaluación puntual (afecta_confidencialidad, etc.
 * en EvaluacionControl), que sigue siendo lo que se usa para calcular riesgo.
 */
final class Proceso
{
    public const RELACION_PRIMARIA = 'P';
    public const RELACION_SECUNDARIA = 'S';

    public function __construct(
        public readonly int $numero,
        public readonly string $dominio,
        public readonly string $nombre,
        public readonly string $ancla,
        /** Posición de presentación, distinta del número de catálogo. */
        public readonly int $orden = 0,
        public readonly ?string $relacionConfidencialidad = null,
        public readonly ?string $relacionIntegridad = null,
        public readonly ?string $relacionDisponibilidad = null,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeArreglo(array $fila): self
    {
        return new self(
            numero:  (int) ($fila['numero'] ?? 0),
            dominio: (string) ($fila['dominio'] ?? ''),
            nombre:  (string) ($fila['nombre'] ?? ''),
            ancla:   (string) ($fila['ancla'] ?? ''),
            orden:   (int) ($fila['orden'] ?? 0),
            relacionConfidencialidad: self::textoONulo($fila['relacion_confidencialidad'] ?? null),
            relacionIntegridad:       self::textoONulo($fila['relacion_integridad'] ?? null),
            relacionDisponibilidad:   self::textoONulo($fila['relacion_disponibilidad'] ?? null),
        );
    }

    private static function textoONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }
}
