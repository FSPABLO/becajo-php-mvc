<?php

declare(strict_types=1);

/**
 * @var list<array{valor: string, etiqueta: string}> $metricas
 * @var array<string, mixed>                         $caso
 */
?>
<section id="resultados" class="bg-marina-950 py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <dl class="grid gap-10 border-b border-white/10 pb-16 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($metricas as $metrica): ?>
                <div>
                    <dt class="text-4xl font-extrabold tracking-tight text-acento-400 sm:text-5xl">
                        <?= e($metrica['valor']) ?>
                    </dt>
                    <dd class="mt-2 text-sm leading-relaxed text-marina-200">
                        <?= e($metrica['etiqueta']) ?>
                    </dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <div class="mt-16 grid items-center gap-12 lg:grid-cols-2">

            <figure>
                <p class="text-sm font-semibold uppercase tracking-widest text-acento-400">
                    <?= e($caso['sector']) ?>
                </p>
                <blockquote class="mt-5 text-2xl font-medium leading-relaxed text-white sm:text-3xl">
                    <p>&laquo;<?= e($caso['cita']) ?>&raquo;</p>
                </blockquote>
                <figcaption class="mt-6 text-sm text-marina-300">
                    <span class="font-semibold text-white"><?= e($caso['autor']) ?></span>
                    &mdash; <?= e($caso['empresa']) ?>
                </figcaption>
            </figure>

            <ul class="space-y-4">
                <?php foreach ($caso['logros'] as $logro): ?>
                    <li class="flex items-start gap-4 rounded-lg border border-white/10 bg-white/5 p-5">
                        <span class="mt-0.5 shrink-0 text-acento-400"><?= icono('check', 'h-5 w-5') ?></span>
                        <span class="font-medium text-marina-100"><?= e($logro) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
