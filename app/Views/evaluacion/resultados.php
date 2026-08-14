<?php

declare(strict_types=1);

/**
 * Indicadores de la auditoría. Todo lo que se muestra aquí sale de
 * pkg_indicadores: ni una cifra se calcula en PHP.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var array<string, mixed> $resumen
 * @var list<array<string, mixed>> $dominios
 * @var list<\App\Models\Entidades\ResultadoRiesgo> $exposicion
 * @var list<array<string, mixed>> $menorMadurez
 * @var list<array<string, mixed>> $mayorRiesgo
 * @var list<\App\Models\Entidades\EvaluacionControl> $evaluaciones
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
/*
 * Zona -> tono de la escala semántica, y zona -> etiqueta legible.
 *
 * La etiqueta nombra el NIVEL DE RIESGO y no el color: «ROJO» es inútil para
 * quien no distingue el color o lee el informe impreso en gris. La base sigue
 * guardando ROJO/AMARILLO/VERDE (así lo define el CHECK de resultado_riesgo);
 * la traducción a algo legible ocurre aquí, en la vista.
 */
$tonoZona = [
    'ROJO'     => 'crit',
    'AMARILLO' => 'warn',
    'VERDE'    => 'ok',
];

$etiquetaZona = [
    'ROJO'     => $vista->t('eval.zona_alta'),
    'AMARILLO' => $vista->t('eval.zona_media'),
    'VERDE'    => $vista->t('eval.zona_baja'),
];

// Mismos cortes que fn_zona en pkg_indicadores, para que el color del
// dominio coincida con el de las tarjetas de exposición.
$zonaDe = static function (mixed $frac): ?string {
    if ($frac === null) {
        return null;
    }

    $frac = (float) $frac;

    return match (true) {
        $frac < 0.5 => 'ROJO',
        $frac < 0.8 => 'AMARILLO',
        default     => 'VERDE',
    };
};

$fondoZona = [
    'ROJO'     => 'bg-bad',
    'AMARILLO' => 'bg-warn',
    'VERDE'    => 'bg-ok',
];

$porcentaje = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float) $v * 100, 1, ',', '') . ' %';

$hayRiesgoCritico = array_filter($exposicion, static fn ($r) => $r->zona === 'ROJO') !== [];

// $riesgo->etiqueta() devuelve el nombre en español (viene del dominio, no
// de la BD); se traduce aquí por tipo para no tocar la entidad.
$etiquetaTipo = [
    \App\Models\Entidades\ResultadoRiesgo::CONFIDENCIALIDAD => $vista->t('eval.confidencialidad'),
    \App\Models\Entidades\ResultadoRiesgo::INTEGRIDAD       => $vista->t('eval.integridad'),
    \App\Models\Entidades\ResultadoRiesgo::DISPONIBILIDAD   => $vista->t('eval.disponibilidad'),
];

