<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $empresa
 * @var list<array{etiqueta: string, destino: string}> $navegacion
 */
?>
<header class="fixed inset-x-0 top-0 z-50 border-b border-white/10 bg-marina-950/80 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6 lg:px-8">

        <a href="#inicio" class="flex items-center gap-2.5">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-acento-500 font-extrabold text-marina-950">B</span>
            <span class="text-lg font-bold tracking-tight text-white"><?= e($empresa['nombre']) ?></span>
        </a>

        <nav class="hidden items-center gap-8 md:flex" aria-label="Navegación principal">
            <?php foreach ($navegacion as $enlace): ?>
                <a href="<?= e($enlace['destino']) ?>"
                   class="text-sm font-medium text-marina-200 transition hover:text-white">
                    <?= e($enlace['etiqueta']) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="flex items-center gap-3">
            <a href="#contacto"
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
                <a href="<?= e($enlace['destino']) ?>"
                   class="block rounded-lg px-3 py-2 text-sm font-medium text-marina-200 transition hover:bg-white/5 hover:text-white">
                    <?= e($enlace['etiqueta']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
