<?php

declare(strict_types=1);

/**
 * Tendencia del ISBD: las últimas cuarenta lecturas.
 *
 * ── Una serie, un eje, y la rejilla son las bandas ──────────────────────────
 *
 * Aquí solo hay una medida, así que no hay ocasión de inventar un segundo tono
 * categórico. Las líneas horizontales van en 40, 60, 75 y 90 —las fronteras del
 * §5.2— y no en una escala decimal: así el gráfico se lee sin traducir nada, y
 * un tramo por debajo de la línea de 40 es CRÍTICO se mire como se mire.
 *
 * ── La marca de lectura topada ───────────────────────────────────────────────
 *
 * Un rombo señala las lecturas en las que mordió el eslabón más débil: el
 * promedio ponderado daba más, pero un componente en mal estado bajó el índice
 * hasta la frontera de su banda. Por eso esas lecturas caen CLAVADAS en 40, 60,
 * 75 o 90 y la serie dibuja mesetas planas justo sobre la línea — la meseta es
 * la firma de la regla, no un fallo del dibujo, y el rombo lo confirma.
 *
 * La marca es una FORMA (rombo frente a círculo) y no solo un color: en gris,
 * impresa o con daltonismo se sigue distinguiendo. El color la refuerza y la
 * leyenda la nombra; ninguno de los tres canales va solo.
 *
 * ── Los huecos se saltan ─────────────────────────────────────────────────────
 *
 * Una lectura sin índice publicado (cobertura bajo el piso) parte la línea. Unir
 * por encima del hueco dibujaría una pendiente que nadie midió, y bajar a cero
 * diría «estaba en el suelo» cuando lo que pasa es que no se sabe.
 *
 * Sin JavaScript: viewBox fijo, el SVG escala solo y sale bien en el primer
 * pintado. Los valores se consultan con el título emergente nativo.
 *
 * @var \App\Core\Vista $vista
 * @var array<string, mixed> $tendencia  lecturas, topadas, cadencia_min
 * @var \Closure(?float): string $cifra
 */
$lecturas = $tendencia['lecturas'];
$topadas  = array_flip($tendencia['topadas']);
$cadencia = (int) $tendencia['cadencia_min'];
$total    = count($lecturas);

$publicadas = array_values(array_filter($lecturas, static fn (?float $v): bool => $v !== null));
?>
<?php if (count($publicadas) < 2): ?>
    <p class="rv-hundido rounded-rv border border-borde bg-fondo px-4 py-8 text-center text-sm text-texto-2">
        <?= e($vista->t('mon.sin_tendencia')) ?>
    </p>
