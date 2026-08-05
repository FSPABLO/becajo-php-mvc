<?php

declare(strict_types=1);

/** @var list<\App\Models\Entidades\Integrante> $equipo */
?>
<section id="equipo" class="bg-white py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-acento-600">
                Equipo
            </p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-marina-950 sm:text-4xl">
                Quiénes somos
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                Consultores especializados en la administración, seguridad y continuidad
                de bases de datos empresariales.
            </p>
        </div>

        <!--
            Cuatro columnas en pantalla ancha y dos en tableta: con cuatro
            integrantes, una rejilla de tres dejaba a uno solo en la segunda fila.
        -->
        <ul class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($equipo as $integrante): ?>
                <li class="rounded-xl border border-slate-200 p-7 transition hover:border-acento-400">
                    <span class="grid h-14 w-14 place-items-center rounded-full bg-marina-950 text-lg font-bold text-acento-400">
                        <?= e($integrante->iniciales) ?>
                    </span>
                    <h3 class="mt-5 font-bold text-marina-950">
                        <?= e($integrante->nombre) ?>
                    </h3>
                    <p class="mt-1 text-sm font-semibold text-acento-600">
                        <?= e($integrante->rol) ?>
                    </p>
                    <?php if ($integrante->descripcion !== ''): ?>
                        <p class="mt-3 text-sm leading-relaxed text-slate-600">
                            <?= e($integrante->descripcion) ?>
                        </p>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
