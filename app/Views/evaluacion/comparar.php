<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var array<string, list<\App\Models\Entidades\Auditoria>> $porOrganizacion
 * @var array<string, list<array<string, mixed>>> $historicoPorOrganizacion
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
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-primario hover:underline">
            <?= e($vista->t('eval.volver_auditorias')) ?>
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="rv-titulo text-3xl font-semibold text-texto"><?= e($vista->t('eval.comparacion_historica')) ?></h1>
        <p class="mt-1 text-texto-2"><?= e($vista->t('eval.evolucion_indice')) ?></p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($porOrganizacion === []): ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto"><?= e($vista->t('eval.sin_auditorias_comparar')) ?></p>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($porOrganizacion as $organizacion => $grupo): ?>
                <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
                    <h2 class="rv-titulo text-lg font-semibold text-texto"><?= e($organizacion) ?></h2>

                    <?php if (count($grupo) < 2): ?>
                        <p class="mt-2 text-sm text-texto-2">
                            <?= e($vista->t('eval.solo_una_auditoria')) ?>
                        </p>
                    <?php endif; ?>

                    <div class="mt-4 flex items-end gap-3">
                        <?php foreach ($grupo as $auditoria): ?>
                            <?php $indice = $auditoria->indiceGeneralRiesgo; ?>
                            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>"
                               class="flex w-16 flex-col items-center gap-1 text-center">
                                <span class="text-xs font-semibold tabular text-texto">
                                    <?= $indice === null ? '—' : e(number_format($indice, 2)) ?>
                                </span>
                                <div class="flex h-24 w-full items-end rounded bg-elevado">
                                    <div class="w-full rounded bg-primario"
                                         style="height: <?= $indice === null ? 0 : round(($indice / $maximo) * 100) ?>%"></div>
                                </div>
                                <span class="text-[11px] text-texto-2"><?= e($auditoria->fecha) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php
                    // Punto 18: desglose de la misma tendencia, pero por dominio en
                    // vez de por índice general — para ver en qué áreas concretas
                    // mejoró o empeoró cada auditoría, no solo el número global.
                    $historico = $historicoPorOrganizacion[$organizacion] ?? [];
                    if ($historico !== []):
                        $fechas = [];
                        $porDominio = [];
                        foreach ($historico as $fila) {
                            $fechas[$fila['fecha']] = true;
                            $porDominio[$fila['dominio']][$fila['fecha']] = (float) $fila['madurez_promedio'];
                        }
                        $fechas = array_keys($fechas);
                        sort($fechas);
                    ?>
                        <div class="mt-6 overflow-x-auto">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-texto-2">
                                Madurez ponderada por dominio
                            </p>
                            <table class="w-full text-left text-xs">
                                <thead class="text-texto-2">
                                    <tr>
                                        <th class="py-1 pr-3 font-semibold">Dominio</th>
                                        <?php foreach ($fechas as $fecha): ?>
                                            <th class="px-2 py-1 text-center font-semibold"><?= e($fecha) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-borde">
                                    <?php foreach ($porDominio as $dominio => $valoresPorFecha): ?>
                                        <tr>
                                            <td class="py-1.5 pr-3 font-medium text-texto"><?= e($dominio) ?></td>
                                            <?php foreach ($fechas as $fecha): ?>
                                                <td class="px-2 py-1.5 text-center tabular text-texto-2">
                                                    <?= isset($valoresPorFecha[$fecha]) ? e(number_format($valoresPorFecha[$fecha], 2)) : '—' ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
