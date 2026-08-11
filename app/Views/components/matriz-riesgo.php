<?php

declare(strict_types=1);

/**
 * Matriz de riesgo: impacto (eje horizontal) × probabilidad (eje vertical).
 *
 * Vive aquí y no dentro de una vista porque la pintan dos pantallas —el panel
 * de entrada y los resultados de una auditoría— y el color de una celda se
 * deriva de los mismos cortes que usa fn_zona en pkg_indicadores. Dos copias de
 * esa aritmética se separan en cuanto alguien mueva un corte en la base.
 *
 * EL COLOR NO ES EL ÚNICO CANAL, y aquí lo son tres a la vez: la POSICIÓN (el
 * riesgo crece hacia arriba y hacia la derecha, siempre), la CIFRA dentro de la
 * celda y el título emergente, que nombra el nivel con palabras. La leyenda de
 * la cabecera pone la cuarta. Quien no distinga los tonos sigue leyendo la
 * matriz entera.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\EvaluacionControl> $evaluaciones
 * @var bool|null $compacto  Sin ejes numerados ni pie, para tarjeta de panel.
 */
$compacto = $compacto ?? false;

/*
 * Conteo de controles por celda. Solo entran los que tienen las DOS
 * coordenadas: un control con impacto y sin probabilidad no está en ninguna
 * casilla de la matriz, y colocarlo en una fila cualquiera sería inventarlo.
 */
$celdas = [];

foreach ($evaluaciones as $evaluacion) {
    if ($evaluacion->impacto === null || $evaluacion->probabilidad === null) {
        continue;
    }

    $clave = $evaluacion->impacto . '-' . $evaluacion->probabilidad;
    $celdas[$clave] = ($celdas[$clave] ?? 0) + 1;
}

/*
 * Mismos cortes que EvaluacionControl::nivelRiesgoCalculado(), solo que por
 * posición de la celda y no por un control puntual.
 */
$zonaCelda = static fn (int $impacto, int $probabilidad): string => match (true) {
    ($impacto + $probabilidad) / 2 <= 2   => 'VERDE',
    ($impacto + $probabilidad) / 2 <= 3.5 => 'AMARILLO',
    default                               => 'ROJO',
};

$fondoZona = [
    'VERDE'    => 'bg-ok',
    'AMARILLO' => 'bg-warn',
    'ROJO'     => 'bg-bad',
];

$etiquetaZona = [
    'VERDE'    => $vista->t('eval.zona_baja'),
    'AMARILLO' => $vista->t('eval.zona_media'),
    'ROJO'     => $vista->t('eval.zona_alta'),
];
?>
<?php if ($celdas === []): ?>
    <p class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-4 py-8 text-center text-sm text-texto-2">
        <?= e($vista->t('eval.sin_impacto_prob')) ?>
    </p>
<?php else: ?>
    <?php
    /*
     * El ancho se limita porque las celdas son cuadradas: sin tope, la matriz
     * crece con la columna y una tarjeta de panel acaba midiendo media pantalla
     * de alto por un tablero de cinco por cinco.
     */
    ?>
    <div class="flex max-w-[340px] gap-2">
        <?php
        /*
         * Escala de probabilidad. Va en rejilla de cinco filas —y no repartida
         * con justify-between— para que cada número quede centrado en SU fila:
         * repartido, los extremos se pegan a los bordes y los de en medio caen
         * entre dos casillas.
         */
        ?>
        <div class="grid w-4 shrink-0 grid-rows-5 gap-1.5 text-[11px] font-semibold tabular text-texto-2"
             aria-hidden="true">
            <?php for ($p = 5; $p >= 1; $p--): ?>
                <span class="flex items-center justify-end"><?= $p ?></span>
            <?php endfor; ?>
        </div>

        <div class="min-w-0 flex-1">
            <?php
            /*
             * gap-1.5 es el separador de superficie entre celdas contiguas: sin
             * él, dos casillas del mismo tono se leen como una sola mancha y se
             * pierde el conteo de cada una.
             */
            ?>
            <div class="grid grid-cols-5 gap-1.5">
                <?php for ($p = 5; $p >= 1; $p--): ?>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php
                        $conteo = $celdas[$i . '-' . $p] ?? 0;
                        $zona   = $zonaCelda($i, $p);
                        ?>
                        <?php
                        /*
                         * La celda vacía se atenúa en vez de desaparecer: el
                         * degradado de riesgo del tablero se lee de un vistazo
                         * y sigue estando ahí aunque nadie haya caído en ella.
                         *
                         * El texto va en primario-texto y no en texto: sobre
                         * los tres rellenos llenos —verde, oro y rojo— la tinta
                         * del cuerpo no llega al contraste mínimo en ninguno de
                         * los dos lienzos.
                         *
                         * RELIEVE SOLO EN LAS CELDAS OCUPADAS. Las casillas con
                         * controles se levantan del tablero como fichas puestas
                         * sobre él; las vacías se quedan al ras, que es lo que
                         * son. Es el mismo criterio del gráfico de evolución —la
                         * altura ordena lo que se lee primero— y no estrena
                         * ningún canal: dónde hay controles ya lo decían la
                         * cifra y la opacidad, el relieve solo lo repite. Una
                         * sombra bajo una celda vacía sería relieve sin dato,
                         * que es exactamente lo que la impresión desactiva.
                         */
                        ?>
                        <div class="flex aspect-square items-center justify-center rounded-rv text-sm font-semibold tabular text-primario-texto <?= e($fondoZona[$zona]) ?> <?= $conteo === 0 ? 'opacity-20' : 'rv-extruido-sm' ?>">
                            <?php if ($conteo > 0): ?>
                                <span title="<?= e($vista->t(
                                    'eval.celda_matriz',
                                    (string) $i,
                                    (string) $p,
                                    (string) $conteo,
                                    $etiquetaZona[$zona],
                                )) ?>"><?= e((string) $conteo) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                <?php endfor; ?>
            </div>

            <?php /* Escala de impacto: de izquierda a derecha, 1 a 5. */ ?>
            <div class="mt-1.5 grid grid-cols-5 gap-1.5 text-center text-[11px] font-semibold tabular text-texto-2"
                 aria-hidden="true">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span><?= $i ?></span>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <?php if (!$compacto): ?>
        <p class="mt-3 text-center text-xs text-texto-2"><?= e($vista->t('eval.eje_matriz')) ?></p>
    <?php endif; ?>
<?php endif; ?>
