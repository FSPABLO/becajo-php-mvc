<?php

declare(strict_types=1);

/**
 * Memoria de la base de datos en el tiempo, con refresco automático.
 *
 * Eje vertical: megabytes OCUPADOS sobre el total asignado. Eje horizontal:
 * tiempo activo de la instancia —no la hora del reloj—, que es lo que permite
 * comparar dos bases que arrancaron en momentos distintos y leer «lleva doce
 * horas subiendo» sin hacer cuentas.
 *
 * ── Un solo eje, y por qué los umbrales no son un segundo eje ────────────────
 *
 * Los dos umbrales se declaran en porcentaje del total porque así se pactan con
 * el DBA, pero se dibujan en MEGABYTES, convertidos contra el mismo techo que
 * la serie. Dejarlos en su propia escala habría metido un segundo eje vertical
 * por la puerta de atrás, y con dos escalas quien elige los topes decide qué
 * línea va por encima — eso no es un dato.
 *
 * ── Los umbrales no se comunican solo con color ──────────────────────────────
 *
 * Cada línea va rotulada con su nombre y su valor sobre el propio dibujo. El
 * color (ámbar / rojo) refuerza; el trazo discontinuo las separa de la serie,
 * que es continua; y el rótulo las nombra. Impresas en gris siguen siendo
 * legibles, que es la prueba que hay que pasar.
 *
 * ── Qué es «en vivo» aquí, exactamente ───────────────────────────────────────
 *
 * Esto es una MAQUETA: el punto que aparece cada treinta segundos lo genera el
 * navegador, no el agente. La cabecera lo dice con todas las letras. Cuando
 * exista el extremo real, el guion cambia de dónde saca el punto y el dibujo no
 * se entera — por eso la serie entra por `data-serie` y no incrustada en el JS.
 *
 * El primer pintado sale del servidor, completo y sin depender del guion: si el
 * JavaScript no carga, el gráfico se ve igual, solo que quieto.
 *
 * @var \App\Core\Vista $vista
 * @var array<string, mixed> $memoria
 * @var string $instancia
 */
$total       = (float) $memoria['total_mb'];
$aceptacion  = $total * (float) $memoria['umbral_aceptacion_pct'] / 100;
$peligro     = $total * (float) $memoria['umbral_peligro_pct'] / 100;
$cadencia    = (int) $memoria['cadencia_seg'];
$uptimeFinal = (int) $memoria['uptime_min'];
$serie       = array_map('floatval', $memoria['serie']);
$puntos      = count($serie);

$mb = static fn (float $v): string => number_format($v, 0, ',', ' ') . ' MB';

// Tiempo activo en horas y minutos: «12 h 18 min». En minutos sueltos, 738
// obliga a dividir de cabeza justo en el eje que se lee de reojo.
$uptime = static function (int $minutos) use ($vista): string {
    return $minutos < 60
        ? $vista->t('mon.uptime_min', (string) $minutos)
        : $vista->t('mon.uptime_horas', (string) intdiv($minutos, 60), (string) ($minutos % 60));
};

$actual = $serie[$puntos - 1];
$pct    = $total > 0 ? $actual / $total * 100 : 0.0;

/*
 * En qué luz cae el uso actual. No reutiliza semaforo() porque esto no es una
 * banda de salud del §5.2: es la posición frente a dos umbrales operativos
 * pactados aparte. Mezclar las dos cosas en la misma función haría que un
 * cambio en los umbrales de memoria moviera bandas de salud.
 */
$luz = $actual >= $peligro ? 'bad' : ($actual >= $aceptacion ? 'warn' : 'ok');

$etiquetaLuz = $actual >= $peligro
    ? $vista->t('mon.mem_sobre_peligro')
    : ($actual >= $aceptacion ? $vista->t('mon.mem_sobre_aceptacion') : $vista->t('mon.mem_normal'));

