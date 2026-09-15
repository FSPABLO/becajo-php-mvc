<?php

declare(strict_types=1);

/**
 * El histórico de UNA empresa auditada.
 *
 * Es el contenido que antes se apilaba, repetido, dentro de cada tarjeta de
 * /evaluacion/comparar. Con pantalla propia responde las tres preguntas de
 * siempre, ahora a tamaño de página y de lo general a lo detallado:
 *
 *   ¿cómo está hoy?      — las tres losetas de cabecera.
 *   ¿va mejorando?       — columnas del índice general, una por auditoría.
 *   ¿en qué está floja?  — perfil de madurez por dominio de la última.
 *   ¿cuánto, exactamente, y desde cuándo? — la tabla del desglose.
 *
 * Sin enlace propio de vuelta: la miga de la barra superior dice «Auditorías /
 * Comparar histórico / <empresa>» y ese nivel del medio ES el camino de
 * regreso, por la misma razón que /evaluacion/{id} dejó de repetir su
 * «← Mis auditorías».
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var string $organizacion
 * @var array<string, mixed> $ficha  Ficha derivada de la cartera: recuentos,
 *      índice, variación, zona y áreas. NO se llama $empresa porque ese
 *      nombre es el de la consultora, que el marco del módulo pinta en el
 *      encabezado y el pie.
 * @var list<\App\Models\Entidades\Auditoria> $auditorias  Más antigua primero.
 * @var list<array<string, mixed>> $historico  Filas de sp_historico_dominio.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

$ultima = $ficha['ultima'];

/*
 * Zona → tono de pill(), la misma correspondencia de evaluacion/resultados y de
 * la antesala: la zona de fn_zona tiene UN color en todo el producto.
 */
$tonoZona = ['VERDE' => 'ok', 'AMARILLO' => 'warn', 'ROJO' => 'bad'];

$etiquetaZona = [
    'VERDE'    => $vista->t('eval.zona_baja'),
    'AMARILLO' => $vista->t('eval.zona_media'),
    'ROJO'     => $vista->t('eval.zona_alta'),
];

/*
 * Desglose por dominio, reagrupado por AUDITORÍA. El procedimiento devuelve una
 * fila por (auditoría, dominio) y ya ordenada por fecha, así que el orden de
 * aparición es el cronológico: se conserva tal cual en vez de reordenar por la
 * cadena de la fecha, que según el formato con que la sirva Oracle puede no
 * ordenar como fecha.
 *
 * Los dominios se indexan por CLAVE y se ordenan por la columna 'orden' del
 * instrumento, no por la clave ni por el orden en que aparezcan: los ejes del
 * radar y las filas de la tabla tienen que salir en la misma sucesión, y
 * alfabéticamente 'dato' (Protección) caería entre Continuidad y Gobierno.
 */
$columnas = [];
$dominios = [];

foreach ($historico as $fila) {
    $idAuditoria = (string) $fila['id_auditoria'];
    $clave       = (string) $fila['clave_dominio'];

    $dominios[$clave] = [
        'nombre' => (string) $fila['dominio'],
        // Con un procedimiento sin recargar la columna no llega y todos empatan
        // a cero; la ordenación es estable, así que en ese caso se queda el
        // orden del cursor.
        'orden'  => (int) ($fila['orden_dominio'] ?? 0),
    ];

    $columnas[$idAuditoria]['fecha']           = (string) $fila['fecha'];
    $columnas[$idAuditoria]['valores'][$clave] = (float) $fila['madurez_promedio'];
}

uasort($dominios, static fn (array $a, array $b): int
    => [$a['orden'], $a['nombre']] <=> [$b['orden'], $b['nombre']]);

/*
 * Ejes del radar: los dominios que evaluó la ÚLTIMA auditoría con desglose. Los
 * que esa auditoría no tocó no entran —no valen cero— y por eso el radar puede
 * tener menos ejes que filas la tabla.
 */
$ultimaColumna = $columnas === [] ? null : $columnas[array_key_last($columnas)];
$ejesRadar     = [];

