<?php

declare(strict_types=1);

/**
 * Ficha: el resumen de una auditoría.
 *
 * Las mismas cifras que la cabecera de /evaluacion/{id}/resultados, salidas de
 * los mismos procedimientos (sp_resumen_auditoria y sp_cumplimiento_dominio):
 * dos pantallas que dijeran números distintos sobre la misma auditoría no se
 * podrían creer ninguna de las dos.
 *
 * No lleva el color de zona por dominio a propósito: esa regla vive en la vista
 * de resultados con los cortes de fn_zona, y una tercera copia es la que un día
 * dice otra cosa. El detalle con color está a un clic, en «Ver resultados».
 *
 * «Sin dato no es cero»: un cumplimiento sin controles Sí/No se imprime como
 * guion, no como 0,0 %.
 *
 * @var \App\Core\Vista                     $vista
 * @var \App\Models\Entidades\Auditoria     $auditoria
 * @var array<string, mixed>                $resumen
 * @var list<array<string, mixed>>          $dominios
 */
$porcentaje = static fn (mixed $fraccion): string => $fraccion === null || $fraccion === ''
    ? '—'
    : number_format((float) $fraccion * 100, 1, ',', '') . ' %';

$decimal = static fn (mixed $valor): string => $valor === null || $valor === ''
    ? '—'
    : number_format((float) $valor, 1, ',', '');

$cifras = [
    [$vista->t('eval.cumplimiento_general'), $porcentaje($resumen['cumplimiento'] ?? null)],
    [$vista->t('eval.madurez_promedio'), $decimal($resumen['madurez_promedio'] ?? null)],
    [$vista->t('eval.indice_general_riesgo'), $decimal($auditoria->indiceGeneralRiesgo)],
];
?>
<section class="rv-extruido-sm overflow-hidden rounded-rv-lg border border-borde bg-superficie text-[13px]">
    <header class="flex items-start justify-between gap-3 border-b border-borde px-3.5 py-2.5">
        <div class="min-w-0">
            <h3 class="font-semibold text-texto"><?= e($vista->t('asistente.ficha_resumen', (string) $auditoria->id)) ?></h3>
            <p class="truncate text-[12px] text-texto-2"><?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?></p>
        </div>
        <span class="flex-none">
            <?= $auditoria->estaFinalizada()
                ? pill('ok', $vista->t('eval.finalizada'))
                : pill('warn', $vista->t('eval.en_progreso')) ?>
        </span>
    </header>

    <dl class="grid grid-cols-3 divide-x divide-borde border-b border-borde">
        <?php foreach ($cifras as [$rotulo, $valor]): ?>
            <div class="px-3 py-2.5">
                <dt class="text-[10.5px] uppercase leading-tight tracking-[0.06em] text-texto-2"><?= e($rotulo) ?></dt>
                <dd class="mt-1 text-[17px] font-semibold tabular text-texto"><?= e($valor) ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>

    <p class="border-b border-borde px-3.5 py-2 text-[12px] tabular text-texto-2">
        <?= e($vista->t(
            'asistente.respuestas_si_no_na',
            (string) (int) ($resumen['controles_si'] ?? 0),
            (string) (int) ($resumen['controles_no'] ?? 0),
            (string) (int) ($resumen['controles_na'] ?? 0),
        )) ?>
    </p>

    <?php if ($dominios !== []): ?>
        <div class="px-3.5 py-2.5">
            <h4 class="mb-1.5 text-[10.5px] uppercase tracking-[0.06em] text-texto-2"><?= e($vista->t('eval.cumplimiento_dominio')) ?></h4>
            <ul class="space-y-1">
                <?php foreach ($dominios as $dominio): ?>
                    <li class="flex items-baseline justify-between gap-3">
                        <span class="flex min-w-0 items-center gap-1.5 text-texto">
                            <?= iconoDominio((string) ($dominio['clave_dominio'] ?? ''), 'h-3.5 w-3.5 flex-none text-texto-2') ?>
                            <span class="truncate"><?= e((string) ($dominio['nombre_dominio'] ?? '')) ?></span>
                        </span>
                        <span class="flex-none font-semibold tabular text-texto"><?= e($porcentaje($dominio['cumplimiento'] ?? null)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <footer class="border-t border-borde px-3.5 py-2">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/resultados')) ?>" class="text-[12px] font-semibold text-primario hover:underline">
            <?= e($vista->t('eval.ver_resultados')) ?> →
        </a>
    </footer>
</section>
