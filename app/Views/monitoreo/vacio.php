<?php

declare(strict_types=1);

/**
 * Monitor de salud sin ninguna instancia registrada.
 *
 * Existe como vista aparte y no como un `if` dentro del panel porque el panel
 * presupone una instancia seleccionada. Rellenarla con una falsa para poder
 * pintar el vacío es justo lo que produce tableros que enseñan ceros
 * inventados — y el criterio de aceptación del §12 es que ninguna cifra de
 * salud aparezca sin venir de una muestra real.
 *
 * El estado vacío va HUNDIDO porque el hundido señala «aquí se recibe algo»:
 * este hueco se llenará cuando el agente empiece a dejar muestras.
 *
 * @var \App\Core\Vista $vista
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
?>
<section class="mx-auto w-full max-w-3xl px-6 py-8 lg:px-8">

    <?php
    /*
     * Sin rótulo visible, igual que el panel. Aquí la caja SÍ sigue centrada y
     * estrecha: un estado vacío es un mensaje, y un mensaje a dos mil píxeles de
     * ancho no se lee, se busca.
     */
    ?>
    <h1 class="sr-only"><?= e($vista->t('mon.titulo')) ?></h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
        <span class="inline-block text-na"><?= icono('corazon', 'h-10 w-10') ?></span>
        <p class="mt-4 font-semibold text-texto"><?= e($vista->t('mon.vacio_titulo')) ?></p>
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-texto-2">
            <?= e($vista->t('mon.vacio_texto')) ?>
        </p>
    </div>

</section>