/*
 * ── Geometría del lienzo ─────────────────────────────────────────────────────
 *
 * PROPORCIÓN PARA MEDIA COLUMNA: el gráfico comparte el ancho de la consola con
 * la tendencia del ISBD, y un viewBox muy apaisado metido en media columna sale
 * espachurrado —y con él la letra, que escala con el dibujo—. De ahí que el
 * lienzo mida menos de dos veces lo que mide de alto, y que los rótulos vayan
 * en font-size 11: en un lienzo estrecho el texto se amplifica menos.
 */
$ancho     = 480;
$alto      = 250;
$izq       = 52;
$arriba    = 14;
$anchoPlot = $ancho - $izq - 16;
$altoPlot  = 178;
$base      = $arriba + $altoPlot;

/*
 * La ventana horizontal se declara en PUNTOS y no en píxeles para que el guion
 * pueda desplazar la serie sin recalcular el lienzo: cada lectura ocupa siempre
 * la misma anchura y la más antigua sale por la izquierda.
 */
$paso = $anchoPlot / max(1, $puntos - 1);

$x = static fn (int $i): float => $izq + $paso * $i;
$y = static fn (float $v): float => $base - ($v / max(1.0, $total)) * $altoPlot;

// Cuatro marcas verticales, en múltiplos limpios del total.
$marcas = [0.0, $total * 0.25, $total * 0.5, $total * 0.75, $total];

$coordenadas = [];

foreach ($serie as $i => $v) {
    $coordenadas[] = sprintf('%.2f,%.2f', $x($i), $y($v));
}

$area = 'M ' . sprintf('%.2f %.2f', $izq, $base)
      . ' L ' . implode(' L ', array_map(
          static fn (string $p): string => str_replace(',', ' ', $p),
          $coordenadas,
      ))
      . ' L ' . sprintf('%.2f %.2f', $x($puntos - 1), $base) . ' Z';

$ventanaMin = (int) round($puntos * $cadencia / 60);

