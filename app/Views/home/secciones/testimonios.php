<?php

declare(strict_types=1);

/**
 * Sección de testimonios.
 *
 * Tres huecos —anterior atenuado, activo al centro, siguiente atenuado— y un
 * recorrido circular: desde el primero, «anterior» lleva al último.
 *
 * Los CINCO testimonios se imprimen aquí, completos. El guion de
 * assets/js/principal.js solo reparte los papeles (data-posicion); no rellena
 * texto. Consecuencia buscada: sin JavaScript la sección se lee igual —tres
 * fichas y su contenido— en vez de quedarse vacía.
 *
 * @var \App\Core\Vista $vista
 * @var array{etiqueta: string, titulo: string, texto: string} $encabezado
 * @var list<\App\Models\Entidades\Testimonio> $testimonios
 */
$total = count($testimonios);

/*
 * Con cuál abre el carrusel: el testimonio marcado como 'destacado' en
 * config/contenido.php, o el primero si ninguno lo está. Se resuelve aquí y
 * no en el guion para que el HTML servido ya venga con las tres fichas en su
 * sitio, sin salto al cargar y sin depender de JavaScript.
 */
$inicial = 0;
foreach ($testimonios as $i => $candidato) {
    if ($candidato->destacado) {
        $inicial = $i;
        break;
    }
}

