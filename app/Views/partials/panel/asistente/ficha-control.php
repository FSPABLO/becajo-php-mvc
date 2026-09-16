<?php

declare(strict_types=1);

/**
 * Ficha: abrir un control para llenarlo.
 *
 * Es la forma que tiene Lembas de «llenar una auditoría» sin que ninguna
 * respuesta pase por el modelo: explica lo que pide el control —del catálogo,
 * que es público— y lleva al formulario de siempre, que guarda por el mismo
 * endpoint y con la misma validación que las 75 tarjetas del panel. Lembas no
 * escribe ni una casilla.
 *
 * Enlaza a /evaluacion/{id}/controles/{codigo} y no a la tarjeta dentro de
 * /evaluacion/{id}: esa pantalla no abre la pestaña del dominio a partir del
 * ancla, y el enlace dejaría al auditor en otro dominio sin la tarjeta a la
 * vista.
 *
 * @var \App\Core\Vista                              $vista
 * @var \App\Models\Entidades\Auditoria              $auditoria
 * @var \App\Models\Entidades\Control                $control
 * @var \App\Models\Entidades\EvaluacionControl|null $evaluacion
 */
$estado = $evaluacion?->estado;
$etiquetaEstado = match ($estado) {
    'SI'    => $vista->t('eval.estado_si'),
    'NO'    => $vista->t('eval.estado_no'),
    'NA'    => $vista->t('eval.estado_na'),
    default => null,
};
?>
<section class="rv-extruido-sm overflow-hidden rounded-rv-lg border border-borde bg-superficie text-[13px]">
    <header class="flex items-start justify-between gap-3 border-b border-borde px-3.5 py-2.5">
        <div class="min-w-0">
            <h3 class="font-semibold text-texto">
                <span class="rv-id"><?= e($control->id) ?></span>
                <span class="font-normal text-texto-2">· <?= e($vista->t('asistente.auditoria_n', (string) $auditoria->id)) ?></span>
            </h3>
            <p class="text-[12px] text-texto-2">
                <?php /* El estado en palabras, sin color: aquí no se valora la respuesta, se dice si existe. */ ?>
                <?= e($etiquetaEstado === null
                    ? $vista->t('eval.sin_responder')
                    : $vista->t('asistente.respuesta_registrada', $etiquetaEstado)) ?>
            </p>
        </div>
    </header>

    <div class="space-y-2 px-3.5 py-2.5 leading-relaxed">
        <p class="text-texto"><?= e($control->enunciado) ?></p>

        <?php if ($control->pregunta !== ''): ?>
            <p class="text-texto-2">
                <span class="font-semibold text-texto"><?= e($vista->t('asistente.pregunta_auditoria')) ?></span>
                <?= e($control->pregunta) ?>
            </p>
        <?php endif; ?>

        <?php if ($control->evidencia !== ''): ?>
            <p class="text-texto-2">
                <span class="font-semibold text-texto"><?= e($vista->t('asistente.evidencia_esperada')) ?></span>
                <?= e($control->evidencia) ?>
            </p>
        <?php endif; ?>

        <?php if ($auditoria->estaFinalizada()): ?>
            <p class="flex items-start gap-2 text-texto-2">
                <?= icono('alert-circle', 'mt-0.5 h-4 w-4 flex-none') ?>
                <?= e($vista->t('asistente.auditoria_finalizada')) ?>
            </p>
        <?php endif; ?>
    </div>

    <footer class="border-t border-borde px-3.5 py-2.5">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/controles/' . rawurlencode($control->id))) ?>"
           class="rv-extruido-sm rv-interactivo-sm inline-flex items-center gap-2 rounded-rv bg-primario px-3 py-1.5 text-[12.5px] font-semibold text-primario-texto">
            <?= e($vista->t($estado === null ? 'asistente.abrir_formulario' : 'asistente.revisar_formulario')) ?>
            <?= icono('flecha', 'h-3.5 w-3.5') ?>
        </a>
    </footer>
</section>
