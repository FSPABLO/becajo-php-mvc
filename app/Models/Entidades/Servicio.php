<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un servicio de la consultora.
 *
 * Es una entidad: un objeto que representa un concepto del negocio. Usar una
 * clase en lugar de un arreglo suelto da dos ventajas concretas:
 *
 *  1. El editor autocompleta $servicio->titulo y avisa si escribes $servicio->titlo.
 *  2. Si mañana la tabla SERVICIOS de Oracle cambia de columnas, el ajuste se
 *     hace en desdeArreglo() y no en las seis vistas que usan el dato.
 */
final class Servicio
{
    public function __construct(
        public readonly string $icono,
        public readonly string $titulo,
        public readonly string $texto,
    ) {
    }

    /**
     * Construye la entidad a partir de un registro (arreglo o fila de la base).
     *
     * @param array<string, mixed> $fila
     */
    public static function desdeArreglo(array $fila): self
    {
        return new self(
            icono:  (string) ($fila['icono'] ?? 'check'),
            titulo: (string) ($fila['titulo'] ?? ''),
            texto:  (string) ($fila['texto'] ?? ''),
        );
    }
}
