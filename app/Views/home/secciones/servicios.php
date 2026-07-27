<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista                        $vista
 * @var array{titulo: string, texto: string}   $encabezado
 * @var list<\App\Models\Entidades\Servicio>   $servicios
 */
?>
<section id="servicios" class="bg-slate-50 py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase tracking-widest text-acento-600">
                Servicios
            </p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-marina-950 sm:text-4xl">
                <?= e($encabezado['titulo']) ?>
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                <?= e($encabezado['texto']) ?>
            </p>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($servicios as $servicio): ?>
                <?= $vista->componente('tarjeta-servicio', ['servicio' => $servicio]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
