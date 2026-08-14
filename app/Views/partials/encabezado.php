<?php

declare(strict_types=1);

/**
 * Barra de navegación global.
 *
 * Tres zonas: la marca a la izquierda, los enlaces centrados ocupando el
 * espacio sobrante, y las acciones a la derecha. Sigue fija (y no pegajosa)
 * porque todas las vistas del producto reservan arriba el alto de esta barra.
 *
 * El selector de idioma son ENLACES, no un formulario ni un <select> con
 * guion: cambiar de idioma es una petición GET a /idioma, así que funciona sin
 * JavaScript. Por eso el encabezado recibe $rutaActual — para volver a donde
 * estaba el visitante en lugar de mandarlo a la portada.
 *
 * Los menús desplegables se declaran en $menus y se pintan con un solo bloque,
 * no uno por menú: son la misma pieza con distinto contenido, y duplicar el
 * marcado es garantizar que «Nosotros» y «Herramientas» se comporten distinto
 * en cuanto alguien corrija uno de los dos. El guion no necesita saber cuántos
 * hay — recorre todo [data-desplegable] de la página.
 *
 * @var \App\Core\Vista     $vista
 * @var array<string, mixed> $empresa
 * @var list<array{etiqueta: string, destino: string}> $navegacion
 * @var list<array{etiqueta: string, descripcion: string, destino: string, icono: string}> $nosotros
 * @var list<array{etiqueta: string, descripcion: string, destino: string, icono: string}> $herramientas
 * @var \App\Models\Entidades\Usuario|null $usuarioActual
 * @var string|null $rutaActual
 */
$nosotros = $nosotros ?? [];
$herramientas = $herramientas ?? [];
$usuarioActual = $usuarioActual ?? null;
$rutaActual = $rutaActual ?? '/';

/*
 * Un menú vacío no se declara: sin entradas, el botón abriría una caja en
 * blanco. La 'clave' compone los id que enlazan botón y panel para las ayudas
 * técnicas, así que tiene que ser única dentro de la página.
 */
$menus = [];

if ($nosotros !== []) {
    $menus[] = ['clave' => 'nosotros', 'etiqueta' => $vista->t('nav.nosotros'), 'elementos' => $nosotros];
}

if ($herramientas !== []) {
    $menus[] = ['clave' => 'herramientas', 'etiqueta' => $vista->t('nav.herramientas'), 'elementos' => $herramientas];
}

$enlaceIdioma = static fn (string $codigo): string =>
    '?codigo=' . rawurlencode($codigo) . '&destino=' . rawurlencode($rutaActual);

