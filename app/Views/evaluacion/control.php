<?php

declare(strict_types=1);

/**
 * La plantilla en pantalla: donde el auditor califica UN control.
 *
 * Es la pantalla central del sistema. Los name= de los campos son los que lee
 * AuditoriaController::leerRespuesta(), no cambiarlos sin actualizar ese método.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var \App\Models\Entidades\Control $control
 * @var \App\Models\Entidades\Proceso|null $proceso
 * @var \App\Models\Entidades\EvaluacionControl|null $evaluacion
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 * @var list<string> $estados
 * @var list<string> $criterios
 * @var array{anterior: ?\App\Models\Entidades\Control, siguiente: ?\App\Models\Entidades\Control} $vecinos
 * @var array<string, string> $errores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$abierta = !$auditoria->estaFinalizada();
$base = 'evaluacion/' . $auditoria->id;

$etiquetaEstado = ['SI' => $vista->t('eval.estado_si'), 'NO' => $vista->t('eval.estado_no'), 'NA' => $vista->t('eval.estado_na')];
$etiquetaCriterio = [
    'DOCUMENTADO' => $vista->t('eval.criterio_documentado'),
    'REPETIBLE'   => $vista->t('eval.criterio_repetible'),
    'EVIDENCIA'   => $vista->t('eval.criterio_evidencia'),
];
?>
<section class="mx-auto w-full max-w-3xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url($base)) ?>" class="text-acento-600 hover:underline">
            ← <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
        </a>
    </nav>

    <header class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-widest text-acento-500">
            <?= e($control->id) ?> · <?= e($proceso?->nombre ?? $vista->t('eval.sin_proceso')) ?>
        </p>
        <h1 class="mt-2 text-2xl font-extrabold leading-snug text-marina-950">
            <?= e($control->enunciado) ?>
        </h1>
        <p class="mt-2 text-sm text-slate-500"><?= e($control->iso) ?></p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <div class="mb-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
        <p class="text-sm font-semibold text-marina-950"><?= e($vista->t('eval.pregunta_auditoria')) ?></p>
        <p class="mt-1.5 text-slate-700"><?= e($evaluacion?->preguntaPersonalizada ?? $control->pregunta) ?></p>

        <?php if ($control->evidencia !== ''): ?>
            <p class="mt-4 text-sm font-semibold text-marina-950"><?= e($vista->t('eval.evidencia_esperada')) ?></p>
            <p class="mt-1 text-sm text-slate-600"><?= e($control->evidencia) ?></p>
        <?php endif; ?>
    </div>

    <form method="post" action="<?= e($vista->url($base . '/controles/' . $control->id)) ?>" class="space-y-7">
        <?= $vista->campoToken() ?>
        <fieldset <?= $abierta ? '' : 'disabled' ?> class="space-y-7">

            <!-- Respuesta -->
            <div>
                <span class="block text-sm font-semibold text-marina-950"><?= e($vista->t('eval.respuesta')) ?></span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <?php foreach ($estados as $opcion): ?>
                        <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-acento-500 has-[:checked]:border-acento-500 has-[:checked]:bg-acento-500/10">
                            <input type="radio" name="estado" value="<?= e($opcion) ?>" class="sr-only"
                                <?= $evaluacion?->estado === $opcion ? 'checked' : '' ?>>
                            <?= e($etiquetaEstado[$opcion] ?? $opcion) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errores['estado'])): ?>
                    <p class="mt-1.5 text-sm text-alerta-600"><?= e($errores['estado']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Madurez -->
            <div>
                <label for="madurez" class="block text-sm font-semibold text-marina-950">
                    <?= e($vista->t('eval.nivel_madurez')) ?>
                </label>
                <select id="madurez" name="madurez"
                        class="mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-slate-900 outline-none transition <?= isset($errores['madurez']) ? 'border-alerta-500' : 'border-slate-300 focus:border-acento-500' ?>">
                    <option value=""><?= e($vista->t('eval.sin_calificar')) ?></option>
                    <?php foreach ($escala as $nivel): ?>
                        <option value="<?= e((string) $nivel['nivel']) ?>"
                            <?= $evaluacion?->madurez === $nivel['nivel'] ? 'selected' : '' ?>>
                            <?= e((string) $nivel['nivel']) ?> — <?= e($nivel['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['madurez'])): ?>
                    <p class="mt-1.5 text-sm text-alerta-600"><?= e($errores['madurez']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Criterio -->
            <div>
                <label for="criterio" class="block text-sm font-semibold text-marina-950">
                    <?= e($vista->t('eval.criterio_comprobacion')) ?>
                </label>
                <select id="criterio" name="criterio"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-acento-500">
                    <option value=""><?= e($vista->t('eval.ninguno')) ?></option>
                    <?php foreach ($criterios as $opcion): ?>
                        <option value="<?= e($opcion) ?>" <?= $evaluacion?->criterio === $opcion ? 'selected' : '' ?>>
                            <?= e($etiquetaCriterio[$opcion] ?? $opcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dimensiones CID -->
            <div>
                <span class="block text-sm font-semibold text-marina-950">
                    <?= e($vista->t('eval.que_compromete')) ?>
                </span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <?php
                    $dimensiones = [
                        'confidencialidad' => [$vista->t('eval.confidencialidad'), $evaluacion?->afectaConfidencialidad],
                        'integridad'       => [$vista->t('eval.integridad'),       $evaluacion?->afectaIntegridad],
                        'disponibilidad'   => [$vista->t('eval.disponibilidad'),   $evaluacion?->afectaDisponibilidad],
                    ];
                    ?>
                    <?php foreach ($dimensiones as $campo => [$etiqueta, $marcada]): ?>
                        <label class="cursor-pointer rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-acento-500 has-[:checked]:border-acento-500 has-[:checked]:bg-acento-500/10">
                            <input type="checkbox" name="<?= e($campo) ?>" value="1" class="sr-only"
                                <?= $marcada ? 'checked' : '' ?>>
                            <?= e($etiqueta) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Impacto y probabilidad -->
            <div class="grid gap-5 sm:grid-cols-2">
                <?php foreach (['impacto' => $vista->t('eval.impacto'), 'probabilidad' => $vista->t('eval.probabilidad')] as $campo => $etiqueta): ?>
                    <div>
                        <label for="<?= e($campo) ?>" class="block text-sm font-semibold text-marina-950">
                            <?= e($etiqueta) ?> (1 a 5)
                        </label>
                        <select id="<?= e($campo) ?>" name="<?= e($campo) ?>"
                                class="mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-slate-900 outline-none transition <?= isset($errores[$campo]) ? 'border-alerta-500' : 'border-slate-300 focus:border-acento-500' ?>">
                            <option value="">—</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= e((string) $i) ?>"
                                    <?= ($campo === 'impacto' ? $evaluacion?->impacto : $evaluacion?->probabilidad) === $i ? 'selected' : '' ?>>
                                    <?= e((string) $i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <?php if (isset($errores[$campo])): ?>
                            <p class="mt-1.5 text-sm text-alerta-600"><?= e($errores[$campo]) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($evaluacion?->nivelRiesgo !== null): ?>
                <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <?= e($vista->t('eval.nivel_riesgo_reg')) ?>
                    <strong class="text-marina-950 tabular-nums"><?= e(number_format($evaluacion->nivelRiesgo, 2)) ?></strong>
                    <?= e($vista->t('eval.promedio_impacto')) ?>
                </p>
            <?php endif; ?>

            <!-- Hallazgo y recomendación -->
            <div>
                <label for="hallazgo" class="block text-sm font-semibold text-marina-950"><?= e($vista->t('eval.hallazgo')) ?></label>
                <textarea id="hallazgo" name="hallazgo" rows="4"
                          class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-acento-500"><?= e($evaluacion?->hallazgo ?? '') ?></textarea>
            </div>

            <div>
                <label for="recomendacion" class="block text-sm font-semibold text-marina-950"><?= e($vista->t('eval.recomendacion')) ?></label>
                <textarea id="recomendacion" name="recomendacion" rows="3"
                          class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-acento-500"><?= e($evaluacion?->recomendacion ?? '') ?></textarea>
            </div>

            <?php if ($abierta): ?>
            <div class="flex flex-wrap gap-3">
                <button type="submit"
                        class="rounded-lg bg-marina-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-marina-900">
                    <?= e($vista->t('eval.guardar')) ?>
                </button>
                <?php if ($vecinos['siguiente'] !== null): ?>
                    <button type="submit" name="siguiente" value="1"
                            class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-marina-950 transition hover:bg-slate-50">
                        <?= e($vista->t('eval.guardar_siguiente')) ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </fieldset>
    </form>

    <nav class="mt-10 flex justify-between border-t border-slate-200 pt-5 text-sm">
        <?php if ($vecinos['anterior'] !== null): ?>
            <a href="<?= e($vista->url($base . '/controles/' . $vecinos['anterior']->id)) ?>"
               class="text-acento-600 hover:underline">← <?= e($vecinos['anterior']->id) ?></a>
        <?php else: ?><span></span><?php endif; ?>

        <?php if ($vecinos['siguiente'] !== null): ?>
            <a href="<?= e($vista->url($base . '/controles/' . $vecinos['siguiente']->id)) ?>"
               class="text-acento-600 hover:underline"><?= e($vecinos['siguiente']->id) ?> →</a>
        <?php endif; ?>
    </nav>
</section>
