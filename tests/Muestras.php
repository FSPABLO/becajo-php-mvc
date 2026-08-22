<?php

declare(strict_types=1);

namespace Pruebas;

use InvalidArgumentException;

/**
 * Acceso a las muestras crudas de ejemplo, en un solo sitio.
 *
 * Existe para no repetir en cada prueba la carga del archivo, sus claves y la
 * ruta a la raíz del proyecto. Un `require` suelto que falla lanza un `Error`
 * genérico, y una prueba con `expectException` lo atrapa como si fuera lo que
 * estaba probando: el fallo real queda escondido detrás de un mensaje sobre
 * otra cosa. Aquí se comprueba y se explica.
 *
 * Las claves de las muestras van como constantes por la misma razón: una
 * errata en un literal suelto da «Undefined array key» en tiempo de ejecución,
 * y con constante no compila.
 *
 * No es una clase de producción. Vive en `tests/` y nadie fuera de ahí la usa.
 */
final class Muestras
{
    /** Único sitio donde vive el nombre del archivo. */
    private const ARCHIVO = 'monitor-muestras.php';

    public const COMPLETA = 'completa';
    public const CONTEXTO_RAIZ_CAIDO = 'contexto-raiz-caido';
    public const FALLIDA = 'fallida';

    /** @return array<string, mixed> */
    public static function cruda(string $nombre): array
    {
        $muestras = self::todas();

        if (!isset($muestras[$nombre])) {
            throw new InvalidArgumentException("No existe la muestra de ejemplo: {$nombre}");
        }

        return $muestras[$nombre];
    }

    /** @return array<string, array<string, mixed>> */
    public static function todas(): array
    {
        $ruta = self::raiz() . '/config/' . self::ARCHIVO;

        if (!is_file($ruta)) {
            throw new InvalidArgumentException(
                "No se encontró el archivo de muestras de ejemplo en {$ruta}. "
                . 'Si se renombró, actualícese Pruebas\Muestras::ARCHIVO.'
            );
        }

        /** @var array<string, array<string, mixed>> $muestras */
        $muestras = require $ruta;

        return $muestras;
    }

    public static function raiz(): string
    {
        return dirname(__DIR__);
    }
}
