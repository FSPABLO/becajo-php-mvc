<?php

declare(strict_types=1);

/**
 * Hero de la portada.
 *
 * Sigue el artefacto de diseño: dos columnas 1,15 / 0,85 — el argumento a la
 * izquierda y, a la derecha, una tarjeta de índice de riesgo que ENSEÑA el
 * producto en vez de describirlo. Quien llega ve de una vez qué entrega la
 * auditoría: un número, un estado y el desglose de controles.
 *
 * @var \App\Core\Vista $vista
 * @var array<string, mixed> $hero
 */
$panel = $hero['panel'] ?? null;

/**
 * Tonos admitidos para los estados del panel.
 *
 * El contenido no elige clases: elige un tono de la escala semántica y pill()
 * lo traduce, añadiendo ícono y etiqueta. Un valor inventado en
 * config/contenido.php degrada a un pill neutro en vez de romper la maqueta.
 */
$tonos = ['ok', 'warn', 'bad', 'crit', 'na'];
$tono = static fn (mixed $valor): string =>
    in_array($valor, $tonos, true) ? (string) $valor : 'na';
?>
<section id="inicio" class="relative overflow-hidden bg-fondo pt-16">

    <div class="relative mx-auto grid max-w-[1180px] items-center gap-12 px-5 pb-[74px] pt-[66px] lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">

        <div class="flex flex-col items-start gap-[22px]">

            <?php /* Badge normativo: oro y mono, extruido. */ ?>
            <span class="rv-extruido rv-badge-norma px-[13px] py-[7px]">
                <?= e($hero['norma']) ?>
            </span>

            <h1 class="rv-titulo m-0 text-[2.4rem] font-medium leading-[1.08] tracking-[-0.01em] text-texto sm:text-5xl lg:text-[56px]">
                <?= e($hero['titulo']) ?>
            </h1>

            <p class="rv-titulo m-0 max-w-[52ch] text-xl leading-[1.55] text-texto-2">
                <?= e($hero['texto']) ?>
            </p>

            <div class="flex flex-wrap gap-3">
                <a href="<?= e($vista->destino($hero['cta_primario']['destino'])) ?>"
                   class="rv-extruido rv-interactivo rounded-rv-lg bg-primario px-[26px] py-[15px] text-[15px] font-semibold text-primario-texto">
                    <?= e($hero['cta_primario']['etiqueta']) ?>
                </a>
                <a href="<?= e($vista->destino($hero['cta_secundario']['destino'])) ?>"
                   class="rv-extruido rv-interactivo rounded-rv-lg bg-superficie px-[26px] py-[15px] text-[15px] font-medium text-texto">
                    <?= e($hero['cta_secundario']['etiqueta']) ?>
                </a>
            </div>

            <?php if (($hero['cifras'] ?? []) !== []): ?>
                <dl class="flex flex-wrap gap-[26px] pt-1.5">
                    <?php foreach ($hero['cifras'] as $cifra): ?>
                        <div class="flex flex-col gap-0.5">
                            <dt class="order-2 text-[11.5px] uppercase tracking-[0.05em] text-texto-2">
                                <?= e($cifra['etiqueta']) ?>
                            </dt>
                            <dd class="tabular order-1 m-0 text-[21px] font-semibold text-texto">
                                <?= e($cifra['valor']) ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>

        <?php if ($panel !== null): ?>
            <div class="rv-extruido-lg rounded-[20px] bg-superficie p-[22px]">

                <div class="mb-4 flex items-baseline justify-between gap-2.5">
                    <span class="rv-titulo text-[17px] text-texto"><?= e($panel['titulo']) ?></span>
                    <?php /* Identificador de auditoría: mono y oro. */ ?>
                    <span class="rv-id text-[11px]"><?= e($panel['referencia']) ?></span>
                </div>

                <div class="mb-1 flex items-end gap-2.5">
                    <span class="tabular text-[52px] font-semibold leading-none text-bad"><?= e($panel['indice']) ?></span>
                    <span class="pb-[7px] text-[13px] text-texto-2"><?= e($panel['indice_maximo']) ?></span>
                </div>

                <div class="mb-5">
                    <?= pill($tono($panel['estado'] ?? null), $panel['estado_etiqueta']) ?>
                </div>

                <div class="flex flex-col gap-3.5">
                    <?php foreach ($panel['barras'] as $barra): ?>
                        <?php
                        // El porcentaje se acota aquí y no en la hoja de estilos:
                        // un valor fuera de rango en el contenido desbordaría la pista.
                        $ancho = max(0, min(100, (int) ($barra['porcentaje'] ?? 0)));
                        ?>
                        <div class="flex flex-col gap-[7px]">
                            <div class="flex justify-between text-[12.5px]">
                                <span class="text-texto-2"><?= e($barra['etiqueta']) ?></span>
                                <span class="tabular font-semibold text-texto"><?= e($barra['valor']) ?></span>
                            </div>
                            <?php /* La pista va hundida: es un hueco que se rellena. */ ?>
                            <div class="rv-hundido h-[11px] rounded-full bg-fondo">
                                <div class="h-[11px] rounded-full bg-primario" style="width: <?= $ancho ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-[18px] flex flex-wrap gap-2 border-t border-borde pt-4">
                    <?php foreach ($panel['conteos'] as $conteo): ?>
                        <?= pill($tono($conteo['estado'] ?? null), $conteo['etiqueta']) ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
