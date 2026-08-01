<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un dominio del instrumento: el agrupador de mayor nivel.
 *
 * La clave es la que viaja al navegador en los atributos data- y la que usa el
 * guion para filtrar. Se mantiene corta y sin acentos por esa razón.
 */
final class Dominio
{
    public function __construct(
        public readonly string $clave,
        public readonly string $nombre,
        public readonly string $corto,
        public readonly string $descripcion,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeArreglo(array $fila): self
    {
        $nombre = (string) ($fila['nombre'] ?? '');

        return new self(
            clave:       (string) ($fila['clave'] ?? ''),
            nombre:      $nombre,
            // El nombre corto solo existe para que los siete tabs quepan en una
            // fila; si falta, se cae al nombre completo sin romper nada.
            corto:       (string) ($fila['corto'] ?? $nombre),
            descripcion: (string) ($fila['descripcion'] ?? ''),
        );
    }
}
