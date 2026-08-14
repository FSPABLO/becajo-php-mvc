<?php

declare(strict_types=1);

/**
 * Índice general de riesgo de una organización, auditoría por auditoría.
 *
 * Sustituye a las barras sueltas que dibujaba la vista de comparación. Aquellas
 * se escalaban contra el MÁXIMO de la serie, y eso hacía que una cartera con
 * índices de 0,14 a 0,65 y otra de 0,80 a 0,95 salieran con la misma silueta:
 * el tope lo decidía el dato, no la escala, así que la altura de una columna no
 * significaba nada fuera de su propia tarjeta. Aquí el eje va FIJO de 0 a 1, que
 * es el rango real del indicador (calcular_riesgo_auditoria normaliza la madurez
 * ponderada dividiéndola entre 5), y una columna a media altura es media altura
 * en cualquier organización de la página.
 *
 * Una sola medida y por tanto un solo eje. La referencia normativa —el corte de
 * zona verde de fn_zona— entra en oro y de trazo discontinuo: el oro significa
 * exactamente eso en Rivendel y no se gasta en ninguna otra cosa del gráfico.
 *
 * Sin JavaScript, igual que components/evolucion-mensual: viewBox fijo, el SVG
 * escala solo y el dibujo sale entero en el primer pintado.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Auditoria> $auditorias  Más antigua primero.
 * @var string $id  Sufijo de los ids de <defs>. La página pinta un gráfico por
 *                  organización y dos filtros con el mismo id se pisan: el
 *                  segundo bloque nunca llega a aplicarse.
 */

/*
 * Las ocho últimas auditorías. Por encima de eso las columnas se estrechan
 * hasta volverse rayas y las fechas del pie se solapan; el desglose completo lo
 * da la tabla que va debajo del gráfico.
 */
$serie = array_slice($auditorias, -8);

// Una auditoría sin índice calculado no dibuja columna: no vale cero. Se
// cuentan aparte para poder decirlo en el pie en vez de callar el hueco.
$conIndice = array_values(array_filter(
    $serie,
    static fn (\App\Models\Entidades\Auditoria $a): bool => $a->indiceGeneralRiesgo !== null,
));

$total = count($serie);

$cifra = static fn (float $v): string => number_format($v, 2, ',', '');

/*
 * Corte de la zona verde en fn_zona (pkg_indicadores): por debajo de 0,80 la
 * auditoría no llega a zona verde. Si mañana se mueve el corte en la base, se
 * mueve aquí — son dos sitios y el comentario lo dice en los dos.
 */
$umbral = 0.80;

/*
 * Etiqueta de fecha del pie. Con toda la serie dentro del mismo año el año
 * sobra en cada columna y solo gasta ancho; en cuanto la serie lo cruza vuelve,
 * porque «04-14» de dos años distintos son dos columnas idénticas.
 */
$anios = [];

foreach ($serie as $auditoria) {
    $anios[substr($auditoria->fecha, 0, 4)] = true;
}

$conAnio = count($anios) > 1;

$etiquetaFecha = static function (string $fecha) use ($conAnio): string {
    // 2026-04-14 -> 04-14, o 26-04-14 cuando la serie cruza el año. Orden ISO
    // en los dos casos: se lee igual en español y en inglés, y es tabular.
    return $conAnio ? substr($fecha, 2) : substr($fecha, 5);
};
?>
<?php if ($conIndice === []): ?>
    <p class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-4 py-10 text-center text-sm text-texto-2">
        <?= e($vista->t('eval.sin_indice_calculado')) ?>
    </p>
