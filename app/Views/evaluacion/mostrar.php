<?php

declare(strict_types=1);

/**
 * Detalle de una auditoría: encabezado, avance y los 75 controles.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var list<\App\Models\Entidades\Control> $controles
 * @var array<int, \App\Models\Entidades\Proceso> $procesos
 * @var array<string, \App\Models\Entidades\EvaluacionControl> $evaluaciones
 * @var int $evaluados
 * @var int $total
 * @var list<\App\Models\Entidades\Usuario> $administradores
 * @var array<string, string> $errores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$abierta = !$auditoria->estaFinalizada();
$porcentaje = $total > 0 ? round($evaluados / $total * 100) : 0;

$valores = [
    'administrador' => (string) $auditoria->idAdministradorBd,
    'area'          => $auditoria->areaEvaluada,
    'fecha'         => $auditoria->fecha,
];

$etiquetaEstado = [
    'SI' => [$vista->t('eval.estado_si'), 'bg-exito-400/15 text-exito-600'],
    'NO' => [$vista->t('eval.estado_no'), 'bg-alerta-400/15 text-alerta-600'],
    'NA' => [$vista->t('eval.estado_na'), 'bg-slate-200 text-slate-600'],
];
?>
<section class="mx-auto w-full max-w-5xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-acento-600 hover:underline">
            <?= e($vista->t('eval.volver_auditorias')) ?>
        </a>
    </nav>

    <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-extrabold text-marina-950">
                    <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
                </h1>
                <?php if ($abierta): ?>
                    <span class="rounded-full bg-aviso-400/20 px-2.5 py-1 text-xs font-semibold text-aviso-600">
                        <?= e($vista->t('eval.en_progreso')) ?>
                    </span>
                <?php else: ?>
                    <span class="rounded-full bg-exito-400/15 px-2.5 py-1 text-xs font-semibold text-exito-600">
                        <?= e($vista->t('eval.finalizada')) ?>
                    </span>
                <?php endif; ?>
            </div>
            <p class="mt-1 text-slate-600">
                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?>
            </p>
            <p class="mt-0.5 text-sm text-slate-500">
                <?= e($auditoria->fecha) ?> · <?= e($vista->t('eval.entrevistado')) ?>
                <?= e($auditoria->nombreAdministradorBd) ?>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/resultados')) ?>"
               class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-marina-950 transition hover:bg-slate-50">
                <?= e($vista->t('eval.ver_resultados')) ?>
            </a>

            <?php if ($abierta): ?>
                <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/finalizar')) ?>">
                    <?= $vista->campoToken() ?>
                    <button type="submit"
                            class="rounded-lg bg-marina-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-marina-900">
                        <?= e($vista->t('eval.finalizar')) ?>
                    </button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reabrir')) ?>">
                    <?= $vista->campoToken() ?>
                    <button type="submit"
                            class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-marina-950 transition hover:bg-slate-50">
                        <?= e($vista->t('eval.reabrir')) ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if (!$abierta): ?>
        <p class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <?= e($vista->t('eval.aviso_finalizada')) ?>
        </p>
    <?php endif; ?>

    <!-- Avance -->
    <div class="mb-10 rounded-2xl border border-slate-200 p-5">
        <div class="flex items-baseline justify-between">
            <p class="text-sm font-semibold text-marina-950"><?= e($vista->t('eval.avance')) ?></p>
            <p class="text-sm tabular-nums text-slate-600">
                <?= e((string) $evaluados) ?> <?= e($vista->t('eval.de_controles')) ?> <?= e((string) $total) ?> <?= e($vista->t('eval.controles_palabra')) ?>
                (<?= e((string) $porcentaje) ?>%)
            </p>
        </div>
        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-200">
            <div class="h-full rounded-full bg-acento-500" style="width: <?= e((string) $porcentaje) ?>%"></div>
        </div>
    </div>

    <!-- Encabezado editable -->
    <?php if ($abierta): ?>
    <details class="mb-10 rounded-2xl border border-slate-200 p-5" <?= $errores !== [] ? 'open' : '' ?>>
        <summary class="cursor-pointer text-sm font-semibold text-marina-950">
            <?= e($vista->t('eval.editar_encabezado')) ?>
        </summary>
        <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="mt-5 space-y-5">
            <?= $vista->campoToken() ?>
            <?= $vista->renderizar('evaluacion/_encabezado-form', compact('errores', 'valores', 'administradores')) ?>
            <button type="submit"
                    class="rounded-lg bg-marina-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-marina-900">
                <?= e($vista->t('eval.guardar_encabezado')) ?>
            </button>
        </form>
    </details>
    <?php endif; ?>

    <!-- Controles -->
    <h2 class="mb-4 text-xl font-bold text-marina-950"><?= e($vista->t('eval.controles_instrumento')) ?></h2>

    <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full min-w-[48rem] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_codigo')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_proceso')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_enunciado')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_resp')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_madurez')) ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($controles as $control): ?>
                <?php
                $evaluacion = $evaluaciones[$control->id] ?? null;
                $estado = $evaluacion?->estado;
                [$texto, $clase] = $etiquetaEstado[$estado] ?? ['—', 'text-slate-400'];
                ?>
                <tr class="align-top hover:bg-slate-50">
                    <td class="px-4 py-3 whitespace-nowrap">
                        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/controles/' . $control->id)) ?>"
                           class="font-semibold text-acento-600 hover:underline">
                            <?= e($control->id) ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-slate-600">
                        <?= e($procesos[$control->proceso]->nombre ?? '—') ?>
                    </td>
                    <td class="px-4 py-3 text-slate-700">
                        <?= e(mb_strimwidth($control->enunciado, 0, 110, '…')) ?>
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= e($clase) ?>">
                            <?= e($texto) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 tabular-nums text-slate-700">
                        <?= $evaluacion?->madurez === null ? '<span class="text-slate-400">—</span>' : e((string) $evaluacion->madurez) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
