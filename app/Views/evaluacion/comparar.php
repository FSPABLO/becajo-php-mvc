<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var array<string, list<\App\Models\Entidades\Auditoria>> $porOrganizacion
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$maximo = 1;

foreach ($porOrganizacion as $grupo) {
    foreach ($grupo as $auditoria) {
        if ($auditoria->indiceGeneralRiesgo !== null) {
            $maximo = max($maximo, $auditoria->indiceGeneralRiesgo);
        }
    }
}
?>
<section class="mx-auto w-full max-w-5xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-acento-600 hover:underline">
            <?= e($vista->t('eval.volver_auditorias')) ?>
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-marina-950"><?= e($vista->t('eval.comparacion_historica')) ?></h1>
        <p class="mt-1 text-slate-600"><?= e($vista->t('eval.evolucion_indice')) ?></p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($porOrganizacion === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center">
            <p class="font-semibold text-marina-950"><?= e($vista->t('eval.sin_auditorias_comparar')) ?></p>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($porOrganizacion as $organizacion => $grupo): ?>
                <div class="rounded-2xl border border-slate-200 p-5">
                    <h2 class="text-lg font-bold text-marina-950"><?= e($organizacion) ?></h2>

                    <?php if (count($grupo) < 2): ?>
                        <p class="mt-2 text-sm text-slate-600">
                            <?= e($vista->t('eval.solo_una_auditoria')) ?>
                        </p>
                    <?php endif; ?>

                    <div class="mt-4 flex items-end gap-3">
                        <?php foreach ($grupo as $auditoria): ?>
                            <?php $indice = $auditoria->indiceGeneralRiesgo; ?>
                            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>"
                               class="flex w-16 flex-col items-center gap-1 text-center">
                                <span class="text-xs font-semibold tabular-nums text-marina-950">
                                    <?= $indice === null ? '—' : e(number_format($indice, 2)) ?>
                                </span>
                                <div class="flex h-24 w-full items-end rounded bg-slate-100">
                                    <div class="w-full rounded bg-acento-500"
                                         style="height: <?= $indice === null ? 0 : round(($indice / $maximo) * 100) ?>%"></div>
                                </div>
                                <span class="text-[11px] text-slate-500"><?= e($auditoria->fecha) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
