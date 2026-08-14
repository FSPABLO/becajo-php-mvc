<?php

declare(strict_types=1);

/**
 * Perfil de madurez por dominio de UNA auditoría — el gráfico de araña.
 *
 * Responde una pregunta que ni las columnas ni la tabla contestan de un vistazo:
 * en qué está fuerte y en qué está floja esta organización. El índice general la
 * resume en una cifra y la tabla la abre en veinte celdas; entre las dos falta
 * la SILUETA, que es lo único que se lee sin contar. Una muesca en el polígono
 * es un dominio rezagado, y se ve antes de leer ningún número.
 *
 * UNA SOLA AUDITORÍA, y no la última contra la anterior. Rivendel no tiene un
 * segundo tono categórico —el oro es referencia normativa y la escala de estado
 * es estado—, así que dos siluetas superpuestas tendrían que distinguirse por
 * forma, y en un radar la forma ES el dato: no queda ningún canal libre. La
 * comparación en el tiempo la hacen las columnas de al lado y la tabla de abajo,
 * que para eso están.
 *
 * SOLO ENTRAN LOS DOMINIOS QUE ESA AUDITORÍA EVALUÓ. Un dominio sin madurez
 * calculada no es un cero: es una pregunta sin hacer, y clavarlo en el centro
 * dibujaría una muesca que nadie encontró. Se queda fuera del polígono y el pie
 * dice cuántos ejes hay, que es la forma honesta de contarlo.
 *
 * Sin JavaScript: viewBox fijo y el SVG escala solo.
 *
 * @var \App\Core\Vista $vista
 * @var list<array{dominio: string, madurez: float}> $ejes
 * @var string $fecha  Fecha de la auditoría que describe el perfil.
 * @var string $id     Sufijo de los ids de <defs>; la página pinta un radar por
 *                     organización y dos filtros homónimos se pisan.
 */

// Escala de madurez del instrumento: 0 «Inexistente» a 5 «Optimizado».
$niveles = 5;

$total = count($ejes);

$cifra = static fn (float $v): string => number_format($v, 2, ',', '');
?>
<?php if ($total < 3): ?>
    <?php
    /*
     * Con dos ejes el polígono es un segmento y con uno, un punto: ninguno de
     * los dos es un perfil, y dibujarlo prometería una lectura que no puede
     * dar. Se dice qué falta en vez de degradar el gráfico.
     */
    ?>
    <p class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-4 py-10 text-center text-sm text-texto-2">
        <?= e($vista->t('eval.radar_insuficiente', (string) $total)) ?>
    </p>
