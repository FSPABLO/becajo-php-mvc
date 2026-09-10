<?php

declare(strict_types=1);

/**
 * Medidor radial del ISBD.
 *
 * Un anillo con el arco proporcional al índice, la cifra dentro y la banda
 * debajo. Es la única cifra de la pantalla que se lee de lejos, que es lo que
 * pide un panel de operación: el resto del tablero se consulta, esta se ve.
 *
 * ── El arco no es el único canal ─────────────────────────────────────────────
 *
 * El color del arco refuerza, no comunica: debajo va la pill con su icono y su
 * etiqueta, y dentro va la cifra. Un anillo verde a secas no dice nada a quien
 * no distingue el verde del ámbar, y este es justo el elemento que más tentación
 * da de dejar en color puro.
 *
 * ── Las marcas de banda ──────────────────────────────────────────────────────
 *
 * Sobre el anillo van cuatro muescas en 40, 60, 75 y 90: las fronteras del §5.2.
 * Sin ellas el arco solo dice «más o menos lleno», y con ellas dice en qué banda
 * cae y cuánto falta para la siguiente — que es la pregunta real de quien mira.
 * Van en tinta secundaria y no en el color de cada banda: cuatro muescas de
 * colores distintos convertirían el marco en el dato.
 *
 * ── El anillo tiene fondo ──────────────────────────────────────────
 *
 * El carril va HUNDIDO y el arco EXTRUIDO: un canal labrado en el pergamino con
 * el índice apoyado dentro. No es adorno —es la misma pareja de gestos con la
 * que el producto separa lo que recibe algo de lo que se levanta, y un carril es
 * literalmente la pista de una barra de progreso—, y de paso separa la escala
 * del dato por una vía más que el color: el fondo se hunde, la medida sobresale.
 * Los filtros están abajo, junto al dibujo.
 *
 * ── Sin índice publicado ─────────────────────────────────────────────────────
 *
 * Si la muestra no publica ISBD (cobertura bajo el piso, o instancia caída) el
 * anillo va a trazos y vacío, y dentro va un guion. NO se dibuja un arco corto:
 * un anillo casi vacío se lee como «está muy mal», y lo que pasa es que no se
 * sabe. Son cosas distintas y el dibujo tiene que distinguirlas.
 *
 * @var \App\Core\Vista $vista
 * @var float|null $isbd
 * @var string|null $banda
 * @var string $luz      Luz del conjunto: 'rojo' | 'amarillo' | 'verde' | 'na'.
 * @var string $motivo   Texto del estado cuando no hay índice.
 * @var \Closure(?float): string $cifra
 * @var \Closure(?string): string $etiquetaBanda
 */
$publica = $isbd !== null;

// Geometría del anillo, en unidades de usuario del viewBox.
$lado   = 240;
$centro = $lado / 2;
$radio  = 96;
$grosor = 14;

$circunferencia = 2 * M_PI * $radio;
$arco = $publica ? $circunferencia * max(0.0, min(100.0, $isbd)) / 100 : 0.0;

/*
 * DOS lecturas del mismo índice, y cada una tiene su sitio:
 *
 * - la PILL lleva la banda exacta del §5.2 (Crítico, Degradado, …), que es lo
 *   que dicen las alertas y la explicación del tope;
 * - el ARCO lleva la luz del CONJUNTO, que no se calcula sobre el ISBD sino
 *   sobre sus tres índices: rojo si alguno está en rojo, verde solo si los
 *   tres están en verde.
 *
 * No pueden contradecirse —el semáforo agrupa las mismas bandas y la regla del
 * eslabón más débil ya impide que el ISBD quede en verde con un componente en
 * rojo—, pero el arco se tiñe por la regla del conjunto A PROPÓSITO: si mañana
 * se tocaran los topes, el color seguiría obedeciendo «un índice en rojo pinta
 * todo de rojo» sin depender de que otra regla lo garantice de rebote.
 */