?>
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="text-primario hover:underline">
            ← <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
        </a>
    </nav>

    <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="rv-titulo text-3xl font-semibold text-texto"><?= e($vista->t('eval.resultados')) ?></h1>
            <p class="mt-1 text-texto-2">
                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?> · <?= e($auditoria->fecha) ?>
            </p>
        </div>
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reporte')) ?>"
           class="inline-flex shrink-0 items-center gap-2 rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
            <?= icono('imprimir', 'h-4 w-4') ?>
            <?= e($vista->t('eval.reporte_pdf')) ?>
        </a>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($hayRiesgoCritico): ?>
        <div role="alert"
             class="mb-8 flex items-start gap-3 rounded-rv-lg border border-bad/10 bg-bad/10 px-4 py-3 text-sm text-bad">
            <?= icono('alerta', 'h-5 w-5 shrink-0') ?>
            <span><?= e($vista->t('eval.aviso_riesgo_critico')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Resumen general -->
    <div class="mb-10 grid gap-4 sm:grid-cols-3">
        <?php
        $tarjetas = [
            [$vista->t('eval.cumplimiento_general'), $porcentaje($resumen['cumplimiento'] ?? null)],
            [$vista->t('eval.madurez_promedio'), $resumen['madurez_promedio'] ?? '—'],
            [$vista->t('eval.indice_general_riesgo'), $auditoria->indiceGeneralRiesgo === null
                ? $vista->t('eval.sin_calcular') : number_format($auditoria->indiceGeneralRiesgo, 2)],
        ];
        ?>
        <?php foreach ($tarjetas as [$etiqueta, $valor]): ?>
            <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
                <p class="text-sm text-texto-2"><?= e($etiqueta) ?></p>
                <p class="mt-1 text-2xl font-extrabold tabular text-texto"><?= e((string) $valor) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="mb-10 text-sm text-texto-2">
        <?= e($vista->t('eval.controles_respondidos')) ?>
        <strong><?= e((string) ($resumen['controles_si'] ?? 0)) ?></strong> <?= e($vista->t('eval.si_minuscula')) ?> ·
        <strong><?= e((string) ($resumen['controles_no'] ?? 0)) ?></strong> <?= e($vista->t('eval.no_minuscula')) ?> ·
        <strong><?= e((string) ($resumen['controles_na'] ?? 0)) ?></strong> <?= e($vista->t('eval.na_minuscula')) ?>
    </p>

    <!-- Exposición al riesgo C/I/D -->
    <h2 class="mb-4 text-xl font-bold text-texto"><?= e($vista->t('eval.exposicion_riesgo')) ?></h2>

    <?php if ($exposicion === []): ?>
        <p class="mb-10 rounded-rv-lg border border-borde bg-elevado px-4 py-3 text-sm text-texto-2">
            <?= e($vista->t('eval.sin_dimensiones')) ?>
        </p>
    <?php else: ?>
        <div class="mb-10 grid gap-4 sm:grid-cols-3">
            <?php foreach ($exposicion as $riesgo): ?>
                <?php
                /*
                 * La zona no se comunica solo por color: el pill trae además
                 * ícono y etiqueta. Y la etiqueta nombra el nivel de riesgo,
                 * no el color — «ROJO» no le dice nada a quien no lo ve.
                 */
                ?>
                <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
                    <p class="text-sm font-semibold text-texto-2"><?= e($etiquetaTipo[$riesgo->tipo] ?? $riesgo->etiqueta()) ?></p>
                    <p class="tabular mt-1 text-2xl font-semibold text-texto">
                        <?= $riesgo->porcentaje() === null
                            ? '—'
                            : e(number_format((float) $riesgo->porcentaje(), 1, ',', '')) . ' %' ?>
                    </p>
                    <p class="mt-2"><?= pill($tonoZona[$riesgo->zona] ?? 'na', $etiquetaZona[$riesgo->zona] ?? '—') ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Matriz de riesgo impacto x probabilidad -->
    <h2 class="mb-4 text-xl font-bold text-texto"><?= e($vista->t('eval.matriz_riesgo')) ?></h2>

    <?php
    /*
     * La matriz se comparte con el panel de entrada (components/matriz-riesgo).
     * El conteo por celda y los cortes de zona viven allí, en un solo sitio:
     * son los mismos que aplica fn_zona en pkg_indicadores y dos copias se
     * separan en cuanto alguien mueva un corte en la base.
     */
    ?>
    <div class="mb-10 overflow-x-auto rounded-rv-lg border border-borde p-5">
        <?= $vista->componente('matriz-riesgo', [
            'vista'        => $vista,
            'evaluaciones' => $evaluaciones,
        ]) ?>
    </div>

    <!-- Cumplimiento por dominio -->
    <h2 class="mb-4 text-xl font-bold text-texto"><?= e($vista->t('eval.cumplimiento_dominio')) ?></h2>

    <?php if ($dominios === []): ?>
        <p class="mb-10 text-sm text-texto-2"><?= e($vista->t('eval.sin_controles_eval')) ?></p>
    <?php else: ?>
        <!-- Gráfico de barras -->
        <div class="mb-6 space-y-3 rounded-rv-lg border border-borde p-5">
            <?php foreach ($dominios as $fila): ?>
                <?php $frac = $fila['cumplimiento'] ?? null; ?>
                <div class="flex items-center gap-3">
                    <span class="w-40 shrink-0 truncate text-sm text-texto-2" title="<?= e((string) $fila['nombre_dominio']) ?>">
                        <?= e((string) $fila['nombre_dominio']) ?>
                    </span>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-elevado">
                        <div class="h-full rounded-full <?= e($fondoZona[$zonaDe($frac)] ?? 'bg-na') ?>"
                             style="width: <?= $frac === null ? 0 : round($frac * 100) ?>%"></div>
                    </div>
                    <span class="w-14 shrink-0 text-right text-sm font-semibold tabular text-texto">
                        <?= e($porcentaje($frac)) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mapa de calor -->
        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($dominios as $fila): ?>
                <?php $zona = $zonaDe($fila['cumplimiento'] ?? null); ?>
                <div class="rv-extruido rv-relieve-sutil rounded-rv-lg border border-borde bg-superficie p-4">
                    <p class="text-sm font-semibold text-texto-2"><?= e((string) $fila['nombre_dominio']) ?></p>
                    <p class="tabular mt-1 text-xl font-semibold text-texto"><?= e($porcentaje($fila['cumplimiento'] ?? null)) ?></p>
                    <p class="mt-2">
                        <?= $zona === null
                            ? pill('na', $vista->t('eval.sin_datos'))
                            : pill($tonoZona[$zona] ?? 'na', $etiquetaZona[$zona] ?? '—') ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-10 overflow-x-auto rounded-rv-lg border border-borde">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead class="bg-elevado text-xs uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_dominio')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.estado_si')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.estado_no')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.estado_na')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_cumplimiento')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_madurez')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                <?php foreach ($dominios as $fila): ?>
                    <tr>
                        <td class="px-4 py-3 font-medium text-texto"><?= e((string) $fila['nombre_dominio']) ?></td>
                        <td class="px-4 py-3 tabular"><?= e((string) $fila['controles_si']) ?></td>
                        <td class="px-4 py-3 tabular"><?= e((string) $fila['controles_no']) ?></td>
                        <td class="px-4 py-3 tabular"><?= e((string) $fila['controles_na']) ?></td>
                        <td class="px-4 py-3 tabular"><?= e($porcentaje($fila['cumplimiento'] ?? null)) ?></td>
                        <td class="px-4 py-3 tabular"><?= e((string) ($fila['madurez_promedio'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Listas críticas -->
    <div class="grid gap-8 lg:grid-cols-2">
        <?php
        $listas = [
            [$vista->t('eval.menor_madurez'), $menorMadurez, 'madurez'],
            [$vista->t('eval.mayor_riesgo'), $mayorRiesgo, 'nivel_riesgo'],
        ];
        ?>
        <?php foreach ($listas as [$titulo, $filas, $columna]): ?>
            <div>
                <h2 class="mb-4 text-xl font-bold text-texto"><?= e($titulo) ?></h2>
                <?php if ($filas === []): ?>
                    <p class="text-sm text-texto-2"><?= e($vista->t('eval.sin_datos_suficientes')) ?></p>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($filas as $fila): ?>
                            <li class="rounded-rv-lg border border-borde p-4">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="font-semibold text-texto"><?= e((string) $fila['codigo_control']) ?></span>
                                    <span class="tabular text-sm font-semibold text-texto-2">
                                        <?= e((string) ($fila[$columna] ?? '—')) ?>
                                        <?php if (!empty($fila['dimensiones'])): ?>
                                            · <?= e((string) $fila['dimensiones']) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-texto-2">
                                    <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 120, '…')) ?>
                                </p>
                                <p class="mt-1 text-xs text-texto-2"><?= e((string) $fila['dominio']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
