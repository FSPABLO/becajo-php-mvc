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
        <svg viewBox="0 0 <?= $lado ?> <?= $lado ?>" width="100%" height="auto"
             role="img" aria-label="<?= e($lectura) ?>">

            <?php
            /*
             * Carril. Va en el GRIS NEUTRO y no en el color del borde: sobre
             * el tinte dorado, `--rv-border` da 1,2:1 y el carril se
             * desvanecería del todo; y en cualquier color con carga semántica
             * —verde, ámbar— el tramo vacío se leería como un segundo dato. El
             * carril no es un dato: es la escala.
             *
             * Sin nada que medir el carril adelgaza y pasa a un punteado fino:
             * un anillo GRUESO a trazos no se lee como «vacío», se lee como un
             * engranaje —los trazos de catorce píxeles parecen muescas—, y un
             * anillo grueso y lleno de gris se leería como un valor bajo. El
             * contorno fino dice lo único que hay que decir: aquí iría una
             * medida y no la hay.
             */
            ?>
            <?php if ($publica): ?>
                <circle cx="<?= $centro ?>" cy="<?= $centro ?>" r="<?= $radio ?>"
                        fill="none" stroke="rgb(var(--rv-na) / 0.30)" stroke-width="<?= $grosor ?>"></circle>
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
                        transform="rotate(-90 <?= $centro ?> <?= $centro ?>)"></circle>
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
