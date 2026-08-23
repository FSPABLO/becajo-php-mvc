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
/*
 * Mismo criterio que resultados.php: la zona se nombra por su nivel de
 * riesgo, no por su color. Aquí importa el doble, porque este documento está
 * hecho para imprimirse y puede salir en escala de grises.
 */
$tonoZona = [
    'ROJO'     => 'crit',
    'AMARILLO' => 'warn',
    'VERDE'    => 'ok',
];

$etiquetaZona = [
    'ROJO'     => $vista->t('eval.zona_alta'),
    'AMARILLO' => $vista->t('eval.zona_media'),
    'VERDE'    => $vista->t('eval.zona_baja'),
];

$fondoZona = [
    'ROJO'     => 'bg-bad',
    'AMARILLO' => 'bg-warn',
    'VERDE'    => 'bg-ok',
];

$porcentaje = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float) $v * 100, 1, ',', '') . ' %';

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
    <?php
    /*
     * Cinzel entra aquí: §2 le permite el logotipo, la portada y el encabezado
     * de informe exportado. Es una cadena de dos palabras, dentro del límite
     * de tres que fija la regla tipográfica.
     */
    ?>
    <p class="rv-marca text-sm text-oro-texto"><?= e($empresa['nombre'] ?? 'Rivendel') ?></p>
    <p class="mt-3 text-xs font-semibold uppercase tracking-widest text-texto-2"><?= e($vista->t('eval.reporte_ejecutivo')) ?></p>
    <h1 class="rv-titulo mt-1 text-3xl font-semibold text-texto"><?= e($vista->t('auth.eyebrow')) ?></h1>
    <p class="mt-2 text-sm text-texto-2"><?= e($vista->t('eval.generado_el', date('Y-m-d H:i'), $usuario->nombre)) ?></p>

    <dl class="mt-6 grid gap-4 border-t border-borde pt-6 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-texto-2"><?= e($vista->t('eval.col_organizacion')) ?></dt>
            <dd class="mt-0.5 text-texto"><?= e($auditoria->organizacion) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-texto-2"><?= e($vista->t('eval.col_area')) ?></dt>
            <dd class="mt-0.5 text-texto"><?= e($auditoria->areaEvaluada) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-texto-2"><?= e($vista->t('eval.auditor')) ?></dt>
            <dd class="mt-0.5 text-texto"><?= e($auditoria->nombreAuditor) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-texto-2"><?= e($vista->t('eval.admin_entrevistado')) ?></dt>
            <dd class="mt-0.5 text-texto"><?= e($auditoria->nombreAdministradorBd) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-texto-2"><?= e($vista->t('eval.col_fecha')) ?></dt>
            <dd class="mt-0.5 text-texto"><?= e($auditoria->fecha) ?></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-texto-2"><?= e($vista->t('eval.col_estado')) ?></dt>
            <dd class="mt-0.5 text-texto"><?= e($auditoria->estaFinalizada() ? $vista->t('eval.finalizada') : $vista->t('eval.en_progreso')) ?></dd>
        </div>
    </dl>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-rv-lg border border-borde p-4">
            <p class="text-xs text-texto-2"><?= e($vista->t('eval.cumplimiento_general')) ?></p>
            <p class="mt-1 text-2xl font-extrabold text-texto"><?= e($porcentaje($resumen['cumplimiento'] ?? null)) ?></p>
        </div>
        <div class="rounded-rv-lg border border-borde p-4">
            <p class="text-xs text-texto-2"><?= e($vista->t('eval.madurez_promedio')) ?></p>
            <p class="mt-1 text-2xl font-extrabold text-texto"><?= e((string) ($resumen['madurez_promedio'] ?? '—')) ?></p>
        </div>
        <div class="rounded-rv-lg border border-borde p-4">
            <p class="text-xs text-texto-2"><?= e($vista->t('eval.indice_general_riesgo')) ?></p>
            <p class="mt-1 text-2xl font-extrabold text-texto">
                <?= $auditoria->indiceGeneralRiesgo === null ? e($vista->t('eval.sin_calcular')) : e(number_format($auditoria->indiceGeneralRiesgo, 2)) ?>
            </p>
        </div>
    </div>

    <h2 class="mb-3 mt-10 text-lg font-bold text-texto"><?= e($vista->t('eval.exposicion_riesgo')) ?></h2>
    <div class="grid gap-3 sm:grid-cols-3">
        <?php foreach ($exposicion as $riesgo): ?>
            <div class="rounded-rv-lg border border-borde p-4">
                <p class="text-sm font-semibold text-texto-2"><?= e($etiquetaTipo[$riesgo->tipo] ?? $riesgo->etiqueta()) ?></p>
                <p class="tabular mt-1 text-xl font-semibold text-texto">
                    <?= $riesgo->porcentaje() === null
                        ? '—'
                        : e(number_format((float) $riesgo->porcentaje(), 1, ',', '')) . ' %' ?>
                </p>
                <p class="mt-2"><?= pill($tonoZona[$riesgo->zona] ?? 'na', $etiquetaZona[$riesgo->zona] ?? '—') ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($celdas !== []): ?>
        <h2 class="mb-3 mt-10 text-lg font-bold text-texto"><?= e($vista->t('eval.matriz_riesgo')) ?></h2>
        <div class="flex gap-1.5">
            <?php for ($p = 5; $p >= 1; $p--): ?>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php $conteo = $celdas[$i . '-' . $p] ?? 0; ?>
                    <div class="flex h-9 w-9 items-center justify-center rounded text-xs font-bold text-texto <?= e($colorCelda($i, $p)) ?> <?= $conteo === 0 ? 'opacity-25' : '' ?>">
                        <?= $conteo > 0 ? e((string) $conteo) : '' ?>
                    </div>
                <?php endfor; ?>
            <?php endfor; ?>
        </div>
        <p class="mt-1.5 text-xs text-texto-2"><?= e($vista->t('eval.eje_matriz_reporte')) ?></p>
    <?php endif; ?>

    <h2 class="mb-3 mt-10 text-lg font-bold text-texto"><?= e($vista->t('eval.cumplimiento_dominio')) ?></h2>
    <table class="w-full text-left text-sm">
        <thead class="border-b border-borde text-xs uppercase text-texto-2">
            <tr>
                <th class="py-2"><?= e($vista->t('eval.col_dominio')) ?></th>
                <th class="py-2"><?= e($vista->t('eval.col_cumplimiento')) ?></th>
                <th class="py-2"><?= e($vista->t('eval.col_madurez')) ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-borde">
            <?php foreach ($dominios as $fila): ?>
                <tr>
                    <td class="py-2 font-medium text-texto">
                        <span class="inline-flex items-center gap-1.5">
                            <?= iconoDominio((string) $fila['clave_dominio'], 'h-4 w-4 shrink-0') ?>
                            <?= e((string) $fila['nombre_dominio']) ?>
                        </span>
                    </td>
                    <td class="py-2"><?= e($porcentaje($fila['cumplimiento'] ?? null)) ?></td>
                    <td class="py-2"><?= e((string) ($fila['madurez_promedio'] ?? '—')) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2 class="mb-3 mt-10 text-lg font-bold text-texto"><?= e($vista->t('eval.menor_madurez')) ?></h2>
    <?php if ($menorMadurez === []): ?>
        <p class="text-sm text-texto-2"><?= e($vista->t('eval.sin_datos_suficientes')) ?></p>
    <?php else: ?>
        <ul class="space-y-2 text-sm">
            <?php foreach ($menorMadurez as $fila): ?>
                <li class="border-b border-borde pb-2">
                    <strong class="text-texto"><?= e((string) $fila['codigo_control']) ?></strong>
                    — <?= e($vista->t('eval.col_madurez')) ?> <?= e((string) ($fila['madurez'] ?? '—')) ?>
                    · <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 100, '…')) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2 class="mb-3 mt-10 text-lg font-bold text-texto"><?= e($vista->t('eval.mayor_riesgo')) ?></h2>
    <?php if ($mayorRiesgo === []): ?>
        <p class="text-sm text-texto-2"><?= e($vista->t('eval.sin_datos_suficientes')) ?></p>
    <?php else: ?>
        <ul class="space-y-2 text-sm">
            <?php foreach ($mayorRiesgo as $fila): ?>
                <li class="border-b border-borde pb-2">
                    <strong class="text-texto"><?= e((string) $fila['codigo_control']) ?></strong>
                    — <?= e($vista->t('eval.riesgo')) ?> <?= e((string) ($fila['nivel_riesgo'] ?? '—')) ?>
                    · <?= e(mb_strimwidth((string) $fila['enunciado'], 0, 100, '…')) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