<?php else: ?>
    <?php
    /* Geometría del lienzo, en unidades del viewBox. */
    $ancho     = 560;
    $alto      = 214;
    $izq       = 46;   // gutter de las etiquetas del eje vertical
    $arriba    = 18;
    $anchoPlot = $ancho - $izq - 14;
    $altoPlot  = 150;
    $base      = $arriba + $altoPlot;

    $banda  = $anchoPlot / $total;
    $centro = static fn (int $i): float => $izq + $banda * ($i + 0.5);
    $y      = static fn (float $v): float => $base - $v * $altoPlot;

    // Columna contenida dentro de su banda: sin aire entre columnas contiguas
    // la serie se lee como un único bloque dentado.
    $anchoBarra = min(46.0, $banda * 0.52);

    /*
     * Columna de extremo redondeado y base a escuadra, misma receta que
     * components/evolucion-mensual: el redondeo marca dónde termina el valor y
     * una base redondeada despegaría la columna de su propia línea de cero.
     */
    $columna = static function (float $x, float $altura) use ($base, $anchoBarra): string {
        $r     = min(6.0, $anchoBarra / 2, $altura);
        $x0    = $x - $anchoBarra / 2;
        $x1    = $x + $anchoBarra / 2;
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

    $ultima = $serie[$total - 1];

    $resumenLectura = $vista->t(
        'eval.indice_resumen',
        (string) count($conIndice),
        $ultima->indiceGeneralRiesgo === null ? '—' : $cifra($ultima->indiceGeneralRiesgo),
    );
    ?>

    <?php
    /*
     * Leyenda en HTML y no dentro del SVG: hereda tipografía y tokens de la
     * tarjeta sin repetir tamaños, y deja el dibujo sin texto suelto que pueda
     * caer encima de una columna. Cada muestra tiene la FORMA de lo que nombra.
     */
    ?>
    <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-texto-2">
        <span class="flex items-center gap-2">
            <span class="rv-extruido-xs h-3 w-3 rounded-[2px] bg-primario" aria-hidden="true"></span>
            <?= e($vista->t('eval.indice_general_riesgo')) ?>
        </span>
        <span class="flex items-center gap-2">
            <span class="flex h-3 w-5 items-center" aria-hidden="true">
                <span class="h-0 w-full border-t-2 border-dashed border-oro"></span>
            </span>
            <?= e($vista->t('eval.umbral_verde')) ?>
        </span>
    </div>

    <div class="font-sans tabular">
        <svg viewBox="0 0 <?= $ancho ?> <?= $alto ?>" width="100%" height="auto"
             role="img" aria-label="<?= e($resumenLectura) ?>">

            <?php
            /*
             * Relieve de las columnas. box-shadow no entra en un SVG, así que
             * el par de sombras de .rv-extruido se dibuja con filtros que leen
             * las MISMAS ternas --nm-* desde .rv-nm-sombra / .rv-nm-realce
             * (ver «Relieve dentro de un SVG» en rivendel.css). Un filtro por
             * grupo y no por marca: mismo dibujo, una sola pasada.
             *
             * color-interpolation-filters="sRGB" no es adorno — el valor por
             * omisión es linearRGB y ahí la misma opacidad rinde una sombra
             * más pálida que la de las tarjetas de al lado.
             */
            ?>
            <defs>
                <filter id="rv-nm-col-<?= e($id) ?>" filterUnits="userSpaceOnUse"
                        x="0" y="0" width="<?= $ancho ?>" height="<?= $alto ?>"
                        color-interpolation-filters="sRGB">
                    <feDropShadow in="SourceGraphic" dx="2.6" dy="2.6" stdDeviation="2.2"
                                  class="rv-nm-sombra" result="sombra"></feDropShadow>
                    <feDropShadow in="SourceGraphic" dx="-1.8" dy="-1.8" stdDeviation="1.8"
                                  class="rv-nm-realce" result="realce"></feDropShadow>
                    <feMerge>
                        <feMergeNode in="sombra"></feMergeNode>
                        <feMergeNode in="realce"></feMergeNode>
                    </feMerge>
                </filter>
            </defs>

            <?php
            /*
             * Rejilla discontinua y recesiva: orienta la lectura de la altura
             * sin competir con las columnas. Discontinua a propósito, para que
             * ninguna de estas líneas pueda confundirse con un dato.
             */
            ?>
            <?php foreach ([0.0, 0.25, 0.50, 0.75, 1.0] as $marca): ?>
                <?php $yMarca = $y($marca); ?>
                <line x1="<?= $izq ?>" y1="<?= sprintf('%.2f', $yMarca) ?>"
                      x2="<?= $izq + $anchoPlot ?>" y2="<?= sprintf('%.2f', $yMarca) ?>"
                      stroke="rgb(var(--rv-border))" stroke-width="1"
                      stroke-dasharray="<?= $marca === 0.0 ? 'none' : '2 5' ?>"></line>
                <text x="<?= $izq - 9 ?>" y="<?= sprintf('%.2f', $yMarca + 3.5) ?>"
                      text-anchor="end" font-size="10"
                      fill="rgb(var(--rv-text-2))"><?= e($cifra($marca)) ?></text>
            <?php endforeach; ?>

            <?php /* Columnas. El grupo entero lleva un solo relieve. */ ?>
            <g filter="url(#rv-nm-col-<?= e($id) ?>)">
                <?php foreach ($serie as $i => $auditoria): ?>
                    <?php
                    $indice = $auditoria->indiceGeneralRiesgo;

                    if ($indice === null || $indice <= 0) {
                        continue;
                    }

                    $trazo = $columna($centro($i), min($indice, 1.0) * $altoPlot);

                    /*
                     * La auditoría más reciente va a pleno color y las
                     * anteriores atenuadas. No estrena canal: cuál es la
                     * última ya lo dice la posición —la serie corre en el
                     * tiempo de izquierda a derecha— y el contraste solo
                     * repite lo que la posición afirma. Es la lectura que
                     * trae quien abre esta pantalla: dónde quedó lo último,
                     * con lo anterior de contexto detrás.
                     */
                    $esUltima = $i === $total - 1;
                    ?>
                    <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>">
                        <?php
                        /*
                         * Dos veces el MISMO contorno: primero opaco del color
                         * de la tarjeta y encima el tinte. Sin la capa de
                         * abajo, una columna translúcida deja ver su propia
                         * sombra por dentro y en vez de levantarse se ensucia.
                         */
                        ?>
                        <path d="<?= e($trazo) ?>" fill="rgb(var(--rv-surface))"></path>
                        <path d="<?= e($trazo) ?>"
                              fill="rgb(var(--rv-primary)<?= $esUltima ? '' : ' / 0.45' ?>)">
                            <title><?= e($vista->t(
                                'eval.columna_indice',
                                $auditoria->fecha,
                                $cifra($indice),
                            )) ?></title>
                        </path>
                    </a>
                <?php endforeach; ?>
            </g>

            <?php
            /*
             * Umbral normativo por encima de las columnas: es el corte contra
             * el que se leen, y por debajo lo taparía justo la columna que hay
             * que comparar con él. En oro y discontinuo — el oro es referencia
             * normativa y nunca dato.
             */
            ?>
            <line x1="<?= $izq ?>" y1="<?= sprintf('%.2f', $y($umbral)) ?>"
                  x2="<?= $izq + $anchoPlot ?>" y2="<?= sprintf('%.2f', $y($umbral)) ?>"
                  stroke="rgb(var(--rv-gold))" stroke-width="1.5" stroke-dasharray="6 4"></line>

            <?php
            /*
             * Rótulo directo SOLO en la última columna: es la cifra que se
             * busca al abrir la pantalla. Rotularlas todas convierte el
             * gráfico en una tabla mal maquetada, y la tabla ya está debajo.
             */
            ?>
            <?php if ($ultima->indiceGeneralRiesgo !== null): ?>
                <text x="<?= sprintf('%.2f', $centro($total - 1)) ?>"
                      y="<?= sprintf('%.2f', max($y(min($ultima->indiceGeneralRiesgo, 1.0)) - 8, 11)) ?>"
                      text-anchor="middle" font-size="12" font-weight="600"
                      fill="rgb(var(--rv-text))"><?= e($cifra($ultima->indiceGeneralRiesgo)) ?></text>
            <?php endif; ?>

            <?php /* Fechas. La sin índice también aparece: existe aunque no dibuje. */ ?>
            <?php foreach ($serie as $i => $auditoria): ?>
                <text x="<?= sprintf('%.2f', $centro($i)) ?>" y="<?= $base + 18 ?>"
                      text-anchor="middle" font-size="10"
                      fill="rgb(var(--rv-text-2))"><?= e($etiquetaFecha($auditoria->fecha)) ?></text>
            <?php endforeach; ?>
        </svg>
    </div>

    <?php if (count($conIndice) < $total): ?>
        <p class="mt-2 text-xs text-texto-2">
            <?= e($vista->t('eval.auditorias_sin_indice', (string) ($total - count($conIndice)))) ?>
        </p>
    <?php endif; ?>
<?php endif; ?>
