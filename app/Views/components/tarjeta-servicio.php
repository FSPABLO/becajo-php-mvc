<?php

declare(strict_types=1);

/**
 * Componente reutilizable: tarjeta de un servicio.
 *
 * Recibe una entidad, no un arreglo. Por eso se accede con $servicio->titulo:
 * si el nombre de la propiedad estuviera mal escrito, el error saldría aquí y
 * no en silencio.
 *
 * @var \App\Models\Entidades\Servicio $servicio
 */
?>
<article class="group relative rounded-xl border border-slate-200 bg-white p-7 transition hover:-translate-y-1 hover:border-acento-400 hover:shadow-lg hover:shadow-marina-950/5">

    <div class="grid h-12 w-12 place-items-center rounded-lg bg-marina-950 text-acento-400 transition group-hover:bg-acento-500 group-hover:text-marina-950">
        <?= icono($servicio->icono, 'h-6 w-6') ?>
    </div>

    <h3 class="mt-5 text-lg font-bold text-marina-950">
        <?= e($servicio->titulo) ?>
    </h3>

    <p class="mt-2.5 leading-relaxed text-slate-600">
        <?= e($servicio->texto) ?>
    </p>

    <?php if ($servicio->enlace !== null): ?>
        <a href="<?= e($vista->url($servicio->enlace)) ?>"
           class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-acento-600 transition group-hover:text-acento-700">
            <?= e($servicio->etiquetaEnlace ?? 'Ver más') ?>
            <?= icono('flecha', 'h-4 w-4') ?>
        </a>
    <?php endif; ?>
</article>
