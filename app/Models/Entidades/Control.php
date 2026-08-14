<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un control del instrumento de consultoría.
 *
 * El control y su pregunta de auditoría viven en la MISMA entidad a propósito.
 * La pestaña Instrumento y la pestaña Cuestionario deben operar sobre el mismo
 * identificador; si fueran dos listas separadas, bastaría un descuido al
 * redactar para que C-042 preguntara por otra cosa que la que evalúa C-042.
 */
final class Control
{
    /** Importancia relativa del control (COBIT 4.1, Apéndice II). */
    public const PESO_ALTA = 'ALTA';
    public const PESO_MEDIA = 'MEDIA';
    public const PESO_BAJA = 'BAJA';

    public function __construct(
        public readonly string $id,
        public readonly int $proceso,
        public readonly string $iso,
        public readonly string $enunciado,
        public readonly string $evidencia,
        public readonly string $pregunta,
        public readonly string $peso = self::PESO_MEDIA,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeArreglo(array $fila): self
    {
        return new self(
            id:        (string) ($fila['id'] ?? ''),
            proceso:   (int) ($fila['proceso'] ?? 0),
            iso:       (string) ($fila['iso'] ?? ''),
            enunciado: (string) ($fila['enunciado'] ?? ''),
            evidencia: (string) ($fila['evidencia'] ?? ''),
            pregunta:  (string) ($fila['pregunta'] ?? ''),
            peso:      (string) ($fila['peso'] ?? self::PESO_MEDIA),
        );
    }
}
