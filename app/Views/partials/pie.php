<?php

declare(strict_types=1);

/**
 * Pie de página.
 *
 * Usa los tokens de la barra (bg-nav / nav-texto) y no los del cuerpo, para
 * que el pie cierre la página con el mismo tono con el que la abre el
 * encabezado.
 *
 * El pie ES ahora la sección de contacto, no un bloque debajo de ella: lleva el
 * ancla #contacto a la que apuntan el plan de pago y la redirección de
 * ContactoController tras enviar el formulario.
 *
 * Ya NO lleva panel de navegación. El encabezado es fijo y acompaña al
 * visitante hasta abajo, así que repetir ahí los mismos enlaces no ofrecía una
 * segunda vía: duplicaba el menú. Ese espacio es el que ocupa el formulario.
 *
 * El formulario solo se pinta donde hay datos de contacto que pintar —los
 * provee HomeController y llegan hasta aquí a través del layout—. En el resto
 * del producto el pie se queda en marca y datos. Eso es deliberado: alimentar
 * el formulario en todas las páginas obligaría a leer errores de sesión en
 * cada petición, y abriría cookie de sesión donde hoy no hace falta ninguna.
 *
 * @var \App\Core\Vista      $vista
 * @var array<string, mixed> $empresa
 * @var array{titulo: string, texto: string}|null $contacto
 * @var list<string>         $motores
 * @var array{aviso: string|null, error: string|null} $mensajes
 * @var array<string, string> $erroresContacto
 * @var array<string, string> $valoresContacto
 */
$contacto        = $contacto ?? null;
$motores         = $motores ?? [];
$mensajes        = $mensajes ?? [];
$erroresContacto = $erroresContacto ?? [];
$valoresContacto = $valoresContacto ?? [];

$hayFormulario = $contacto !== null;

// Clases del campo: idénticas en los cuatro, así que se escriben una vez.
$clasesCampo = 'rv-hundido mt-1.5 w-full rounded-rv border border-borde bg-fondo px-4 py-3 '
             . 'text-texto placeholder-texto-2/60 outline-none transition focus:border-primario-hover';
