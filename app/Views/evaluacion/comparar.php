<?php

declare(strict_types=1);

/**
 * Comparación histórica: una tarjeta por organización auditada.
 *
 * Cada tarjeta responde tres preguntas distintas y por eso lleva tres piezas,
 * en orden de lo general a lo detallado:
 *
 *   ¿va mejorando?      — columnas del índice general, una por auditoría.
 *   ¿en qué está floja? — perfil de madurez por dominio de la última auditoría.
 *   ¿cuánto, exactamente, y desde cuándo? — la tabla del desglose.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var array<string, list<\App\Models\Entidades\Auditoria>> $porOrganizacion
 * @var array<string, list<array<string, mixed>>> $historicoPorOrganizacion
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

/*
 * Los ids de <defs> de un SVG son globales al documento: dos gráficos con el
 * mismo id de filtro se pisan y el segundo se queda sin relieve. Cada tarjeta
 * numera los suyos con este contador en vez de con el nombre de la
 * organización, que trae acentos y espacios.
 */
$nTarjeta = 0;
?>
<?php
/*
 * Sin encabezado propio ni enlace de vuelta: los dos los pone ya el marco del
 * módulo. La miga de pan de la barra superior dice «Auditorías / Comparar
 * histórico» —que es el título y el camino de regreso a la vez— y la barra
 * lateral tiene «Mis auditorías» iluminado. Repetirlos aquí gastaba el primer
 * tercio de la pantalla en decir tres veces dónde está uno, y empujaba hacia
 * abajo lo único que esta vista aporta, que son las tarjetas.
 */
?>
<section class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-8">

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($porOrganizacion === []): ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto"><?= e($vista->t('eval.sin_auditorias_comparar')) ?></p>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($porOrganizacion as $organizacion => $grupo): ?>
                <?php
                $nTarjeta++;

                $ultimaAuditoria = $grupo[count($grupo) - 1];

                /*
                 * Desglose por dominio, reagrupado por AUDITORÍA. El
                 * procedimiento devuelve una fila por (auditoría, dominio) y ya
                 * ordenada por fecha, así que el orden de aparición es el orden
                 * cronológico: se conserva tal cual en vez de reordenar por la
                 * cadena de la fecha, que según el formato con que la sirva
                 * Oracle puede no ordenar como fecha.
                 *
                 * Los dominios se indexan por CLAVE y se ordenan por la columna
                 * 'orden' del instrumento, no por la clave ni por el orden en
                 * que aparezcan: los ejes del radar y las filas de la tabla
                 * tienen que salir en la misma sucesión para todas las
                 * organizaciones de la página, y alfabéticamente 'dato'
                 * (Protección) caería entre Continuidad y Gobierno.
                 */
                $historico = $historicoPorOrganizacion[$organizacion] ?? [];
                $columnas  = [];
                $dominios  = [];

                foreach ($historico as $fila) {
                    $idAuditoria = (string) $fila['id_auditoria'];
                    $clave       = (string) $fila['clave_dominio'];

                    $dominios[$clave] = [
                        'nombre' => (string) $fila['dominio'],
                        // Con un procedimiento sin recargar la columna no llega
                        // y todos empatan a cero; la ordenación es estable, así
                        // que en ese caso se queda el orden del cursor.
                        'orden'  => (int) ($fila['orden_dominio'] ?? 0),
                    ];

                    $columnas[$idAuditoria]['fecha']            = (string) $fila['fecha'];
                    $columnas[$idAuditoria]['valores'][$clave]  = (float) $fila['madurez_promedio'];
                }

                uasort($dominios, static fn (array $a, array $b): int
                    => [$a['orden'], $a['nombre']] <=> [$b['orden'], $b['nombre']]);

                /*
                 * Ejes del radar: los dominios que evaluó la ÚLTIMA auditoría
                 * con desglose. Los que esa auditoría no tocó no entran —no
                 * valen cero— y por eso el radar puede tener menos ejes que
                 * filas la tabla.
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
                ?>
                <article class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5 sm:p-6">

                    <header class="mb-5 flex flex-wrap items-start justify-between gap-x-4 gap-y-2">
                        <div class="min-w-0">
                            <h2 class="rv-titulo text-xl font-semibold text-texto"><?= e($organizacion) ?></h2>
                            <p class="mt-0.5 text-xs text-texto-2">
                                <?= e($vista->t(
                                    'eval.auditorias_de_organizacion',
                                    (string) count($grupo),
                                    $ultimaAuditoria->fecha,
                                )) ?>
                            </p>
                        </div>

                        <?php
                        /*
                         * Un solo enlace en la cabecera, a la auditoría más
                         * reciente. Las anteriores se alcanzan desde su propia
                         * columna del gráfico, que es donde el lector ya está
                         * mirando cuando quiere abrir una.
                         */
                        ?>
                        <a href="<?= e($vista->url('evaluacion/' . $ultimaAuditoria->id . '/resultados')) ?>"
                           class="shrink-0 text-sm font-medium text-primario hover:underline">
                            <?= e($vista->t('eval.ver_resultados')) ?> →
                        </a>
                    </header>

                    <?php if (count($grupo) < 2): ?>
                        <p class="rv-hundido rv-relieve-sutil mb-5 rounded-rv border border-borde bg-fondo px-4 py-3 text-sm text-texto-2">
                            <?= e($vista->t('eval.solo_una_auditoria')) ?>
                        </p>
                    <?php endif; ?>

                    <?php
                    /*
                     * El reparto 3/2 no es estético: la serie de tiempo
                     * necesita anchura o las pendientes se exageran, mientras
                     * que el radar es cuadrado y se lee entero de un vistazo.
                     * Es el mismo criterio del tablero de entrada.
                     */
                    ?>
                    <div class="grid gap-6 lg:grid-cols-5">

                        <section class="lg:col-span-3">
                            <h3 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.indice_por_auditoria')) ?></h3>
                            <p class="mb-3 mt-0.5 text-xs text-texto-2"><?= e($vista->t('eval.indice_por_auditoria_texto')) ?></p>

                            <?= $vista->componente('indice-historico', [
                                'vista'      => $vista,
                                'auditorias' => $grupo,
                                'id'         => (string) $nTarjeta,
                            ]) ?>
                        </section>

                        <section class="lg:col-span-2">
                            <h3 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.perfil_dominios')) ?></h3>
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
                                    'id'    => (string) $nTarjeta,
                                ]) ?>
                            <?php endif; ?>
                        </section>
                    </div>

                    <?php if ($columnas !== []): ?>
                        <?php
                        /*
                         * El desglose completo. En tabla densa el relieve no
                         * debe competir con la lectura del dato: va hundido y
                         * en el nivel sutil, y las filas no llevan sombra
                         * propia (.rv-tabla en rivendel.css).
                         */
                        ?>
                        <section class="mt-6">
                            <h3 class="mb-2 text-sm font-semibold text-texto">
                                <?= e($vista->t('eval.madurez_por_dominio')) ?>
                            </h3>

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
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
