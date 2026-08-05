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
?>
<section class="mx-auto w-full max-w-5xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="text-acento-600 hover:underline">
            ← Auditoría <?= e((string) $auditoria->id) ?>
        </a>
    </nav>

    <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-marina-950">Resultados</h1>
            <p class="mt-1 text-slate-600">
                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?> · <?= e($auditoria->fecha) ?>
            </p>
        </div>
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reporte')) ?>"
           class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-marina-950 transition hover:bg-slate-50">
            <?= icono('imprimir', 'h-4 w-4') ?>
            Reporte ejecutivo (PDF)
        </a>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($hayRiesgoCritico): ?>
        <div role="alert"
             class="mb-8 flex items-start gap-3 rounded-xl border border-alerta-400/40 bg-alerta-400/10 px-4 py-3 text-sm text-alerta-600">
            <?= icono('alerta', 'h-5 w-5 shrink-0') ?>
            <span>Esta auditoría tiene al menos una dimensión (C, I o D) en zona roja. Revise la exposición al riesgo antes de entregar el informe.</span>
        </div>
    <?php endif; ?>

    <!-- Resumen general -->
    <div class="mb-10 grid gap-4 sm:grid-cols-3">
        <?php
        $tarjetas = [
            ['Cumplimiento general', $porcentaje($resumen['cumplimiento'] ?? null)],
            ['Madurez promedio', $resumen['madurez_promedio'] ?? '—'],
            ['Índice general de riesgo', $auditoria->indiceGeneralRiesgo === null
                ? 'sin calcular' : number_format($auditoria->indiceGeneralRiesgo, 2)],
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
        Controles respondidos:
        <strong><?= e((string) ($resumen['controles_si'] ?? 0)) ?></strong> sí ·
        <strong><?= e((string) ($resumen['controles_no'] ?? 0)) ?></strong> no ·
        <strong><?= e((string) ($resumen['controles_na'] ?? 0)) ?></strong> no aplica
    </p>

    <!-- Exposición al riesgo C/I/D -->
    <h2 class="mb-4 text-xl font-bold text-marina-950">Exposición al riesgo</h2>

    <?php if ($exposicion === []): ?>
        <p class="mb-10 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            Todavía no hay controles con dimensiones marcadas y madurez calificada.
        </p>
    <?php else: ?>
        <div class="mb-10 grid gap-4 sm:grid-cols-3">
            <?php foreach ($exposicion as $riesgo): ?>
                <div class="rounded-2xl border p-5 <?= e($colorZona[$riesgo->zona] ?? 'border-slate-200') ?>">
                    <p class="text-sm font-semibold"><?= e($riesgo->etiqueta()) ?></p>
                    <p class="mt-1 text-2xl font-extrabold tabular-nums">
                        <?= e($riesgo->porcentaje() === null ? '—' : $riesgo->porcentaje() . '%') ?>
                    </p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide"><?= e((string) $riesgo->zona) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Cumplimiento por dominio -->
    <h2 class="mb-4 text-xl font-bold text-marina-950">Cumplimiento por dominio</h2>

    <?php if ($dominios === []): ?>
        <p class="mb-10 text-sm text-slate-600">Sin controles evaluados todavía.</p>
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
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide"><?= e($zona ?? 'sin datos') ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mb-10 overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Dominio</th>
                        <th class="px-4 py-3 font-semibold">Sí</th>
                        <th class="px-4 py-3 font-semibold">No</th>
                        <th class="px-4 py-3 font-semibold">N/A</th>
                        <th class="px-4 py-3 font-semibold">Cumplimiento</th>
                        <th class="px-4 py-3 font-semibold">Madurez</th>
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
            ['Controles con menor madurez', $menorMadurez, 'madurez'],
            ['Controles con mayor riesgo', $mayorRiesgo, 'nivel_riesgo'],
        ];
        ?>
        <?php foreach ($listas as [$titulo, $filas, $columna]): ?>
            <div>
                <h2 class="mb-4 text-xl font-bold text-marina-950"><?= e($titulo) ?></h2>
                <?php if ($filas === []): ?>
                    <p class="text-sm text-slate-600">Sin datos suficientes.</p>
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
