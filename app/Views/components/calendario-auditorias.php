<?php

declare(strict_types=1);

/**
 * Las auditorías del mes, en cuadrícula de calendario.
 *
 * Responde una pregunta que ni la tabla ni el gráfico responden: CUÁNDO se
 * trabajó. En una lista ordenada por fecha, tres auditorías del mismo martes y
 * tres repartidas por el mes se ven igual; aquí no.
 *
 * SIN JAVASCRIPT. Los dos meses vecinos son enlaces —cada mes tiene su URL, se
 * comparte y se abre en otra pestaña— y el detalle de un día se consulta con el
 * título emergente nativo, igual que en los gráficos del producto.
 *
 * EL DÍA CON TRABAJO NO SE DISTINGUE SOLO POR COLOR. Lleva el fondo teñido, sí,
 * pero también el número en negrita y un contador de auditorías debajo; en gris
 * o con daltonismo la casilla sigue diciendo lo mismo. Es la regla de la casa
 * (§5.5) aplicada a una cuadrícula.
 *
 * La semana empieza en LUNES, como en el calendario que usa toda la región, y
 * no en domingo: `date('N')` da 1 para el lunes, que es justo el desplazamiento
 * que hace falta.
 *
 * @var \App\Core\Vista $vista
 * @var string $mes                Mes que se pinta, YYYY-MM.
 * @var array<int, list<\App\Models\Entidades\Auditoria>> $porDia
 * @var string $mesAnterior
 * @var string $mesSiguiente
 * @var string $rutaBaseMes        A dónde apuntan las flechas, sin el ?mes=.
 */
$porDia = $porDia ?? [];

$primero = (int) strtotime($mes . '-01');
$dias    = (int) date('t', $primero);

// Lunes = 0. `N` da 1..7 con el lunes en 1, así que restar uno deja el hueco
// exacto que hay que dejar en blanco antes del día 1.
$desplazamiento = (int) date('N', $primero) - 1;

/*
 * El nombre del mes se arma con claves traducidas y no con `strftime` ni con
 * `IntlDateFormatter`: el primero está obsoleto y el segundo pide la extensión
 * intl, que esta imagen no compila. Doce claves por idioma es más aburrido y
 * funciona en cualquier PHP.
 */
$nombreMes = $vista->t('cal.mes_' . date('n', $primero)) . ' ' . date('Y', $primero);

/*
 * Cuántas semanas ocupa el mes en la cuadrícula: los huecos de delante más los
 * días, en filas de siete. Sale 4 (un febrero de 28 días que empieza en lunes),
 * 5 o 6, y hace falta saberlo para repartir la altura sobrante entre las filas
 * que de verdad hay — con `grid-rows-6` fijo, un mes de cinco semanas dejaría
 * una franja vacía al pie.
 *
 * La clase se elige de una lista cerrada y no se compone con concatenación:
 * Tailwind entra por CDN y genera el CSS de las clases que encuentra en el DOM,
 * así que tienen que aparecer escritas enteras en alguna parte.
 */
$filas = (int) ceil(($desplazamiento + $dias) / 7);

$filasClase = match ($filas) {
    4       => 'grid-rows-4',
    5       => 'grid-rows-5',
    default => 'grid-rows-6',
};

$hoy = date('Y-m-d');

$enlaceMes = static fn (string $destino): string =>
    $rutaBaseMes . '?mes=' . rawurlencode($destino);
?>
<?php
/*
 * `h-full` y columna flexible: la tarjeta ocupa toda la altura que le da la
 * fila —la manda la ficha del usuario, que es más alta— y dentro solo hay UNA
 * pieza elástica, la cuadrícula de días. La cabecera y el resumen del pie miden
 * su contenido; lo que sobre se lo reparten las semanas.
 */