$resumen = $vista->t(
    'mon.mem_resumen',
    $mb($actual),
    $mb($total),
    number_format($pct, 1, ',', ''),
);
?>
<div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5"
     data-memoria
     data-instancia="<?= e($instancia) ?>"
     data-total="<?= e((string) $total) ?>"
     data-aceptacion="<?= e(sprintf('%.2f', $aceptacion)) ?>"
     data-peligro="<?= e(sprintf('%.2f', $peligro)) ?>"
     data-cadencia="<?= e((string) $cadencia) ?>"
     data-uptime="<?= e((string) $uptimeFinal) ?>"
     <?php
     /*
      * La geometría viaja al guion en vez de que este la despeje o la repita:
      * aquí se decidió y aquí se declara. Si mañana cambia el alto del lienzo,
      * cambia en un sitio.
      */
     ?>
     data-izq="<?= e(sprintf('%.2f', $izq)) ?>"
     data-paso="<?= e(sprintf('%.4f', $paso)) ?>"
     data-base="<?= e(sprintf('%.2f', $base)) ?>"
     data-alto-plot="<?= e(sprintf('%.2f', $altoPlot)) ?>"
     data-serie="<?= e(implode(',', array_map(static fn (float $v): string => sprintf('%.0f', $v), $serie))) ?>">

    <div class="mb-4 flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
        <?php
        /*
         * `md:flex-1 min-w-0`: en media columna el rótulo y su pie suman más de
         * lo que queda libre, y sin base cero flexbox baja el bloque de la cifra
         * a una segunda fila —donde queda alineado a la derecha de una caja que
         * empieza a la izquierda, que se lee como un descuadre—. Con base cero
         * el que cede es el pie, envolviendo su texto. Por debajo de `md` no
         * aplica: ahí la tarjeta va a todo el ancho y estrechar el título para
         * salvar una fila sería el cambio equivocado.
         */
        ?>
        <div class="min-w-0 md:flex-1">
            <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-texto-2">
                <?= e($vista->t('mon.memoria_titulo')) ?>
            </h3>
            <p class="mt-1 text-xs text-texto-2">
                <?= e($vista->t('mon.memoria_pie', (string) $cadencia, (string) $ventanaMin)) ?>
            </p>
        </div>

        <div class="text-right">
            <p class="tabular text-2xl font-semibold leading-none text-texto" data-memoria-actual>
                <?= e($mb($actual)) ?>
            </p>
            <p class="tabular mt-1 text-xs text-texto-2">
                <span data-memoria-pct><?= e(number_format($pct, 1, ',', '')) ?></span> %
                <?= e($vista->t('mon.mem_de_total', $mb($total))) ?>
            </p>
            <?php
            /*
             * Las TRES pills se pintan aquí, con su icono y su etiqueta ya
             * traducidos, y el guion solo enseña la que corresponde a la última
             * lectura. Fabricarlas en JavaScript obligaría a llevar allí los
             * iconos y los textos de los tres estados, y bastaría con olvidar
             * uno para que el color cambiara y la palabra no.
             */
            ?>
            <div class="mt-2 flex justify-end" data-memoria-estado>
                <?php foreach ([
                    'ok'   => $vista->t('mon.mem_normal'),
                    'warn' => $vista->t('mon.mem_sobre_aceptacion'),
                    'bad'  => $vista->t('mon.mem_sobre_peligro'),
                ] as $tono => $etiqueta): ?>
                    <span data-memoria-luz="<?= e($tono) ?>" <?= $tono === $luz ? '' : 'hidden' ?>>
                        <?= pill($tono, $etiqueta) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="font-sans tabular">
        <?php
        /*
         * La plantilla del resumen viaja con sus huecos sin rellenar para que el
         * guion pueda rehacerlo en cada refresco. Un aria-label que se queda
         * clavado en la primera lectura es peor que no ponerlo: afirma un dato
         * que dejó de ser cierto.
         */
        ?>
        <svg viewBox="0 0 <?= $ancho ?> <?= $alto ?>" width="100%" height="auto"
             role="img" aria-label="<?= e($resumen) ?>" data-memoria-svg
             data-plantilla-resumen="<?= e($vista->t('mon.mem_resumen', '{usado}', $mb($total), '{pct}')) ?>">

            <defs>
                <?php
                /*
                 * Relleno bajo la serie: el mismo verde del trazo, desvanecido.
                 * No es una segunda serie ni un segundo color categórico — solo
                 * da cuerpo a la lectura y se apaga hacia abajo para no competir
                 * con la rejilla ni con las líneas de umbral.
                 */
                ?>
                <linearGradient id="rv-area-memoria" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="rgb(var(--rv-primary))" stop-opacity="0.30"></stop>
                    <stop offset="100%" stop-color="rgb(var(--rv-primary))" stop-opacity="0"></stop>
                </linearGradient>
            </defs>

            <?php /* Rejilla y eje vertical en megabytes. */ ?>
            <?php foreach ($marcas as $marca): ?>
                <?php $ym = $y($marca); ?>
                <line x1="<?= $izq ?>" y1="<?= sprintf('%.2f', $ym) ?>"
                      x2="<?= $izq + $anchoPlot ?>" y2="<?= sprintf('%.2f', $ym) ?>"
                      stroke="rgb(var(--rv-border))" stroke-width="1"></line>
                <text x="<?= $izq - 8 ?>" y="<?= sprintf('%.2f', $ym + 3.5) ?>"
                      text-anchor="end" font-size="11"
                      fill="rgb(var(--rv-text-2))"><?= e(number_format($marca, 0, ',', ' ')) ?></text>
            <?php endforeach; ?>

            <?php
            /*
             * Los dos umbrales. Discontinuos para separarlos de la serie, que es
             * continua, y ROTULADOS sobre el propio dibujo: una línea de color
             * sin nombre obliga a mirar una leyenda aparte justo cuando hay
             * prisa, y desaparece del todo impresa en gris.
             */
            ?>
            <?php foreach ([
                ['valor' => $aceptacion, 'token' => '--rv-warn', 'clave' => 'mon.umbral_aceptacion', 'pct' => $memoria['umbral_aceptacion_pct']],
                ['valor' => $peligro,    'token' => '--rv-bad',  'clave' => 'mon.umbral_peligro',    'pct' => $memoria['umbral_peligro_pct']],
            ] as $umbral): ?>
                <?php $yu = $y((float) $umbral['valor']); ?>
                <line x1="<?= $izq ?>" y1="<?= sprintf('%.2f', $yu) ?>"
                      x2="<?= $izq + $anchoPlot ?>" y2="<?= sprintf('%.2f', $yu) ?>"
                      stroke="rgb(var(<?= $umbral['token'] ?>))" stroke-width="1.5"
                      stroke-dasharray="7 5"></line>
                <?php
                /*
                 * El rótulo se ancla a la IZQUIERDA. A la derecha es donde
                 * termina la serie, así que ahí es donde el texto y la línea de
                 * datos se pisan justo cuando el valor se acerca al umbral —
                 * que es el único momento en que hace falta leer los dos.
                 */
                ?>
                <text x="<?= $izq + 6 ?>" y="<?= sprintf('%.2f', $yu - 5) ?>"
                      text-anchor="start" font-size="11" font-weight="600"
                      fill="rgb(var(<?= $umbral['token'] ?>))"><?= e($vista->t(
                          $umbral['clave'],
                          number_format((float) $umbral['pct'], 0, ',', ''),
                          $mb((float) $umbral['valor']),
                      )) ?></text>
            <?php endforeach; ?>

            <?php /* La serie: área y trazo. Los repinta el guion en cada refresco. */ ?>
            <path d="<?= e($area) ?>" fill="url(#rv-area-memoria)" data-memoria-area></path>
            <polyline fill="none" stroke="rgb(var(--rv-primary))" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round"
                      points="<?= e(implode(' ', $coordenadas)) ?>" data-memoria-linea></polyline>

            <?php
            /*
             * Solo la lectura más reciente lleva marca. Cuarenta puntos con su
             * círculo convierten la serie en una hilera de cuentas y esconden la
             * forma, que es lo único que se busca en un gráfico de vigilancia.
             */
            ?>
            <circle cx="<?= sprintf('%.2f', $x($puntos - 1)) ?>" cy="<?= sprintf('%.2f', $y($actual)) ?>" r="4"
                    fill="rgb(var(--rv-primary))" stroke="rgb(var(--rv-surface))" stroke-width="2"
                    data-memoria-punta></circle>

            <?php /* Eje horizontal: tiempo activo de la instancia. */ ?>
            <?php
            $marcasTiempo = [0, (int) floor(($puntos - 1) / 2), $puntos - 1];
            ?>
            <?php foreach ($marcasTiempo as $i): ?>
                <?php $minutos = $uptimeFinal - (int) round(($puntos - 1 - $i) * $cadencia / 60); ?>
                <text x="<?= sprintf('%.2f', $x($i)) ?>" y="<?= $base + 18 ?>"
                      text-anchor="<?= $i === 0 ? 'start' : ($i === $puntos - 1 ? 'end' : 'middle') ?>"
                      font-size="11" fill="rgb(var(--rv-text-2))"
                      data-memoria-tiempo="<?= e((string) $i) ?>"><?= e($uptime($minutos)) ?></text>
            <?php endforeach; ?>

            <text x="<?= $izq + $anchoPlot / 2 ?>" y="<?= $alto - 4 ?>"
                  text-anchor="middle" font-size="11" fill="rgb(var(--rv-text-2))">
                <?= e($vista->t('mon.eje_uptime')) ?>
            </text>
        </svg>
    </div>

    <p class="mt-2 max-w-[95ch] text-xs leading-relaxed text-texto-2">
        <?= e($vista->t('mon.memoria_nota')) ?>
    </p>
</div>
