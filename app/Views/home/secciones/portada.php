<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $hero
 * @var list<string>         $motores
 */
?>
<section id="inicio" class="relative overflow-hidden bg-marina-950 pt-16">

    <div class="pointer-events-none absolute inset-0 opacity-[0.07]" aria-hidden="true">
        <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="reticula" width="48" height="48" patternUnits="userSpaceOnUse">
                    <path d="M48 0H0V48" fill="none" stroke="currentColor" stroke-width="1"
                          class="text-acento-400"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#reticula)"/>
        </svg>
    </div>

    <div class="pointer-events-none absolute -right-40 -top-40 h-[32rem] w-[32rem] rounded-full bg-acento-500/10 blur-3xl"
         aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-6 py-24 lg:px-8 lg:py-32">
        <div class="max-w-3xl">

            <p class="inline-flex items-center gap-2 rounded-full border border-acento-400/30 bg-acento-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-acento-400">
                <?= e($hero['etiqueta']) ?>
            </p>

            <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                <?= e($hero['titulo']) ?><br>
                <span class="text-acento-400"><?= e($hero['resaltado']) ?></span>
            </h1>

            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-marina-200">
                <?= e($hero['texto']) ?>
            </p>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                <a href="<?= e($hero['cta_primario']['destino']) ?>"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-acento-500 px-7 py-3.5 text-base font-semibold text-marina-950 transition hover:bg-acento-400">
                    <?= e($hero['cta_primario']['etiqueta']) ?>
                    <?= icono('flecha', 'h-4 w-4') ?>
                </a>
                <a href="<?= e($hero['cta_secundario']['destino']) ?>"
                   class="inline-flex items-center justify-center rounded-lg border border-white/20 px-7 py-3.5 text-base font-semibold text-white transition hover:border-white/40 hover:bg-white/5">
                    <?= e($hero['cta_secundario']['etiqueta']) ?>
                </a>
            </div>
        </div>
    </div>

    <div class="relative border-t border-white/10">
        <div class="mx-auto max-w-7xl px-6 py-8 lg:px-8">
            <p class="text-center text-xs font-semibold uppercase tracking-widest text-marina-300">
                Experiencia en los principales motores de base de datos
            </p>
            <ul class="mt-5 flex flex-wrap items-center justify-center gap-x-10 gap-y-3">
                <?php foreach ($motores as $motor): ?>
                    <li class="text-base font-semibold text-marina-300/70"><?= e($motor) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
