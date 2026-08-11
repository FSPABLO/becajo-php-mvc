<?php

declare(strict_types=1);

/**
 * Mitad izquierda de las pantallas de autenticación.
 *
 * La comparten /ingresar y /registrarse: son la misma pieza salvo la etiqueta
 * del primer paso, que nombra lo que el visitante está haciendo en esa página.
 * Vive en un parcial y no copiado en cada vista porque la imagen, el velo y el
 * apilado de capas son fáciles de desincronizar al tocar una sola de las dos.
 *
 * Fondo a tres capas: fotografía, velo y contenido. El contenido va en relative
 * y después de las otras dos en el HTML, así que pinta encima sin necesidad de
 * z-index.
 *
 * La OPACIDAD DEL VELO la decide quien llama, y no es una preferencia estética:
 * sale de medir el contraste de --rv-text-2 contra la zona más clara de cada
 * imagen. Con la de /ingresar, que tiene una banda de verde pálido, el 45 % que
 * bastaba antes deja el texto secundario en 1,93:1 — ilegible. Ver la tabla en
 * la llamada de cada vista.
 *
 * @var \App\Core\Vista $vista
 * @var string          $pasoUno  Etiqueta del primer paso (el que se cursa aquí).
 * @var string          $imagen   Ruta pública de la fotografía de fondo.
 * @var string          $velo     Clase de opacidad del velo (bg-fondo/NN).
 */

/*
 * Los tres pasos del flujo. El primero es el que se está dando ahora mismo:
 * va extruido y en verde, y los pendientes hundidos. El relieve es el que
 * comunica el avance — el número y la etiqueta van igual en los tres, para que
 * no dependa solo del color.
 */
$pasos = [
    $pasoUno,
    $vista->t('auth.paso_dos'),
    $vista->t('auth.paso_tres'),
];
?>
<div class="relative order-2 flex flex-col justify-center overflow-hidden bg-fondo px-6 py-14 sm:px-10 lg:order-1 lg:px-14 xl:px-20">

    <!--
        Fondo decorativo. alt vacío + aria-hidden: no comunica nada que el
        texto no diga ya, así que un lector de pantalla no debe anunciarlo.

        El bg-fondo del contenedor no sobra: es el color que se ve mientras la
        imagen carga y el que queda si el archivo falta, y garantiza que el
        texto nunca se lea sobre blanco.
    -->
    <img src="<?= e($vista->recurso($imagen)) ?>"
         alt="" aria-hidden="true"
         class="absolute inset-0 h-full w-full object-cover">

    <!--
        Velo PLANO, no un degradado: §4 del sistema visual los prohíbe. Es una
        capa del propio lienzo que baja el brillo de la imagen para que el
        texto conserve su contraste sobre CUALQUIER zona del fondo — no solo
        sobre la que hoy queda detrás de las letras, porque object-cover
        recorta distinto en cada pantalla y la zona clara puede acabar bajo el
        título.
    -->
    <div aria-hidden="true" class="absolute inset-0 <?= e($velo) ?>"></div>

    <!--
        La mitad puede medir 900 px en un monitor ancho, así que el contenido
        lleva su propia medida: una línea de prosa a todo lo ancho del panel
        sería ilegible.
    -->
    <div class="relative mx-auto flex w-full max-w-xl flex-col gap-12">

        <!-- El bloque de título va centrado; mx-auto en el párrafo porque su
             propia medida (max-w-md) lo dejaría pegado a la izquierda. -->
        <header class="text-center">
            <!--
                El eyebrow va en text-texto y no en text-primario: el verde de
                marca sobre estas fotografías se queda en 2,9:1, y aun sobre el
                lienzo plano rondaba el 4,2:1 — por debajo del 4,5:1 que exige
                un texto de 14 px. El acento no se pierde: sigue en la tarjeta
                del paso en curso y en el botón del formulario.
            -->
            <p class="text-sm font-semibold uppercase tracking-widest text-texto">
                <?= e($vista->t('auth.eyebrow')) ?>
            </p>
            <h2 class="rv-titulo mt-4 text-[2.1rem] font-medium leading-[1.1] tracking-[-0.01em] text-texto sm:text-[2.6rem]">
                <?= e($vista->t('auth.panel_titulo')) ?>
            </h2>
            <p class="rv-titulo mx-auto mt-4 max-w-md text-lg leading-relaxed text-texto-2">
                <?= e($vista->t('auth.panel_texto')) ?>
            </p>
        </header>

        <ol class="grid gap-3 sm:grid-cols-3">
            <?php foreach ($pasos as $indice => $paso): ?>
                <?php $actual = $indice === 0; ?>
                <li class="<?= $actual
                        ? 'rv-extruido bg-primario text-primario-texto'
                        : 'rv-hundido bg-elevado text-texto-2' ?> flex flex-col gap-6 rounded-rv-lg p-4">
                    <span class="<?= $actual
                            ? 'bg-primario-texto text-primario'
                            : 'bg-superficie text-texto-2' ?> grid h-7 w-7 place-items-center rounded-full text-[13px] font-semibold tabular">
                        <?= e((string) ($indice + 1)) ?>
                    </span>
                    <span class="text-sm font-semibold leading-snug"><?= e($paso) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</div>