foreach ($dominios as $clave => $dominio) {
    if ($ultimaColumna !== null && isset($ultimaColumna['valores'][$clave])) {
        $ejesRadar[] = [
            'dominio' => $dominio['nombre'],
            'madurez' => $ultimaColumna['valores'][$clave],
        ];
    }
}

$indice    = $ficha['indice'];
$variacion = $ficha['variacion'];
$zona      = $ficha['zona'];
?>
<section class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-8">

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php
    /*
     * Aquí el <h1> SÍ es visible, al revés que en las dos pantallas de lista: el
     * nombre de la empresa no es el rótulo de una sección del menú, es el sujeto
     * de todo lo que hay debajo, y la miga solo lo repite recortado a su último
     * nivel.
     */
    ?>
    <header class="mb-6 flex flex-wrap items-start justify-between gap-x-6 gap-y-2">
        <div class="min-w-0">
            <h1 class="rv-titulo text-2xl font-semibold text-texto"><?= e($organizacion) ?></h1>
            <p class="mt-1 text-sm text-texto-2">
                <?= e($vista->t(
                    'eval.auditorias_de_organizacion',
                    (string) $ficha['total'],
                    $ultima->fecha,
                )) ?>
            </p>
        </div>

        <?php
        /*
         * Un solo enlace en la cabecera, a la auditoría más reciente. Las
         * anteriores se alcanzan desde su propia columna del gráfico, que es
         * donde el lector ya está mirando cuando quiere abrir una.
         */
        ?>
        <a href="<?= e($vista->url('evaluacion/' . $ultima->id . '/resultados')) ?>"
           class="shrink-0 text-sm font-medium text-primario hover:underline">
            <?= e($vista->t('eval.ver_resultados')) ?> →
        </a>
    </header>

    <?php
    /*
     * Las tres losetas: dónde quedó, cuánto trabajo hay detrás y desde cuándo.
     * Es lo que se lee de un vistazo, así que va antes que los gráficos.
     */
    ?>
    <div class="mb-6 grid gap-4 sm:grid-cols-3">

        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">
                <?= e($vista->t('eval.ultimo_indice')) ?>
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <p class="tabular text-3xl font-semibold <?= $indice === null ? 'text-na' : 'text-texto' ?>">
                    <?= $indice === null ? '—' : e(number_format($indice, 2, ',', '')) ?>
                </p>
                <?= pill(
                    $zona === null ? 'na' : ($tonoZona[$zona] ?? 'na'),
                    $zona === null ? $vista->t('eval.filtro_sin_indice') : ($etiquetaZona[$zona] ?? $zona),
                ) ?>
            </div>
            <?php
            /*
             * La variación lleva SIGNO además de color: en gris, o con
             * daltonismo, «+0,12» sigue diciendo que mejoró. Sin una segunda
             * lectura con índice no se inventa un cero.
             */
            ?>
            <p class="mt-1.5 text-xs <?= $variacion === null
                ? 'text-texto-2'
                : ($variacion >= 0 ? 'text-ok' : 'text-bad') ?>">
                <?= $variacion === null
                    ? e($vista->t('eval.primera_lectura'))
                    : e($vista->t(
                        'eval.variacion_anterior',
                        ($variacion >= 0 ? '+' : '−') . number_format(abs($variacion), 2, ',', ''),
                    )) ?>
            </p>
        </div>

        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">
                <?= e($vista->t('eval.kpi_auditorias')) ?>
            </p>
            <p class="tabular mt-2 text-3xl font-semibold text-texto"><?= e((string) $ficha['total']) ?></p>
            <p class="mt-1.5 text-xs text-texto-2">
                <?= e($ficha['enProgreso'] > 0
                    ? $vista->t('eval.en_progreso_n', (string) $ficha['enProgreso'])
                    : $vista->t('eval.kpi_finalizadas')) ?>
            </p>
        </div>

        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">
                <?= e($vista->t('eval.periodo_auditado')) ?>
            </p>
            <p class="tabular mt-2 text-sm font-semibold text-texto">
                <?= e($ficha['primera']->fecha) ?> → <?= e($ultima->fecha) ?>
            </p>
            <p class="mt-1.5 text-xs text-texto-2">
                <?= e(implode(' · ', array_map('strval', $ficha['areas']))) ?>
            </p>
        </div>
    </div>

    <?php if (count($auditorias) < 2): ?>
        <p class="rv-hundido rv-relieve-sutil mb-6 rounded-rv border border-borde bg-superficie px-4 py-3 text-sm text-texto-2">
            <?= e($vista->t('eval.solo_una_auditoria')) ?>
        </p>
    <?php endif; ?>

    <?php
    /*
     * El reparto 3/2 no es estético: la serie de tiempo necesita anchura o las
     * pendientes se exageran, mientras que el radar es cuadrado y se lee entero
     * de un vistazo. Es el mismo reparto —y el mismo ancho de tarjeta— con el
     * que se calibraron los dos componentes cuando vivían dentro de la tarjeta
     * de la antesala: si alguno cambia de columna, hay que devolverle su
     * viewBox, porque el texto de un SVG escala con el dibujo.
     */
    ?>
    <div class="rv-extruido mb-6 grid gap-6 rounded-rv-lg border border-borde bg-superficie p-5 lg:grid-cols-5 sm:p-6">

        <section class="lg:col-span-3">
            <h2 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.indice_por_auditoria')) ?></h2>
            <p class="mb-3 mt-0.5 text-xs text-texto-2"><?= e($vista->t('eval.indice_por_auditoria_texto')) ?></p>

            <?= $vista->componente('indice-historico', [
                'vista'      => $vista,
                'auditorias' => $auditorias,
                // Un solo gráfico por página, pero el sufijo sigue siendo
                // obligatorio: los ids de <defs> son globales al documento.
                'id'         => 'historico',
            ]) ?>
        </section>

        <section class="lg:col-span-2">
            <h2 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.perfil_dominios')) ?></h2>
            <p class="mb-3 mt-0.5 text-xs text-texto-2">
                <?= e($ultimaColumna === null
                    ? $vista->t('eval.sin_desglose_dominio')
                    : $vista->t('eval.perfil_dominios_texto', $ultimaColumna['fecha'])) ?>
            </p>

            <?php if ($ultimaColumna !== null): ?>
                <?= $vista->componente('radar-dominios', [
                    'vista' => $vista,
                    'ejes'  => $ejesRadar,
                    'fecha' => $ultimaColumna['fecha'],
                    'id'    => 'radar',
                ]) ?>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($columnas !== []): ?>
        <?php
        /*
         * El desglose completo. En tabla densa el relieve no debe competir con
         * la lectura del dato: va hundido y en el nivel sutil, y las filas no
         * llevan sombra propia (.rv-tabla en rivendel.css).
         */
        ?>
        <section class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5 sm:p-6">
            <h2 class="mb-2 text-sm font-semibold text-texto">
                <?= e($vista->t('eval.madurez_por_dominio')) ?>
            </h2>

            <div class="rv-hundido rv-relieve-sutil overflow-x-auto rounded-rv border border-borde bg-fondo px-4 py-3">
                <table class="rv-tabla w-full text-left text-xs">
                    <thead class="text-texto-2">
                        <tr>
                            <th class="py-1.5 pr-3 font-semibold"><?= e($vista->t('eval.dominio')) ?></th>
                            <?php foreach ($columnas as $columna): ?>
                                <th class="px-2 py-1.5 text-center font-semibold tabular whitespace-nowrap">
                                    <?= e($columna['fecha']) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde">
                        <?php foreach ($dominios as $clave => $dominio): ?>
                            <tr>
                                <td class="py-1.5 pr-3 font-medium text-texto whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5">
                                        <?= iconoDominio((string) $clave, 'h-4 w-4 shrink-0') ?>
                                        <?= e($dominio['nombre']) ?>
                                    </span>
                                </td>
                                <?php foreach ($columnas as $columna): ?>
                                    <?php $valor = $columna['valores'][$clave] ?? null; ?>
                                    <td class="px-2 py-1.5 text-center tabular text-texto-2">
                                        <?= $valor === null ? '—' : e(number_format($valor, 2, ',', '')) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="mt-2 text-xs text-texto-2"><?= e($vista->t('eval.madurez_por_dominio_pie')) ?></p>
        </section>
    <?php endif; ?>
</section>
