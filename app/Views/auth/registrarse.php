<?php

declare(strict_types=1);

/**
 * Alta de cuenta de auditor.
 *
 * Misma pantalla partida que /ingresar y por las mismas razones: mitades a
 * sangre bajo la barra, el parcial auth/_panel a la izquierda y el formulario
 * sobre .rv-alterno a la derecha. Lo único que cambia en el panel es la
 * etiqueta del primer paso, que aquí es crear la cuenta y no entrar.
 *
 * Los errores llegan indexados por campo y se pintan junto a su casilla: una
 * lista de errores arriba obliga a buscar a cuál corresponde cada uno. Los
 * valores del intento anterior se devuelven para no obligar a reescribirlo
 * todo por una contraseña corta — salvo las contraseñas, que nunca se
 * repueblan.
 *
 * No hay campo de rol a propósito: toda cuenta creada aquí nace como AUDITOR.
 *
 * @var \App\Core\Vista       $vista
 * @var array<string, mixed>  $empresa
 * @var array<string, string> $errores
 * @var array<string, string> $valores
 * @var array{aviso: string|null, error: string|null} $mensajes
 * @var int                   $minimoClave
 */
$errores = $errores ?? [];
$valores = $valores ?? [];

$clasesCampo = static fn (bool $conError): string =>
    'rv-hundido mt-1.5 w-full rounded-rv border bg-superficie px-3.5 py-2.5 text-texto outline-none transition '
    . ($conError
        ? 'border-bad focus:border-bad focus:ring-2 focus:ring-bad/10'
        : 'border-borde focus:border-primario focus:ring-2 focus:ring-primario/30');
?>
<!--
    mt-16 = la altura exacta de la barra fija (h-16). No es un margen de
    respiro: es lo que impide que la mitad clara se meta por debajo del
    encabezado, ya que el layout no reserva ese espacio.
-->
<section class="mt-16 grid w-full lg:min-h-[calc(100vh-4rem)] lg:grid-cols-2">

    <?php
    /*
     * Velo al 50 %, muy por debajo del 80 % que necesita /ingresar: esta
     * fotografía es verde oscuro en toda su superficie y su zona más clara
     * (~#3A5F4C) ya deja --rv-text-2 en 5,2:1 con solo el 45 %. Subirlo al
     * nivel de la otra pantalla apagaría la imagen sin ganar nada.
     */
    ?>
    <?= $vista->renderizar('auth/_panel', [
        'pasoUno' => $vista->t('auth.paso_uno_registro'),
        'imagen'  => 'assets/images/fondos-auth/registrarse.jpg',
        'velo'    => 'bg-fondo/50',
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
                    <?= e($vista->t('auth.registrarse_titulo')) ?>
                </h1>
                <p class="mt-2 text-sm text-texto-2">
                    <?= e($vista->t('auth.registrarse_texto')) ?>
                </p>
            </header>

            <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

            <form method="post" action="<?= e($vista->url('registrarse')) ?>" class="space-y-5">

                <?= $vista->campoToken() ?>

                <div>
                    <label for="nombre" class="block text-sm font-semibold text-texto">
                        <?= e($vista->t('auth.nombre_completo')) ?>
                    </label>
                    <input type="text" id="nombre" name="nombre" required autofocus maxlength="150"
                           autocomplete="name"
                           value="<?= e($valores['nombre'] ?? '') ?>"
                           <?= isset($errores['nombre']) ? 'aria-invalid="true" aria-describedby="error-nombre"' : '' ?>
                           class="<?= e($clasesCampo(isset($errores['nombre']))) ?>">
                    <?php if (isset($errores['nombre'])): ?>
                        <p id="error-nombre" class="mt-1.5 text-sm text-bad"><?= e($errores['nombre']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="correo" class="block text-sm font-semibold text-texto">
                        <?= e($vista->t('auth.correo')) ?>
                    </label>
                    <input type="email" id="correo" name="correo" required maxlength="150"
                           autocomplete="username"
                           value="<?= e($valores['correo'] ?? '') ?>"
                           <?= isset($errores['correo']) ? 'aria-invalid="true" aria-describedby="error-correo"' : '' ?>
                           class="<?= e($clasesCampo(isset($errores['correo']))) ?>">
                    <?php if (isset($errores['correo'])): ?>
                        <p id="error-correo" class="mt-1.5 text-sm text-bad"><?= e($errores['correo']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="organizacion" class="block text-sm font-semibold text-texto">
                        <?= e($vista->t('auth.organizacion')) ?>
                    </label>
                    <input type="text" id="organizacion" name="organizacion" required maxlength="200"
                           autocomplete="organization"
                           value="<?= e($valores['organizacion'] ?? '') ?>"
                           <?= isset($errores['organizacion']) ? 'aria-invalid="true" aria-describedby="error-organizacion"' : '' ?>
                           class="<?= e($clasesCampo(isset($errores['organizacion']))) ?>">
                    <?php if (isset($errores['organizacion'])): ?>
                        <p id="error-organizacion" class="mt-1.5 text-sm text-bad"><?= e($errores['organizacion']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="clave" class="block text-sm font-semibold text-texto">
                        <?= e($vista->t('auth.clave')) ?>
                    </label>
                    <input type="password" id="clave" name="clave" required
                           minlength="<?= e((string) $minimoClave) ?>"
                           autocomplete="new-password"
                           <?= isset($errores['clave']) ? 'aria-invalid="true" aria-describedby="error-clave"' : '' ?>
                           class="<?= e($clasesCampo(isset($errores['clave']))) ?>">
                    <?php if (isset($errores['clave'])): ?>
                        <p id="error-clave" class="mt-1.5 text-sm text-bad"><?= e($errores['clave']) ?></p>
                    <?php else: ?>
                        <p class="mt-1.5 text-sm text-texto-2">
                            <?= e($vista->t('auth.minimo_caracteres', (string) $minimoClave)) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="confirmacion" class="block text-sm font-semibold text-texto">
                        <?= e($vista->t('auth.confirmar_clave')) ?>
                    </label>
                    <input type="password" id="confirmacion" name="confirmacion" required
                           autocomplete="new-password"
                           <?= isset($errores['confirmacion']) ? 'aria-invalid="true" aria-describedby="error-confirmacion"' : '' ?>
                           class="<?= e($clasesCampo(isset($errores['confirmacion']))) ?>">
                    <?php if (isset($errores['confirmacion'])): ?>
                        <p id="error-confirmacion" class="mt-1.5 text-sm text-bad"><?= e($errores['confirmacion']) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit"
                        class="w-full rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-3 font-semibold text-primario-texto">
                    <?= e($vista->t('auth.crear_cuenta')) ?>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-texto-2">
                <?= e($vista->t('auth.ya_tiene_cuenta')) ?>
                <a href="<?= e($vista->url('ingresar')) ?>"
                   class="font-semibold text-primario underline-offset-2 hover:underline">
                    <?= e($vista->t('auth.iniciar_sesion')) ?>
                </a>
            </p>
        </div>
    </div>
</section>
