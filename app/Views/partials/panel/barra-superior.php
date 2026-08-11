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
 * @var \App\Models\Entidades\Usuario|null $usuarioActual
 * @var string|null     $rutaActual
 * @var bool|null       $lateralOculta
 */
$migaGrupo     = $migaGrupo ?? null;
$migaElemento  = $migaElemento ?? null;
$usuarioActual = $usuarioActual ?? null;
$rutaActual    = $rutaActual ?? '/';
$lateralOculta = $lateralOculta ?? false;

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
         * Miga de pan de dos niveles. No es navegación redundante: repite en
         * texto lo que la barra lateral dice con un resalte, para quien llega
         * desde una URL profunda (/evaluacion/81/controles/C-042) y no tiene
         * la barra a la vista en pantalla angosta.
         */
        ?>
        <?php if ($migaElemento !== null): ?>
            <nav class="min-w-0 flex-1" aria-label="<?= e($vista->t('panel.ubicacion')) ?>">
                <ol class="flex items-center gap-2 text-[13px]">
                    <?php if ($migaGrupo !== null): ?>
                        <li class="hidden text-texto-2 sm:block"><?= e($migaGrupo) ?></li>
                        <li class="hidden text-texto-2/60 sm:block" aria-hidden="true">/</li>
                    <?php endif; ?>
                    <li class="truncate font-semibold text-texto" aria-current="page">
                        <?= e($migaElemento) ?>
                    </li>
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

        <?php if ($usuarioActual !== null): ?>
            <?php
            /*
             * La organización, no el nombre: el nombre ya está en la ficha de
             * la barra lateral, y lo que se pierde de vista al llevar varias
             * auditorías abiertas es a nombre de quién se está trabajando.
             */
            ?>
            <p class="hidden max-w-[220px] truncate text-[13px] text-texto-2 md:block">
                <?= e($usuarioActual->organizacion) ?>
            </p>
        <?php endif; ?>
    </div>
</header>
