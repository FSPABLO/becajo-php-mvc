<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Una norma evaluable: ISO/IEC 27002 o COBIT 2019.
 *
 * Un dominio pertenece a una norma, y por la cadena dominio -> proceso ->
 * control la pertenencia llega hasta el último control sin columnas extra.
 * Una auditoría se crea contra una norma y ya no cambia: sus respuestas solo
 * tienen sentido contra ese catálogo.
 */
final class Estandar
{
    /** Norma de las auditorías y del instrumento público anteriores a multinorma. */
    public const ISO = 'ISO27002';

    /** Sí/No/No aplica y madurez en cada control (ISO). */
    public const MODO_CONTROL = 'CONTROL';

    /** Capacidad declarada por objetivo y logro N/P/L/F por práctica (COBIT). */
    public const MODO_OBJETIVO = 'OBJETIVO';

    public function __construct(
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly string $version,
        public readonly string $organismo,
        public readonly int $escalaNiveles,
        public readonly int $orden = 0,
        public readonly string $modoEvaluacion = self::MODO_CONTROL,
    ) {
    }

    public function evaluaPorObjetivo(): bool
    {
        return $this->modoEvaluacion === self::MODO_OBJETIVO;
    }

    /** "COBIT 2019", "ISO/IEC 27002 2022". */
    public function etiqueta(): string
    {
        return trim($this->nombre . ' ' . $this->version);
    }

    /** @param array<string, mixed> $fila */
    public static function desdeArreglo(array $fila): self
    {
        return new self(
            codigo:        (string) ($fila['codigo'] ?? ''),
            nombre:        (string) ($fila['nombre'] ?? ''),
            version:       (string) ($fila['version'] ?? ''),
            organismo:     (string) ($fila['organismo'] ?? ''),
            escalaNiveles: (int) ($fila['escala_niveles'] ?? 6),
            orden:         (int) ($fila['orden'] ?? 0),
            modoEvaluacion: (string) ($fila['modo_evaluacion'] ?? self::MODO_CONTROL),
        );
    }
}