$clasesEnlace = 'rv-enlace-nav rounded-rv px-[13px] py-2 text-[13.5px] font-medium text-nav-texto';
?>
<header class="fixed inset-x-0 top-0 z-50 border-b border-borde bg-fondo/95 px-5 backdrop-blur">
    <nav class="mx-auto flex h-16 max-w-[1180px] items-center gap-[18px]">

        <?php /* Cinzel: solo el logotipo. Una palabra, caja alta, 0.09em. */ ?>
        <a href="<?= e($vista->destino('#inicio')) ?>" class="flex flex-none items-center gap-[11px]">
            <span class="rv-extruido grid h-[38px] w-[38px] place-items-center rounded-[10px] bg-superficie font-marca text-[15px] tracking-[0.02em] text-oro">R</span>
            <span class="rv-marca text-[15px] text-nav-texto"><?= e($empresa['nombre']) ?></span>
        </a>

        <div class="hidden flex-1 flex-wrap items-center justify-center gap-0.5 md:flex">
            <?php foreach ($navegacion as $enlace): ?>
                <a href="<?= e($vista->destino($enlace['destino'])) ?>" class="<?= e($clasesEnlace) ?>">
                    <?= e($enlace['etiqueta']) ?>
                </a>
            <?php endforeach; ?>

            <?php foreach ($menus as $menu): ?>
                <div class="relative" data-desplegable>
                    <button type="button"
                            id="boton-<?= e($menu['clave']) ?>"
                            data-desplegable-boton
                            class="<?= e($clasesEnlace) ?> flex items-center gap-1.5"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="menu-<?= e($menu['clave']) ?>">
                        <?= e($menu['etiqueta']) ?>
                        <span data-desplegable-flecha class="text-nav-texto2 transition-transform duration-200">
                            <?= icono('chevron', 'h-3.5 w-3.5') ?>
                        </span>
                    </button>

                    <div id="menu-<?= e($menu['clave']) ?>"
                         data-desplegable-panel
                         class="absolute left-0 top-full hidden w-[300px] pt-3"
                         role="menu"
                         aria-labelledby="boton-<?= e($menu['clave']) ?>">
                        <div class="rv-extruido flex flex-col gap-1 rounded-rv-lg border border-borde bg-superficie p-2.5">
                            <?php foreach ($menu['elementos'] as $elemento): ?>
                                <a href="<?= e($vista->destino($elemento['destino'])) ?>"
                                   role="menuitem"
                                   class="flex flex-col gap-[3px] rounded-[9px] px-3 py-[11px] transition hover:bg-elevado">
                                    <span class="text-[13.5px] font-semibold text-texto">
                                        <?= e($elemento['etiqueta']) ?>
                                    </span>
                                    <span class="text-xs leading-[1.4] text-texto-2">
                                        <?= e($elemento['descripcion']) ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="ml-auto flex flex-none items-center gap-[9px] md:ml-0">

            <?php if ($usuarioActual !== null && $usuarioActual->esAdministrador()): ?>
                <a href="<?= e($vista->url('catalogo')) ?>" class="<?= e($clasesEnlace) ?> hidden sm:inline-block">
                    <?= e($vista->t('nav.catalogo')) ?>
                </a>
            <?php endif; ?>

            <?php
            /*
             * Grupo de idioma: la pieza va hundida y el idioma activo sobresale
             * dentro de ella. El activo se marca con aria-current y no solo con
             * el color, que aquí tampoco puede ser el único canal.
             */
            ?>
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
                           : 'text-nav-texto2 hover:text-nav-texto' ?>">
                        <?= e($codigo) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php
            /*
             * Botón invertido: lienzo claro sobre la barra oscura. Es el único
             * de la portada con este tratamiento, y por eso destaca sin gastar
             * el verde, que aquí significaría cumplimiento.
             *
             * Con sesión abierta ese sitio lo ocupa el PERFIL, no la salida.
             * Cerrar sesión se hace desde la barra lateral del módulo, que es
             * donde vive la ficha de sesión entera —nombre, rol y salir—; el
             * botón de aquí es el camino hasta ella.
             *
             * El retrato son las iniciales: el esquema no guarda fotografía de
             * usuario, y un disco con las iniciales es lo que ya usa la barra
             * lateral. El disco va oscuro DENTRO del botón claro, que es la
             * misma inversión en pequeño; sin ella el avatar se disolvería en
             * el propio botón. La palabra acompaña siempre a la imagen: un
             * disco con dos letras no dice a dónde lleva.
             */
            ?>
            <?php if ($usuarioActual !== null): ?>
                <a href="<?= e($vista->url('evaluacion')) ?>"
                   title="<?= e($usuarioActual->nombre) ?>"
                   class="rv-extruido rv-interactivo flex items-center gap-2.5 rounded-[9px] bg-texto py-[5px] pl-[5px] pr-[14px] text-[13px] font-medium text-fondo">
                    <span class="grid h-7 w-7 flex-none place-items-center rounded-full bg-fondo text-[11px] font-semibold text-texto"
                          aria-hidden="true"><?= e(iniciales($usuarioActual->nombre)) ?></span>
                    <?= e($vista->t('nav.perfil')) ?>
                </a>
            <?php else: ?>
                <a href="<?= e($vista->url('ingresar')) ?>"
                   class="rv-extruido rv-interactivo rounded-[9px] bg-texto px-[15px] py-[9px] text-[13px] font-medium text-fondo">
                    <?= e($vista->t('nav.ingresar')) ?>
                </a>
            <?php endif; ?>

            <button type="button"
                    id="boton-menu"
                    class="text-nav-texto2 transition hover:text-nav-texto md:hidden"
                    aria-expanded="false"
                    aria-controls="menu-movil"
                    aria-label="<?= e($vista->t('nav.abrir_menu')) ?>">
                <?= icono('menu', 'h-6 w-6') ?>
            </button>
        </div>
    </nav>

    <div id="menu-movil" class="hidden border-t border-borde bg-fondo md:hidden">
        <nav class="space-y-1 px-6 py-4" aria-label="<?= e($vista->t('nav.movil')) ?>">
            <?php foreach ($navegacion as $enlace): ?>
                <a href="<?= e($vista->destino($enlace['destino'])) ?>"
                   class="block rounded-rv px-3 py-2 text-sm font-medium text-nav-texto transition hover:bg-elevado hover:text-oro">
                    <?= e($enlace['etiqueta']) ?>
                </a>
            <?php endforeach; ?>

            <?php
            /*
             * En pantalla angosta no hay desplegable: cada menú se abre entero
             * bajo su rótulo. Un submenú dentro de un menú ya desplegado
             * escondería lo mismo dos veces.
             */
            ?>
            <?php foreach ($menus as $menu): ?>
                <p class="px-3 pb-1 pt-4 font-mono text-xs uppercase tracking-wider text-nav-texto2">
                    <?= e($menu['etiqueta']) ?>
                </p>
                <?php foreach ($menu['elementos'] as $elemento): ?>
                    <a href="<?= e($vista->destino($elemento['destino'])) ?>"
                       class="flex items-center gap-3 rounded-rv px-3 py-2 text-sm font-medium text-nav-texto transition hover:bg-elevado hover:text-oro">
                        <?= icono($elemento['icono'], 'h-4 w-4 shrink-0 text-oro') ?>
                        <?= e($elemento['etiqueta']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if ($usuarioActual !== null): ?>
                <?php
                /*
                 * Mismo destino y mismo retrato que el botón de la barra: dos
                 * entradas para la misma sesión en la misma cabecera no pueden
                 * llamarse distinto según el ancho de la pantalla.
                 */
                ?>
                <a href="<?= e($vista->url('evaluacion')) ?>"
                   class="mt-4 flex items-center gap-3 rounded-rv px-3 py-2 text-sm font-medium text-nav-texto transition hover:bg-elevado hover:text-oro">
                    <span class="rv-extruido grid h-7 w-7 flex-none place-items-center rounded-full bg-elevado text-[11px] font-semibold text-texto"
                          aria-hidden="true"><?= e(iniciales($usuarioActual->nombre)) ?></span>
                    <?= e($vista->t('nav.perfil')) ?>
                </a>
                <?php if ($usuarioActual->esAdministrador()): ?>
                    <a href="<?= e($vista->url('catalogo')) ?>"
                       class="block rounded-rv px-3 py-2 text-sm font-medium text-nav-texto transition hover:bg-elevado hover:text-oro">
                        <?= e($vista->t('nav.catalogo')) ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= e($vista->url('ingresar')) ?>"
                   class="mt-4 block rounded-rv px-3 py-2 text-sm font-medium text-nav-texto transition hover:bg-elevado hover:text-oro">
                    <?= e($vista->t('nav.ingresar')) ?>
                </a>
            <?php endif; ?>

            <p class="px-3 pb-1 pt-4 font-mono text-xs uppercase tracking-wider text-nav-texto2">
                <?= e($vista->t('nav.idioma')) ?>
            </p>
            <div class="rv-hundido mx-3 mt-1.5 inline-flex rounded-[9px] bg-fondo p-[3px]"
                 role="group" aria-label="<?= e($vista->t('nav.idioma')) ?>">
                <?php foreach (\App\Core\Idioma::DISPONIBLES as $codigo => $nombre): ?>
                    <?php $activo = $vista->idiomaActual() === $codigo; ?>
                    <a href="<?= e($vista->url('idioma') . $enlaceIdioma($codigo)) ?>"
                       hreflang="<?= e($codigo) ?>"
                       lang="<?= e($codigo) ?>"
                       <?= $activo ? 'aria-current="true"' : '' ?>
                       title="<?= e($nombre) ?>"
                       class="rounded-[7px] px-2.5 py-[5px] text-[11.5px] font-semibold uppercase tracking-[0.04em] <?= $activo
                           ? 'rv-extruido bg-primario text-primario-texto'
                           : 'text-nav-texto2' ?>">
                        <?= e($codigo) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>
</header>
