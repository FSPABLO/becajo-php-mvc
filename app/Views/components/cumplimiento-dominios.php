<?php

declare(strict_types=1);

/**
 * Cumplimiento por dominio, en columnas verticales.
 *
 * UNA sola medida y UN solo eje: la fracción de controles conformes de cada
 * dominio, de 0 a 100 %. Con una sola serie no hay leyenda que poner —el título
 * de la tarjeta la nombra— ni un segundo tono que elegir, así que tampoco hay
 * aquí ninguno de los dos problemas que obligaron a `evolucion-mensual` a
 * distinguir sus series por la FORMA.
 *
 * POR QUÉ COLUMNAS Y NO BARRAS HORIZONTALES
 * -----------------------------------------
 * Lo que se compara son siete magnitudes de la misma naturaleza, sin orden
 * temporal y con nombres cortos: siete columnas se recorren de un vistazo y la
 * altura se compara sola. La versión anterior eran siete barras horizontales
 * apiladas, cada una con su nombre a la izquierda y su cifra a la derecha, que
 * es una tabla dibujada — ocupaba tres veces el alto y no dejaba comparar dos
 * dominios que no fueran vecinos.
 *
 * EL COLOR ES ESTADO, NO IDENTIDAD
 * --------------------------------
 * Cada columna se tiñe con la escala semántica según la zona en la que cae su
 * cumplimiento, con los MISMOS cortes que `fn_zona` de pkg_indicadores. No es
 * un color por dominio: si el dominio mejora, cambia de color, que es
 * exactamente lo que tiene que pasar.
 *
 * Y el estado no viaja SOLO en el color, que es la regla de la casa (§5.5) y
 * también la del sentido común aquí: cada columna lleva su cifra rotulada
 * encima, y las dos líneas de corte —50 % y 80 %— están dibujadas, así que la
 * zona de un dominio se lee por su ALTURA respecto de esas líneas aunque la
 * pantalla esté en gris o quien mire no distinga el verde del ámbar. Las filas
 * por dominio de debajo dicen lo mismo en texto.
 *
 * Sin JavaScript: el desglose de cada columna se consulta con el título
 * emergente nativo, igual que en los otros gráficos del producto.
 *
 * @var \App\Core\Vista $vista
 * @var list<array<string, mixed>> $dominios  Filas de sp_cumplimiento_dominio.
 */

/*
 * Los cortes de zona. Repetidos aquí NO por gusto: son los de `fn_zona` en
 * Scripts/03, y la vista de resultados aplica los mismos a sus pastillas. Si
 * un día se mueven en la base, este es uno de los dos sitios de PHP que hay
 * que mover con ellos — están anotados en los dos.
 */
$zonaDe = static function (mixed $frac): ?string {
    if ($frac === null) {
        return null;
    }

    return match (true) {
        (float) $frac < 0.5 => 'ROJO',
        (float) $frac < 0.8 => 'AMARILLO',
        default             => 'VERDE',
    };
};

$colorZona = [
    'ROJO'     => 'var(--rv-bad)',
    'AMARILLO' => 'var(--rv-warn)',
    'VERDE'    => 'var(--rv-ok)',
];

$porcentaje = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float) $v * 100, 1, ',', '') . ' %';

/*
 * El nombre del dominio partido en dos líneas como mucho. Los nombres reales
 * llegan a 24 caracteres («Memoria y almacenamiento») y en una sola línea se
 * solaparían con los de las columnas vecinas; rotarlos los haría ilegibles, que
 * es peor que partirlos. El corte va por la última palabra que quepa, no por el
 * carácter 13: partir «almacenamien-to» no ayuda a nadie.
 *
 * El ancho por omisión acompaña al del lienzo: si el gráfico vuelve a ocupar la
 * fila entera, sube con él.
 *
 * @return list<string>
 */
$enDosLineas = static function (string $texto, int $ancho = 13): array {
    $palabras = preg_split('/\s+/u', trim($texto)) ?: [$texto];
    $lineas = [''];

    foreach ($palabras as $palabra) {
        $candidata = $lineas[count($lineas) - 1] === ''
            ? $palabra
            : $lineas[count($lineas) - 1] . ' ' . $palabra;

        if (mb_strlen($candidata) <= $ancho || $lineas[count($lineas) - 1] === '') {
            $lineas[count($lineas) - 1] = $candidata;
        } elseif (count($lineas) < 2) {
            $lineas[] = $palabra;
        } else {
            $lineas[1] .= ' ' . $palabra;
        }
    }

    return $lineas;
};

$total = count($dominios);
?>
<?php if ($total === 0): ?>
    <p class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-4 py-8 text-center text-sm text-texto-2">
        <?= e($vista->t('eval.sin_controles_eval')) ?>
    </p>
