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
        public readonly string $foto = '',
        // Referencia de la auditoría a la que corresponde el testimonio. Va en
        // oro y en mono porque es eso, una referencia; si está vacía, la ficha
        // no dibuja la línea.
        public readonly string $referencia = '',
        // Con cuál abre el carrusel. Es una decisión de contenido —cuál se
        // quiere enseñar primero—, no de maquetación, así que se marca aquí y
        // no con un índice escrito en la vista. Si hay varios marcados manda
        // el primero; si no hay ninguno, abre con el primero de la lista.
        public readonly bool $destacado = false,
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
            foto:        (string) ($fila['foto'] ?? ''),
            referencia:  (string) ($fila['referencia'] ?? ''),
            destacado:   (bool) ($fila['destacado'] ?? false),
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

    /**
     * La fila completa de cinco estrellas.
     *
     * La vista la imprime DOS veces —una apagada de fondo y otra encendida
     * recortada a porcentaje()— en vez de contar estrellas llenas y vacías.
     * Contarlas obligaría a redondear, y entonces un 4,0 y un 4,5 dibujarían
     * exactamente la misma fila; recortando, el medio punto se ve.
     */
    public function estrellas(): string
    {
        return str_repeat('★', self::MAXIMO);
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
