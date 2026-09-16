<?php

declare(strict_types=1);

/**
 * Ficha: los controles de mayor riesgo de una auditoría (sp_mayor_riesgo).
 *
 * Cada fila enlaza al formulario de su control: quien pregunta qué atender
 * primero suele querer abrirlo a continuación. El nivel va como cifra y las
 * dimensiones como letras (C/I/D), igual que en la vista de resultados.
 *
 * @var \App\Core\Vista                  $vista
 * @var \App\Models\Entidades\Auditoria  $auditoria
 * @var list<array<string, mixed>>       $controles
 */
?>
<section class="rv-extruido-sm overflow-hidden rounded-rv-lg border border-borde bg-superficie text-[13px]">
    <header class="flex items-center justify-between gap-3 border-b border-borde px-3.5 py-2.5">
        <h3 class="flex items-center gap-2 font-semibold text-texto">
            <?= icono('alert-triangle', 'h-4 w-4 text-texto-2') ?>
            <?= e($vista->t('asistente.ficha_riesgo', (string) $auditoria->id)) ?>
        </h3>
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/resultados')) ?>" class="text-[12px] font-semibold text-primario hover:underline">
            <?= e($vista->t('eval.ver_resultados')) ?>
        </a>
    </header>

    <?php if ($controles === []): ?>
        <p class="px-3.5 py-3 text-texto-2"><?= e($vista->t('asistente.ficha_sin_riesgo')) ?></p>
    <?php else: ?>
        <ol class="divide-y divide-borde">
            <?php foreach ($controles as $fila): ?>
                <?php $codigo = (string) ($fila['codigo_control'] ?? ''); ?>
                <li>
                    <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/controles/' . rawurlencode($codigo))) ?>"
                       class="block px-3.5 py-2.5 transition hover:bg-primario/10">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="rv-id text-[12px]"><?= e($codigo) ?></span>
                            <span class="text-[12px] tabular text-texto-2">
                                <?= e($vista->t('eval.nivel_riesgo')) ?>
                                <strong class="text-[14px] text-texto"><?= e(number_format((float) ($fila['nivel_riesgo'] ?? 0), 0, ',', '')) ?></strong>
                                <?php if (($fila['dimensiones'] ?? '') !== ''): ?>
                                    · <span class="font-mono"><?= e((string) $fila['dimensiones']) ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                        <span class="mt-0.5 block text-[12px] text-texto-2"><?= e((string) ($fila['dominio'] ?? '')) ?></span>
                        <span class="mt-1 line-clamp-2 block text-texto"><?= e((string) ($fila['enunciado'] ?? '')) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
