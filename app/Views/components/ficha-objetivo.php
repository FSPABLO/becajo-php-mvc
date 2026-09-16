<?php

declare(strict_types=1);

/**
 * Cabecera de un objetivo COBIT dentro del panel de la auditoría: la
 * capacidad que el auditor declara y su justificación. Ocupa el lugar del
 * título del proceso en ISO; las prácticas del objetivo van debajo.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Proceso $proceso
 * @var \App\Models\Entidades\EvaluacionObjetivo|null $evaluacion
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 * @var int  $idAuditoria
 * @var bool $abierta
 * @var array<string, string> $errores  Del último intento de ESTE objetivo.
 * @var array<string, mixed>  $valores
 */

$evaluacion = $evaluacion ?? null;
$errores    = $errores ?? [];
$valores    = $valores ?? [];
$hayIntento = $valores !== [];

$vCapacidad     = $hayIntento ? ($valores['capacidad'] ?? null) : $evaluacion?->capacidad;
$vJustificacion = $hayIntento ? ($valores['justificacion'] ?? '') : ($evaluacion?->justificacion ?? '');

$nombreNivel = null;

foreach ($escala as $nivel) {
    if ($evaluacion !== null && $nivel['nivel'] === $evaluacion->capacidad) {
        $nombreNivel = $nivel['nombre'];
    }
}

$suf = (string) $proceso->numero;

$campo = static fn (bool $mal): string =>
    'rv-hundido mt-1.5 w-full rounded-rv border bg-superficie px-3 py-2 text-sm text-texto outline-none transition '
    . ($mal
        ? 'border-bad focus:border-bad focus:ring-1 focus:ring-bad'
        : 'border-borde focus:border-primario focus:ring-1 focus:ring-primario');
?>
<div id="objetivo-<?= e($suf) ?>" class="scroll-mt-24 border-b border-borde pb-4">

    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-2">
        <h4 class="text-base font-bold text-texto">
            <span class="text-primario"><?= e($proceso->ancla) ?>.</span>
            <?= e($proceso->nombre) ?>
        </h4>
        <?= $evaluacion === null
            ? pill('na', $vista->t('eval.capacidad_sin_declarar'))
            : pill('ok', $vista->t('eval.capacidad') . ' ' . $evaluacion->capacidad
                . ($nombreNivel !== null ? ' — ' . $nombreNivel : '')) ?>
    </div>

    <form method="post"
          action="<?= e($vista->url('evaluacion/' . $idAuditoria . '/objetivos/' . $suf)) ?>"
          class="mt-3 grid gap-3 sm:grid-cols-[14rem_minmax(0,1fr)_auto] sm:items-end">
        <?= $vista->campoToken() ?>

        <div>
            <label for="capacidad-<?= e($suf) ?>" class="block text-xs font-medium text-texto-2">
                <?= e($vista->t('eval.capacidad_objetivo')) ?>
            </label>
            <select id="capacidad-<?= e($suf) ?>" name="capacidad"
                    class="<?= e($campo(isset($errores['capacidad']))) ?>"
                    <?= $abierta ? '' : 'disabled' ?>>
                <option value=""><?= e($vista->t('eval.sin_calificar')) ?></option>
                <?php foreach ($escala as $nivel): ?>
                    <option value="<?= e((string) $nivel['nivel']) ?>"
                            title="<?= e($nivel['descripcion']) ?>"
                        <?= (string) $vCapacidad === (string) $nivel['nivel'] ? 'selected' : '' ?>>
                        <?= e((string) $nivel['nivel']) ?> — <?= e($nivel['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errores['capacidad'])): ?>
                <p class="mt-1 text-[13px] text-bad"><?= e($errores['capacidad']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="justificacion-<?= e($suf) ?>" class="block text-xs font-medium text-texto-2">
                <?= e($vista->t('eval.justificacion_capacidad')) ?>
            </label>
            <textarea id="justificacion-<?= e($suf) ?>" name="justificacion" rows="2"
                      maxlength="<?= e((string) \App\Models\Entidades\EvaluacionObjetivo::JUSTIFICACION_MAXIMA) ?>"
                      class="<?= e($campo(isset($errores['justificacion']))) ?>"
                      <?= $abierta ? '' : 'disabled' ?>><?= e((string) $vJustificacion) ?></textarea>
            <?php if (isset($errores['justificacion'])): ?>
                <p class="mt-1 text-[13px] text-bad"><?= e($errores['justificacion']) ?></p>
            <?php endif; ?>
        </div>

        <?php if ($abierta): ?>
            <button type="submit"
                    class="rv-extruido rv-relieve-pleno rv-interactivo rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
                <?= e($vista->t('eval.guardar_capacidad')) ?>
            </button>
        <?php endif; ?>
    </form>
</div>
