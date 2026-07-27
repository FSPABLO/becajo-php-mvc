<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un integrante del equipo de consultoría.
 */
final class Integrante
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $rol,
        public readonly string $iniciales,
        public readonly string $descripcion = '',
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeArreglo(array $fila): self
    {
        $nombre = (string) ($fila['nombre'] ?? '');

        return new self(
            nombre:      $nombre,
            rol:         (string) ($fila['rol'] ?? ''),
            iniciales:   (string) ($fila['iniciales'] ?? self::calcularIniciales($nombre)),
            descripcion: (string) ($fila['descripcion'] ?? ''),
        );
    }

    /** Toma la primera letra del nombre y la del primer apellido. */
    private static function calcularIniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/u', trim($nombre)) ?: [];

        if ($partes === [] || $partes[0] === '') {
            return '??';
        }

        $primera = mb_substr($partes[0], 0, 1, 'UTF-8');
        $segunda = count($partes) > 1 ? mb_substr($partes[1], 0, 1, 'UTF-8') : '';

        return mb_strtoupper($primera . $segunda, 'UTF-8');
    }
}
