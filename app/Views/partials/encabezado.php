<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista     $vista
 * @var array<string, mixed> $empresa
 * @var list<array{etiqueta: string, destino: string}> $navegacion
 * @var list<array{etiqueta: string, descripcion: string, destino: string, icono: string}> $herramientas
 * @var \App\Models\Entidades\Usuario|null $usuarioActual
 */
$herramientas = $herramientas ?? [];
$usuarioActual = $usuarioActual ?? null;
?>
<header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-marina-950/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 lg:px-8">

        <a href="<?= e($vista->destino('#inicio')) ?>" class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-acento-500 font-extrabold text-marina-950">B</span>
            <span class="text-lg font-bold tracking-tight text-white"><?= e($empresa['nombre']) ?></span>
        </a>

        <nav class="hidden items-center gap-8 md:flex" aria-label="Navegación principal">
            <?php foreach ($navegacion as $enlace): ?>
                <a href="<?= e($vista->destino($enlace['destino'])) ?>"
                   class="text-sm font-medium text-marina-200 transition hover:text-white">
                    <?= e($enlace['etiqueta']) ?>
                </a>
            <?php endforeach; ?>

            <?php if ($herramientas !== []): ?>
                <div class="relative" data-desplegable>
                    <button type="button"
                            id="boton-herramientas"
                            data-desplegable-boton
                            class="flex items-center gap-1.5 text-sm font-medium text-marina-200 transition hover:text-white"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="menu-herramientas">
                        Herramientas
                        <span data-desplegable-flecha class="transition-transform duration-200">
                            <?= icono('chevron', 'h-4 w-4') ?>
                        </span>
                    </button>

                    <div id="menu-herramientas"
                         data-desplegable-panel
                         class="absolute right-0 top-full hidden w-80 pt-3"
                         role="menu"
                         aria-labelledby="boton-herramientas">
                        <div class="overflow-hidden rounded-xl border border-white/10 bg-marina-950 p-2 shadow-2xl shadow-marina-950/40">
                            <?php foreach ($herramientas as $herramienta): ?>
                                <a href="<?= e($vista->destino($herramienta['destino'])) ?>"
                                   role="menuitem"
                                   class="group flex gap-3 rounded-lg p-3 transition hover:bg-white/5">
                                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/5 text-acento-400 transition group-hover:bg-acento-500 group-hover:text-marina-950">
                                        <?= icono($herramienta['icono'], 'h-5 w-5') ?>
                                    </span>
                                    <span>
                                        <span class="block text-sm font-semibold text-white">
                                            <?= e($herramienta['etiqueta']) ?>
                                        </span>
                                        <span class="mt-1 block text-xs leading-relaxed text-marina-300">
                                            <?= e($herramienta['descripcion']) ?>
                                        </span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <div class="flex items-center gap-3">
            <?php if ($usuarioActual !== null): ?>
                <a href="<?= e($vista->url('evaluacion')) ?>"
                   class="hidden text-sm font-medium text-marina-200 transition hover:text-white sm:inline-block">
                    Mis auditorías
                </a>
                <?php if ($usuarioActual->esAdministrador()): ?>
                    <a href="<?= e($vista->url('catalogo')) ?>"
                       class="hidden text-sm font-medium text-marina-200 transition hover:text-white sm:inline-block">
                        Catálogo
                    </a>
                <?php endif; ?>
                <form method="post" action="<?= e($vista->url('salir')) ?>" class="hidden sm:block">
                    <?= $vista->campoToken() ?>
                    <button type="submit" class="text-sm font-medium text-marina-200 transition hover:text-white">
                        Salir
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= e($vista->url('ingresar')) ?>"
                   class="hidden text-sm font-medium text-marina-200 transition hover:text-white sm:inline-block">
                    Ingresar
                </a>
            <?php endif; ?>
            <a href="<?= e($vista->destino('#contacto')) ?>"
               class="hidden rounded-lg bg-acento-500 px-4 py-2 text-sm font-semibold text-marina-950 transition hover:bg-acento-400 sm:inline-block">
                Contactar
            </a>
            <button type="button"
                    id="boton-menu"
                    class="text-marina-200 transition hover:text-white md:hidden"
                    aria-expanded="false"
                    aria-controls="menu-movil"
                    aria-label="Abrir menú de navegación">
                <?= icono('menu', 'h-6 w-6') ?>
            </button>
        </div>
    </div>

    <div id="menu-movil" class="hidden border-t border-white/10 bg-marina-950 md:hidden">
        <nav class="space-y-1 px-6 py-4" aria-label="Navegación móvil">
            <?php foreach ($navegacion as $enlace): ?>
                <a href="<?= e($vista->destino($enlace['destino'])) ?>"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-marina-200 transition hover:bg-white/5 hover:text-white">
                    <?= e($enlace['etiqueta']) ?>
                </a>
            <?php endforeach; ?>

            <?php if ($herramientas !== []): ?>
                <p class="px-3 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-marina-400">
                    Herramientas
                </p>
                <?php foreach ($herramientas as $herramienta): ?>
                    <a href="<?= e($vista->destino($herramienta['destino'])) ?>"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-marina-200 transition hover:bg-white/5 hover:text-white">
                        <?= icono($herramienta['icono'], 'h-4 w-4 shrink-0 text-acento-400') ?>
                        <?= e($herramienta['etiqueta']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($usuarioActual !== null): ?>
                <a href="<?= e($vista->url('evaluacion')) ?>"
                   class="mt-4 block rounded-lg px-3 py-2 text-sm font-medium text-marina-200 transition hover:bg-white/5 hover:text-white">
                    Mis auditorías
                </a>
                <?php if ($usuarioActual->esAdministrador()): ?>
                    <a href="<?= e($vista->url('catalogo')) ?>"
                       class="block rounded-lg px-3 py-2 text-sm font-medium text-marina-200 transition hover:bg-white/5 hover:text-white">
                        Catálogo
                    </a>
                <?php endif; ?>
                <form method="post" action="<?= e($vista->url('salir')) ?>">
                    <?= $vista->campoToken() ?>
                    <button type="submit"
                            class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-marina-200 transition hover:bg-white/5 hover:text-white">
                        Salir
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= e($vista->url('ingresar')) ?>"
                   class="mt-4 block rounded-lg px-3 py-2 text-sm font-medium text-marina-200 transition hover:bg-white/5 hover:text-white">
                    Ingresar
                </a>
            <?php endif; ?>
        </nav>
    </div>
</header>