?>
<div class="rv-extruido flex h-full flex-col rounded-rv-lg border border-borde bg-superficie p-6">

    <header class="mb-4 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-texto">
            <?= e($vista->t('perfil.calendario')) ?>
        </h2>

        <?php
        /*
         * Las dos flechas y el mes, juntos: son un mando, no tres cosas. El
         * rótulo va en medio porque es lo que cambia al pulsar cualquiera de
         * las dos, y con el mes a un lado no se sabría cuál de las dos lo mueve.
         */
        ?>
        <div class="flex items-center gap-1">
            <a href="<?= e($enlaceMes($mesAnterior)) ?>"
               class="rv-lateral-enlace grid h-8 w-8 place-items-center rounded-rv text-texto-2"
               aria-label="<?= e($vista->t('cal.mes_anterior')) ?>">
                <span class="rotate-180"><?= icono('chevron', 'h-4 w-4') ?></span>
            </a>
            <span class="min-w-[8.5rem] text-center text-sm font-semibold text-texto"><?= e($nombreMes) ?></span>
            <a href="<?= e($enlaceMes($mesSiguiente)) ?>"
               class="rv-lateral-enlace grid h-8 w-8 place-items-center rounded-rv text-texto-2"
               aria-label="<?= e($vista->t('cal.mes_siguiente')) ?>">
                <?= icono('chevron', 'h-4 w-4') ?>
            </a>
        </div>
    </header>

    <?php /* Cabecera de días. aria-hidden: cada casilla ya dice su fecha entera. */ ?>
    <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-semibold uppercase tracking-wide text-texto-2"
         aria-hidden="true">
        <?php foreach ([1, 2, 3, 4, 5, 6, 7] as $n): ?>
            <span class="py-1"><?= e($vista->t('cal.dia_' . $n)) ?></span>
        <?php endforeach; ?>
    </div>

    <div class="mt-1.5 grid flex-1 grid-cols-7 gap-1.5 <?= e($filasClase) ?>">

        <?php /* Los huecos antes del día 1. Sin contenido y fuera del árbol. */ ?>
        <?php for ($i = 0; $i < $desplazamiento; $i++): ?>
            <span aria-hidden="true"></span>
        <?php endfor; ?>

        <?php for ($dia = 1; $dia <= $dias; $dia++): ?>
            <?php
            $delDia = $porDia[$dia] ?? [];
            $fecha  = $mes . '-' . str_pad((string) $dia, 2, '0', STR_PAD_LEFT);
            $esHoy  = $fecha === $hoy;

            /*
             * El emergente lleva los NOMBRES, que es lo que se viene a buscar
             * al pasar por encima de un día. Se recortan a cinco: un día con
             * doce auditorías daría un tooltip más alto que el calendario, y a
             * partir de ahí la tabla de abajo responde mejor.
             */
            $emergente = '';

            if ($delDia !== []) {
                $nombres = array_map(
                    static fn ($a): string => $vista->t('eval.auditoria_n', (string) $a->id) . ' · ' . $a->organizacion,
                    array_slice($delDia, 0, 5),
                );

                if (count($delDia) > 5) {
                    $nombres[] = $vista->t('perfil.y_mas', (string) (count($delDia) - 5));
                }

                $emergente = $fecha . "\n" . implode("\n", $nombres);
            }
            ?>
            <?php if ($delDia === []): ?>
                <?php
                /*
                 * Un día sin trabajo no es un botón ni tiene emergente: solo
                 * está para que la cuadrícula sea un mes y no una lista de días
                 * sueltos. Va en tinta secundaria y sin relieve.
                 */
                ?>
                <span class="grid min-h-[2.75rem] place-items-center rounded-rv text-[15px] text-texto-2/70
                             <?= $esHoy ? 'ring-1 ring-primario' : '' ?>">
                    <?= e((string) $dia) ?>
                </span>
            <?php else: ?>
                <?php
                /*
                 * Con trabajo: hundido y teñido —«aquí hay algo» es exactamente
                 * lo que el inset significa en este sistema—, el número en
                 * negrita y el recuento debajo. Tres canales para el mismo dato.
                 *
                 * Es un <span tabindex="0"> y no un <button>: no ejecuta nada, y
                 * un botón anunciaría una acción que no existe. Enfocable para
                 * que el emergente también se alcance con el teclado.
                 */
                ?>
                <span tabindex="0"
                      title="<?= e($emergente) ?>"
                      class="rv-hundido grid min-h-[2.75rem] cursor-help place-items-center rounded-rv bg-primario/10 text-[15px] font-bold text-texto outline-none ring-primario focus-visible:ring-2
                             <?= $esHoy ? 'ring-1 ring-primario' : '' ?>">
                    <span class="leading-none"><?= e((string) $dia) ?></span>
                    <span class="mt-1 text-[11px] font-semibold leading-none text-primario">
                        <?= e((string) count($delDia)) ?>
                    </span>
                </span>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <?php
    /*
     * El resumen del mes, y la lectura alternativa de la cuadrícula: quien no
     * ve el calendario se queda sin saber si el mes tuvo trabajo o no.
     */
    ?>
    <p class="mt-4 shrink-0 border-t border-borde pt-3 text-xs text-texto-2">
        <?= e($porDia === []
            ? $vista->t('perfil.mes_sin_auditorias')
            : $vista->t(
                'perfil.mes_resumen',
                (string) array_sum(array_map('count', $porDia)),
                (string) count($porDia),
            )) ?>
    </p>
</div>
