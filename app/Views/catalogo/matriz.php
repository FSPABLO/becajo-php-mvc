<?php

declare(strict_types=1);

/**
 * Mapa de procesos vs C-I-D (punto 17).
 *
 * Reproduce visualmente la tabla del Apéndice II de COBIT 4.1: cada proceso
 * en una fila, y su relación declarada (Primaria / Secundaria / ninguna) con
 * Confidencialidad, Integridad y Disponibilidad en tres columnas.
 *
 * Es la relación DECLARADA a nivel de catálogo (Proceso.relacion_*), no lo
 * que el auditor marcó en una evaluación concreta — ver la nota al pie de
 * esta misma vista.
 *
 * Vista funcional, no definitiva. Persona 4 puede reescribir el marcado.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var list<\App\Models\Entidades\Proceso> $procesos
 */

$nombreDominio = [];
foreach ($dominios as $dominio) {
    $nombreDominio[$dominio->clave] = $dominio->nombre;
}

// Agrupa los procesos por dominio y los ordena tal como ya vienen (por
// 'orden'), para que la matriz siga la misma secuencia que el resto del sitio.
$procesosPorDominio = [];
foreach ($procesos as $proceso) {
    $procesosPorDominio[$proceso->dominio][] = $proceso;
}

$etiquetaRelacion = static function (?string $relacion): string {
    return match ($relacion) {
        'P'     => '<span class="rounded bg-marina-950 px-2 py-0.5 text-xs font-bold text-white">P</span>',
        'S'     => '<span class="rounded border border-slate-300 px-2 py-0.5 text-xs font-semibold text-slate-600">S</span>',
        default => '<span class="text-slate-300">—</span>',
    };
};
?>
<section class="mx-auto w-full max-w-5xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('catalogo')) ?>" class="text-acento-600 hover:underline">
            ← Volver al catálogo
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-marina-950">Mapa de procesos vs C-I-D</h1>
        <p class="mt-1 text-slate-600">
            Relación declarada de cada proceso con Confidencialidad, Integridad y Disponibilidad —
            notación de COBIT 4.1 (Apéndice II): <strong>P</strong> = relación primaria,
            <strong>S</strong> = relación secundaria, sin marca = sin relación relevante.
        </p>
    </header>

    <?php if ($procesos === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center">
            <p class="font-semibold text-marina-950">Todavía no hay procesos en el catálogo.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Proceso</th>
                        <th class="px-4 py-3 text-center">Confidencialidad</th>
                        <th class="px-4 py-3 text-center">Integridad</th>
                        <th class="px-4 py-3 text-center">Disponibilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($procesosPorDominio as $claveDominio => $procesosDelDominio): ?>
                        <tr class="bg-slate-50/60">
                            <td colspan="4" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-marina-950">
                                <?= e($nombreDominio[$claveDominio] ?? $claveDominio) ?>
                            </td>
                        </tr>
                        <?php foreach ($procesosDelDominio as $proceso): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="<?= e($vista->url('catalogo/procesos/' . $proceso->numero)) ?>"
                                       class="font-medium text-marina-950 hover:text-acento-600">
                                        <?= e($proceso->nombre) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-center"><?= $etiquetaRelacion($proceso->relacionConfidencialidad) ?></td>
                                <td class="px-4 py-3 text-center"><?= $etiquetaRelacion($proceso->relacionIntegridad) ?></td>
                                <td class="px-4 py-3 text-center"><?= $etiquetaRelacion($proceso->relacionDisponibilidad) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-xs text-slate-500">
            Esta relación es de catálogo (referencia general del proceso), no la de una auditoría
            puntual: la exposición al riesgo real de cada auditoría usa lo que el auditor marcó
            control por control en su evaluación, que puede variar según lo que encuentre.
        </p>
    <?php endif; ?>
</section>
