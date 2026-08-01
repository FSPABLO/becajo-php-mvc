<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista      $vista
 * @var array<string, mixed> $empresa
 * @var list<array{etiqueta: string, destino: string}> $navegacion
 * @var list<array{etiqueta: string, descripcion: string, destino: string, icono: string}> $herramientas
 */
$herramientas = $herramientas ?? [];
?>
<footer class="border-t border-white/10 bg-marina-950">
    <div class="mx-auto max-w-7xl px-6 py-14 lg:px-8">

        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">

            <div class="lg:col-span-2">
                <div class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-lg bg-acento-500 font-extrabold text-marina-950">B</span>
                    <span class="text-lg font-bold text-white"><?= e($empresa['nombre']) ?></span>
                </div>
                <p class="mt-4 max-w-sm leading-relaxed text-marina-300">
                    <?= e($empresa['eslogan']) ?>. Reducimos el riesgo operativo de la
                    información crítica de su organización.
                </p>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Navegación</h2>
                <ul class="mt-4 space-y-2.5">
                    <?php foreach ($navegacion as $enlace): ?>
                        <li>
                            <a href="<?= e($vista->destino($enlace['destino'])) ?>"
                               class="text-marina-300 transition hover:text-acento-400">
                                <?= e($enlace['etiqueta']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php foreach ($herramientas as $herramienta): ?>
                        <li>
                            <a href="<?= e($vista->destino($herramienta['destino'])) ?>"
                               class="text-marina-300 transition hover:text-acento-400">
                                <?= e($herramienta['etiqueta']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-white">Contacto</h2>
                <ul class="mt-4 space-y-2.5 text-marina-300">
                    <li>
                        <a class="transition hover:text-acento-400"
                           href="mailto:<?= e($empresa['correo']) ?>">
                            <?= e($empresa['correo']) ?>
                        </a>
                    </li>
                    <li><?= e($empresa['telefono']) ?></li>
                    <li><?= e($empresa['ciudad']) ?></li>
                </ul>
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-2 border-t border-white/10 pt-7 text-sm text-marina-400 sm:flex-row sm:items-center sm:justify-between">
            <p>
                &copy; <?= e((string) $empresa['anio']) ?> <?= e($empresa['nombre']) ?>.
                Proyecto académico &mdash; Administración de Bases de Datos.
            </p>
            <p>Arquitectura MVC en PHP <?= e(PHP_VERSION) ?></p>
        </div>
    </div>
</footer>