// Misma vuelta circular que hace el guion: desde el primero, el anterior es
// el último.
$anterior  = $total > 0 ? ($inicial - 1 + $total) % $total : 0;
$siguiente = $total > 0 ? ($inicial + 1) % $total : 0;
?>
<section id="testimonios" class="rv-alterno bg-fondo">
    <?php
    /*
     * data-carrusel-testimonios envuelve TODO, encabezado incluido: el guion
     * busca las flechas dentro de este contenedor, y si solo abarcara la lista
     * no las encontraría.
     */
    ?>
    <div class="mx-auto max-w-[1180px] px-5 py-16" data-carrusel-testimonios>

        <div class="mb-[30px] flex flex-col items-center gap-5">

            <div class="flex flex-col items-center gap-2 text-center">
                <span class="text-[11.5px] uppercase tracking-[0.14em] text-texto-2">
                    <?= e($encabezado['etiqueta']) ?>
                </span>
                <h2 class="rv-titulo m-0 text-[2rem] font-medium leading-[1.15] text-texto sm:text-4xl">
                    <?= e($encabezado['titulo']) ?>
                </h2>
            </div>

            <?php if ($total > 1): ?>
                <div class="flex items-center gap-2.5">
                    <button type="button" data-carrusel-anterior
                            aria-label="<?= e($vista->t('testimonios.anterior')) ?>"
                            class="rv-extruido rv-interactivo grid h-[46px] w-[46px] place-items-center rounded-rv-lg bg-fondo text-primario">
                        <?= icono('flecha', 'h-4 w-4 rotate-180') ?>
                    </button>
                    <button type="button" data-carrusel-siguiente
                            aria-label="<?= e($vista->t('testimonios.siguiente')) ?>"
                            class="rv-extruido rv-interactivo grid h-[46px] w-[46px] place-items-center rounded-rv-lg bg-fondo text-primario">
                        <?= icono('flecha', 'h-4 w-4') ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php /* tabindex hace la pista enfocable, para recorrerla con ← y →. */ ?>
        <div>
            <ul class="rv-tst" data-carrusel-pista tabindex="0"
                data-inicial="<?= e((string) $inicial) ?>"
                aria-live="polite" aria-label="<?= e($vista->t('testimonios.lista')) ?>">

                <?php foreach ($testimonios as $indice => $testimonio): ?>
                    <?php
                    /*
                     * Posición inicial ya resuelta en el servidor: sin ella,
                     * sin JavaScript se verían los cinco en fila.
                     *
                     * 'activo' se comprueba primero porque con dos testimonios
                     * el anterior y el siguiente son el mismo, y con uno solo
                     * los tres coinciden.
                     */
                    $posicion = match (true) {
                        $indice === $inicial               => 'activo',
                        $indice === $anterior              => 'anterior',
                        $indice === $siguiente             => 'siguiente',
                        default                            => 'oculto',
                    };
                    ?>
                    <li data-carrusel-tarjeta data-posicion="<?= e($posicion) ?>">
                        <figure class="rv-tst__ficha">

                            <?php
                            /*
                             * Cubre la ficha lateral: al pulsarla se navega. No
                             * lleva el sentido escrito, porque una ficha cambia
                             * de lado al girar el carrusel; lo deduce el guion
                             * del data-posicion que tenga en ese momento.
                             */
                            ?>
                            <button type="button" class="rv-tst__ir" data-carrusel-ir
                                    aria-label="<?= e($vista->t('testimonios.ir_a', $testimonio->nombre)) ?>"></button>

                            <?php if ($testimonio->foto !== ''): ?>
                                <div class="rv-retrato rv-retrato--redondo rv-tst__retrato shrink-0">
                                    <img src="<?= e($vista->recurso($testimonio->foto)) ?>"
                                         alt="" loading="lazy" width="96" height="96">
                                </div>
                            <?php else: ?>
                                <div class="rv-retrato rv-retrato--redondo rv-tst__retrato grid shrink-0 place-items-center"
                                     aria-hidden="true">
                                    <span class="font-mono text-lg text-texto-2"><?= e($testimonio->iniciales) ?></span>
                                </div>
                            <?php endif; ?>

                            <figcaption class="flex flex-col items-center gap-[3px]">
                                <span class="text-[15.5px] font-semibold leading-snug text-texto">
                                    <?= e($testimonio->nombre) ?>
                                </span>
                                <?php if ($testimonio->cargo !== ''): ?>
                                    <span class="text-[12.5px] leading-snug text-texto-2">
                                        <?= e($testimonio->cargo) ?>
                                    </span>
                                <?php endif; ?>

                                <?php
                                /*
                                 * Estrellas y puntaje solo en la ficha central.
                                 * La cifra acompaña siempre a las estrellas: la
                                 * calificación no puede depender de contar
                                 * formas pequeñas de un solo color.
                                 */
                                ?>
                                <div class="rv-tst__solo-activo mt-1 flex items-center gap-2">
                                    <?php /* Fila apagada de fondo; encima, la misma fila recortada al puntaje. */ ?>
                                    <span class="rv-estrellas" aria-hidden="true"><?= e($testimonio->estrellas()) ?><span
                                          class="rv-estrellas__lleno"
                                          style="width: <?= e(number_format($testimonio->porcentaje(), 2, '.', '')) ?>%"><?= e($testimonio->estrellas()) ?></span></span>
                                    <span class="tabular text-[12.5px] text-texto-2">
                                        <?= e($vista->t('testimonios.calificacion_corta', number_format($testimonio->puntaje, 1, ',', ''))) ?>
                                    </span>
                                </div>
                            </figcaption>

                            <?php /* El tamaño y el color de la cita los pone la posición, en el CSS. */ ?>
                            <blockquote class="rv-titulo rv-tst__cita m-0">
                                <?= e($testimonio->descripcion) ?>
                            </blockquote>

                            <div class="rv-tst__solo-activo flex w-full flex-col items-center gap-3 border-t border-borde pt-3.5">
                                <?php if ($testimonio->referencia !== ''): ?>
                                    <span class="rv-id text-[11.5px]"><?= e($testimonio->referencia) ?></span>
                                <?php endif; ?>

                                <?php if ($total > 1): ?>
                                    <div class="flex gap-2" role="tablist" aria-label="<?= e($vista->t('testimonios.lista')) ?>">
                                        <?php foreach ($testimonios as $i => $otro): ?>
                                            <button type="button" class="rv-tst__punto"
                                                    data-carrusel-punto data-indice="<?= e((string) $i) ?>"
                                                    aria-selected="<?= $i === $indice ? 'true' : 'false' ?>"
                                                    aria-label="<?= e($vista->t('testimonios.ir_a', $otro->nombre)) ?>"></button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php
                            /*
                             * Pista de hacia dónde lleva pulsar la ficha
                             * lateral. Se imprimen las dos y el CSS enseña la
                             * que corresponde: al girar el carrusel, una misma
                             * ficha pasa de un lado al otro.
                             */
                            ?>
                            <span class="rv-tst__pista rv-tst__pista--anterior">
                                ← <?= e($vista->t('testimonios.anterior_corto')) ?>
                            </span>
                            <span class="rv-tst__pista rv-tst__pista--siguiente">
                                <?= e($vista->t('testimonios.siguiente_corto')) ?> →
                            </span>
                        </figure>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>