$tono = $publica ? tonoBanda((string) $banda) : 'na';

$variable = match (tonoSemaforo($luz)) {
    'ok'    => '--rv-ok',
    'warn'  => '--rv-warn',
    'bad'   => '--rv-bad',
    default => '--rv-na',
};

// Punto de una muesca sobre el anillo, en el ángulo del valor v.
$muesca = static function (float $v) use ($centro, $radio, $grosor): array {
    $angulo = deg2rad(-90 + 360 * $v / 100);
    $dentro = $radio - $grosor / 2 - 1;
    $fuera  = $radio + $grosor / 2 + 1;

    return [
        $centro + $dentro * cos($angulo), $centro + $dentro * sin($angulo),
        $centro + $fuera * cos($angulo),  $centro + $fuera * sin($angulo),
    ];
};

$lectura = $publica
    ? $vista->t('mon.medidor_lectura', $cifra($isbd), $etiquetaBanda((string) $banda))
    : $motivo;
?>
<div class="flex flex-col items-center">
    <div class="relative w-full max-w-[15rem]">
        <?php
        /*
         * `rv-relieve-pleno`: el nivel alto de las mismas ternas --nm-*, el que
         * ya llevan la tarjeta y el botón de «nueva auditoría». En el nivel de
         * por defecto —pensado para una tarjeta de 18 px de desenfoque— un
         * anillo de catorce de grueso apenas insinúa el surco: la pieza es
         * demasiado pequeña para que una sombra tan tenue se acumule. Y esta es
         * la cifra que se lee de lejos; si algo de la pantalla merece el
         * relieve alto, es esta.
         */
        ?>
        <svg viewBox="0 0 <?= $lado ?> <?= $lado ?>" width="100%" height="auto"
             class="rv-relieve-pleno"
             role="img" aria-label="<?= e($lectura) ?>">

            <?php if ($publica): ?>
                <?php
                /*
                 * ── EL RELIEVE DEL MEDIDOR ──────────────────────────────────
                 *
                 * El carril se HUNDE y el arco se EXTRUYE, que es la misma
                 * pareja de gestos que el producto usa fuera del SVG: el
                 * hundido es lo que recibe algo —campos, franja de mensajes y,
                 * literalmente, las pistas de barras de progreso, que es lo que
                 * un carril es— y el extruido es la pieza que se levanta. Aquí
                 * eso cuenta una escena: un canal labrado en el pergamino y,
                 * dentro, el arco del índice como una pieza apoyada en él.
                 *
                 * box-shadow no entra en un SVG, así que va con filtros; los
                 * valores salen de las ternas --nm-* vía .rv-nm-sombra y
                 * .rv-nm-realce (ver «Relieve dentro de un SVG» en
                 * rivendel.css), de modo que un cambio de paleta o de nivel de
                 * relieve llega hasta aquí sin tocar este archivo —y en la
                 * región oscura el par se invierte solo—.
                 *
                 * Los desplazamientos van en unidades del viewBox y son CORTOS:
                 * los 7 px de una tarjeta, sobre un anillo de catorce de grueso,
                 * no son relieve sino un borón.
                 */
                ?>
                <defs>
                    <?php
                    /*
                     * Hundido. La receta de `inset` en SVG: se desplaza el alfa
                     * de la propia figura, se difumina y se resta de ella; lo
                     * que queda es una banda pegada al borde CONTRARIO al
                     * desplazamiento, que es justo donde cae la sombra dentro de
                     * un surco. Con la sombra bajando a la derecha, la banda
                     * oscura sale arriba-izquierda y el realce abajo-derecha:
                     * el mismo orden que `inset 5px 5px sombra, inset -4px -4px
                     * realce` de .rv-hundido.
                     *
                     * SourceGraphic entra el PRIMERO en la mezcla porque las dos
                     * bandas van encima del propio carril; sin él se pintarían
                     * las sombras y no la pieza.
                     */
                    ?>
                    <filter id="rv-nm-carril-isbd" filterUnits="userSpaceOnUse"
                            x="0" y="0" width="<?= $lado ?>" height="<?= $lado ?>"
                            color-interpolation-filters="sRGB">
                        <feOffset in="SourceAlpha" dx="3.6" dy="3.6" result="cae"></feOffset>
                        <feGaussianBlur in="cae" stdDeviation="3" result="cae-difusa"></feGaussianBlur>
                        <feComposite in="SourceAlpha" in2="cae-difusa" operator="out" result="canto-alto"></feComposite>
                        <feFlood class="rv-nm-sombra" result="tinta-sombra"></feFlood>
                        <feComposite in="tinta-sombra" in2="canto-alto" operator="in" result="sombra"></feComposite>

                        <feOffset in="SourceAlpha" dx="-3" dy="-3" result="sube"></feOffset>
                        <feGaussianBlur in="sube" stdDeviation="2.4" result="sube-difusa"></feGaussianBlur>
                        <feComposite in="SourceAlpha" in2="sube-difusa" operator="out" result="canto-bajo"></feComposite>
                        <feFlood class="rv-nm-realce" result="tinta-realce"></feFlood>
                        <feComposite in="tinta-realce" in2="canto-bajo" operator="in" result="realce"></feComposite>

                        <feMerge>
                            <feMergeNode in="SourceGraphic"></feMergeNode>
                            <feMergeNode in="sombra"></feMergeNode>
                            <feMergeNode in="realce"></feMergeNode>
                        </feMerge>
                    </filter>

                    <?php
                    /*
                     * Extruido, el mismo par que ya levanta las marcas de los
                     * otros gráficos. feDropShadow devuelve la sombra CON su
                     * figura encima, por eso la mezcla no repite SourceGraphic.
                     *
                     * El desplazamiento es uniforme y no direccional: en una
                     * silueta cerrada, la sombra larga de una tarjeta despega la
                     * pieza por un lado y la entierra por el otro.
                     *
                     * Y es algo más CORTO que el del surco a propósito: el arco
                     * se apoya dentro del canal, no flota sobre él. Subirle el
                     * desenfoque hasta igualarlo lo despega —y en la región
                     * oscura, donde el realce es blanco al 5 %, la sombra clara
                     * deja de leerse como canto y pasa a leerse como halo—.
                     */
                    ?>
                    <filter id="rv-nm-arco-isbd" filterUnits="userSpaceOnUse"
                            x="0" y="0" width="<?= $lado ?>" height="<?= $lado ?>"
                            color-interpolation-filters="sRGB">
                        <feDropShadow in="SourceGraphic" dx="3" dy="3" stdDeviation="2.6"
                                      class="rv-nm-sombra" result="sombra"></feDropShadow>
                        <feDropShadow in="SourceGraphic" dx="-2" dy="-2" stdDeviation="2"
                                      class="rv-nm-realce" result="realce"></feDropShadow>
                        <feMerge>
                            <feMergeNode in="sombra"></feMergeNode>
                            <feMergeNode in="realce"></feMergeNode>
                        </feMerge>
                    </filter>
                </defs>
            <?php endif; ?>

            <?php
            /*
             * Carril. Va en el GRIS NEUTRO y no en el color del borde: sobre
             * el tinte dorado, `--rv-border` da 1,2:1 y el carril se
             * desvanecería del todo; y en cualquier color con carga semántica
             * —verde, ámbar— el tramo vacío se leería como un segundo dato. El
             * carril no es un dato: es la escala.
             *
             * Su gris es MENOS denso que el de antes porque ya no está solo:
             * ahora lleva el surco. Un carril tintado a fondo y además labrado
             * suma dos señales para decir lo mismo y sale sucio; el tinte
             * marca por dónde va la escala y el relieve la hunde.
             *
             * Sin nada que medir el carril adelgaza y pasa a un punteado fino:
             * un anillo GRUESO a trazos no se lee como «vacío», se lee como un
             * engranaje —los trazos de catorce píxeles parecen muescas—, y un
             * anillo grueso y lleno de gris se leería como un valor bajo. El
             * contorno fino dice lo único que hay que decir: aquí iría una
             * medida y no la hay.
             *
             * Y va PLANO, sin surco: el relieve extruye una superficie, y labrar
             * un canal donde no hay nada que alojar es prometer una pieza que
             * falta. Sin dato tampoco hay relieve.
             */
            ?>
            <?php if ($publica): ?>
                <circle cx="<?= $centro ?>" cy="<?= $centro ?>" r="<?= $radio ?>"
                        fill="none" stroke="rgb(var(--rv-na) / 0.22)" stroke-width="<?= $grosor ?>"
                        filter="url(#rv-nm-carril-isbd)"></circle>
            <?php else: ?>
                <circle cx="<?= $centro ?>" cy="<?= $centro ?>" r="<?= $radio ?>"
                        fill="none" stroke="rgb(var(--rv-na) / 0.55)" stroke-width="2"
                        stroke-dasharray="4 7"></circle>
            <?php endif; ?>

            <?php if ($publica): ?>
                <?php
                /*
                 * El arco arranca a las 12 (de ahí el giro de -90°) y avanza en
                 * el sentido del reloj. stroke-linecap redondeado solo en la
                 * punta del dato: el origen queda a escuadra porque el cero no
                 * es un valor que se haya medido, es donde empieza la escala.
                 */
                ?>
                <circle cx="<?= $centro ?>" cy="<?= $centro ?>" r="<?= $radio ?>"
                        fill="none" stroke="rgb(var(<?= $variable ?>))" stroke-width="<?= $grosor ?>"
                        stroke-linecap="round"
                        stroke-dasharray="<?= sprintf('%.2f %.2f', $arco, $circunferencia - $arco) ?>"
                        transform="rotate(-90 <?= $centro ?> <?= $centro ?>)"
                        filter="url(#rv-nm-arco-isbd)"></circle>
            <?php endif; ?>

            <?php
            /*
             * Muescas de las cuatro fronteras de banda. Se dibujan DESPUÉS del
             * arco para que crucen también la parte llena: si solo se vieran
             * sobre el carril, desaparecerían justo en la zona donde hay que
             * leer en qué banda cayó el índice.
             *
             * Van en la tinta secundaria —no en el color de cada banda—: cuatro
             * muescas de colores distintos convertirían el marco en el dato.
             */
            ?>
            <?php foreach ($publica ? [40, 60, 75, 90] : [] as $frontera): ?>
                <?php [$x1, $y1, $x2, $y2] = $muesca((float) $frontera); ?>
                <line x1="<?= sprintf('%.2f', $x1) ?>" y1="<?= sprintf('%.2f', $y1) ?>"
                      x2="<?= sprintf('%.2f', $x2) ?>" y2="<?= sprintf('%.2f', $y2) ?>"
                      stroke="rgb(var(--rv-text-2) / 0.55)" stroke-width="2"></line>
            <?php endforeach; ?>
        </svg>

        <?php
        /*
         * La cifra va en HTML sobre el SVG y no como <text>: así hereda la
         * tipografía, el token de color y la clase tabular del resto del
         * producto, sin repetir tamaños dentro del dibujo.
         */
        ?>
        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
            <span class="tabular text-5xl font-semibold leading-none <?= $publica ? 'text-texto' : 'text-na' ?>">
                <?= e($publica ? $cifra($isbd) : '—') ?>
            </span>
            <span class="mt-1.5 text-xs font-semibold uppercase tracking-widest text-texto-2">
                <?= e($vista->t('mon.isbd')) ?>
            </span>
        </div>
    </div>

    <div class="mt-4">
        <?= $publica
            ? pill($tono, $etiquetaBanda((string) $banda))
            : pill('na', $motivo) ?>
    </div>
</div>
