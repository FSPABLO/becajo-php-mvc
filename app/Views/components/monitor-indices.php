<?php

declare(strict_types=1);

/**
 * Los tres índices que componen el ISBD —IP, IM e IA— como fichas horizontales.
 *
 * Cada ficha es además la PESTAÑA que despliega, debajo, la tabla de procesos
 * que ese índice evalúa. Se hacen las dos cosas con el mismo elemento a
 * propósito: un «ver detalle» aparte obligaría a elegir dos veces —primero el
 * índice, después el enlace— para responder una sola pregunta, «¿por qué este
 * índice está así?».
 *
 * Van en el orden de la fórmula —0,30·IP + 0,35·IM + 0,35·IA— y no en orden
 * alfabético: quien lea la fórmula arriba encuentra los sumandos abajo en la
 * misma secuencia.
 *
 * CONSULTAS **no está aquí**, y esa ausencia es la que comunica el §3.1 del
 * plan: se recolecta, se muestra y alerta, pero no es un sumando del índice.
 *
 * ── El semáforo, y por qué no contradice a la pill ───────────────────────────
 *
 * La franja de color y la barra llevan la luz del semáforo; la pill lleva la
 * banda exacta. No pueden discrepar porque el semáforo AGRUPA las cinco bandas
 * del §5.2 en lugar de competir con ellas (ver semaforo() en funciones.php):
 * verde es siempre Saludable u Óptimo, ámbar es siempre Advertencia y rojo es
 * siempre Degradado o Crítico. El color dice si hay que levantarse; la pill
 * dice exactamente qué pasa.
 *
 * Y el color nunca va solo: pill con icono y etiqueta, más el porcentaje en
 * cifra. Tres canales para el mismo hecho.
 *
 * @var \App\Core\Vista $vista
 * @var list<array<string, mixed>> $indices  Solo los componentes del ISBD.
 * @var array<string, array{columnas: list<string>, filas: list<array<string, mixed>>}> $procesos
 * @var string $activo   Clave del índice cuya tabla se muestra al cargar.
 * @var \Closure(?float): string $cifra
 * @var \Closure(?string): string $etiquetaBanda
 */

// PROCESOS -> IP, MEMORIA -> IM, ARCHIVOS -> IA. La sigla es del modelo del
// equipo, no de una norma: por eso NO va en oro (el oro solo significa
// referencia normativa) sino en la tinta del cuerpo.
$sigla = static fn (string $clave): string => match ($clave) {
    'PROCESOS' => 'IP',
    'MEMORIA'  => 'IM',
    'ARCHIVOS' => 'IA',
    default    => '—',
};

