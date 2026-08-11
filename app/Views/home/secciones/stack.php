<?php

declare(strict_types=1);

/**
 * Stack tecnológico: carrusel con los motores a los que damos soporte.
 *
 * @var \App\Core\Vista     $vista
 * @var array<string, mixed> $stack
 */
?>
<?php
/*
 * Esta sección respira más que sus vecinas (py-24) a propósito: el carrusel es
 * el único contenido y el aire alrededor es lo que lo separa de los servicios
 * y de los resultados en lugar de una línea divisoria.
 */
?>
<section id="stack" class="rv-alterno bg-fondo py-48 lg:py-56">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-primario">
                <?= e($stack['etiqueta']) ?>
            </p>
            <h2 class="rv-titulo mt-3 text-4xl font-extrabold tracking-tight text-texto sm:text-5xl">
                <?= e($stack['titulo']) ?>
            </h2>
        </div>

        <?php if (($stack['logos'] ?? []) !== []): ?>
            <?php
            /*
             * El carrusel imprime la lista de logotipos dos veces: el segundo
             * juego es la copia que permite el ciclo infinito (ver la regla
             * .carrusel-logos en assets/css/rivendel.css). La copia va con
             * aria-hidden y alt vacío para que un lector de pantalla no lea
             * seis motores dos veces.
             */
            ?>
            <div class="carrusel-logos mt-24 lg:mt-28" role="group"
                 aria-label="<?= e($stack['etiqueta']) ?>">
                <div class="carrusel-logos__pista">
                    <?php foreach ([false, true] as $esCopia): ?>
                        <ul class="carrusel-logos__grupo flex shrink-0 items-center<?= $esCopia ? ' carrusel-logos__copia' : '' ?>"
                            <?= $esCopia ? 'aria-hidden="true"' : '' ?>>
                            <?php foreach ($stack['logos'] as $logo): ?>
                                <li class="flex w-[268px] shrink-0 items-center justify-center px-6 sm:w-96 sm:px-8">
                                    <?php
                                    /*
                                     * recurso() y no url(): añade ?v=<fecha del
                                     * archivo>. Sin eso, reemplazar un logotipo
                                     * conservando el nombre no se ve — el
                                     * navegador sigue mostrando el que tenía en
                                     * caché, que fue justo lo que pasó al quitar
                                     * los fondos blancos de mysql y sql-server.
                                     */
                                    ?>
                                    <img src="<?= e($vista->recurso($logo['imagen'])) ?>"
                                         alt="<?= $esCopia ? '' : e($logo['nombre']) ?>"
                                         width="<?= (int) $logo['ancho'] ?>"
                                         height="<?= (int) $logo['alto'] ?>"
                                         loading="lazy" decoding="async"
                                         class="h-[100px] w-auto max-w-full object-contain sm:h-[119px]">
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
