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
$colorZona = [
    'ROJO'     => 'bg-alerta-400/15 text-alerta-600 border-alerta-400/40',
    'AMARILLO' => 'bg-aviso-400/20 text-aviso-600 border-aviso-400/40',
    'VERDE'    => 'bg-exito-400/15 text-exito-600 border-exito-400/40',
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
    'ROJO'     => 'bg-alerta-500',
    'AMARILLO' => 'bg-aviso-400',
    'VERDE'    => 'bg-exito-500',
];

$porcentaje = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float) $v * 100, 1) . '%';

$hayRiesgoCritico = array_filter($exposicion, static fn ($r) => $r->zona === 'ROJO') !== [];

// $riesgo->etiqueta() devuelve el nombre en español (viene del dominio, no
// de la BD); se traduce aquí por tipo para no tocar la entidad.
$etiquetaTipo = [
    \App\Models\Entidades\ResultadoRiesgo::CONFIDENCIALIDAD => $vista->t('eval.confidencialidad'),
    \App\Models\Entidades\ResultadoRiesgo::INTEGRIDAD       => $vista->t('eval.integridad'),
    \App\Models\Entidades\ResultadoRiesgo::DISPONIBILIDAD   => $vista->t('eval.disponibilidad'),
];

// Conteo de controles por celda impacto x probabilidad, para la matriz.
$celdas = [];
foreach ($evaluaciones as $ev) {
    if ($ev->impacto === null || $ev->probabilidad === null) {
        continue;
    }
    $clave = $ev->impacto . '-' . $ev->probabilidad;
    $celdas[$clave] = ($celdas[$clave] ?? 0) + 1;
}

