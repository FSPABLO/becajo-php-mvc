<?php

declare(strict_types=1);

namespace App\Models\Calculo;

/**
 * Línea base sobre ventana móvil
 *
 * Compara la salud de una métrica contra su propio comportamiento habitual en
 * el mismo tramo horario. Es lo que cubre el «comportamiento anómalo» de
 * A.8.16: sin esto solo se cumple el umbral fijo, que es otra cosa.
 */
final class LineaBase
{
    /**
     * @param int $minimoMuestras Bajo esta cantidad la ventana no dice nada.
     * @param float $sigmas Desviaciones que separan lo raro de lo anómalo.
     */
    public function __construct(
        private readonly int $minimoMuestras = 8,
        private readonly float $sigmas = 3.0,
    ) {
    }

    /**
     * Devuelve null cuando la ventana no alcanza: con cuatro muestras
     * cualquier desviación es ruido con aspecto de hallazgo.
     *
     * Solo señala el deterioro. Una salud muy por encima de lo habitual es
     * buena noticia, no una anomalía que atender.
     *
     * @param list<float> $ventana Valores normalizados del mismo tramo horario.
     * @return array{media: float, desviacion: float, z: float, anomala: bool}|null
     */
    public function evaluar(array $ventana, float $salud): ?array
    {
        if (count($ventana) < $this->minimoMuestras) {
            return null;
        }

        $media = array_sum($ventana) / count($ventana);
        $varianza = 0.0;

        foreach ($ventana as $valor) {
            $varianza += ($valor - $media) ** 2;
        }

        $desviacion = sqrt($varianza / count($ventana));

        if ($desviacion <= 0.0) {
            return [
                'media'      => round($media, 1),
                'desviacion' => 0.0,
                'z'          => 0.0,
                'anomala'    => false,
            ];
        }

        $z = ($salud - $media) / $desviacion;

        return [
            'media'      => round($media, 1),
            'desviacion' => round($desviacion, 2),
            'z'          => round($z, 2),
            'anomala'    => $z <= -$this->sigmas,
        ];
    }
}
