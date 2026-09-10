<?php

declare(strict_types=1);

/**
 * "Mis auditorías": punto de entrada del módulo.
 *
 * Ya no repite la sesión ni el botón de salir: eso vive en la barra lateral del
 * diseño layouts/panel. Lo que queda aquí es lo propio de la pantalla —el
 * resumen de la cartera del auditor y la lista— y por eso la cabecera solo
 * lleva las dos acciones que crean o comparan auditorías.
 *
 * Las cifras del resumen se calculan SOBRE $auditorias, que ya está en memoria.
 * No hay consulta nueva ni procedimiento nuevo: son recuentos de una lista que
 * el controlador tuvo que traer de todos modos para pintar la tabla.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Auditoria> $auditorias
 * @var int $total  Controles del catálogo.
 * @var \App\Models\Entidades\Auditoria|null $ultima  La más reciente.
 * @var list<\App\Models\Entidades\EvaluacionControl> $evaluacionesUltima
 * @var list<array<string, mixed>> $evolucion  Filas de sp_evolucion_auditor.
 * @var list<string> $organizaciones  Empresas que este auditor ha evaluado.
 * @var string|null $organizacion  La empresa que mira el gráfico ahora mismo.
 * @var string|null $organizacionEscrita  Lo que el auditor tecleó, tal cual.
 * @var bool $organizacionSinCoincidencia  Lo escrito no casó con ninguna.
 * @var list<\App\Models\Entidades\Auditoria> $visibles  La página actual.
 * @var int $encontradas  Auditorías que pasan el filtro (todas las páginas).
 * @var string|null $buscar
 * @var string $orden   'reciente' | 'indice'
 * @var int $pagina
 * @var int $paginas
 * @var int $porPagina
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$ultima             = $ultima ?? null;
$evaluacionesUltima = $evaluacionesUltima ?? [];
$evolucion          = $evolucion ?? [];
$organizaciones     = $organizaciones ?? [];
$organizacion       = $organizacion ?? null;
$organizacionEscrita = $organizacionEscrita ?? null;
$organizacionSinCoincidencia = $organizacionSinCoincidencia ?? false;
$visibles           = $visibles ?? [];
$encontradas        = $encontradas ?? 0;
$buscar             = $buscar ?? null;
$orden              = $orden ?? 'reciente';
$pagina             = $pagina ?? 1;
$paginas            = $paginas ?? 1;
$porPagina          = $porPagina ?? 4;
$enProgreso  = 0;
$finalizadas = 0;
$indices     = [];

/*
 * El recuento de empresas distintas NO se recalcula aquí: sale de
 * $organizaciones, que el controlador ya armó para el selector del gráfico.
 * Cuando esta vista lo recontaba por su cuenta, la variable local pisaba la
 * lista del controlador —el mismo nombre— y el <datalist> del gráfico acababa
 * ofreciendo «1» en vez de los nombres de las empresas.
 */
foreach ($auditorias as $auditoria) {
    $auditoria->estaFinalizada() ? $finalizadas++ : $enProgreso++;

    // Null no es cero: una auditoría sin calcular no puede arrastrar el
    // promedio hacia abajo como si tuviera madurez nula.
    if ($auditoria->indiceGeneralRiesgo !== null) {
        $indices[] = $auditoria->indiceGeneralRiesgo;
    }
}

$promedio = $indices === [] ? null : array_sum($indices) / count($indices);

/*
 * Las cuatro casillas del resumen. Se declaran como datos y se pintan en un
 * solo bucle: cuatro bloques de marcado casi idénticos se desincronizan en
 * cuanto alguien retoca uno.
 */
