<?php

declare(strict_types=1);

/**
 * Ficha: remediaciones vencidas (sp_remediaciones_vencidas). Solo ADMIN_BD:
 * Lembas ni siquiera le ofrece la herramienta a un auditor, y la vuelve a
 * negar al ejecutarla si llegara a pedirla.
 *
 * La fecha límite se imprime como la trae Oracle, igual que en
 * /remediaciones/vencidas, y en la tinta de `bad` con su palabra al lado: ya
 * venció, que es lo que la lista existe para decir.
 *
 * @var \App\Core\Vista             $vista
 * @var list<array<string, mixed>>  $remediaciones
 * @var int                         $total
 */
?>
<section class="rv-extruido-sm overflow-hidden rounded-rv-lg border border-borde bg-superficie text-[13px]">
    <header class="flex items-center justify-between gap-3 border-b border-borde px-3.5 py-2.5">
        <h3 class="flex items-center gap-2 font-semibold text-texto">
            <?= icono('alerta', 'h-4 w-4 text-texto-2') ?>
            <?= e($vista->t('panel.remediaciones_vencidas')) ?>
            <span class="font-normal tabular text-texto-2">(<?= e((string) $total) ?>)</span>
        </h3>
        <a href="<?= e($vista->url('remediaciones/vencidas')) ?>" class="text-[12px] font-semibold text-primario hover:underline">
            <?= e($vista->t('asistente.ver_todas')) ?>
        </a>
    </header>

    <?php if ($remediaciones === []): ?>
        <p class="px-3.5 py-3 text-texto-2"><?= e($vista->t('asistente.ficha_sin_vencidas')) ?></p>
    <?php else: ?>
        <ul class="divide-y divide-borde">
            <?php foreach ($remediaciones as $fila): ?>
                <li>
                    <a href="<?= e($vista->url('evaluacion/' . (int) ($fila['id_auditoria'] ?? 0) . '/remediaciones')) ?>"
                       class="block px-3.5 py-2.5 transition hover:bg-primario/10">
                        <span class="flex items-baseline justify-between gap-3">
                            <span class="rv-id text-[12px]"><?= e((string) ($fila['codigo_control'] ?? '')) ?></span>
                            <span class="text-[12px] tabular text-bad">
                                <?= e($vista->t('asistente.vencio', (string) ($fila['fecha_limite'] ?? ''))) ?>
                            </span>
                        </span>
                        <span class="mt-0.5 block truncate text-texto"><?= e((string) ($fila['organizacion'] ?? '')) ?></span>
                        <span class="block truncate text-[12px] text-texto-2">
                            <?= e($vista->t('asistente.responsable', (string) (($fila['responsable'] ?? '') !== '' ? $fila['responsable'] : '—'))) ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($total > count($remediaciones)): ?>
            <p class="border-t border-borde px-3.5 py-2 text-[12px] text-texto-2">
                <?= e($vista->t('asistente.mostrando_de', (string) count($remediaciones), (string) $total)) ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</section>