// Mismo promedio que EvaluacionControl::nivelRiesgoCalculado(), solo que
// aquí es por posición de la celda y no por un control puntual.
$colorCelda = static function (int $impacto, int $probabilidad) use ($fondoZona): string {
    $nivel = ($impacto + $probabilidad) / 2;

    return match (true) {
        $nivel <= 2   => $fondoZona['VERDE'],
        $nivel <= 3.5 => $fondoZona['AMARILLO'],
        default       => $fondoZona['ROJO'],
    };
};
?>
<section class="mx-auto w-full max-w-5xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="text-acento-600 hover:underline">
            ← <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
        </a>
    </nav>

    <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-marina-950"><?= e($vista->t('eval.resultados')) ?></h1>
            <p class="mt-1 text-slate-600">
                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?> · <?= e($auditoria->fecha) ?>
            </p>
        </div>
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reporte')) ?>"
           class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-marina-950 transition hover:bg-slate-50">
            <?= icono('imprimir', 'h-4 w-4') ?>
            <?= e($vista->t('eval.reporte_pdf')) ?>
        </a>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($hayRiesgoCritico): ?>
        <div role="alert"
             class="mb-8 flex items-start gap-3 rounded-xl border border-alerta-400/40 bg-alerta-400/10 px-4 py-3 text-sm text-alerta-600">
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
            <div class="rounded-2xl border border-slate-200 p-5">
                <p class="text-sm text-slate-500"><?= e($etiqueta) ?></p>
                <p class="mt-1 text-2xl font-extrabold tabular-nums text-marina-950"><?= e((string) $valor) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="mb-10 text-sm text-slate-600">
        <?= e($vista->t('eval.controles_respondidos')) ?>
        <strong><?= e((string) ($resumen['controles_si'] ?? 0)) ?></strong> <?= e($vista->t('eval.si_minuscula')) ?> ·
        <strong><?= e((string) ($resumen['controles_no'] ?? 0)) ?></strong> <?= e($vista->t('eval.no_minuscula')) ?> ·
        <strong><?= e((string) ($resumen['controles_na'] ?? 0)) ?></strong> <?= e($vista->t('eval.na_minuscula')) ?>
    </p>

    <!-- Exposición al riesgo C/I/D -->
    <h2 class="mb-4 text-xl font-bold text-marina-950"><?= e($vista->t('eval.exposicion_riesgo')) ?></h2>

    <?php if ($exposicion === []): ?>
        <p class="mb-10 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <?= e($vista->t('eval.sin_dimensiones')) ?>
        </p>
    <?php else: ?>
        <div class="mb-10 grid gap-4 sm:grid-cols-3">
            <?php foreach ($exposicion as $riesgo): ?>
                <div class="rounded-2xl border p-5 <?= e($colorZona[$riesgo->zona] ?? 'border-slate-200') ?>">
                    <p class="text-sm font-semibold"><?= e($etiquetaTipo[$riesgo->tipo] ?? $riesgo->etiqueta()) ?></p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums">
                        <?= e($riesgo->porcentaje() === null ? '—' : $riesgo->porcentaje() . '%') ?>
                    </p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide"><?= e((string) $riesgo->zona) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Matriz de riesgo impacto x probabilidad -->
    <h2 class="mb-4 text-xl font-bold text-marina-950"><?= e($vista->t('eval.matriz_riesgo')) ?></h2>

    <?php if ($celdas === []): ?>
        <p class="mb-10 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <?= e($vista->t('eval.sin_impacto_prob')) ?>
        </p>
    <?php else: ?>
        <div class="mb-10 overflow-x-auto rounded-2xl border border-slate-200 p-5">
            <div class="flex gap-2">
                <div class="flex w-8 shrink-0 flex-col-reverse justify-between py-1 text-xs font-semibold text-slate-500">
                    <?php for ($p = 1; $p <= 5; $p++): ?>
                        <span><?= $p ?></span>
                    <?php endfor; ?>
                </div>
                <div class="flex-1">
                    <div class="grid grid-cols-5 gap-1.5">
                        <?php for ($p = 5; $p >= 1; $p--): ?>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php $conteo = $celdas[$i . '-' . $p] ?? 0; ?>
                                <div class="flex aspect-square items-center justify-center rounded-lg text-sm font-bold text-white <?= e($colorCelda($i, $p)) ?> <?= $conteo === 0 ? 'opacity-25' : '' ?>"
                                     title="Impacto <?= $i ?> · Probabilidad <?= $p ?>">
                                    <?= $conteo > 0 ? e((string) $conteo) : '' ?>
                                </div>
                            <?php endfor; ?>
                        <?php endfor; ?>
                    </div>
                    <div class="mt-2 grid grid-cols-5 gap-1.5 text-center text-xs font-semibold text-slate-500">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span><?= $i ?></span>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-center text-xs text-slate-500"><?= e($vista->t('eval.eje_matriz')) ?></p>
        </div>
    <?php endif; ?>

    <!-- Cumplimiento por dominio -->
    <h2 class="mb-4 text-xl font-bold text-marina-950"><?= e($vista->t('eval.cumplimiento_dominio')) ?></h2>

    <?php if ($dominios === []): ?>
        <p class="mb-10 text-sm text-slate-600"><?= e($vista->t('eval.sin_controles_eval')) ?></p>
    <?php else: ?>
        <!-- Gráfico de barras -->
        <div class="mb-6 space-y-3 rounded-2xl border border-slate-200 p-5">
            <?php foreach ($dominios as $fila): ?>
                <?php $frac = $fila['cumplimiento'] ?? null; ?>
                <div class="flex items-center gap-3">
                    <span class="w-40 shrink-0 truncate text-sm text-slate-700" title="<?= e((string) $fila['nombre_dominio']) ?>">
                        <?= e((string) $fila['nombre_dominio']) ?>
                    </span>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full <?= e($fondoZona[$zonaDe($frac)] ?? 'bg-slate-300') ?>"
                             style="width: <?= $frac === null ? 0 : round($frac * 100) ?>%"></div>
                    </div>
                    <span class="w-14 shrink-0 text-right text-sm font-semibold tabular-nums text-marina-950">
                        <?= e($porcentaje($frac)) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Mapa de calor -->
        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($dominios as $fila): ?>
                <?php $zona = $zonaDe($fila['cumplimiento'] ?? null); ?>
                <div class="rounded-xl border p-4 <?= e($colorZona[$zona] ?? 'border-slate-200 bg-slate-50 text-slate-500') ?>">
                    <p class="text-sm font-semibold"><?= e((string) $fila['nombre_dominio']) ?></p>
                    <p class="mt-1 text-xl font-extrabold tabular-nums"><?= e($porcentaje($fila['cumplimiento'] ?? null)) ?></p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide"><?= e($zona ?? $vista->t('eval.sin_datos')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-10 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_dominio')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.estado_si')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.estado_no')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.estado_na')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_cumplimiento')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_madurez')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($dominios as $fila): ?>
                    <tr>
                        <td class="px-4 py-3 font-medium text-marina-950"><?= e((string) $fila['nombre_dominio']) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= e((string) $fila['controles_si']) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= e((string) $fila['controles_no']) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= e((string) $fila['controles_na']) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= e($porcentaje($fila['cumplimiento'] ?? null)) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= e((string) ($fila['madurez_promedio'] ?? '—')) ?></td>
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
                <h2 class="mb-4 text-xl font-bold text-marina-950"><?= e($titulo) ?></h2>
                <?php if ($filas === []): ?>
                    <p class="text-sm text-slate-600"><?= e($vista->t('eval.sin_datos_suficientes')) ?></p>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($filas as $fila): ?>
                            <li class="rounded-xl border border-slate-200 p-4">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="font-semibold text-marina-950"><?= e((string) $fila['codigo_control']) ?></span>
                                    <span class="tabular-nums text-sm font-semibold text-slate-600">
                                        <?= e((string) ($fila[$columna] ?? '—')) ?>
                                        <?php if (!empty($fila['dimensiones'])): ?>
                                            · <?= e((string) $fila['dimensiones']) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">
                                    <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 120, '…')) ?>
                                </p>
                                <p class="mt-1 text-xs text-slate-400"><?= e((string) $fila['dominio']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
