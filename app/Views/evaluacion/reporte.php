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
 * @var list<\App\Models\Entidades\EvaluacionControl> $evaluaciones
 */
$colorZona = [
    'ROJO'     => 'bg-alerta-400/15 text-alerta-600 border-alerta-400/40',
    'AMARILLO' => 'bg-aviso-400/20 text-aviso-600 border-aviso-400/40',
    'VERDE'    => 'bg-exito-400/15 text-exito-600 border-exito-400/40',
];

$fondoZona = [
    'ROJO'     => 'bg-alerta-500',
    'AMARILLO' => 'bg-aviso-400',
    'VERDE'    => 'bg-exito-500',
];

$porcentaje = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float) $v * 100, 1) . '%';

$etiquetaTipo = [
    \App\Models\Entidades\ResultadoRiesgo::CONFIDENCIALIDAD => $vista->t('eval.confidencialidad'),
    \App\Models\Entidades\ResultadoRiesgo::INTEGRIDAD       => $vista->t('eval.integridad'),
    \App\Models\Entidades\ResultadoRiesgo::DISPONIBILIDAD   => $vista->t('eval.disponibilidad'),
];

$celdas = [];
foreach ($evaluaciones as $ev) {
    if ($ev->impacto === null || $ev->probabilidad === null) {
        continue;
    }
    $clave = $ev->impacto . '-' . $ev->probabilidad;
    $celdas[$clave] = ($celdas[$clave] ?? 0) + 1;
}

$colorCelda = static function (int $impacto, int $probabilidad) use ($fondoZona): string {
    $nivel = ($impacto + $probabilidad) / 2;

    return match (true) {
        $nivel <= 2   => $fondoZona['VERDE'],
        $nivel <= 3.5 => $fondoZona['AMARILLO'],
        default       => $fondoZona['ROJO'],
    };
};
?>
<article>
    <p class="text-xs font-semibold uppercase tracking-widest text-acento-600"><?= e($vista->t('eval.reporte_ejecutivo')) ?></p>
    <h1 class="mt-1 text-3xl font-extrabold text-marina-950"><?= e($vista->t('auth.eyebrow')) ?></h1>
    <p class="mt-2 text-sm text-slate-600"><?= e($vista->t('eval.generado_el', date('Y-m-d H:i'), $usuario->nombre)) ?></p>

    <dl class="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= e($vista->t('eval.col_organizacion')) ?></dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->organizacion) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= e($vista->t('eval.col_area')) ?></dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->areaEvaluada) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= e($vista->t('eval.auditor')) ?></dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->nombreAuditor) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= e($vista->t('eval.admin_entrevistado')) ?></dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->nombreAdministradorBd) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= e($vista->t('eval.col_fecha')) ?></dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->fecha) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500"><?= e($vista->t('eval.col_estado')) ?></dt>
            <dd class="mt-0.5 text-marina-950"><?= e($auditoria->estaFinalizada() ? $vista->t('eval.finalizada') : $vista->t('eval.en_progreso')) ?></dd>
        </div>
    </dl>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500"><?= e($vista->t('eval.cumplimiento_general')) ?></p>
            <p class="mt-1 text-2xl font-extrabold text-marina-950"><?= e($porcentaje($resumen['cumplimiento'] ?? null)) ?></p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500"><?= e($vista->t('eval.madurez_promedio')) ?></p>
            <p class="mt-1 text-2xl font-extrabold text-marina-950"><?= e((string) ($resumen['madurez_promedio'] ?? '—')) ?></p>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500"><?= e($vista->t('eval.indice_general_riesgo')) ?></p>
            <p class="mt-1 text-2xl font-extrabold text-marina-950">
                <?= $auditoria->indiceGeneralRiesgo === null ? e($vista->t('eval.sin_calcular')) : e(number_format($auditoria->indiceGeneralRiesgo, 2)) ?>
            </p>
        </div>
    </div>

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950"><?= e($vista->t('eval.exposicion_riesgo')) ?></h2>
    <div class="grid gap-3 sm:grid-cols-3">
        <?php foreach ($exposicion as $riesgo): ?>
            <div class="rounded-xl border p-4 <?= e($colorZona[$riesgo->zona] ?? 'border-slate-200') ?>">
                <p class="text-sm font-semibold"><?= e($etiquetaTipo[$riesgo->tipo] ?? $riesgo->etiqueta()) ?></p>
                <p class="mt-1 text-xl font-extrabold"><?= e($riesgo->porcentaje() === null ? '—' : $riesgo->porcentaje() . '%') ?></p>
                <p class="mt-1 text-xs font-semibold uppercase"><?= e((string) $riesgo->zona) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($celdas !== []): ?>
        <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950"><?= e($vista->t('eval.matriz_riesgo')) ?></h2>
        <div class="flex gap-1.5">
            <?php for ($p = 5; $p >= 1; $p--): ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php $conteo = $celdas[$i . '-' . $p] ?? 0; ?>
                    <div class="flex h-9 w-9 items-center justify-center rounded text-xs font-bold text-white <?= e($colorCelda($i, $p)) ?> <?= $conteo === 0 ? 'opacity-25' : '' ?>">
                        <?= $conteo > 0 ? e((string) $conteo) : '' ?>
                    </div>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
        <p class="mt-1.5 text-xs text-slate-500"><?= e($vista->t('eval.eje_matriz_reporte')) ?></p>
    <?php endif; ?>

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950"><?= e($vista->t('eval.cumplimiento_dominio')) ?></h2>
    <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
            <tr>
                <th class="py-2"><?= e($vista->t('eval.col_dominio')) ?></th>
                <th class="py-2"><?= e($vista->t('eval.col_cumplimiento')) ?></th>
                <th class="py-2"><?= e($vista->t('eval.col_madurez')) ?></th>
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

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950"><?= e($vista->t('eval.menor_madurez')) ?></h2>
    <?php if ($menorMadurez === []): ?>
        <p class="text-sm text-slate-600"><?= e($vista->t('eval.sin_datos_suficientes')) ?></p>
    <?php else: ?>
        <ul class="space-y-2 text-sm">
            <?php foreach ($menorMadurez as $fila): ?>
                <li class="border-b border-slate-100 pb-2">
                    <strong class="text-marina-950"><?= e((string) $fila['codigo_control']) ?></strong>
                    — <?= e($vista->t('eval.col_madurez')) ?> <?= e((string) ($fila['madurez'] ?? '—')) ?>
                    · <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 100, '…')) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2 class="mb-3 mt-10 text-lg font-bold text-marina-950"><?= e($vista->t('eval.mayor_riesgo')) ?></h2>
    <?php if ($mayorRiesgo === []): ?>
        <p class="text-sm text-slate-600"><?= e($vista->t('eval.sin_datos_suficientes')) ?></p>
    <?php else: ?>
        <ul class="space-y-2 text-sm">
            <?php foreach ($mayorRiesgo as $fila): ?>
                <li class="border-b border-slate-100 pb-2">
                    <strong class="text-marina-950"><?= e((string) $fila['codigo_control']) ?></strong>
                    — <?= e($vista->t('eval.riesgo')) ?> <?= e((string) ($fila['nivel_riesgo'] ?? '—')) ?>
                    · <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 100, '…')) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
