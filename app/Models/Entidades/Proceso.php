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
 */
final class Proceso
{
    public function __construct(
        public readonly int $numero,
        public readonly string $dominio,
        public readonly string $nombre,
        public readonly string $ancla,
        /** Posición de presentación, distinta del número de catálogo. */
        public readonly int $orden = 0,
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
        );
    }
}
