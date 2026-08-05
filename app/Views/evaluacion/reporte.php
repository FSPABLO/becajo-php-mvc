<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var array<string, mixed> $resumen
 * @var list<array<string, mixed>> $dominios
 * @var list<\App\Models\Entidades\ResultadoRiesgo> $exposicion
 * @var list<array<string, mixed>> $menorMadurez
 * @var list<array<string, mixed>> $mayorRiesgo
 */
$colorZona = [
    'ROJO'     => 'bg-alerta-400/15 text-alerta-600 border-alerta-400/40',
    'AMARILLO' => 'bg-aviso-400/20 text-aviso-600 border-aviso-400/40',
    'VERDE'    => 'bg-exito-400/15 text-exito-600 border-exito-400/40',
];

$porcentaje = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float) $v * 100, 1) . '%';
?>
<article>
    <p class="text-xs font-semibold uppercase tracking-widest text-acento-600">Reporte ejecutivo</p>
    <h1 class="mt-1 text-3xl font-extrabold text-marina-950">Evaluación de riesgo ISO/IEC 27002</h1>
    <p class="mt-2 text-sm text-slate-600">Generado el <?= e(date('Y-m-d H:i')) ?> por <?= e($usuario->nombre) ?></p>

    <dl class="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Organización</dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->organizacion) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Área evaluada</dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->areaEvaluada) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Auditor</dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->nombreAuditor) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Administrador de BD entrevistado</dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->nombreAdministradorBd) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha</dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->fecha) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</dt>
            <dd class="mt-0.5 text-marina-950"><?= $auditoria->estaFinalizada() ? 'Finalizada' : 'En progreso' ?></dd>
        </div>
    </dl>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Cumplimiento general</p>
            <p class="mt-1 text-2xl font-extrabold text-marina-950"><?= e($porcentaje($resumen['cumplimiento'] ?? null)) ?></p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Madurez promedio</p>
            <p class="mt-1 text-2xl font-extrabold text-marina-950"><?= e((string) ($resumen['madurez_promedio'] ?? '—')) ?></p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500">Índice general de riesgo</p>
            <p class="mt-1 text-2xl font-extrabold text-marina-950">
                <?= $auditoria->indiceGeneralRiesgo === null ? 'sin calcular' : e(number_format($auditoria->indiceGeneralRiesgo, 2)) ?>
            </p>
        </div>
    </div>

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950">Exposición al riesgo</h2>
    <div class="grid gap-3 sm:grid-cols-3">
        <?php foreach ($exposicion as $riesgo): ?>
            <div class="rounded-xl border p-4 <?= e($colorZona[$riesgo->zona] ?? 'border-slate-200') ?>">
                <p class="text-sm font-semibold"><?= e($riesgo->etiqueta()) ?></p>
                <p class="mt-1 text-xl font-extrabold"><?= e($riesgo->porcentaje() === null ? '—' : $riesgo->porcentaje() . '%') ?></p>
                <p class="mt-1 text-xs font-semibold uppercase"><?= e((string) $riesgo->zona) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950">Cumplimiento por dominio</h2>
    <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
            <tr>
                <th class="py-2">Dominio</th>
                <th class="py-2">Cumplimiento</th>
                <th class="py-2">Madurez</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($dominios as $fila): ?>
                <tr>
                    <td class="py-2 font-medium text-marina-950"><?= e((string) $fila['nombre_dominio']) ?></td>
                    <td class="py-2"><?= e($porcentaje($fila['cumplimiento'] ?? null)) ?></td>
                    <td class="py-2"><?= e((string) ($fila['madurez_promedio'] ?? '—')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950">Controles con menor madurez</h2>
    <?php if ($menorMadurez === []): ?>
        <p class="text-sm text-slate-600">Sin datos suficientes.</p>
    <?php else: ?>
        <ul class="space-y-2 text-sm">
            <?php foreach ($menorMadurez as $fila): ?>
                <li class="border-b border-slate-100 pb-2">
                    <strong class="text-marina-950"><?= e((string) $fila['codigo_control']) ?></strong>
                    — madurez <?= e((string) ($fila['madurez'] ?? '—')) ?>
                    · <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 100, '…')) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950">Controles con mayor riesgo</h2>
    <?php if ($mayorRiesgo === []): ?>
        <p class="text-sm text-slate-600">Sin datos suficientes.</p>
    <?php else: ?>
        <ul class="space-y-2 text-sm">
            <?php foreach ($mayorRiesgo as $fila): ?>
                <li class="border-b border-slate-100 pb-2">
                    <strong class="text-marina-950"><?= e((string) $fila['codigo_control']) ?></strong>
                    — riesgo <?= e((string) ($fila['nivel_riesgo'] ?? '—')) ?>
                    · <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 100, '…')) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
