<?php

declare(strict_types=1);

/**
 * @var list<array{valor: string, etiqueta: string}> $metricas
 * @var array<string, mixed>                         $caso
 */
?>
<section id="resultados" class="bg-fondo py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <dl class="grid gap-10 border-b border-borde pb-16 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($metricas as $metrica): ?>
                <div>
                    <dt class="text-4xl font-extrabold tracking-tight text-primario-hover sm:text-5xl">
                        <?= e($metrica['valor']) ?>
                    </dt>
                    <dd class="mt-2 text-sm leading-relaxed text-texto-2">
                        <?= e($metrica['etiqueta']) ?>
                    </dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <div class="mt-16 grid items-center gap-12 lg:grid-cols-2">

            <figure>
                <p class="text-sm font-semibold uppercase tracking-widest text-primario-hover">
                    <?= e($caso['sector']) ?>
                </p>
                <blockquote class="mt-5 text-2xl font-medium leading-relaxed text-texto sm:text-3xl">
                    <p>&laquo;<?= e($caso['cita']) ?>&raquo;</p>
                </blockquote>
                <figcaption class="mt-6 text-sm text-texto-2">
                    <span class="font-semibold text-texto"><?= e($caso['autor']) ?></span>
                    &mdash; <?= e($caso['empresa']) ?>
                </figcaption>
            </figure>

            <ul class="space-y-4">
                <?php foreach ($caso['logros'] as $logro): ?>
                    <li class="rv-extruido flex items-start gap-4 rounded-rv border border-borde bg-superficie p-5">
                        <span class="mt-0.5 shrink-0 text-primario-hover"><?= icono('check', 'h-5 w-5') ?></span>
                        <span class="font-medium text-texto-2"><?= e($logro) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