// Clases de relleno por luz. Se escriben literales y no se arman por
// concatenación para que el generador de utilidades las vea en el marcado.
$rellenoLuz = static fn (string $luz): string => match ($luz) {
    'rojo'     => 'bg-bad',
    'amarillo' => 'bg-warn',
    'verde'    => 'bg-ok',
    default    => 'bg-na',
};
?>
<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" role="tablist"
     aria-label="<?= e($vista->t('mon.indices_lista')) ?>">

    <?php foreach ($indices as $comp): ?>
        <?php
        $clave  = (string) $comp['clave'];
        $tiene  = $comp['publicado'] !== null;
        $valor  = $tiene ? (float) $comp['publicado'] : null;
        $luz    = semaforo($tiene ? (string) $comp['banda'] : null);
        $esActivo = $clave === $activo;

        // Sin dato la barra NO se dibuja: una barra a cero diría «cero», y lo
        // que hay es ausencia de medida.
        $ancho   = $tiene ? max(0.0, min(100.0, $valor)) : 0.0;
        $relleno = $rellenoLuz($luz);

        // Las FILAS, no el arreglo del indice: este trae ademas sus columnas.
        $cuantos = count($procesos[$clave]['filas'] ?? []);
        ?>
        <button type="button"
                role="tab"
                id="ficha-<?= e(strtolower($clave)) ?>"
                data-pestana-indice="<?= e($clave) ?>"
                aria-controls="procesos-<?= e(strtolower($clave)) ?>"
                aria-selected="<?= $esActivo ? 'true' : 'false' ?>"
                tabindex="<?= $esActivo ? '0' : '-1' ?>"
                class="group flex h-full flex-col rounded-rv-lg border bg-superficie p-4 text-left transition
                       <?= $esActivo
                           ? 'rv-hundido border-primario'
                           : 'rv-extruido border-borde hover:border-primario-hover' ?>">

            <div class="flex items-center justify-between gap-2">
                <span class="flex items-center gap-2">
                    <?php
                    /*
                     * La luz del semáforo, como franja. Va pegada a la sigla y
                     * no suelta en una esquina: así se lee «IP está en rojo» y
                     * no «hay algo rojo en esta ficha».
                     */
                    ?>
                    <span class="h-4 w-1.5 rounded-full <?= e($relleno) ?>" aria-hidden="true"></span>
                    <span class="text-sm font-bold tracking-wider text-texto-2"><?= e($sigla($clave)) ?></span>
                </span>

                <?= $tiene
                    ? pill(tonoBanda((string) $comp['banda']), $etiquetaBanda((string) $comp['banda']))
                    : pill('na', $vista->t('mon.banda_sin_dato')) ?>
            </div>

            <?php /* El porcentaje: el índice va de 0 a 100 y se lee como tal. */ ?>
            <p class="tabular mt-3 text-4xl font-semibold leading-none <?= $tiene ? 'text-texto' : 'text-na' ?>">
                <?= e($tiene ? $cifra($valor) : '—') ?><?= $tiene ? '<span class="text-2xl font-normal text-texto-2"> %</span>' : '' ?>
            </p>

            <?php if ($tiene): ?>
                <?php
                /*
                 * aria-hidden: la barra no aporta nada que el porcentaje de
                 * arriba no diga ya, y un lector de pantalla no necesita oír la
                 * misma medida dos veces.
                 */
                ?>
                <div class="rv-hundido mt-3 h-1.5 w-full overflow-hidden rounded-full bg-fondo" aria-hidden="true">
                    <div class="h-full rounded-full <?= e($relleno) ?>"
                         style="width: <?= sprintf('%.1f', $ancho) ?>%"></div>
                </div>
            <?php else: ?>
                <div class="mt-3 h-1.5" aria-hidden="true"></div>
            <?php endif; ?>

            <p class="mt-3 text-sm font-semibold text-texto">
                <?= e($vista->t('mon.comp_' . strtolower($clave))) ?>
                <span class="tabular font-normal text-texto-2">
                    · <?= e($vista->t('mon.peso', number_format((float) $comp['peso'] * 100, 0, ',', ''))) ?>
                </span>
            </p>
            <p class="mt-1 text-xs leading-relaxed text-texto-2">
                <?= e($vista->t('mon.desc_' . strtolower($clave))) ?>
            </p>

            <?php if ($tiene && $comp['tope'] !== null): ?>
                <p class="tabular mt-2.5 text-xs leading-relaxed text-texto-2">
                    <?= e($vista->t(
                        'mon.comp_topado',
                        $cifra((float) $comp['bruto']),
                        $cifra((float) $comp['tope']),
                    )) ?>
                </p>
            <?php elseif (!$tiene): ?>
                <p class="mt-2.5 text-xs leading-relaxed text-texto-2">
                    <?= e($vista->t('mon.comp_sin_metricas')) ?>
                </p>
            <?php endif; ?>

            <?php
            /*
             * El pie dice que la ficha se puede pulsar y qué va a salir. Sin él
             * la ficha parece un dato terminado y nadie descubre la tabla: una
             * zona pulsable que no se anuncia es una zona que no existe.
             *
             * mt-auto la empuja al fondo para que las tres fichas la alineen
             * aunque sus descripciones midan distinto.
             */
            ?>
            <span class="mt-auto flex items-center gap-1.5 pt-3 text-xs font-semibold
                         <?= $esActivo ? 'text-primario' : 'text-texto-2 group-hover:text-texto' ?>">
                <?= icono('chevron', 'h-3.5 w-3.5 shrink-0 transition ' . ($esActivo ? 'rotate-180' : '')) ?>
                <?= e($vista->t('mon.ver_procesos', (string) $cuantos)) ?>
            </span>
        </button>
    <?php endforeach; ?>
</div>