<?php else: ?>
    <?php
    /* Geometría, en unidades del viewBox. */
    $ancho = 380;
    $alto  = 300;
    $cx    = 190.0;
    $cy    = 150.0;
    $radio = 86.0;

    // Los rótulos viven fuera de la telaraña; 18 unidades de aire bastan para
    // que ninguno toque el anillo exterior.
    $radioEtiqueta = $radio + 18;

    /*
     * Primer vértice ARRIBA y giro en sentido horario. Es el reparto que espera
     * cualquiera que haya visto un radar, y deja el eje vertical libre para
     * colgar de él la escala.
     */
    $angulo = static fn (int $i): float => -M_PI / 2 + (2 * M_PI * $i) / $total;

    /** Punto de un eje a una fracción 0-1 del radio. */
    $punto = static function (int $i, float $fraccion) use ($angulo, $cx, $cy, $radio): array {
        $a = $angulo($i);

        return [$cx + cos($a) * $radio * $fraccion, $cy + sin($a) * $radio * $fraccion];
    };

    /** @param list<array{0: float, 1: float}> $puntos */
    $trazar = static fn (array $puntos): string => implode(' ', array_map(
        static fn (array $p): string => sprintf('%.2f,%.2f', $p[0], $p[1]),
        $puntos,
    ));

    // Vértices del dato, con la madurez llevada a fracción del radio.
    $vertices = [];

    foreach ($ejes as $i => $eje) {
        $fraccion = max(0.0, min(1.0, $eje['madurez'] / $niveles));
        $vertices[] = [...$punto($i, $fraccion), 'v' => $eje['madurez'], 'dominio' => $eje['dominio']];
    }

    $poligonoDato = $trazar(array_map(static fn (array $p): array => [$p[0], $p[1]], $vertices));

    /*
     * Lectura alternativa al dibujo: el mismo perfil dicho en palabras, dominio
     * por dominio. Es lo que oye quien navega con lector de pantalla, así que
     * lleva las cifras completas y no un resumen.
     */
    $resumenLectura = $vista->t(
        'eval.radar_resumen',
        $fecha,
        implode('; ', array_map(
            static fn (array $e): string => $e['dominio'] . ' ' . $cifra($e['madurez']),
            $ejes,
        )),
    );
    ?>
    <div class="font-sans tabular">
        <svg viewBox="0 0 <?= $ancho ?> <?= $alto ?>" width="100%" height="auto"
             role="img" aria-label="<?= e($resumenLectura) ?>">

            <?php
            /*
             * Mismo relieve que el resto del producto, dibujado con filtros
             * porque box-shadow no entra en un SVG. Los valores salen de las
             * ternas --nm-* vía .rv-nm-sombra / .rv-nm-realce (ver «Relieve
             * dentro de un SVG» en rivendel.css), así que un cambio de paleta
             * o de nivel de relieve llega hasta aquí sin tocar el archivo.
             *
             * El desplazamiento es corto —2 unidades sobre un radio de 86— y
             * uniforme: una silueta cerrada con la sombra larga de una tarjeta
             * se despegaría del centro por un lado y se hundiría por el otro.
             */
            ?>
            <defs>
                <filter id="rv-nm-radar-<?= e($id) ?>" filterUnits="userSpaceOnUse"
                        x="0" y="0" width="<?= $ancho ?>" height="<?= $alto ?>"
                        color-interpolation-filters="sRGB">
                    <feDropShadow in="SourceGraphic" dx="2.4" dy="2.4" stdDeviation="2.4"
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
             * Telaraña: un anillo por nivel de madurez, de modo que contar
             * anillos sea leer la escala. Recesiva —del color del borde— para
             * que el polígono del dato se lea encima sin esfuerzo.
             */
            ?>
            <?php for ($k = 1; $k <= $niveles; $k++): ?>
                <?php
                $anillo = [];

                for ($i = 0; $i < $total; $i++) {
                    $anillo[] = $punto($i, $k / $niveles);
                }
                ?>
                <polygon points="<?= e($trazar($anillo)) ?>"
                         fill="none" stroke="rgb(var(--rv-border))"
                         stroke-width="<?= $k === $niveles ? '1.4' : '1' ?>"></polygon>
            <?php endfor; ?>

            <?php /* Radios: uno por dominio, del centro al anillo exterior. */ ?>
            <?php for ($i = 0; $i < $total; $i++): ?>
                <?php [$x, $yFin] = $punto($i, 1.0); ?>
                <line x1="<?= sprintf('%.2f', $cx) ?>" y1="<?= sprintf('%.2f', $cy) ?>"
                      x2="<?= sprintf('%.2f', $x) ?>" y2="<?= sprintf('%.2f', $yFin) ?>"
                      stroke="rgb(var(--rv-border))" stroke-width="1"></line>
            <?php endfor; ?>

            <?php /* El perfil: silueta y vértices, una sola pieza levantada. */ ?>
            <g filter="url(#rv-nm-radar-<?= e($id) ?>)">
                <polygon points="<?= e($poligonoDato) ?>"
                         fill="rgb(var(--rv-primary) / 0.22)"
                         stroke="rgb(var(--rv-primary))" stroke-width="2.5"
                         stroke-linejoin="round"></polygon>

                <?php foreach ($vertices as $vertice): ?>
                    <?php
                    /*
                     * Anillo del color de la superficie: cuando un vértice cae
                     * sobre un radio o sobre un anillo de la telaraña, el
                     * contorno lo despega en vez de fundirlo con ellos.
                     */
                    ?>
                    <circle cx="<?= sprintf('%.2f', $vertice[0]) ?>" cy="<?= sprintf('%.2f', $vertice[1]) ?>"
                            r="3.5" fill="rgb(var(--rv-primary))"
                            stroke="rgb(var(--rv-surface))" stroke-width="1.5">
                        <title><?= e($vista->t(
                            'eval.radar_vertice',
                            $vertice['dominio'],
                            $cifra($vertice['v']),
                            (string) $niveles,
                        )) ?></title>
                    </circle>
                <?php endforeach; ?>
            </g>

            <?php
            /*
             * Escala colgada del eje vertical, que es el único que sube recto.
             * Sin ella la telaraña son anillos sin unidad y el polígono no se
             * puede leer, solo comparar consigo mismo.
             *
             * Va DESPUÉS del perfil y con un halo del color de la superficie
             * (paint-order dibuja el contorno antes que el relleno, así que el
             * halo queda por debajo de la cifra y no la engorda). Debajo del
             * polígono, el nivel que cruzaba el trazo desaparecía: en la
             * primera prueba el «4» se lo comió el contorno del perfil, que es
             * justo el nivel que hacía falta leer.
             */
            ?>
            <?php for ($k = 1; $k <= $niveles; $k++): ?>
                <text x="<?= sprintf('%.2f', $cx - 6) ?>"
                      y="<?= sprintf('%.2f', $cy - $radio * $k / $niveles + 3) ?>"
                      text-anchor="end" font-size="8.5"
                      paint-order="stroke" stroke="rgb(var(--rv-surface))" stroke-width="2.5"
                      stroke-linejoin="round"
                      fill="rgb(var(--rv-text-2))"><?= $k ?></text>
            <?php endfor; ?>

            <?php
            /*
             * Rótulo de cada dominio, fuera de la telaraña. El anclaje se
             * deriva del ángulo: los de la derecha arrancan en su eje, los de
             * la izquierda terminan en él y los de arriba y abajo se centran.
             * Anclarlos todos al medio dejaría los laterales montados sobre el
             * dibujo.
             */
            ?>
            <?php foreach ($ejes as $i => $eje): ?>
                <?php
                $a   = $angulo($i);
                $cos = cos($a);
                $sen = sin($a);

                $ancla = match (true) {
                    $cos >  0.25 => 'start',
                    $cos < -0.25 => 'end',
                    default      => 'middle',
                };

                // Corrección de línea de base: un rótulo arriba se sube sobre
                // su vértice, uno abajo se baja, y los de los costados se
                // centran contra su propio eje.
                $ajuste = match (true) {
                    $sen < -0.5 => -3.0,
                    $sen >  0.5 => 9.0,
                    default     => 3.5,
                };
                ?>
                <text x="<?= sprintf('%.2f', $cx + $cos * $radioEtiqueta) ?>"
                      y="<?= sprintf('%.2f', $cy + $sen * $radioEtiqueta + $ajuste) ?>"
                      text-anchor="<?= $ancla ?>" font-size="10.5"
                      fill="rgb(var(--rv-text-2))"><?= e($eje['dominio']) ?></text>
            <?php endforeach; ?>
        </svg>
    </div>

    <p class="mt-1 text-center text-xs text-texto-2">
        <?= e($vista->t('eval.radar_pie', (string) $total, (string) $niveles)) ?>
    </p>
<?php endif; ?>
