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

/*
 * Los tres estados del cruce no se distinguen solo por color: la letra («P»,
 * «S», «—») es ya el segundo canal, y el título accesible es el tercero, para
 * que un lector de pantalla no anuncie una letra suelta sin contexto.
 *
 * La relación es referencia de catálogo, no cumplimiento: por eso «P» va en
 * oro, la voz de lo normativo, y no en el verde de la escala de estado.
 */
$etiquetaRelacion = static function (?string $relacion): string {
    return match ($relacion) {
        'P' => '<span class="rounded border border-oro bg-oro-tinte px-2 py-0.5 font-mono text-xs font-bold text-oro-texto"'
             . ' title="Relación primaria">P<span class="sr-only"> — relación primaria</span></span>',
        'S' => '<span class="rounded border border-borde px-2 py-0.5 font-mono text-xs font-semibold text-texto-2"'
             . ' title="Relación secundaria">S<span class="sr-only"> — relación secundaria</span></span>',
        default => '<span class="text-na" title="Sin relación relevante">—'
             . '<span class="sr-only">Sin relación relevante</span></span>',
    };
};
?>
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('catalogo')) ?>" class="text-primario hover:underline">
            ← Volver al catálogo
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="rv-titulo text-3xl font-semibold text-texto">Mapa de procesos vs C-I-D</h1>
        <p class="mt-1 text-texto-2">
            Relación declarada de cada proceso con Confidencialidad, Integridad y Disponibilidad —
            notación de COBIT 4.1 (Apéndice II): <strong>P</strong> = relación primaria,
            <strong>S</strong> = relación secundaria, sin marca = sin relación relevante.
        </p>
    </header>

    <?php if ($procesos === []): ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto">Todavía no hay procesos en el catálogo.</p>
        </div>
    <?php else: ?>
        <div class="rv-extruido rv-relieve-sutil rv-tabla overflow-x-auto rounded-rv-lg border border-borde bg-superficie">
            <table class="w-full text-left text-sm">
                <thead class="bg-elevado text-xs font-semibold uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3">Proceso</th>
                        <th class="px-4 py-3 text-center">Confidencialidad</th>
                        <th class="px-4 py-3 text-center">Integridad</th>
                        <th class="px-4 py-3 text-center">Disponibilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <?php foreach ($procesosPorDominio as $claveDominio => $procesosDelDominio): ?>
                        <tr class="bg-elevado/60">
                            <td colspan="4" class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-texto">
                                <?= e($nombreDominio[$claveDominio] ?? $claveDominio) ?>
                            </td>
                        </tr>
                        <?php foreach ($procesosDelDominio as $proceso): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="<?= e($vista->url('catalogo/procesos/' . $proceso->numero)) ?>"
                                       class="font-medium text-texto hover:text-primario">
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

        <p class="mt-4 text-xs text-texto-2">
            Esta relación es de catálogo (referencia general del proceso), no la de una auditoría
            puntual: la exposición al riesgo real de cada auditoría usa lo que el auditor marcó
            control por control en su evaluación, que puede variar según lo que encuentre.
        </p>
    <?php endif; ?>
</section>