<?php else: ?>
    <?php
    /*
     * PROPORCIÓN PARA MEDIA COLUMNA. El gráfico comparte el ancho de la consola
     * con el de memoria, así que el lienzo mide poco más del doble de lo que
     * mide de alto y no 3,2 veces: en una columna estrecha un viewBox muy
     * apaisado se aplasta y, como el texto escala con el dibujo, los rótulos del
     * eje se quedan en letra de nueve píxeles. El alto se ciñe a la rejilla —no
     * hay eje horizontal que rotular— para no dejar una franja muerta debajo.
     */
    $ancho     = 470;
    $alto      = 200;
    $izq       = 30;
    $arriba    = 12;
    $anchoPlot = $ancho - $izq - 12;
    $altoPlot  = 168;
    $base      = $arriba + $altoPlot;

    $x = static fn (int $i): float => $izq + ($total <= 1 ? 0 : $anchoPlot * $i / ($total - 1));
    $y = static fn (float $v): float => $base - ($v / 100) * $altoPlot;

    /*
     * Tramos continuos. Cada hueco cierra el tramo en curso y abre otro, así
     * que la línea y su relleno se dibujan por tramos y nunca cruzan un hueco.
     */
    $tramos = [];
    $tramo  = [];
    $huecos = [];

    foreach ($lecturas as $i => $v) {
        if ($v === null) {
            $huecos[] = $x($i);

            if ($tramo !== []) {
                $tramos[] = $tramo;
                $tramo = [];
            }

            continue;
        }

        $tramo[] = [
            'x' => $x($i),
            'y' => $y((float) $v),
            'v' => (float) $v,
            'i' => $i,
            'topada' => isset($topadas[$i]),
        ];
    }

    if ($tramo !== []) {
        $tramos[] = $tramo;
    }

    $puntos = array_merge(...$tramos);
    $ultimo = $puntos[count($puntos) - 1];

    $minutos = ($total - 1) * $cadencia;

    $resumen = $vista->t(
        'mon.tendencia_resumen',
        (string) $total,
        $cifra($ultimo['v']),
        (string) count($tendencia['topadas']),
    );

    // Un rombo centrado en el punto: la marca de "aquí mordió el tope".
    $rombo = static function (float $cx, float $cy, float $r): string {
        return sprintf(
            'M %.2f %.2f L %.2f %.2f L %.2f %.2f L %.2f %.2f Z',
            $cx, $cy - $r,
            $cx + $r, $cy,
            $cx, $cy + $r,
            $cx - $r, $cy,
        );
    };
    ?>

    <?php
    /*
     * Leyenda en HTML: hereda tipografía y tokens de la tarjeta sin repetir
     * tamaños. Cada muestra lleva la FORMA de lo que nombra.
     */
    ?>
    <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-texto-2">
        <span class="flex items-center gap-2">
            <span class="relative flex h-3 w-5 items-center" aria-hidden="true">
                <span class="h-[2px] w-full rounded-full bg-primario"></span>
                <span class="absolute left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-primario"></span>
            </span>
            <?= e($vista->t('mon.isbd_publicado')) ?>
        </span>
        <span class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rotate-45 bg-bad" aria-hidden="true"></span>
            <?= e($vista->t('mon.leyenda_topada')) ?>
        </span>
        <span class="flex items-center gap-2">
            <span class="h-3 w-0 border-l border-dashed border-na" aria-hidden="true"></span>
            <?= e($vista->t('mon.leyenda_hueco')) ?>
        </span>
    </div>

    <div class="font-sans tabular">
        <svg viewBox="0 0 <?= $ancho ?> <?= $alto ?>" width="100%" height="auto"
             role="img" aria-label="<?= e($resumen) ?>">

            <defs>
                <?php
                /*
                 * Degradado del relleno bajo la línea. NO es una segunda serie
                 * ni un segundo color: es el mismo verde del trazo, desvaneci-
                 * do, y su única función es dar cuerpo a la lectura. Se apaga
                 * hacia abajo para no competir con la rejilla.
                 */
                ?>
                <linearGradient id="rv-area-isbd" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="rgb(var(--rv-primary))" stop-opacity="0.28"></stop>
                    <stop offset="100%" stop-color="rgb(var(--rv-primary))" stop-opacity="0"></stop>
                </linearGradient>
            </defs>

            <?php /* Rejilla: las fronteras de las cinco bandas. */ ?>
            <?php foreach ([0, 40, 60, 75, 90, 100] as $marca): ?>
                <?php $ym = $y((float) $marca); ?>
                <line x1="<?= $izq ?>" y1="<?= sprintf('%.2f', $ym) ?>"
                      x2="<?= $izq + $anchoPlot ?>" y2="<?= sprintf('%.2f', $ym) ?>"
                      stroke="rgb(var(--rv-border))" stroke-width="1"></line>
                <text x="<?= $izq - 6 ?>" y="<?= sprintf('%.2f', $ym + 3.5) ?>"
                      text-anchor="end" font-size="11" fill="rgb(var(--rv-text-2))"><?= $marca ?></text>
            <?php endforeach; ?>

            <?php /* Relleno bajo cada tramo. */ ?>
            <?php foreach ($tramos as $segmento): ?>
                <?php if (count($segmento) < 2) { continue; } ?>
                <?php
                $area = 'M ' . sprintf('%.2f %.2f', $segmento[0]['x'], $base);

                foreach ($segmento as $p) {
                    $area .= sprintf(' L %.2f %.2f', $p['x'], $p['y']);
                }

                $area .= sprintf(' L %.2f %.2f Z', $segmento[count($segmento) - 1]['x'], $base);
                ?>
                <path d="<?= e($area) ?>" fill="url(#rv-area-isbd)"></path>
            <?php endforeach; ?>

            <?php /* Los huecos, marcados: una línea partida sin explicación se lee como un fallo del dibujo. */ ?>
            <?php foreach ($huecos as $hx): ?>
                <line x1="<?= sprintf('%.2f', $hx) ?>" y1="<?= $arriba ?>"
                      x2="<?= sprintf('%.2f', $hx) ?>" y2="<?= $base ?>"
                      stroke="rgb(var(--rv-na))" stroke-width="1"
                      stroke-dasharray="3 3" opacity="0.75"></line>
            <?php endforeach; ?>

            <?php /* La línea, tramo a tramo. */ ?>
            <?php foreach ($tramos as $segmento): ?>
                <?php if (count($segmento) < 2) { continue; } ?>
                <polyline fill="none" stroke="rgb(var(--rv-primary))" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round"
                          points="<?= e(implode(' ', array_map(
                              static fn (array $p): string => sprintf('%.2f,%.2f', $p['x'], $p['y']),
                              $segmento,
                          ))) ?>"></polyline>
            <?php endforeach; ?>

            <?php
            /*
             * Las marcas. Con cuarenta lecturas no se rotula ninguna —eso
             * convertiría el gráfico en una tabla mal maquetada—: los valores
             * están a un puntero de distancia y el último va rotulado aparte.
             *
             * Las topadas se dibujan DESPUÉS de las normales para que queden
             * encima donde se solapen: son las que hay que ver.
             */
            ?>
            <?php foreach ($puntos as $p): ?>
                <?php if ($p['topada']) { continue; } ?>
                <circle cx="<?= sprintf('%.2f', $p['x']) ?>" cy="<?= sprintf('%.2f', $p['y']) ?>" r="2.4"
                        fill="rgb(var(--rv-primary))">
                    <title><?= e($vista->t('mon.punto_lectura', (string) ($p['i'] + 1), $cifra($p['v']))) ?></title>
                </circle>
            <?php endforeach; ?>

            <?php foreach ($puntos as $p): ?>
                <?php if (!$p['topada']) { continue; } ?>
                <path d="<?= e($rombo($p['x'], $p['y'], 3.8)) ?>"
                      fill="rgb(var(--rv-bad))"
                      stroke="rgb(var(--rv-surface))" stroke-width="1">
                    <title><?= e($vista->t('mon.punto_topado', (string) ($p['i'] + 1), $cifra($p['v']))) ?></title>
                </path>
            <?php endforeach; ?>

            <?php /* Rótulo directo solo en la última: es el valor que se busca al entrar. */ ?>
            <text x="<?= sprintf('%.2f', $ultimo['x']) ?>"
                  y="<?= sprintf('%.2f', max($ultimo['y'] - 10, 10)) ?>"
                  text-anchor="end" font-size="13" font-weight="600"
                  fill="rgb(var(--rv-text))"><?= e($cifra($ultimo['v'])) ?></text>
        </svg>
    </div>

    <p class="mt-2 max-w-[95ch] text-xs leading-relaxed text-texto-2">
        <?= e($vista->t('mon.tendencia_pie', (string) $total, (string) $cadencia, (string) $minutos)) ?>
    </p>
<?php endif; ?>