<?php else: ?>
    <?php
    /*
     * Geometría del lienzo, en un viewBox fijo que el SVG escala solo: el
     * dibujo no depende de medir nada en el navegador y sale igual en el primer
     * pintado, sin guion de por medio.
     *
     * LA PROPORCIÓN ESTÁ CALCULADA PARA UN TERCIO DEL ANCHO de la pantalla de
     * resultados, que es donde vive: comparte fila con la matriz de riesgo y
     * con la exposición. Nació a 760 ocupando la fila entera y hubo que
     * acortarlo al bajarlo a esta columna, por lo mismo que está anotado en los
     * gráficos del monitor: el texto de un SVG escala con el dibujo, y a 760
     * los rótulos de 11 caían a 8 píxeles reales.
     *
     * Si algún día vuelve al ancho completo hay que devolverle el lienzo largo
     * —y volver a subir el corte de línea de los nombres—, o se verá gigante.
     */
    $ancho  = 560;
    $izq    = 40;   // hueco de las etiquetas del eje vertical
    $arriba = 22;   // aire para la cifra rotulada sobre la columna más alta
    $altoPlot = 170;
    $altoPie  = 44; // dos líneas de nombre bajo la línea de cero
    $alto     = $arriba + $altoPlot + $altoPie;

    $anchoPlot = $ancho - $izq - 12;
    $base      = $arriba + $altoPlot;

    $banda  = $anchoPlot / $total;
    $centro = static fn (int $i): float => $izq + $banda * ($i + 0.5);
    $y      = static fn (float $frac): float => $base - $frac * $altoPlot;

    // Ancho contenido: deja aire entre columnas contiguas para que se lean como
    // siete magnitudes y no como una silueta continua. El hueco que sobra a los
    // lados es además donde respiran los nombres largos de dos líneas.
    $anchoBarra = min(40.0, $banda * 0.52);

    /*
     * Columna con el extremo de dato redondeado y la base a escuadra — la misma
     * receta que `evolucion-mensual`: el redondeo marca dónde termina el valor,
     * y una base redondeada despegaría la columna de su propia línea de cero.
     */
    $columna = static function (float $x, float $altura) use ($base, $anchoBarra): string {
        $r  = min(4.0, $anchoBarra / 2, $altura);
        $x0 = $x - $anchoBarra / 2;
        $x1 = $x + $anchoBarra / 2;
        $yTope = $base - $altura;

        return sprintf(
            'M %.2f %.2f L %.2f %.2f Q %.2f %.2f %.2f %.2f L %.2f %.2f Q %.2f %.2f %.2f %.2f L %.2f %.2f Z',
            $x0, $base,
            $x0, $yTope + $r,
            $x0, $yTope, $x0 + $r, $yTope,
            $x1 - $r, $yTope,
            $x1, $yTope, $x1, $yTope + $r,
            $x1, $base,
        );
    };

    /*
     * El resumen para quien no ve el dibujo. No describe la imagen («un gráfico
     * de barras»), que no le sirve de nada: da la lectura — cuántos dominios y
     * cuál es el peor, que es con lo que uno se queda al mirarlo.
     */
    $conDato = array_filter($dominios, static fn (array $f): bool => ($f['cumplimiento'] ?? null) !== null);
    $peor = null;

    foreach ($conDato as $fila) {
        if ($peor === null || (float) $fila['cumplimiento'] < (float) $peor['cumplimiento']) {
            $peor = $fila;
        }
    }

    $resumenLectura = $peor === null
        ? $vista->t('eval.sin_controles_eval')
        : $vista->t(
            'eval.gr_dominios_resumen',
            (string) $total,
            (string) $peor['nombre_dominio'],
            $porcentaje($peor['cumplimiento']),
        );
    ?>
    <div class="font-sans tabular">
        <svg viewBox="0 0 <?= $ancho ?> <?= $alto ?>" width="100%" height="auto"
             role="img" aria-label="<?= e($resumenLectura) ?>">

            <?php
            /*
             * Relieve de las marcas: el neumorfismo del producto es box-shadow,
             * y box-shadow no entra en un SVG. El equivalente es el par de
             * sombras de .rv-extruido —oscura abajo-derecha, realce claro
             * arriba-izquierda— y los valores salen de las mismas ternas --nm-*
             * vía .rv-nm-sombra / .rv-nm-realce (ver «Relieve dentro de un SVG»
             * en rivendel.css). Cambiar la paleta o el nivel de relieve mueve
             * este gráfico con el resto sin tocar el archivo.
             *
             * Un solo filtro para el grupo entero y no uno por columna: mismo
             * dibujo —las columnas no se solapan— con una pasada en vez de siete.
             *
             * color-interpolation-filters="sRGB" no es adorno: por omisión es
             * linearRGB, y ahí la misma opacidad rinde una sombra bastante más
             * pálida que la de las tarjetas de al lado.
             */
            ?>
            <defs>
                <filter id="rv-nm-dominio" filterUnits="userSpaceOnUse"
                        x="0" y="0" width="<?= $ancho ?>" height="<?= $alto ?>"
                        color-interpolation-filters="sRGB">
                    <feDropShadow in="SourceGraphic" dx="2.2" dy="2.2" stdDeviation="2"
                                  class="rv-nm-sombra" result="sombra"></feDropShadow>
                    <feDropShadow in="SourceGraphic" dx="-1.6" dy="-1.6" stdDeviation="1.6"
                                  class="rv-nm-realce" result="realce"></feDropShadow>
                    <feMerge>
                        <feMergeNode in="sombra"></feMergeNode>
                        <feMergeNode in="realce"></feMergeNode>
                    </feMerge>
                </filter>
            </defs>

            <?php
            /*
             * La rejilla son los CORTES DE ZONA, no una escala decorativa cada
             * 25 %. Las líneas de 50 y 80 son justamente donde el cumplimiento
             * cambia de zona, así que dibujarlas explica el color de cada
             * columna por su posición: por eso van a trazos, para que se lean
             * como umbrales y no como una marca más del eje.
             */
            ?>
            <?php foreach ([['v' => 0, 'corte' => false], ['v' => 50, 'corte' => true],
                            ['v' => 80, 'corte' => true], ['v' => 100, 'corte' => false]] as $marca): ?>
                <?php $yMarca = $y($marca['v'] / 100); ?>
                <line x1="<?= $izq ?>" y1="<?= sprintf('%.2f', $yMarca) ?>"
                      x2="<?= $izq + $anchoPlot ?>" y2="<?= sprintf('%.2f', $yMarca) ?>"
                      stroke="rgb(var(--rv-border))" stroke-width="1"
                      <?= $marca['corte'] ? 'stroke-dasharray="4 4"' : '' ?>></line>
                <text x="<?= $izq - 8 ?>" y="<?= sprintf('%.2f', $yMarca + 4) ?>"
                      text-anchor="end" font-size="10"
                      fill="rgb(var(--rv-text-2))"><?= $marca['v'] ?> %</text>
            <?php endforeach; ?>

            <?php /* Las columnas. */ ?>
            <g filter="url(#rv-nm-dominio)">
                <?php foreach ($dominios as $i => $fila): ?>
                    <?php
                    $frac = $fila['cumplimiento'] ?? null;

                    if ($frac === null) {
                        continue;
                    }

                    $altura = max(2.0, (float) $frac * $altoPlot);
                    ?>
                    <path d="<?= e($columna($centro($i), $altura)) ?>"
                          fill="rgb(<?= e($colorZona[$zonaDe($frac)] ?? 'var(--rv-na)') ?>)"></path>
                <?php endforeach; ?>
            </g>

            <?php
            /*
             * Cifras y nombres van FUERA del grupo con filtro: el relieve
             * extruye la marca, y una sombra proyectada bajo un rótulo de 11
             * píxeles no es relieve, es un borrón.
             *
             * La cifra se rotula en TODAS las columnas y no solo en alguna. En
             * una serie temporal rotularlas todas convierte el gráfico en una
             * tabla mal maquetada, pero aquí son siete magnitudes sin orden y la
             * cifra exacta es media lectura — además de ser lo que impide que la
             * zona se comunique solo con el color.
             */
            ?>
            <?php foreach ($dominios as $i => $fila): ?>
                <?php
                $frac = $fila['cumplimiento'] ?? null;
                $x = $centro($i);
                $lineas = $enDosLineas((string) $fila['nombre_dominio']);

                // El desglose completo, al pasar por encima. Nativo del
                // navegador: ni una línea de guion.
                $emergente = sprintf(
                    '%s — %s (%s: %s · %s: %s · %s: %s)',
                    (string) $fila['nombre_dominio'],
                    $porcentaje($frac),
                    $vista->t('eval.estado_si'), (string) ($fila['controles_si'] ?? 0),
                    $vista->t('eval.estado_no'), (string) ($fila['controles_no'] ?? 0),
                    $vista->t('eval.estado_na'), (string) ($fila['controles_na'] ?? 0),
                );
                ?>
                <g>
                    <title><?= e($emergente) ?></title>

                    <?php
                    /*
                     * Banda transparente de la altura del gráfico: el título
                     * emergente se consulta apuntando a la COLUMNA o a
                     * cualquier punto de su vertical, y no solo a los pocos
                     * píxeles de alto que tiene un dominio al 8 %.
                     */
                    ?>
                    <rect x="<?= sprintf('%.2f', $x - $banda / 2) ?>" y="<?= $arriba ?>"
                          width="<?= sprintf('%.2f', $banda) ?>" height="<?= $altoPlot ?>"
                          fill="transparent"></rect>

                    <text x="<?= sprintf('%.2f', $x) ?>"
                          y="<?= sprintf('%.2f', $frac === null ? $base - 8 : $y((float) $frac) - 7) ?>"
                          text-anchor="middle" font-size="11" font-weight="600"
                          fill="rgb(var(--rv-text))"><?= e($porcentaje($frac)) ?></text>

                    <?php foreach ($lineas as $n => $linea): ?>
                        <text x="<?= sprintf('%.2f', $x) ?>"
                              y="<?= sprintf('%.2f', $base + 15 + $n * 12) ?>"
                              text-anchor="middle" font-size="10"
                              fill="rgb(var(--rv-text-2))"><?= e($linea) ?></text>
                    <?php endforeach; ?>
                </g>
            <?php endforeach; ?>
        </svg>
    </div>

    <p class="mt-2 text-xs text-texto-2">
        <?= e($vista->t('eval.gr_dominios_pie')) ?>
    </p>
<?php endif; ?>
