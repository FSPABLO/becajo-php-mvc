<?php

declare(strict_types=1);

/**
 * Barra superior del módulo interno.
 *
 * Delgada a propósito: los destinos ya están en la barra lateral, así que aquí
 * solo queda el contexto —dónde estoy— y lo que no pertenece a ninguna sección
 * en concreto: el idioma y la sesión abierta.
 *
 * Va PEGAJOSA (sticky) y no fija: al ser hermana del contenido dentro de la
 * columna, se desplaza con ella y ninguna vista tiene que reservar su alto por
 * arriba. Eso es lo que permitió quitar el pt-24 de todas las vistas del módulo.
 *
 * El selector de idioma se conserva como ENLACES, igual que en el sitio
 * público: cambiar de idioma es un GET a /idioma y funciona sin JavaScript.
 *
 * @var \App\Core\Vista $vista
 * @var string|null     $migaGrupo     Grupo del menú donde cae la pantalla actual.
 * @var string|null     $migaElemento  Elemento activo del menú.
 * @var string|null     $migaRuta      Ruta de ese elemento, para volver a él.
 * @var list<array{etiqueta: string, ruta?: string|null}>|null $migaPagina
 *      Niveles por debajo de la entrada del menú. Los pone el controlador.
 * @var string|null     $rutaActual
 * @var bool|null       $lateralOculta
 */
$migaGrupo     = $migaGrupo ?? null;
$migaElemento  = $migaElemento ?? null;
$migaRuta      = $migaRuta ?? null;
$migaPagina    = $migaPagina ?? [];
$rutaActual    = $rutaActual ?? '/';
$lateralOculta = $lateralOculta ?? false;

/*
 * ¿La entrada del menú es un destino o es dónde estoy? En /evaluacion/152 el
 * elemento activo es «Mis auditorías» (/evaluacion), que NO es esta página: ahí
 * la miga es el camino de vuelta y va como enlace. En /evaluacion misma las dos
 * rutas coinciden y queda como texto, porque un enlace a la página que ya se
 * está viendo no lleva a ninguna parte.
 *
 * Que haya niveles por debajo lo decide igual: si el controlador añadió
 * «Auditoría 152», la entrada del menú es un ancestro aunque las rutas
 * coincidieran.
 */
$migaEsEnlace = $migaRuta !== null && ($migaPagina !== [] || $migaRuta !== $rutaActual);

/*
 * aria-current="page" va en UN solo sitio: el último nivel, sea el del menú o
 * el que puso el controlador. Repetirlo en varios le diría al lector de
 * pantalla que está en dos páginas a la vez.
 */
$ultimoNivel = $migaPagina === [] ? -1 : array_key_last($migaPagina);

$enlaceIdioma = static fn (string $codigo): string =>
    '?codigo=' . rawurlencode($codigo) . '&destino=' . rawurlencode($rutaActual);
