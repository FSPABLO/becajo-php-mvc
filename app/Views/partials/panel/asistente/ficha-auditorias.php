<?php

declare(strict_types=1);

/**
 * Ficha: las auditorías del usuario.
 *
 * Datos de Oracle pintados por el servidor; el modelo pidió la ficha pero nunca
 * vio su contenido (ver App\Models\Asistente\Lembas). Cada fila enlaza a su
 * auditoría, así que la ficha sirve también para ELEGIR: «muéstrame mis
 * auditorías» suele ir seguido de abrir una.
 *
 * @var \App\Core\Vista                            $vista
 * @var list<\App\Models\Entidades\Auditoria>      $auditorias
 * @var int                                        $total
 */
?>
<section class="rv-extruido-sm overflow-hidden rounded-rv-lg border border-borde bg-superficie text-[13px]">
    <header class="flex items-center justify-between gap-3 border-b border-borde px-3.5 py-2.5">
        <h3 class="flex items-center gap-2 font-semibold text-texto">
            <?= icono('tablero', 'h-4 w-4 text-texto-2') ?>
            <?= e($vista->t('asistente.ficha_auditorias')) ?>
        </h3>
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-[12px] font-semibold text-primario hover:underline">
            <?= e($vista->t('asistente.ver_todas')) ?>
        </a>
    </header>

    <?php if ($auditorias === []): ?>
        <p class="px-3.5 py-3 text-texto-2"><?= e($vista->t('asistente.ficha_sin_auditorias')) ?></p>
    <?php else: ?>
        <ul class="divide-y divide-borde">
            <?php foreach ($auditorias as $auditoria): ?>
                <li>
                    <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>"
                       class="flex items-center gap-3 px-3.5 py-2.5 transition hover:bg-primario/10">
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-texto">
                                <?= e($vista->t('asistente.auditoria_n', (string) $auditoria->id)) ?>
                                <span class="font-normal tabular text-texto-2">· <?= e($auditoria->fecha) ?></span>
                            </span>
                            <span class="block truncate text-[12px] text-texto-2">
                                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?>
                            </span>
                        </span>
                        <span class="flex-none">
                            <?= $auditoria->estaFinalizada()
                                ? pill('ok', $vista->t('eval.finalizada'))
                                : pill('warn', $vista->t('eval.en_progreso')) ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($total > count($auditorias)): ?>
            <p class="border-t border-borde px-3.5 py-2 text-[12px] text-texto-2">
                <?= e($vista->t('asistente.mostrando_de', (string) count($auditorias), (string) $total)) ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</section>
