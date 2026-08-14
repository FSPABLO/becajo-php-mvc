<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista                        $vista
 * @var array{titulo: string, texto: string}   $encabezado
 * @var list<\App\Models\Entidades\Servicio>   $servicios
 */
?>
<section id="servicios" class="bg-fondo py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <?php /* Encabezado centrado, mismo patrón que la sección de retos. */ ?>
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-primario">
                Servicios
            </p>
            <h2 class="rv-titulo mt-3 text-3xl font-extrabold tracking-tight text-texto sm:text-4xl">
                <?= e($encabezado['titulo']) ?>
            </h2>
            <p class="rv-titulo mt-4 text-lg leading-relaxed text-texto-2">
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
