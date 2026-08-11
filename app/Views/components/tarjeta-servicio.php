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
<article class="rv-extruido rv-interactivo group relative rounded-rv-lg border border-borde bg-superficie p-7 transition hover:-translate-y-1 hover:border-primario-hover hover:shadow-lg hover:shadow-fondo/5">

    <div class="grid h-12 w-12 place-items-center rounded-rv bg-fondo text-primario-hover transition group-hover:bg-primario group-hover:text-primario-texto">
        <?= icono($servicio->icono, 'h-6 w-6') ?>
    </div>

    <h3 class="mt-5 text-lg font-bold text-texto">
        <?= e($servicio->titulo) ?>
    </h3>

    <p class="mt-2.5 leading-relaxed text-texto-2">
        <?= e($servicio->texto) ?>
    </p>

    <?php if ($servicio->enlace !== null): ?>
        <a href="<?= e($vista->url($servicio->enlace)) ?>"
           class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primario transition group-hover:text-primario">
            <?= e($servicio->etiquetaEnlace ?? 'Ver más') ?>
            <?= icono('flecha', 'h-4 w-4') ?>
        </a>
    <?php endif; ?>
</article>
