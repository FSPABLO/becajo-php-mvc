<?php

declare(strict_types=1);

/**
 * Toda la evidencia de una auditoría en un solo lugar: qué documentos hay,
 * a qué controles sirve cada uno, y qué controles ya calificados todavía no
 * tienen ningún documento vinculado.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var list<\App\Models\Entidades\EvidenciaDocumento> $documentos
 * @var list<\App\Models\Entidades\Control> $controlesSinEvidencia
 * @var int $totalDocumentos
 * @var int $totalConEvidencia
 */
?>
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="text-primario hover:underline">
            ← <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="rv-titulo text-3xl font-semibold text-texto">Evidencia de la auditoría</h1>
        <p class="mt-1 text-texto-2">
            <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?> · <?= e($auditoria->fecha) ?>
        </p>
    </header>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">Documentos cargados</p>
            <p class="mt-1 text-2xl font-semibold text-texto"><?= e((string) $totalDocumentos) ?></p>
        </div>
        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">Controles con evidencia</p>
            <p class="mt-1 text-2xl font-semibold text-texto">
                <?= e((string) $totalConEvidencia) ?>
            </p>
        </div>
        <div class="rv-extruido rounded-rv-lg border p-5
                    <?= $controlesSinEvidencia === [] ? 'border-borde bg-superficie' : 'border-warn bg-warn/10' ?>">
            <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">Controles evaluados sin evidencia</p>
            <p class="mt-1 text-2xl font-semibold <?= $controlesSinEvidencia === [] ? 'text-texto' : 'text-warn' ?>">
                <?= e((string) count($controlesSinEvidencia)) ?>
            </p>
        </div>
    </div>

    <?php if ($controlesSinEvidencia !== []): ?>
        <h2 class="mb-3 mt-8 text-sm font-semibold uppercase tracking-wider text-texto-2">
            Controles sin evidencia
        </h2>
        <div class="rv-extruido overflow-hidden rounded-rv-lg border border-borde bg-superficie">
            <?php foreach ($controlesSinEvidencia as $control): ?>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-borde px-4 py-3 last:border-0">
                    <div class="min-w-0">
                        <span class="font-mono text-sm font-semibold text-texto"><?= e($control->id) ?></span>
                        <span class="ml-2 text-sm text-texto-2"><?= e($control->enunciado) ?></span>
                    </div>
                    <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/controles/' . $control->id)) ?>"
                       class="shrink-0 rounded-rv border border-borde px-3 py-1.5 text-xs font-semibold text-texto transition hover:bg-elevado">
                        Abrir
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="mb-3 mt-8 text-sm font-semibold uppercase tracking-wider text-texto-2">
        Documentos cargados
    </h2>

    <?php if ($documentos === []): ?>
        <p class="text-sm text-texto-2">Todavía no se ha cargado ningún documento en esta auditoría.</p>
    <?php else: ?>
        <div class="grid gap-3 sm:grid-cols-2">
            <?php foreach ($documentos as $doc): ?>
                <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 truncate text-sm font-medium text-texto" title="<?= e($doc->nombreDocumento) ?>">
                            <?= e($doc->nombreDocumento) ?>
                        </p>
                        <span class="shrink-0 rounded-rv bg-elevado px-2 py-0.5 text-xs font-semibold text-texto-2">
                            <?= e($doc->formato) ?>
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-texto-2">
                        v<?= e($doc->version) ?> · <?= e($doc->responsable) ?> · <?= e($doc->fechaDocumento) ?>
                    </p>
                    <?php if ($doc->controlesVinculados !== ''): ?>
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <?php foreach (explode(', ', $doc->controlesVinculados) as $codigo): ?>
                                <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/controles/' . $codigo)) ?>"
                                   class="rounded-rv bg-ok/15 px-2 py-0.5 font-mono text-xs font-semibold text-ok hover:underline">
                                    <?= e($codigo) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</section>
