<?php

declare(strict_types=1);

/**
 * Formulario de ingreso al módulo de evaluación de riesgo.
 *
 * Pantalla partida en dos mitades A SANGRE: ocupan todo el ancho y toda la
 * altura visible bajo la barra (h-16), sin tarjeta ni márgenes que las
 * encuadren. Por eso el contenedor no lleva relieve: una banda que llega a los
 * dos bordes no puede «flotar» sobre nada. El relieve vive en las piezas de
 * adentro — pasos, campos y botón.
 *
 * - IZQUIERDA — el parcial auth/_panel, compartido con /registrarse.
 * - DERECHA — el formulario, sobre .rv-alterno. Es el mismo recurso que da la
 *   cadencia oscuro-claro de la portada: ESTILO DE SECCIÓN, no un tema. No
 *   introduce paleta nueva ni conmutador; solo re-declara los tokens en la
 *   mitad que los usa, así que aquí dentro se escribe únicamente con tokens
 *   (text-texto, border-borde, bg-superficie…) y nunca con un color fijo.
 *
 * El corte entre ambas es el propio salto de lienzo: no lleva borde, porque
 * pergamino contra verde noche ya separa de sobra.
 *
 * En pantallas angostas las mitades se apilan y el formulario va PRIMERO: quien
 * llega a /ingresar viene a entrar, no a leer la propuesta. El orden del HTML
 * conserva el de lectura de escritorio y lo invierten las utilidades order-*.
 *
 * @var \App\Core\Vista       $vista
 * @var array<string, mixed>  $empresa
 * @var string|null           $correo    Correo del intento anterior.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$clasesCampo = 'rv-hundido mt-1.5 w-full rounded-rv border border-borde bg-superficie px-3.5 py-2.5 '
    . 'text-texto outline-none transition focus:border-primario focus:ring-2 focus:ring-primario/30';
?>
<!--
    mt-16 = la altura exacta de la barra fija (h-16). No es un margen de
    respiro: es lo que impide que la mitad clara se meta por debajo del
    encabezado, ya que el layout no reserva ese espacio.
-->
<section class="mt-16 grid w-full lg:min-h-[calc(100vh-4rem)] lg:grid-cols-2">

    <?php
    /*
     * Velo al 80 %. Es alto porque la fotografía de esta pantalla trae una
     * banda de verde pálido (~#C3D4A8) que puede caer bajo el título según
     * cómo recorte object-cover. Contraste de --rv-text-2 sobre esa zona:
     *
     *     velo 45 % → 1,93:1     velo 65 % → 3,37:1
     *     velo 55 % → 2,53:1     velo 72 % → 4,14:1
     *     velo 80 % → 5,21:1  ← el primero que pasa el 4,5:1
     *
     * Aun al 80 % la forma de la imagen se sigue leyendo: lo que se aplana es
     * el brillo, no el dibujo.
     */
    ?>
    <?= $vista->renderizar('auth/_panel', [
        'pasoUno' => $vista->t('auth.paso_uno'),
        'imagen'  => 'assets/images/fondos-auth/ingresar.jpg',
        'velo'    => 'bg-fondo/80',
    ]) ?>

    <!--
        ── Mitad clara: el formulario ───────────────────────────────────────
        .rv-alterno re-declara la paleta completa (texto y relieve incluidos)
        solo dentro de este div.
    -->
    <div class="rv-alterno order-1 flex flex-col justify-center bg-fondo px-6 py-14 sm:px-10 lg:order-2 lg:px-14">

        <div class="mx-auto w-full max-w-sm">

            <!-- Solo el encabezado va centrado: las etiquetas del formulario
                 se quedan alineadas a la izquierda de su campo. -->
            <header class="mb-8 text-center">
                <h1 class="rv-titulo text-3xl font-semibold text-texto">
                    <?= e($vista->t('auth.ingresar_titulo')) ?>
                </h1>
                <p class="mt-2 text-sm text-texto-2">
                    <?= e($vista->t('auth.ingresar_texto', $empresa['nombre'])) ?>
                </p>
            </header>

            <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

            <form method="post" action="<?= e($vista->url('ingresar')) ?>" class="space-y-5">

                <?= $vista->campoToken() ?>

                <div>
                    <label for="correo" class="block text-sm font-semibold text-texto">
                        <?= e($vista->t('auth.correo')) ?>
                    </label>
                    <input type="email" id="correo" name="correo" required autofocus
                           autocomplete="username"
                           value="<?= e($correo ?? '') ?>"
                           class="<?= e($clasesCampo) ?>">
                </div>

                <div>
                    <label for="clave" class="block text-sm font-semibold text-texto">
                        <?= e($vista->t('auth.clave')) ?>
                    </label>
                    <input type="password" id="clave" name="clave" required
                           autocomplete="current-password"
                           class="<?= e($clasesCampo) ?>">
                </div>

                <button type="submit"
                        class="w-full rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-3 font-semibold text-primario-texto">
                    <?= e($vista->t('auth.entrar')) ?>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-texto-2">
                <?= e($vista->t('auth.sin_cuenta')) ?>
                <a href="<?= e($vista->url('registrarse')) ?>"
                   class="font-semibold text-primario underline-offset-2 hover:underline">
                    <?= e($vista->t('auth.registrarse')) ?>
                </a>
            </p>
        </div>
    </div>
</section>
