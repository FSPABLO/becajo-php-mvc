<?php

declare(strict_types=1);

/**
 * "Mis auditorías": punto de entrada del módulo.
 *
 * Vista funcional, no definitiva. Persona 4 puede reescribir el marcado
 * mientras conserve las rutas de los enlaces y los name= de los formularios.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var list<\App\Models\Entidades\Auditoria> $auditorias
 * @var int $total  Controles del catálogo.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
?>
<section class="mx-auto w-full max-w-5xl px-6 pt-24 pb-14">

    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-acento-500">
                Evaluación de riesgo ISO/IEC 27002
            </p>
            <h1 class="mt-2 text-3xl font-extrabold text-marina-950">Mis auditorías</h1>
            <p class="mt-1 text-sm text-slate-600">
                <?= e($usuario->nombre) ?> · <?= e($usuario->organizacion) ?>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="<?= e($vista->url('evaluacion/comparar')) ?>"
               class="text-sm font-semibold text-slate-600 hover:text-marina-950">
                Comparar histórico
            </a>
            <a href="<?= e($vista->url('evaluacion/nueva')) ?>"
               class="rounded-lg bg-marina-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-marina-900">
                Nueva auditoría
            </a>
            <form method="post" action="<?= e($vista->url('salir')) ?>">
                <?= $vista->campoToken() ?>
                <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-marina-950">
                    Salir
                </button>
            </form>
        </div>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($auditorias === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center">
            <p class="font-semibold text-marina-950">Todavía no hay auditorías.</p>
            <p class="mt-1 text-sm text-slate-600">
                Cree la primera para empezar a evaluar los <?= e((string) $total) ?> controles del instrumento.
            </p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full min-w-[46rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">#</th>
                        <th class="px-4 py-3 font-semibold">Organización</th>
                        <th class="px-4 py-3 font-semibold">Área evaluada</th>
                        <th class="px-4 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 font-semibold">Estado</th>
                        <th class="px-4 py-3 font-semibold">Índice</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($auditorias as $auditoria): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>"
                               class="font-semibold text-acento-600 hover:underline">
                                <?= e((string) $auditoria->id) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-marina-950"><?= e($auditoria->organizacion) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e($auditoria->areaEvaluada) ?></td>
                        <td class="px-4 py-3 tabular-nums text-slate-700"><?= e($auditoria->fecha) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($auditoria->estaFinalizada()): ?>
                                <span class="rounded-full bg-exito-400/15 px-2.5 py-1 text-xs font-semibold text-exito-600">
                                    Finalizada
                                </span>
                            <?php else: ?>
                                <span class="rounded-full bg-aviso-400/20 px-2.5 py-1 text-xs font-semibold text-aviso-600">
                                    En progreso
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 tabular-nums text-slate-700">
                            <?= $auditoria->indiceGeneralRiesgo === null
                                ? '<span class="text-slate-400">sin calcular</span>'
                                : e(number_format($auditoria->indiceGeneralRiesgo, 2)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