?>
<header class="sticky top-0 z-20 border-b border-borde bg-fondo/95 backdrop-blur">
    <div class="flex h-16 items-center gap-4 px-5 lg:px-8">

        <?php
        /*
         * Devuelve la navegación. Aparece exactamente cuando la barra lateral
         * no está a la vista: siempre por debajo de lg, y en pantalla ancha
         * solo mientras esté plegada (lo decide .rv-panel-mostrar en
         * rivendel.css). Así nunca hay dos botones de navegación en pantalla.
         */
        ?>
        <button type="button"
                id="panel-boton-menu"
                data-panel-alternar
                class="rv-panel-mostrar -ml-1 rounded-rv p-2 text-texto-2 transition hover:text-texto lg:hidden"
                aria-expanded="<?= $lateralOculta ? 'false' : 'true' ?>"
                aria-controls="panel-lateral"
                aria-label="<?= e($vista->t('panel.mostrar_navegacion')) ?>"
                title="<?= e($vista->t('panel.mostrar_navegacion')) ?>">
            <?= icono('lateral', 'h-5 w-5') ?>
        </button>

        <?php
        /*
         * Miga de pan. Los dos primeros niveles salen del menú —grupo y
         * entrada activa— y los siguientes los pone el controlador en
         * $migaPagina. No es navegación redundante: repite en texto lo que la
         * barra lateral dice con un resalte, para quien llega desde una URL
         * profunda (/evaluacion/81/controles/C-042) y no tiene la barra a la
         * vista en pantalla angosta.
         *
         * Y desde una URL profunda es TAMBIÉN el camino de vuelta: el último
         * nivel enlaza a su sección cuando la pantalla actual cuelga de ella.
         * Por eso /evaluacion/{id} ya no lleva su propio «← Mis auditorías»
         * encima del título — dos vueltas atrás en la misma pantalla, una de
         * ellas fuera de la barra, es una de sobra. Los «← Auditoría 152» de
         * resultados, remediaciones y un control se quedan: apuntan a la
         * auditoría padre, que no es a donde va la miga.
         *
         * El primer nivel es el GRUPO del menú, no una página: no hay ruta que
         * darle, así que no se disfraza de enlace.
         *
         * El fondo tintado la recorta del resto de la barra: sin él, un enlace
         * suelto a la izquierda se lee como una acción de la pantalla y no como
         * el sitio donde uno está. Sale del primario con opacidad, que es el
         * mecanismo con el que los tokens dan acentos; no es un verde nuevo.
         */
        ?>
        <?php if ($migaElemento !== null): ?>
            <nav class="min-w-0 flex-1" aria-label="<?= e($vista->t('panel.ubicacion')) ?>">
                <ol class="inline-flex max-w-full items-center gap-2 rounded-rv bg-primario/10 px-3 py-1.5 text-[13px]">
                    <?php if ($migaGrupo !== null): ?>
                        <li class="hidden text-texto-2 sm:block"><?= e($migaGrupo) ?></li>
                        <li class="hidden text-texto-2/60 sm:block" aria-hidden="true">/</li>
                    <?php endif; ?>
                    <li class="min-w-0 truncate font-semibold">
                        <?php if ($migaEsEnlace): ?>
                            <a href="<?= e($vista->url($migaRuta)) ?>"
                               class="text-primario transition hover:underline">
                                <?= e($migaElemento) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-texto" <?= $migaPagina === [] ? 'aria-current="page"' : '' ?>>
                                <?= e($migaElemento) ?>
                            </span>
                        <?php endif; ?>
                    </li>

                    <?php
                    /*
                     * Y los niveles del controlador. Aquí «Auditoría 152» no es
                     * decoración: sin él la miga dice «Mis auditorías» en las
                     * cuatro pantallas de una auditoría —la ficha, sus
                     * resultados, sus remediaciones y cada uno de los 75
                     * controles—, que es tanto como no decir dónde se está.
                     *
                     * Llevan ruta los que son ANCESTROS de esta pantalla; el
                     * último no, porque es esta pantalla.
                     */
                    ?>
                    <?php foreach ($migaPagina as $indice => $nivel): ?>
                        <li class="text-texto-2/60" aria-hidden="true">/</li>
                        <li class="min-w-0 truncate font-semibold">
                            <?php if (($nivel['ruta'] ?? null) !== null): ?>
                                <a href="<?= e($vista->url($nivel['ruta'])) ?>"
                                   class="text-primario transition hover:underline">
                                    <?= e($nivel['etiqueta']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-texto" <?= $indice === $ultimoNivel ? 'aria-current="page"' : '' ?>>
                                    <?= e($nivel['etiqueta']) ?>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php else: ?>
            <div class="flex-1"></div>
        <?php endif; ?>

        <?php /* Grupo de idioma: pieza hundida con el activo sobresaliendo dentro. */ ?>
        <div class="rv-hundido hidden rounded-[9px] bg-fondo p-[3px] sm:flex"
             role="group" aria-label="<?= e($vista->t('nav.idioma')) ?>">
            <?php foreach (\App\Core\Idioma::DISPONIBLES as $codigo => $nombre): ?>
                <?php $activo = $vista->idiomaActual() === $codigo; ?>
                <a href="<?= e($vista->url('idioma') . $enlaceIdioma($codigo)) ?>"
                   hreflang="<?= e($codigo) ?>"
                   lang="<?= e($codigo) ?>"
                   <?= $activo ? 'aria-current="true"' : '' ?>
                   title="<?= e($nombre) ?>"
                   class="rounded-[7px] px-2.5 py-[5px] text-[11.5px] font-semibold uppercase tracking-[0.04em] transition <?= $activo
                       ? 'rv-extruido bg-primario text-primario-texto'
                       : 'text-texto-2 hover:text-texto' ?>">
                    <?= e($codigo) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        /*
         * A la derecha del idioma no queda nada. Aquí se pintaba la
         * organización del auditor, y ya está en la ficha de la barra lateral:
         * repetida arriba solo competía con la miga por la atención, en una
         * barra que existe para decir DÓNDE estoy y no a nombre de quién.
         */
        ?>
    </div>
</header>