?>
<footer id="contacto" class="border-t border-borde bg-nav">
    <div class="mx-auto max-w-7xl px-6 py-16 lg:px-8">

        <div class="grid gap-12 <?= $hayFormulario ? 'lg:grid-cols-2 lg:gap-16' : 'sm:grid-cols-2' ?>">

            <div>
                <div class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-rv bg-oro/15 font-marca text-lg text-oro">R</span>
                    <span class="rv-marca text-lg text-nav-texto"><?= e($empresa['nombre']) ?></span>
                </div>
                <p class="rv-titulo mt-4 max-w-sm text-[1.05rem] leading-relaxed text-nav-texto2">
                    <?= e($empresa['eslogan']) ?>. <?= e($vista->t('pie.eslogan_extra')) ?>
                </p>

                <?php /* Oro = referencia normativa. Es el único uso permitido. */ ?>
                <p class="rv-badge-norma mt-6">ISO/IEC 27002 · 27007 · COBIT 4.1</p>

                <?php if ($hayFormulario): ?>
                    <?php
                    /*
                     * El título de la sección de contacto vive junto a los datos
                     * y no encima del formulario: lo que invita a escribir es la
                     * pregunta, y el formulario ya se explica solo.
                     */
                    ?>
                    <h2 class="rv-titulo mt-10 text-2xl font-extrabold tracking-tight text-nav-texto sm:text-3xl">
                        <?= e($contacto['titulo']) ?>
                    </h2>
                    <p class="rv-titulo mt-3 max-w-md text-[1.05rem] leading-relaxed text-nav-texto2">
                        <?= e($contacto['texto']) ?>
                    </p>
                <?php endif; ?>

                <h2 class="mt-10 text-sm font-semibold uppercase tracking-wider text-nav-texto">
                    <?= e($vista->t('pie.contacto')) ?>
                </h2>
                <?php /* Los datos de contacto van en mono: son identificadores, no prosa. */ ?>
                <ul class="mt-4 space-y-2.5 font-mono text-sm text-nav-texto2">
                    <li>
                        <a class="transition hover:text-nav-texto"
                           href="mailto:<?= e($empresa['correo']) ?>">
                            <?= e($empresa['correo']) ?>
                        </a>
                    </li>
                    <li><?= e($empresa['telefono']) ?></li>
                    <li class="font-sans"><?= e($empresa['ciudad']) ?></li>
                </ul>
            </div>

            <?php if ($hayFormulario): ?>
                <?php
                /*
                 * El formulario va en su propia pieza extruida sobre superficie:
                 * sin ella los campos hundidos quedarían sobre el mismo tono del
                 * pie y solo los delataría el borde.
                 */
                ?>
                <div class="rv-extruido-lg rounded-rv-lg border border-borde bg-superficie p-8 lg:p-10">
                    <form class="space-y-5" action="<?= e($vista->url('contacto')) ?>" method="post" novalidate>
                        <?php if (($mensajes['aviso'] ?? null) !== null): ?>
                            <p class="rounded-rv bg-ok/10 px-4 py-3 text-sm text-ok">
                                <?= e($mensajes['aviso']) ?>
                            </p>
                        <?php endif; ?>

                        <div>
                            <label for="nombre" class="block text-sm font-medium text-texto-2">
                                Nombre
                            </label>
                            <input type="text" id="nombre" name="nombre" autocomplete="name"
                                   value="<?= e($valoresContacto['nombre'] ?? '') ?>"
                                   class="<?= e($clasesCampo) ?>"
                                   placeholder="Su nombre">
                            <?php if (isset($erroresContacto['nombre'])): ?>
                                <p class="mt-1 text-sm text-bad"><?= e($erroresContacto['nombre']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="correo" class="block text-sm font-medium text-texto-2">
                                Correo corporativo
                            </label>
                            <input type="email" id="correo" name="correo" autocomplete="email"
                                   value="<?= e($valoresContacto['correo'] ?? '') ?>"
                                   class="<?= e($clasesCampo) ?>"
                                   placeholder="nombre@empresa.com">
                            <?php if (isset($erroresContacto['correo'])): ?>
                                <p class="mt-1 text-sm text-bad"><?= e($erroresContacto['correo']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="motor" class="block text-sm font-medium text-texto-2">
                                Motor de base de datos
                            </label>
                            <select id="motor" name="motor" class="<?= e($clasesCampo) ?>">
                                <?php foreach ($motores as $motor): ?>
                                    <option class="text-texto" value="<?= e($motor) ?>"
                                        <?= ($valoresContacto['motor'] ?? '') === $motor ? 'selected' : '' ?>>
                                        <?= e($motor) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option class="text-texto" value="otro"
                                    <?= ($valoresContacto['motor'] ?? '') === 'otro' ? 'selected' : '' ?>>
                                    Otro
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="mensaje" class="block text-sm font-medium text-texto-2">
                                ¿Cómo podemos ayudarle?
                            </label>
                            <textarea id="mensaje" name="mensaje" rows="4"
                                      class="<?= e($clasesCampo) ?>"
                                      placeholder="Describa brevemente su situación"><?= e($valoresContacto['mensaje'] ?? '') ?></textarea>
                            <?php if (isset($erroresContacto['mensaje'])): ?>
                                <p class="mt-1 text-sm text-bad"><?= e($erroresContacto['mensaje']) ?></p>
                            <?php endif; ?>
                        </div>

                        <button type="submit"
                                class="rv-extruido rv-interactivo w-full rounded-rv bg-primario px-6 py-3.5 font-semibold text-primario-texto">
                            Solicitar diagnóstico
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-12 flex flex-col gap-2 border-t border-borde pt-7 text-sm text-nav-texto2 sm:flex-row sm:items-center sm:justify-between">
            <p>
                &copy; <span class="tabular"><?= e((string) $empresa['anio']) ?></span> <?= e($empresa['nombre']) ?>.
                <?= e($vista->t('pie.proyecto')) ?>
            </p>
            <p><?= e($vista->t('pie.arquitectura')) ?> <span class="font-mono"><?= e(PHP_VERSION) ?></span></p>
        </div>
    </div>
</footer>
