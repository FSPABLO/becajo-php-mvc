<?php

declare(strict_types=1);

/**
 * Testimonios de clientes, en carrusel con la tarjeta central destacada.
 *
 * El carrusel es una lista que se desplaza en horizontal con scroll-snap: el
 * navegador se encarga de encajar cada tarjeta en el centro, y el guion de
 * public/assets/js/principal.js solo marca cuál quedó ahí y mueve la pista al
 * pulsar las flechas o los puntos. Sin JavaScript la sección sigue sirviendo:
 * la lista se arrastra con el dedo o con la rueda.
 *
 * @var \App\Core\Vista                                        $vista
 * @var array{etiqueta: string, titulo: string, texto: string} $encabezado
 * @var list<\App\Models\Entidades\Testimonio>                 $testimonios
 */

/*
 * Una sola estrella maciza, reutilizada por las dos filas de cada calificación.
 * No sale de icono(): aquel catálogo dibuja con trazo y fill="none", y aquí hace
 * falta una silueta rellena que pueda recortarse por la mitad.
 */
$estrella = '<svg class="h-4 w-4 shrink-0" width="16" height="16" viewBox="0 0 24 24" '
          . 'fill="currentColor" aria-hidden="true">'
          . '<path d="m12 2.6 2.9 5.9 6.5.95-4.7 4.58 1.11 6.47L12 17.45 6.19 20.5l1.11-6.47'
          . '-4.7-4.58 6.5-.95L12 2.6Z"/></svg>';

$fila = str_repeat($estrella, \App\Models\Entidades\Testimonio::MAXIMO);
?>
<section id="testimonios" class="bg-slate-50 py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-acento-600">
                <?= e($encabezado['etiqueta']) ?>
            </p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-marina-950 sm:text-4xl">
                <?= e($encabezado['titulo']) ?>
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                <?= e($encabezado['texto']) ?>
            </p>
        </div>

        <div class="carrusel-testimonios mt-14" data-carrusel-testimonios>

            <?php
            /*
             * Las flechas se superponen a los bordes de la pista, así que
             * comparten con ella este contenedor y no el de fuera: colgadas del
             * de fuera quedarían centradas contando también la fila de puntos.
             *
             * Van antes y después de la lista en el HTML —no flotando al
             * final— para que el tabulador las encuentre en el orden en que se
             * ven.
             */
            ?>
            <div class="relative">

                <button type="button"
                        class="carrusel-testimonios__flecha left-0 lg:-left-5"
                        data-carrusel-anterior
                        aria-label="<?= e($vista->t('testimonios.anterior')) ?>">
                    <?= icono('chevron', 'h-5 w-5 rotate-90') ?>
                </button>

                <ul class="carrusel-testimonios__pista" tabindex="0" role="group"
                    aria-label="<?= e($vista->t('testimonios.lista')) ?>"
                    data-carrusel-pista>
                    <?php foreach ($testimonios as $testimonio): ?>
                        <li class="carrusel-testimonios__tarjeta" data-carrusel-tarjeta>
                            <figure class="flex h-full flex-col items-center rounded-xl border border-slate-200 bg-white px-7 pb-8 pt-7 text-center">

                                <span class="carrusel-testimonios__comilla" aria-hidden="true">&ldquo;</span>

                                <?php
                                /*
                                 * Sin fotografías de los clientes, el avatar son las
                                 * iniciales sobre un círculo, igual que en la sección
                                 * de equipo. Es dato decorativo: el nombre ya está
                                 * escrito debajo, así que se oculta al lector de
                                 * pantalla en vez de repetirlo.
                                 */
                                ?>
                                <span class="mt-4 grid h-16 w-16 shrink-0 place-items-center rounded-full bg-marina-950 text-lg font-bold text-acento-400"
                                      aria-hidden="true">
                                    <?= e($testimonio->iniciales) ?>
                                </span>

                                <blockquote class="mt-6 flex-1 text-sm leading-relaxed text-slate-600">
                                    <p><?= e($testimonio->descripcion) ?></p>
                                </blockquote>

                                <?php
                                /*
                                 * La calificación son dos filas de cinco estrellas
                                 * superpuestas: la gris de abajo marca el total y la
                                 * dorada de arriba se recorta al porcentaje del
                                 * puntaje. Eso es lo que dibuja el medio punto de un
                                 * 4.5 sin necesitar una tercera silueta.
                                 *
                                 * El ancho va en un atributo style y no en una clase
                                 * porque es un número calculado, y Tailwind solo
                                 * genera las clases que ya están escritas en el HTML.
                                 * El valor pasa por number_format(), así que nunca es
                                 * otra cosa que dígitos y un punto.
                                 */
                                ?>
                                <div class="relative mt-6 inline-flex gap-0.5" role="img"
                                     aria-label="<?= e($vista->t('testimonios.calificacion', $testimonio->puntajeLegible())) ?>">
                                    <div class="flex gap-0.5 text-slate-200" aria-hidden="true"><?= $fila ?></div>
                                    <div class="absolute inset-y-0 left-0 flex gap-0.5 overflow-hidden text-aviso-400"
                                         style="width: <?= number_format($testimonio->porcentaje(), 2, '.', '') ?>%"
                                         aria-hidden="true"><?= $fila ?></div>
                                </div>

                                <figcaption class="mt-4">
                                    <span class="block font-bold text-marina-950">
                                        <?= e($testimonio->nombre) ?>
                                    </span>
                                    <?php if ($testimonio->cargo !== ''): ?>
                                        <span class="mt-1 block text-sm text-slate-500">
                                            <?= e($testimonio->cargo) ?>
                                        </span>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <button type="button"
                        class="carrusel-testimonios__flecha right-0 lg:-right-5"
                        data-carrusel-siguiente
                        aria-label="<?= e($vista->t('testimonios.siguiente')) ?>">
                    <?= icono('chevron', 'h-5 w-5 -rotate-90') ?>
                </button>
            </div>

            <ol class="mt-10 flex justify-center gap-2.5">
                <?php foreach ($testimonios as $testimonio): ?>
                    <li>
                        <button type="button"
                                class="carrusel-testimonios__punto"
                                data-carrusel-punto
                                aria-label="<?= e($vista->t('testimonios.ir_a', $testimonio->nombre)) ?>">
                        </button>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</section>
