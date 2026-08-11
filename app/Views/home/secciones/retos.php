<?php

declare(strict_types=1);

/**
 * Sección «Retos» — sección clara de la cadencia de la portada.
 *
 * Encabezado centrado (eyebrow en versalitas + título editorial) y una rejilla
 * de tarjetas que se acomodan solas: auto-fit con un mínimo de 250 px, así que
 * pasan de cuatro a dos y a una sin necesitar puntos de quiebre propios.
 *
 * Las tarjetas NO llevan borde ni un fondo distinto del de la sección: es
 * neumorfismo puro — emergen del pergamino solo por el par de sombras. Por eso
 * usan bg-fondo y no bg-superficie; cualquier diferencia de relleno rompería
 * el efecto de estar talladas en la misma pieza.
 *
 * @var array<string, mixed> $retos
 */
?>
<section id="retos" class="rv-alterno bg-fondo">
    <div class="mx-auto max-w-[1180px] px-5 py-16">

        <div class="mb-[30px] flex flex-col items-center gap-2 text-center">
            <?php if (($retos['eyebrow'] ?? '') !== ''): ?>
                <span class="text-[11.5px] uppercase tracking-[0.14em] text-texto-2">
                    <?= e($retos['eyebrow']) ?>
                </span>
            <?php endif; ?>
            <h2 class="rv-titulo m-0 text-[2rem] font-medium leading-[1.15] text-texto sm:text-4xl">
                <?= e($retos['titulo']) ?>
            </h2>
        </div>

        <div class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(250px,1fr))]">
            <?php foreach ($retos['lista'] as $indice => $reto): ?>
                <?php /* items-center centra los bloques; text-center, las líneas de cada uno. */ ?>
                <div class="rv-extruido flex flex-col items-center gap-[9px] rounded-[16px] bg-fondo p-6 text-center">
                    <?php /* La numeración es referencia, no cumplimiento: mono y oro. */ ?>
                    <span class="font-mono text-[11px] text-oro-texto">
                        <?= e(str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT)) ?>
                    </span>
                    <h3 class="m-0 text-[16.5px] font-semibold leading-snug text-texto">
                        <?= e($reto['titulo']) ?>
                    </h3>
                    <p class="rv-titulo m-0 text-base leading-[1.55] text-texto-2">
                        <?= e($reto['texto']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