$resumen = [
    [
        'etiqueta' => $vista->t('eval.kpi_auditorias'),
        'valor'    => (string) count($auditorias),
        'nota'     => $vista->t('eval.kpi_organizaciones', (string) count($organizaciones)),
    ],
    [
        'etiqueta' => $vista->t('eval.en_progreso'),
        'valor'    => (string) $enProgreso,
        'nota'     => $vista->t('eval.kpi_pendientes'),
    ],
    [
        'etiqueta' => $vista->t('eval.kpi_finalizadas'),
        'valor'    => (string) $finalizadas,
        'nota'     => $vista->t('eval.kpi_congeladas'),
    ],
    [
        // Formato numérico español (§2.3): coma decimal y un decimal fijo.
        'etiqueta' => $vista->t('eval.kpi_indice'),
        'valor'    => $promedio === null ? '—' : number_format($promedio, 1, ',', ''),
        'nota'     => $promedio === null
            ? $vista->t('eval.kpi_sin_calculo')
            : $vista->t('eval.kpi_calculadas', (string) count($indices)),
    ],
];
?>
<section class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-8">

    <?php
    /*
     * Cabecera reducida al rótulo.
     *
     * Se retiraron, en este orden, el nombre del auditor con su organización
     * —la barra lateral ya lleva la ficha de sesión y la barra superior la
     * empresa—, los dos botones de acción, que son entradas del menú lateral y
     * estaban repetidos aquí, y el título visible.
     *
     * El <h1> NO desaparece: se queda en sr-only. Es lo único que le dice a un
     * lector de pantalla en qué página está —la miga de la barra superior es
     * navegación, no encabezado— y una página sin h1 rompe el salto por
     * encabezados que usa quien no ve el diseño. No cuesta un píxel.
     */
    ?>
    <header class="mb-8">
        <h1 class="sr-only"><?= e($vista->t('eval.mis_auditorias')) ?></h1>
        <p class="text-sm font-semibold uppercase tracking-widest text-primario">
            <?= e($vista->t('auth.eyebrow')) ?>
        </p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php
    /*
     * Resumen de la cartera. Va antes de la tabla porque responde la pregunta
     * con la que se entra al módulo —"¿cómo voy?"— sin obligar a leer y contar
     * filas. Las cifras llevan la clase tabular para que no bailen de ancho.
     */
    ?>
    <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($resumen as $casilla): ?>
            <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">
                    <?= e($casilla['etiqueta']) ?>
                </p>
                <?php
                /*
                 * El hueco del índice sin calcular va en gris neutro y no en el
                 * color del texto: una raya a 30 px en blanco hueso se lee como
                 * un filete de la tarjeta, no como "todavía no hay dato".
                 */
                ?>
                <p class="tabular mt-2 text-3xl font-semibold <?= $casilla['valor'] === '—' ? 'text-na' : 'text-texto' ?>">
                    <?= e($casilla['valor']) ?>
                </p>
                <p class="mt-1.5 text-xs text-texto-2"><?= e($casilla['nota']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    /*
     * El orden de la pantalla: primero el tablero, después el listado.
     *
     * Antes eran dos pestañas —«Mis auditorías» y «Resumen»— y el listado
     * nacía activo. Se retiraron: ninguna de las dos secciones es larga, así
     * que la pestaña solo cobraba un clic por ver la otra mitad de una página
     * que cabe entera, y quien llegaba desde un enlace con ?buscar= aterrizaba
     * en la pestaña de la tabla por casualidad, no por diseño.
     *
     * El tablero va arriba porque responde «¿cómo voy?» —la pregunta con la
     * que se entra al módulo— y se lee de un vistazo. El listado va debajo
     * porque se RECORRE: filtro, filas y recuento son tres cosas que se leen
     * en orden, y el que viene a por una auditoría concreta ya sabe bajar.
     */
    ?>
    <?php if ($ultima !== null): ?>
        <?php
        /*
         * EL TABLERO ES UNA SOLA FICHA. Eran dos tarjetas —matriz a la
         * izquierda, evolución a la derecha— cada una con su cabecera, y el
         * buscador de empresa vivía dentro de la segunda. Eso decía que la
         * empresa era asunto del gráfico, cuando la pregunta que se responde
         * arriba es una sola: «¿cómo va ESTA empresa?». La matriz dice cómo
         * quedó su última auditoría y la curva si va mejorando; son dos
         * lecturas del mismo sujeto, no dos temas.
         *
         * Por eso el buscador sube a la cabecera COMPARTIDA, y por eso hubo
         * que hacer que gobernara también la matriz —ver panel() en el
         * controlador—: un filtro en la cabecera de una ficha que solo afecta
         * a media ficha es peor que no tenerlo.
         *
         * Dentro, el reparto 2/5 y 3/5 no es estético: la matriz es cuadrada y
         * se lee entera de un vistazo, mientras que una serie de tiempo
         * necesita anchura o las pendientes se exageran. Las separa una línea
         * y no un hueco entre tarjetas, que es lo que las hace una.
         */
        ?>
        <section class="rv-extruido mb-8 rounded-rv-lg border border-borde bg-superficie">

            <header class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 border-b border-borde p-5">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.tablero_titulo')) ?></h2>
                    <p class="mt-0.5 truncate text-xs text-texto-2">
                        <?= e($vista->t('eval.auditoria_n', (string) $ultima->id)) ?> ·
                        <?= e($ultima->organizacion) ?>
                    </p>
                </div>

                <?php
                /*
                 * El auditor ESCRIBE la empresa que quiere mirar.
                 *
                 * Formulario GET, sin guion: cambiar de empresa es una petición
                 * normal, funciona sin JavaScript y cada empresa queda con su
                 * propia URL, que se puede guardar y compartir.
                 *
                 * Debajo hay DOS ayudas para no tener que recordar el nombre
                 * exacto, y las dos sobreviven a que el guion no cargue:
                 *
                 *   - el <datalist>, que es la sugerencia nativa del navegador
                 *     y no necesita nada más;
                 *   - el buscador de coincidencias que monta principal.js
                 *     encima, que filtra mientras se escribe, se recorre con
                 *     las flechas y envía al elegir. Cuando ese arranca, QUITA
                 *     el atributo `list`: dos desplegables a la vez sobre el
                 *     mismo campo se pisan y el navegador acaba enseñando los
                 *     dos.
                 *
                 * Y el controlador acepta el nombre a medias de todos modos,
                 * así que teclear «cooperativa» y pulsar basta.
                 *
                 * Los otros filtros viajan escondidos: cambiar la empresa del
                 * tablero no tiene por qué deshacer la búsqueda de la tabla de
                 * abajo.
                 */
                ?>
                <form method="get" action="<?= e($vista->url('evaluacion')) ?>"
                      class="w-full shrink-0 sm:w-auto" data-buscador-empresa>
                    <?php foreach (['buscar' => $buscar, 'orden' => $orden, 'pagina' => (string) $pagina] as $campo => $valor): ?>
                        <?php if ((string) $valor !== '' && $valor !== null): ?>
                            <input type="hidden" name="<?= e($campo) ?>" value="<?= e((string) $valor) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <label for="organizacion" class="block text-xs font-medium text-texto-2">
                        <?= e($vista->t('eval.empresa_auditada')) ?>
                    </label>

                    <div class="relative mt-1.5 flex gap-2">
                        <div class="relative min-w-0 flex-1 sm:w-64">
                            <input type="text"
                                   id="organizacion"
                                   name="organizacion"
                                   list="empresas-auditadas"
                                   value="<?= e($organizacion ?? '') ?>"
                                   placeholder="<?= e($vista->t('eval.empresa_marcador')) ?>"
                                   autocomplete="off"
                                   data-buscador-campo
                                   class="rv-hundido w-full rounded-rv border border-borde bg-fondo px-3 py-2 text-sm text-texto placeholder-texto-2/60 outline-none transition focus:border-primario-hover">

                            <?php
                            /*
                             * La variable del bucle NO se llama $empresa: el
                             * layout recibe un $empresa con los datos de la
                             * consultora y reutilizar el nombre aquí lo dejaría
                             * pisado para el resto de la vista.
                             */
                            ?>
                            <datalist id="empresas-auditadas">
                                <?php foreach ($organizaciones as $candidata): ?>
                                    <option value="<?= e($candidata) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>

                            <?php
                            /*
                             * El panel de coincidencias. Nace vacío y OCULTO, y
                             * lo llena el guion desde este mismo arreglo, que ya
                             * viaja en el <datalist>: sin guion no hay dos
                             * copias de la lista en el HTML, y con guion no hay
                             * que ir a buscarla al servidor en cada tecla — son
                             * las empresas de este auditor, no un catálogo.
                             */
                            ?>
                            <ul id="empresas-coincidencias" data-buscador-lista hidden
                                role="listbox" aria-label="<?= e($vista->t('eval.empresa_coincidencias')) ?>"
                                class="rv-extruido absolute left-0 right-0 top-full z-20 mt-1 max-h-56 overflow-y-auto rounded-rv border border-borde bg-superficie py-1 text-sm shadow-lg"></ul>
                        </div>

                        <button type="submit"
                                class="rv-extruido rv-interactivo shrink-0 self-start rounded-rv bg-primario px-3.5 py-2 text-sm font-semibold text-primario-texto">
                            <?= e($vista->t('eval.ver_progreso')) ?>
                        </button>
                    </div>

                    <?php
                    /*
                     * Si lo escrito no casó con ninguna empresa se dice, y se
                     * dice CUÁL se está mostrando en su lugar. Callarlo dejaría
                     * al auditor leyendo la curva de otra empresa creyendo que
                     * es la que pidió.
                     */
                    ?>
                    <?php if ($organizacionSinCoincidencia && $organizacion !== null): ?>
                        <p class="mt-2 max-w-sm text-xs text-warn">
                            <?= e($vista->t('eval.empresa_sin_coincidencia', $organizacionEscrita, $organizacion)) ?>
                        </p>
                    <?php endif; ?>
                </form>
            </header>

            <div class="grid gap-5 p-5 lg:grid-cols-5 lg:gap-0 lg:divide-x lg:divide-borde lg:p-0">

                <div class="lg:col-span-2 lg:p-5">
                    <header class="mb-4 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2">
                        <h3 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.matriz_riesgo')) ?></h3>

                        <?php
                        /*
                         * Leyenda de zonas. El punto de color va SIEMPRE con su
                         * etiqueta al lado: el color solo, en una escala de tres
                         * tonos que además significan gravedad, no basta.
                         */
                        ?>
                        <ul class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-texto-2">
                            <?php foreach ([
                                ['bg-ok',   $vista->t('eval.zona_baja')],
                                ['bg-warn', $vista->t('eval.zona_media')],
                                ['bg-bad',  $vista->t('eval.zona_alta')],
                            ] as [$fondo, $etiqueta]): ?>
                                <li class="flex items-center gap-1.5">
                                    <span class="rv-extruido-xs h-2 w-2 rounded-full <?= e($fondo) ?>" aria-hidden="true"></span>
                                    <?= e($etiqueta) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </header>

                    <?= $vista->componente('matriz-riesgo', [
                        'vista'        => $vista,
                        'evaluaciones' => $evaluacionesUltima,
                        'compacto'     => true,
                    ]) ?>

                    <footer class="mt-4 flex items-center justify-between gap-3 text-xs">
                        <span class="text-texto-2"><?= e($vista->t('eval.eje_matriz_corto')) ?></span>
                        <a href="<?= e($vista->url('evaluacion/' . $ultima->id . '/resultados')) ?>"
                           class="shrink-0 font-semibold text-primario hover:underline">
                            <?= e($vista->t('eval.ver_resultados')) ?> →
                        </a>
                    </footer>
                </div>

                <div class="flex flex-col lg:col-span-3 lg:p-5">
                    <header class="mb-4">
                        <h3 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.evolucion_titulo')) ?></h3>
                        <p class="mt-0.5 text-xs text-texto-2"><?= e($vista->t('eval.evolucion_texto')) ?></p>
                    </header>

                    <?php
                    /*
                     * Las dos mitades tienen la misma altura —la manda la
                     * matriz, que es cuadrada— y el contenido de esta se centra
                     * en lo que sobra. Anclado arriba, el estado de un solo mes
                     * deja media ficha en blanco y parece que falta algo por
                     * cargar.
                     */
                    ?>
                    <div class="flex flex-1 flex-col justify-center">
                        <?= $vista->componente('evolucion-mensual', [
                            'vista'     => $vista,
                            'evolucion' => $evolucion,
                        ]) ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($auditorias === []): ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto"><?= e($vista->t('eval.sin_auditorias')) ?></p>
            <p class="mt-1 text-sm text-texto-2">
                <?= e($vista->t('eval.crear_primera', (string) $total)) ?>
            </p>
            <a href="<?= e($vista->url('evaluacion/nueva')) ?>"
               class="rv-extruido rv-interactivo mt-6 inline-block rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto">
                <?= e($vista->t('eval.nueva_auditoria')) ?>
            </a>
        </div>
    <?php else: ?>
        <?php
        /*
         * Filtros, tabla y paginación en UNA sola pieza.
         *
         * Encerrarlos juntos es lo que dice, sin escribirlo, que esos controles
         * mandan sobre esta tabla y no sobre otra cosa de la pantalla. Lo que
         * los mantiene distinguibles del contenido no es estar en cajas
         * separadas, sino la línea divisoria: los filtros arriba, la tabla en
         * medio y el recuento abajo, cada zona con su borde.
         */
        ?>
        <section class="rv-extruido rv-relieve-sutil overflow-hidden rounded-rv-lg border border-borde bg-superficie">

        <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-3 border-b border-borde p-4">

            <form method="get" action="<?= e($vista->url('evaluacion')) ?>" class="flex items-end gap-2">
                <?php
                /*
                 * La empresa del gráfico viaja escondida para no perderse al
                 * buscar. La página NO: al cambiar la búsqueda hay que volver a
                 * la primera, o se cae en una página que ya no existe.
                 */
                ?>
                <?php if ($organizacion !== null): ?>
                    <input type="hidden" name="organizacion" value="<?= e($organizacion) ?>">
                <?php endif; ?>
                <?php if ($orden !== 'reciente'): ?>
                    <input type="hidden" name="orden" value="<?= e($orden) ?>">
                <?php endif; ?>

                <div>
                    <label for="buscar" class="block text-xs font-medium text-texto-2">
                        <?= e($vista->t('eval.buscar_etiqueta')) ?>
                    </label>
                    <input type="search"
                           id="buscar"
                           name="buscar"
                           value="<?= e($buscar ?? '') ?>"
                           placeholder="<?= e($vista->t('eval.buscar_marcador')) ?>"
                           class="rv-hundido mt-1.5 w-56 rounded-rv border border-borde bg-fondo px-3 py-2 text-sm text-texto placeholder-texto-2/60 outline-none transition focus:border-primario-hover">
                </div>

                <button type="submit"
                        class="rv-extruido rv-interactivo rounded-rv bg-primario px-3.5 py-2 text-sm font-semibold text-primario-texto">
                    <?= e($vista->t('comun.buscar')) ?>
                </button>

                <?php if ($buscar !== null): ?>
                    <a href="<?= e($vista->url('evaluacion') . ($organizacion !== null ? '?organizacion=' . rawurlencode($organizacion) : '')) ?>"
                       class="py-2 text-sm font-medium text-texto-2 hover:text-texto">
                        <?= e($vista->t('eval.limpiar')) ?>
                    </a>
                <?php endif; ?>
            </form>

            <?php
            /*
             * Orden. Son enlaces y no un <select>: dos opciones excluyentes que
             * además dejan URL propia. El activo lleva aria-current, no solo el
             * relleno verde.
             */
            ?>
            <div class="rv-hundido flex rounded-[9px] bg-fondo p-[3px]"
                 role="group" aria-label="<?= e($vista->t('eval.ordenar_por')) ?>">
                <?php
                $ordenes = [
                    'reciente' => $vista->t('eval.orden_recientes'),
                    'indice'   => $vista->t('eval.orden_indice'),
                ];

                // Cambiar el orden reordena la lista entera, así que la página
                // vuelve a la primera: la cuarta página de otro orden no es la
                // continuación de nada.
                $baseOrden = [];

                if ($organizacion !== null) {
                    $baseOrden['organizacion'] = $organizacion;
                }

                if ($buscar !== null) {
                    $baseOrden['buscar'] = $buscar;
                }
                ?>
                <?php foreach ($ordenes as $clave => $etiqueta): ?>
                    <?php $activo = $orden === $clave; ?>
                    <a href="<?= e($vista->url('evaluacion') . '?' . http_build_query($baseOrden + ['orden' => $clave])) ?>"
                       <?= $activo ? 'aria-current="true"' : '' ?>
                       class="rounded-[7px] px-3 py-1.5 text-xs font-semibold transition <?= $activo
                           ? 'rv-extruido bg-primario text-primario-texto'
                           : 'text-texto-2 hover:text-texto' ?>">
                        <?= e($etiqueta) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($visibles === []): ?>
            <div class="px-6 py-14 text-center">
                <p class="font-semibold text-texto"><?= e($vista->t('eval.sin_coincidencias')) ?></p>
                <p class="mt-1 text-sm text-texto-2">
                    <?= e($vista->t('eval.sin_coincidencias_texto', (string) $buscar)) ?>
                </p>
            </div>
        <?php else: ?>
        <?php
        /*
         * El relieve vive en la pieza que envuelve todo: las filas van planas
         * (§ tablas densas). La tabla ya no lleva caja propia — sería una caja
         * dentro de otra.
         */
        ?>
        <div class="rv-tabla overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="bg-elevado text-xs uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="whitespace-nowrap px-3 py-3 font-semibold">#</th>
                        <th class="px-3 py-3 font-semibold"><?= e($vista->t('eval.col_organizacion')) ?></th>
                        <th class="px-3 py-3 font-semibold"><?= e($vista->t('eval.col_area')) ?></th>
                        <th class="px-3 py-3 font-semibold"><?= e($vista->t('eval.col_fecha')) ?></th>
                        <th class="px-3 py-3 font-semibold"><?= e($vista->t('eval.col_estado')) ?></th>
                        <th class="px-3 py-3 font-semibold"><?= e($vista->t('eval.col_indice')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                <?php foreach ($visibles as $auditoria): ?>
                    <tr class="hover:bg-elevado">
                        <?php /* El id de auditoría es un identificador: mono y oro. */ ?>
                        <td class="px-3 py-3">
                            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>"
                               class="rv-id font-semibold hover:underline">
                                <?= e((string) $auditoria->id) ?>
                            </a>
                        </td>
                        <?php
                        /*
                         * La organización también enlaza. Hasta ahora el id era
                         * el único blanco de la fila, y son dos caracteres:
                         * quien apunta con el ratón apunta al nombre, que es lo
                         * que está leyendo.
                         */
                        ?>
                        <td class="px-3 py-3">
                            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>"
                               class="font-medium text-texto transition hover:text-primario">
                                <?= e($auditoria->organizacion) ?>
                            </a>
                        </td>
                        <td class="px-3 py-3 text-texto-2"><?= e($auditoria->areaEvaluada) ?></td>
                        <?php
                        /*
                         * La fecha no se parte. En la columna estrecha se
                         * rompía en «2026-» / «08-04», y una fecha en dos
                         * líneas deja de leerse como una fecha. El área sí
                         * puede seguir envolviendo: es prosa.
                         */
                        ?>
                        <td class="px-3 py-3 tabular whitespace-nowrap text-texto-2"><?= e($auditoria->fecha) ?></td>
                        <td class="px-3 py-3">
                            <?= $auditoria->estaFinalizada()
                                ? pill('ok', $vista->t('eval.finalizada'))
                                : pill('warn', $vista->t('eval.en_progreso')) ?>
                        </td>
                        <?php
                        /*
                         * Formato numérico español (§2.3): coma decimal y un
                         * decimal fijo. number_format con ',' de separador
                         * decimal y '' de millares — el índice no pasa de 5.
                         */
                        ?>
                        <td class="px-3 py-3 tabular whitespace-nowrap text-texto-2">
                            <?= $auditoria->indiceGeneralRiesgo === null
                                ? '<span class="text-na">' . e($vista->t('eval.sin_calcular')) . '</span>'
                                : e(number_format($auditoria->indiceGeneralRiesgo, 1, ',', '')) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        /*
         * Paginación. Se pinta aunque haya una sola página: el recuento
         * («1-4 de 23») es lo que dice cuánto hay detrás del filtro, y
         * esconderlo justo cuando el resultado cabe entero es esconder la
         * confirmación de que no falta nada.
         *
         * Los enlaces conservan empresa, búsqueda y orden — cambiar de página
         * no puede deshacer el filtro que llevó hasta ahí.
         */
        $desde = ($pagina - 1) * $porPagina + 1;
        $hasta = min($pagina * $porPagina, $encontradas);

        $parametros = [];

        if ($organizacion !== null) {
            $parametros['organizacion'] = $organizacion;
        }

        if ($buscar !== null) {
            $parametros['buscar'] = $buscar;
        }

        if ($orden !== 'reciente') {
            $parametros['orden'] = $orden;
        }

        $enlacePagina = fn (int $n): string =>
            $vista->url('evaluacion') . '?' . http_build_query($parametros + ['pagina' => $n]);
        ?>
        <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-borde p-4"
             aria-label="<?= e($vista->t('eval.paginacion')) ?>">

            <p class="text-xs text-texto-2">
                <?= e($vista->t('eval.rango', (string) $desde, (string) $hasta, (string) $encontradas)) ?>
            </p>

            <?php if ($paginas > 1): ?>
                <div class="flex items-center gap-1">
                    <?php
                    /*
                     * En los extremos el botón se pinta desactivado en vez de
                     * desaparecer: si se va, los demás se corren de sitio y se
                     * acaba pulsando otra página sin querer.
                     */
                    $clasesPaso = 'rounded-rv border border-borde px-2.5 py-1.5 text-xs font-medium transition';
                    ?>
                    <?php if ($pagina > 1): ?>
                        <a href="<?= e($enlacePagina($pagina - 1)) ?>"
                           rel="prev"
                           class="<?= e($clasesPaso) ?> text-texto-2 hover:bg-elevado hover:text-texto">
                            <?= e($vista->t('eval.anterior')) ?>
                        </a>
                    <?php else: ?>
                        <span class="<?= e($clasesPaso) ?> cursor-not-allowed text-texto-2/40" aria-disabled="true">
                            <?= e($vista->t('eval.anterior')) ?>
                        </span>
                    <?php endif; ?>

                    <?php for ($n = 1; $n <= $paginas; $n++): ?>
                        <?php $actual = $n === $pagina; ?>
                        <a href="<?= e($enlacePagina($n)) ?>"
                           <?= $actual ? 'aria-current="page"' : '' ?>
                           class="tabular min-w-[2rem] rounded-rv px-2.5 py-1.5 text-center text-xs font-semibold transition <?= $actual
                               ? 'rv-extruido bg-primario text-primario-texto'
                               : 'border border-borde text-texto-2 hover:bg-elevado hover:text-texto' ?>">
                            <?= $n ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagina < $paginas): ?>
                        <a href="<?= e($enlacePagina($pagina + 1)) ?>"
                           rel="next"
                           class="<?= e($clasesPaso) ?> text-texto-2 hover:bg-elevado hover:text-texto">
                            <?= e($vista->t('eval.siguiente')) ?>
                        </a>
                    <?php else: ?>
                        <span class="<?= e($clasesPaso) ?> cursor-not-allowed text-texto-2/40" aria-disabled="true">
                            <?= e($vista->t('eval.siguiente')) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        </section>
    <?php endif; ?>
</section>
