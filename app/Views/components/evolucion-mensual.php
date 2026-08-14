<?php

declare(strict_types=1);

/**
 * Evolución mensual del trabajo del auditor: cobertura y cumplimiento.
 *
 * Dos medidas, un solo eje. Las dos son proporciones de 0 a 100 %, así que
 * comparten escala y NO hay un segundo eje vertical: dos escalas en un mismo
 * dibujo dejan que la elección de los topes decida cuál línea va por encima, y
 * eso no es un dato, es una opinión.
 *
 * POR QUÉ UNA COLUMNA Y UNA LÍNEA, Y NO DOS LÍNEAS DE COLORES DISTINTOS
 * --------------------------------------------------------------------
 * Rivendel no tiene un segundo tono categórico y no se puede inventar uno: el
 * oro significa referencia normativa y la escala de estado significa estado.
 * Los pares que quedan (verde contra gris, verde contra tinta) no llegan a la
 * separación mínima ni siquiera con visión normal — con daltonismo protán el
 * verde y el gris se separan por 3 de 100, o sea nada.
 *
 * Así que la identidad NO la lleva el color: la lleva la FORMA. Una columna y
 * una línea no se confunden con ninguna visión, ni impresas en gris. El color
 * solo refuerza. Es también la lectura correcta de las dos medidas: la
 * cobertura es el volumen de trabajo (cuánto del instrumento se aplicó) y el
 * cumplimiento es el resultado (cuánto salió conforme) — contexto detrás,
 * conclusión delante.
 *
 * Las dos series van EXTRUIDAS, con el mismo par de sombras del resto del
 * producto (ver «Relieve dentro de un SVG» en rivendel.css). El relieve no
 * codifica nada —eso lo siguen haciendo la forma y el color—, pero pone a cada
 * serie a su altura: las columnas apoyadas sobre el lienzo y la línea flotando
 * por encima, que es el orden en que se leen.
 *
 * Sin JavaScript. Los valores se consultan con el título emergente nativo de
 * cada marca, y el último de cada serie va rotulado directamente: rotular todos
 * los puntos convierte el gráfico en una tabla mal maquetada.
 *
 * @var \App\Core\Vista $vista
 * @var list<array<string, mixed>> $evolucion  Filas de sp_evolucion_auditor.
 */

/*
 * Los últimos seis meses CON auditorías. Ojo: son los seis últimos registros,
 * no los seis últimos meses del calendario — sp_evolucion_auditor no devuelve
 * los meses vacíos. El pie del gráfico lo dice, porque de otro modo una
 * pendiente entre dos columnas contiguas se leería como un mes de diferencia
 * cuando pueden ser cinco.
 */
$meses = array_slice($evolucion, -6);
$total = count($meses);

$fraccion = static fn (mixed $v): ?float => $v === null ? null : (float) $v;

$porcentaje = static fn (?float $v): string =>
    $v === null ? '—' : number_format($v * 100, 1, ',', '') . ' %';

// 2026-08 -> 08/26. Cifra y no nombre de mes: se lee igual en los dos idiomas,
// es tabular y no obliga a mantener doce claves traducidas por cada uno.
$etiquetaMes = static function (string $mes): string {
    $partes = explode('-', $mes);

    return count($partes) === 2 ? $partes[1] . '/' . substr($partes[0], -2) : $mes;
};

$ultimo = $total > 0 ? $meses[$total - 1] : null;
?>
<?php if ($total === 0): ?>
    <p class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-4 py-8 text-center text-sm text-texto-2">
        <?= e($vista->t('eval.sin_evolucion')) ?>
    </p>
