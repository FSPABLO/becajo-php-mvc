<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * La opinión de un cliente sobre el servicio recibido.
 *
 * El puntaje se normaliza aquí y no en la vista: la plantilla solo dibuja
 * estrellas, no decide qué es una calificación válida. Así un dato mal escrito
 * en config/contenido.php (o mañana en una tabla de Oracle) se corrige en un
 * solo lugar en vez de reventar la maqueta.
 */
final class Testimonio
{
    /** Estrellas que dibuja la sección. */
    public const MAXIMO = 5;

    public function __construct(
        public readonly string $nombre,
        public readonly float $puntaje,
        public readonly string $descripcion,
        public readonly string $cargo = '',
        public readonly string $iniciales = '',
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeArreglo(array $fila): self
    {
        $nombre = (string) ($fila['nombre'] ?? '');

        return new self(
            nombre:      $nombre,
            puntaje:     self::normalizar((float) ($fila['puntaje'] ?? self::MAXIMO)),
            descripcion: (string) ($fila['descripcion'] ?? ''),
            cargo:       (string) ($fila['cargo'] ?? ''),
            iniciales:   (string) ($fila['iniciales'] ?? mb_strtoupper(mb_substr($nombre, 0, 1, 'UTF-8'), 'UTF-8')),
        );
    }

    /**
     * Porcentaje del ancho de las cinco estrellas que va coloreado.
     *
     * La vista pinta una fila gris y encima recorta una fila dorada a este
     * ancho: es lo que permite dibujar media estrella sin un SVG aparte.
     */
    public function porcentaje(): float
    {
        return $this->puntaje / self::MAXIMO * 100;
    }

    /** Texto de la calificación: '4.5' y no '4.50' ni '5.0'. */
    public function puntajeLegible(): string
    {
        return rtrim(rtrim(number_format($this->puntaje, 1, '.', ''), '0'), '.');
    }

    /** Acota el puntaje al rango 0–5 y lo redondea al medio punto más cercano. */
    private static function normalizar(float $puntaje): float
    {
        $acotado = max(0.0, min((float) self::MAXIMO, $puntaje));

        return round($acotado * 2) / 2;
    }
}
