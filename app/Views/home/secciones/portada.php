<?php

declare(strict_types=1);

/** @var array<string, mixed> $hero */

/**
 * Lista blanca de estados del tablero de ejemplo.
 *
 * El contenido no elige clases de Tailwind: elige un estado semántico y esta
 * tabla lo traduce. Así un valor equivocado en config/contenido.php degrada a
 * un color neutro en vez de romper la maqueta o colar clases arbitrarias.
 */
$coloresEstado = [
    'exito'  => ['punto' => 'bg-exito-400',  'texto' => 'text-exito-400'],
    'aviso'  => ['punto' => 'bg-aviso-400',  'texto' => 'text-aviso-400'],
    'alerta' => ['punto' => 'bg-alerta-400', 'texto' => 'text-alerta-400'],
];
$panel = $hero['panel'] ?? null;
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
    <div class="pointer-events-none absolute -bottom-56 -left-32 h-[28rem] w-[28rem] rounded-full bg-marina-500/10 blur-3xl"
         aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-6 py-24 lg:px-8 lg:py-32">
        <div class="grid items-center gap-16 lg:grid-cols-12">

            <div class="lg:col-span-7">

                <p class="inline-flex items-center gap-2 rounded-full border border-acento-400/30 bg-acento-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-acento-400">
                    <span class="relative flex h-2 w-2" aria-hidden="true">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-acento-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-acento-400"></span>
                    </span>
                    <?= e($hero['etiqueta']) ?>
                </p>

                <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    <?= e($hero['titulo']) ?><br>
                    <span class="text-acento-400"><?= e($hero['resaltado']) ?></span>
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-marina-200">
                    <?= e($hero['texto']) ?>
                </p>

                <?php if (($hero['puntos'] ?? []) !== []): ?>
                    <ul class="mt-8 space-y-3">
                        <?php foreach ($hero['puntos'] as $punto): ?>
                            <li class="flex items-start gap-3 text-marina-100">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-acento-500/15 text-acento-400">
                                    <?= icono('check', 'h-3.5 w-3.5') ?>
                                </span>
                                <?= e($punto) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

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

            <?php if ($panel !== null): ?>
                <div class="lg:col-span-5">
                    <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-6 shadow-2xl shadow-marina-950/50 backdrop-blur sm:p-8">

                        <div class="flex items-start justify-between gap-4 border-b border-white/10 pb-5">
                            <div>
                                <p class="text-sm font-semibold text-white"><?= e($panel['titulo']) ?></p>
                                <p class="mt-1 text-xs uppercase tracking-wider text-marina-300">
                                    <?= e($panel['subtitulo']) ?>
                                </p>
                            </div>
                            <span class="text-acento-400"><?= icono('disco', 'h-6 w-6') ?></span>
                        </div>

                        <dl class="divide-y divide-white/5">
                            <?php foreach ($panel['filas'] as $fila): ?>
                                <?php $color = $coloresEstado[$fila['estado']] ?? ['punto' => 'bg-marina-300', 'texto' => 'text-marina-100']; ?>
                                <div class="flex items-center justify-between gap-4 py-3.5">
                                    <dt class="flex items-center gap-2.5 text-sm text-marina-200">
                                        <span class="h-1.5 w-1.5 rounded-full <?= $color['punto'] ?>" aria-hidden="true"></span>
                                        <?= e($fila['etiqueta']) ?>
                                    </dt>
                                    <dd class="text-sm font-semibold <?= $color['texto'] ?>">
                                        <?= e($fila['valor']) ?>
                                    </dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>

                        <p class="mt-5 border-t border-white/10 pt-5 text-xs leading-relaxed text-marina-300">
                            <?= e($panel['pie']) ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