<?php elseif ($total === 1): ?>
    <?php
    /*
     * Con un solo mes no hay tendencia que dibujar, y una línea de un punto es
     * un gráfico que promete una lectura que no puede dar. Se enseñan las dos
     * cifras y se dice qué falta para que aparezca la curva.
     */
    ?>
    <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-5 py-6">
        <div class="flex flex-wrap gap-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">
                    <?= e($vista->t('eval.cumplimiento_general')) ?>
                </p>
                <p class="tabular mt-1 text-2xl font-semibold text-texto">
                    <?= e($porcentaje($fraccion($ultimo['cumplimiento'] ?? null))) ?>
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">
                    <?= e($vista->t('eval.cobertura_instrumento')) ?>
                </p>
                <p class="tabular mt-1 text-2xl font-semibold text-texto">
                    <?= e($porcentaje($fraccion($ultimo['cobertura'] ?? null))) ?>
                </p>
            </div>
        </div>
        <p class="mt-4 text-sm text-texto-2">
            <?= e($vista->t('eval.evolucion_un_mes', $etiquetaMes((string) $ultimo['mes']))) ?>
        </p>
    </div>
<?php else: ?>
    <?php
    /*
     * Geometría del lienzo. Va en un viewBox fijo y el SVG se escala solo: así
     * el dibujo no depende de medir nada en el navegador y sale igual en el
     * primer pintado, sin guion de por medio.
     */
    $ancho  = 520;
    $alto   = 196;
    $izq    = 42;   // hueco para las etiquetas del eje vertical
    $arriba = 14;
    $anchoPlot = $ancho - $izq - 12;
    $altoPlot  = 150;
    $base      = $arriba + $altoPlot;

    $banda = $anchoPlot / $total;
    $centro = static fn (int $i): float => $izq + $banda * ($i + 0.5);
    $y = static fn (float $v): float => $base - $v * $altoPlot;

    // Columna: ancho contenido para que quede aire entre meses contiguos.
    $anchoBarra = min(38.0, $banda * 0.44);

    /*
     * Columna con el extremo de dato redondeado y la base a escuadra: el
     * redondeo marca dónde termina el valor, y una base redondeada despegaría
     * la columna de su propia línea de cero.
     */
    $columna = static function (float $x, float $altura) use ($base, $anchoBarra): string {
        $r = min(4.0, $anchoBarra / 2, $altura);
        $x0 = $x - $anchoBarra / 2;
        $x1 = $x + $anchoBarra / 2;
        $yTope = $base - $altura;

        /*
         * Los ocho pares, en orden: base izquierda, subida por el costado
         * izquierdo, esquina redondeada, tope, esquina redondeada, y BAJADA
         * por el costado derecho hasta la base. Ese último tramo es el que
         * hace que sea una columna: sin él, Z cierra en diagonal desde el tope
         * derecho hasta la base izquierda y la columna sale en cuña.
         */
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

    // Puntos de la línea de cumplimiento. Un mes sin cumplimiento calculable
    // (ninguna respuesta sí/no) no inventa un cero: se queda fuera y la línea
    // salta ese hueco en vez de bajar hasta el suelo.
    $puntos = [];

    foreach ($meses as $i => $fila) {
        $valor = $fraccion($fila['cumplimiento'] ?? null);

        if ($valor !== null) {
            $puntos[] = ['x' => $centro($i), 'y' => $y($valor), 'v' => $valor, 'mes' => (string) $fila['mes']];
        }
    }

    $ultimoPunto = $puntos === [] ? null : $puntos[count($puntos) - 1];
    $ultimaCobertura = $fraccion($ultimo['cobertura'] ?? null);

    $resumenLectura = $vista->t(
        'eval.evolucion_resumen',
        (string) $total,
        $porcentaje($fraccion($ultimo['cumplimiento'] ?? null)),
        $porcentaje($ultimaCobertura),
    );
    ?>

    <?php
    /*
     * Leyenda en HTML y no dentro del SVG: hereda la tipografía y los tokens
     * del resto de la tarjeta sin repetir tamaños. Cada muestra tiene la FORMA
     * de su serie —un bloque para la columna, una línea con punto para la
     * línea—, que es lo que de verdad las distingue en el dibujo.
     */
    ?>
    <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-texto-2">
        <span class="flex items-center gap-2">
            <span class="rv-extruido-xs h-3 w-3 rounded-[2px] bg-na/30" aria-hidden="true"></span>
            <?= e($vista->t('eval.cobertura_instrumento')) ?>
        </span>
        <span class="flex items-center gap-2">
            <span class="relative flex h-3 w-5 items-center" aria-hidden="true">
                <span class="rv-extruido-xs h-[2px] w-full rounded-full bg-primario"></span>
                <span class="rv-extruido-xs absolute left-1/2 h-2 w-2 -translate-x-1/2 rounded-full bg-primario"></span>
            </span>
            <?= e($vista->t('eval.cumplimiento_general')) ?>
        </span>
    </div>

    <div class="font-sans tabular">
        <svg viewBox="0 0 <?= $ancho ?> <?= $alto ?>" width="100%" height="auto"
             role="img" aria-label="<?= e($resumenLectura) ?>">

            <?php
            /*
             * RELIEVE DE LAS MARCAS
             * ---------------------
             * El neumorfismo del producto es box-shadow, y box-shadow no entra
             * en un SVG. El equivalente es un par de sombras proyectadas —una
             * oscura abajo-derecha, un realce claro arriba-izquierda—, que es
             * exactamente la receta de .rv-extruido; los valores salen de las
             * mismas ternas --nm-*, aplicadas desde .rv-nm-sombra/.rv-nm-realce
             * en rivendel.css. Si mañana cambia la paleta o el nivel de
             * relieve, el gráfico cambia con el resto sin tocar este archivo.
             *
             * Cada filtro se aplica UNA vez, al grupo entero de cada serie, y
             * no marca por marca: mismo dibujo (las marcas no se solapan) con
             * una sola pasada en lugar de seis.
             *
             * La región va en unidades de usuario y cubre el lienzo completo:
             * la caja de una polilínea casi horizontal es tan baja que el
             * recorte por defecto (120 % del contorno) se comería su sombra.
             *
             * color-interpolation-filters="sRGB" no es adorno: el valor por
             * omisión es linearRGB y ahí la misma opacidad rinde una sombra
             * mucho más pálida que la de las tarjetas de al lado.
             */
            ?>
            <defs>
                <filter id="rv-nm-columna" filterUnits="userSpaceOnUse"
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

                <?php
                /*
                 * La línea va MÁS levantada que las columnas: es la conclusión
                 * y ellas son el contexto, y en neumorfismo la altura es
                 * jerarquía. Es el mismo criterio de .rv-extruido frente a
                 * .rv-extruido-lg, en pequeño.
                 */
                ?>
                <filter id="rv-nm-linea" filterUnits="userSpaceOnUse"
                        x="0" y="0" width="<?= $ancho ?>" height="<?= $alto ?>"
                        color-interpolation-filters="sRGB">
                    <feDropShadow in="SourceGraphic" dx="3" dy="3" stdDeviation="2.8"
                                  class="rv-nm-sombra" result="sombra"></feDropShadow>
                    <feDropShadow in="SourceGraphic" dx="-2" dy="-2" stdDeviation="2"
                                  class="rv-nm-realce" result="realce"></feDropShadow>
                    <feMerge>
                        <feMergeNode in="sombra"></feMergeNode>
                        <feMergeNode in="realce"></feMergeNode>
                    </feMerge>
                </filter>
            </defs>

            <?php /* Rejilla recesiva: orienta la lectura sin competir con los datos. */ ?>
            <?php foreach ([0, 25, 50, 75, 100] as $marca): ?>
                <?php $yMarca = $y($marca / 100); ?>
                <line x1="<?= $izq ?>" y1="<?= sprintf('%.2f', $yMarca) ?>"
                      x2="<?= $izq + $anchoPlot ?>" y2="<?= sprintf('%.2f', $yMarca) ?>"
                      stroke="rgb(var(--rv-border))" stroke-width="1"></line>
                <text x="<?= $izq - 8 ?>" y="<?= sprintf('%.2f', $yMarca + 4) ?>"
                      text-anchor="end" font-size="11" fill="rgb(var(--rv-text-2))"><?= $marca ?> %</text>
            <?php endforeach; ?>

            <?php /* Cobertura — el volumen de trabajo del mes, detrás. */ ?>
            <g filter="url(#rv-nm-columna)">
                <?php foreach ($meses as $i => $fila): ?>
                    <?php
                    $cobertura = $fraccion($fila['cobertura'] ?? null);

                    if ($cobertura === null || $cobertura <= 0) {
                        continue;
                    }

                    $trazo = $columna($centro($i), $cobertura * $altoPlot);
                    ?>
                    <?php
                    /*
                     * Dos veces el MISMO contorno: primero opaco del color de
                     * la tarjeta, encima el gris al 30 %. El tinte translúcido
                     * es el que había y sigue mandando en el color; la capa de
                     * abajo solo tapa. Sin ella la columna deja ver su propia
                     * sombra por dentro y en vez de levantarse se ensucia.
                     */
                    ?>
                    <path d="<?= e($trazo) ?>" fill="rgb(var(--rv-surface))"></path>
                    <path d="<?= e($trazo) ?>" fill="rgb(var(--rv-na) / 0.30)">
                        <title><?= e($vista->t(
                            'eval.punto_cobertura',
                            $etiquetaMes((string) $fila['mes']),
                            $porcentaje($cobertura),
                            (string) ($fila['auditorias'] ?? 0),
                        )) ?></title>
                    </path>
                <?php endforeach; ?>
            </g>

            <?php
            /*
             * Cumplimiento — el resultado, delante. Línea y puntos comparten
             * grupo y, por tanto, un único relieve: son una sola pieza
             * levantada sobre las columnas, un cordón con sus nudos, y no dos
             * dibujos cada uno con su sombra.
             */
            ?>
            <g filter="url(#rv-nm-linea)">
                <?php if (count($puntos) > 1): ?>
                    <?php
                    /*
                     * Trazo algo más grueso que antes: bajo relieve, dos
                     * píxeles de cordón se leen como un arañazo. Sigue por
                     * debajo del diámetro del punto, que es quien marca el mes.
                     */
                    ?>
                    <polyline fill="none" stroke="rgb(var(--rv-primary))" stroke-width="2.5"
                              stroke-linecap="round" stroke-linejoin="round"
                              points="<?= e(implode(' ', array_map(
                                  static fn (array $p): string => sprintf('%.2f,%.2f', $p['x'], $p['y']),
                                  $puntos,
                              ))) ?>"></polyline>
                <?php endif; ?>

                <?php foreach ($puntos as $punto): ?>
                    <?php
                    /*
                     * Anillo del color de la superficie: cuando un punto cae
                     * sobre su columna, el anillo lo despega en vez de
                     * fundirlo con ella.
                     */
                    ?>
                    <circle cx="<?= sprintf('%.2f', $punto['x']) ?>" cy="<?= sprintf('%.2f', $punto['y']) ?>" r="4"
                            fill="rgb(var(--rv-primary))"
                            stroke="rgb(var(--rv-surface))" stroke-width="2">
                        <title><?= e($vista->t(
                            'eval.punto_cumplimiento',
                            $etiquetaMes($punto['mes']),
                            $porcentaje($punto['v']),
                        )) ?></title>
                    </circle>
                <?php endforeach; ?>
            </g>

            <?php
            /*
             * Rótulo directo SOLO en el último punto. Es el valor que se busca
             * al abrir el panel; los demás están a un puntero de distancia y
             * rotularlos todos taparía la propia línea.
             */
            ?>
            <?php if ($ultimoPunto !== null): ?>
                <text x="<?= sprintf('%.2f', min($ultimoPunto['x'], $izq + $anchoPlot)) ?>"
                      y="<?= sprintf('%.2f', max($ultimoPunto['y'] - 11, 10)) ?>"
                      text-anchor="end" font-size="12" font-weight="600"
                      fill="rgb(var(--rv-text))"><?= e($porcentaje($ultimoPunto['v'])) ?></text>
            <?php endif; ?>

            <?php /* Meses. */ ?>
            <?php foreach ($meses as $i => $fila): ?>
                <text x="<?= sprintf('%.2f', $centro($i)) ?>" y="<?= $base + 18 ?>"
                      text-anchor="middle" font-size="11"
                      fill="rgb(var(--rv-text-2))"><?= e($etiquetaMes((string) $fila['mes'])) ?></text>
            <?php endforeach; ?>
        </svg>
    </div>

    <p class="mt-2 text-xs text-texto-2"><?= e($vista->t('eval.eje_meses')) ?></p>
<?php endif; ?>
