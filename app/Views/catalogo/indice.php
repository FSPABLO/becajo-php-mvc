<?php

declare(strict_types=1);

/**
 * Índice del catálogo maestro. Vista funcional, no definitiva.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var list<\App\Models\Entidades\Proceso> $procesos
 * @var list<\App\Models\Entidades\Control> $controles
 * @var array<string, int> $usosControl  Código de control => evaluaciones que lo responden.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

// Recuentos para saber, de un vistazo, qué se puede borrar y qué no.
$procesosPorDominio = [];
foreach ($procesos as $proceso) {
    $procesosPorDominio[$proceso->dominio] = ($procesosPorDominio[$proceso->dominio] ?? 0) + 1;
}

$controlesPorProceso = [];
foreach ($controles as $control) {
    $controlesPorProceso[$control->proceso] = ($controlesPorProceso[$control->proceso] ?? 0) + 1;
}

$nombreDominio = [];
foreach ($dominios as $dominio) {
    $nombreDominio[$dominio->clave] = $dominio->corto;
}

$botonBorrar = static function (\App\Core\Vista $vista, string $ruta, string $etiqueta): string {
    return '<form method="post" action="' . e($vista->url($ruta)) . '"'
        . ' onsubmit="return confirm(\'¿Eliminar ' . e($etiqueta) . '? Esta acción no se puede deshacer.\')">'
        . $vista->campoToken()
        . '<button type="submit" class="text-xs font-semibold text-alerta-600 hover:underline">Eliminar</button>'
        . '</form>';
};
?>
<section class="mx-auto w-full max-w-6xl px-6 py-14">

    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-acento-500">
                Administración
            </p>
            <h1 class="mt-2 text-3xl font-extrabold text-marina-950">Catálogo de controles</h1>
            <p class="mt-1 text-sm text-slate-600">
                <?= e((string) count($dominios)) ?> dominios ·
                <?= e((string) count($procesos)) ?> procesos ·
                <?= e((string) count($controles)) ?> controles
            </p>
        </div>
        <a href="<?= e($vista->url('evaluacion')) ?>"
           class="text-sm font-semibold text-acento-600 hover:underline">
            Ir a mis auditorías →
        </a>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <p class="mb-10 rounded-xl border border-aviso-400/40 bg-aviso-400/10 px-4 py-3 text-sm text-aviso-600">
        Editar un control cambia el significado de las respuestas ya guardadas en
        auditorías anteriores. Las claves (clave de dominio, número de proceso,
        código de control) no se pueden modificar una vez creadas.
    </p>

    <!-- Dominios -->
    <div class="mb-12">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-marina-950">Dominios</h2>
            <a href="<?= e($vista->url('catalogo/dominios/nuevo')) ?>"
               class="rounded-lg bg-marina-950 px-3.5 py-2 text-sm font-semibold text-white hover:bg-marina-900">
                Nuevo dominio
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Orden</th>
                        <th class="px-4 py-3 font-semibold">Clave</th>
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Procesos</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($dominios as $dominio): ?>
                    <?php $usos = $procesosPorDominio[$dominio->clave] ?? 0; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 tabular-nums text-slate-500"><?= e((string) $dominio->orden) ?></td>
                        <td class="px-4 py-3">
                            <a href="<?= e($vista->url('catalogo/dominios/' . $dominio->clave)) ?>"
                               class="font-semibold text-acento-600 hover:underline"><?= e($dominio->clave) ?></a>
                        </td>
                        <td class="px-4 py-3 text-marina-950"><?= e($dominio->nombre) ?></td>
                        <td class="px-4 py-3 tabular-nums text-slate-600"><?= e((string) $usos) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($usos === 0): ?>
                                <?= $botonBorrar($vista, 'catalogo/dominios/' . $dominio->clave . '/eliminar', 'el dominio ' . $dominio->clave) ?>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">en uso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Procesos -->
    <div class="mb-12">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-marina-950">Procesos</h2>
            <a href="<?= e($vista->url('catalogo/procesos/nuevo')) ?>"
               class="rounded-lg bg-marina-950 px-3.5 py-2 text-sm font-semibold text-white hover:bg-marina-900">
                Nuevo proceso
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Orden</th>
                        <th class="px-4 py-3 font-semibold">Nº</th>
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Dominio</th>
                        <th class="px-4 py-3 font-semibold">Controles</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($procesos as $proceso): ?>
                    <?php $usos = $controlesPorProceso[$proceso->numero] ?? 0; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 tabular-nums text-slate-500"><?= e((string) $proceso->orden) ?></td>
                        <td class="px-4 py-3">
                            <a href="<?= e($vista->url('catalogo/procesos/' . $proceso->numero)) ?>"
                               class="font-semibold text-acento-600 hover:underline"><?= e((string) $proceso->numero) ?></a>
                        </td>
                        <td class="px-4 py-3 text-marina-950"><?= e($proceso->nombre) ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= e($nombreDominio[$proceso->dominio] ?? $proceso->dominio) ?></td>
                        <td class="px-4 py-3 tabular-nums text-slate-600"><?= e((string) $usos) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($usos === 0): ?>
                                <?= $botonBorrar($vista, 'catalogo/procesos/' . $proceso->numero . '/eliminar', 'el proceso ' . $proceso->numero) ?>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">en uso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Controles -->
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-xl font-bold text-marina-950">Controles</h2>
            <a href="<?= e($vista->url('catalogo/controles/nuevo')) ?>"
               class="rounded-lg bg-marina-950 px-3.5 py-2 text-sm font-semibold text-white hover:bg-marina-900">
                Nuevo control
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full min-w-[46rem] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Código</th>
                        <th class="px-4 py-3 font-semibold">Proceso</th>
                        <th class="px-4 py-3 font-semibold">Enunciado</th>
                        <th class="px-4 py-3 font-semibold">Evaluado</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                <?php foreach ($controles as $control): ?>
                    <?php $usos = $usosControl[$control->id] ?? 0; ?>
                    <tr class="align-top hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="<?= e($vista->url('catalogo/controles/' . $control->id)) ?>"
                               class="font-semibold text-acento-600 hover:underline"><?= e($control->id) ?></a>
                        </td>
                        <td class="px-4 py-3 tabular-nums text-slate-600"><?= e((string) $control->proceso) ?></td>
                        <td class="px-4 py-3 text-slate-700"><?= e(mb_strimwidth($control->enunciado, 0, 90, '…')) ?></td>
                        <td class="px-4 py-3 tabular-nums text-slate-600"><?= e((string) $usos) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($usos === 0): ?>
                                <?= $botonBorrar($vista, 'catalogo/controles/' . $control->id . '/eliminar', 'el control ' . $control->id) ?>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">en uso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
