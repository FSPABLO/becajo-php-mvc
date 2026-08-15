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

/*
 * Estado de cada control -> tono de la escala semántica (§4).
 *
 * 'NA' es gris neutro a propósito, nunca verde: teñir de verde un control
 * excluido inflaría visualmente el cumplimiento y contradiría la lógica de la
 * Declaración de Aplicabilidad, donde esos controles salen del denominador.
 */
$tonoEstado = [
    'SI' => ['ok',   $vista->t('eval.estado_si')],
    'NO' => ['bad',  $vista->t('eval.estado_no')],
    'NA' => ['na',   $vista->t('eval.estado_na')],
];
?>
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-primario hover:underline">
            <?= e($vista->t('eval.volver_auditorias')) ?>
        </a>
    </nav>

    <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="rv-titulo text-3xl font-semibold text-texto">
                    <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
                </h1>
                <?= $abierta
                    ? pill('warn', $vista->t('eval.en_progreso'))
                    : pill('ok', $vista->t('eval.finalizada')) ?>
            </div>
            <p class="mt-1 text-texto-2">
                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?>
            </p>
            <p class="mt-0.5 text-sm text-texto-2">
                <?= e($auditoria->fecha) ?> · <?= e($vista->t('eval.entrevistado')) ?>
                <?= e($auditoria->nombreAdministradorBd) ?>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/resultados')) ?>"
               class="rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                <?= e($vista->t('eval.ver_resultados')) ?>
            </a>

            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/evidencias')) ?>"
               class="rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                Evidencia
            </a>

            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/remediaciones')) ?>"
               class="rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                Remediaciones
            </a>

            <?php if ($abierta): ?>
                <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/finalizar')) ?>">
                    <?= $vista->campoToken() ?>
                    <button type="submit"
                            class="rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto">
                        <?= e($vista->t('eval.finalizar')) ?>
                    </button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reabrir')) ?>">
                    <?= $vista->campoToken() ?>
                    <button type="submit"
                            class="rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                        <?= e($vista->t('eval.reabrir')) ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if (!$abierta): ?>
        <p class="mb-6 rounded-rv-lg border border-borde bg-elevado px-4 py-3 text-sm text-texto-2">
            <?= e($vista->t('eval.aviso_finalizada')) ?>
        </p>
    <?php endif; ?>

    <!-- Avance -->
    <div class="mb-10 rounded-rv-lg border border-borde p-5">
        <div class="flex items-baseline justify-between">
            <p class="text-sm font-semibold text-texto"><?= e($vista->t('eval.avance')) ?></p>
            <p class="text-sm tabular text-texto-2">
                <?= e((string) $evaluados) ?> <?= e($vista->t('eval.de_controles')) ?> <?= e((string) $total) ?> <?= e($vista->t('eval.controles_palabra')) ?>
                (<?= e((string) $porcentaje) ?>%)
            </p>
        </div>
        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-elevado">
            <div class="h-full rounded-full bg-primario" style="width: <?= e((string) $porcentaje) ?>%"></div>
        </div>
    </div>

    <!-- Encabezado editable -->
    <?php if ($abierta): ?>
    <details class="mb-10 rounded-rv-lg border border-borde p-5" <?= $errores !== [] ? 'open' : '' ?>>
        <summary class="cursor-pointer text-sm font-semibold text-texto">
            <?= e($vista->t('eval.editar_encabezado')) ?>
        </summary>
        <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="mt-5 space-y-5">
            <?= $vista->campoToken() ?>
            <?= $vista->renderizar('evaluacion/_encabezado-form', compact('errores', 'valores', 'administradores')) ?>
            <button type="submit"
                    class="rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto">
                <?= e($vista->t('eval.guardar_encabezado')) ?>
            </button>
        </form>
    </details>
    <?php endif; ?>

    <!-- Controles -->
    <h2 class="mb-4 text-xl font-bold text-texto"><?= e($vista->t('eval.controles_instrumento')) ?></h2>

    <?php /* Relieve sutil y solo en el contenedor: 75 filas con sombra propia
             convertirían la tabla en un relieve y no en un dato legible. */ ?>
    <div class="rv-extruido rv-relieve-sutil rv-tabla overflow-x-auto rounded-rv-lg border border-borde bg-superficie">
        <table class="w-full min-w-[48rem] text-left text-sm">
            <thead class="bg-elevado text-xs uppercase tracking-wide text-texto-2">
                <tr>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_codigo')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_proceso')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_enunciado')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_resp')) ?></th>
                    <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_madurez')) ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-borde">
            <?php foreach ($controles as $control): ?>
                <?php
                $evaluacion = $evaluaciones[$control->id] ?? null;
                $estado = $evaluacion?->estado;
                $tono = $tonoEstado[$estado] ?? null;
                ?>
                <tr class="align-top hover:bg-elevado">
                    <?php /* Código del control: identificador, luego mono y oro. */ ?>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/controles/' . $control->id)) ?>"
                           class="rv-id font-medium hover:underline">
                            <?= e($control->id) ?>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-texto-2">
                        <?= e($procesos[$control->proceso]->nombre ?? '—') ?>
                    </td>
                    <td class="rv-titulo px-4 py-3 text-[1rem] text-texto-2">
                        <?= e(mb_strimwidth($control->enunciado, 0, 110, '…')) ?>
                    </td>
                    <td class="px-4 py-3">
                        <?= $tono === null
                            ? '<span class="text-na">—</span>'
                            : pill($tono[0], $tono[1]) ?>
                    </td>
                    <td class="px-4 py-3 tabular text-texto-2">
                        <?= $evaluacion?->madurez === null
                            ? '<span class="text-na">—</span>'
                            : e(number_format((float) $evaluacion->madurez, 1, ',', '')) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
