<?php

declare(strict_types=1);

/**
 * @var array{titulo: string, texto: string} $contacto
 * @var array<string, mixed>                 $empresa
 * @var list<string>                         $motores
 * @var array{aviso: string|null, error: string|null} $mensajes
 * @var array<string, string> $erroresContacto
 * @var array<string, string> $valoresContacto
 */
$erroresContacto = $erroresContacto ?? [];
$valoresContacto = $valoresContacto ?? [];
?>
<section id="contacto" class="bg-slate-50 py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        <div class="grid gap-12 rounded-2xl bg-marina-950 p-10 lg:grid-cols-2 lg:p-16">

            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    <?= e($contacto['titulo']) ?>
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-marina-200">
                    <?= e($contacto['texto']) ?>
                </p>

                <dl class="mt-10 space-y-3 text-marina-200">
                    <div class="flex gap-3">
                        <dt class="w-24 shrink-0 font-semibold text-white">Correo</dt>
                        <dd>
                            <a class="transition hover:text-acento-400"
                               href="mailto:<?= e($empresa['correo']) ?>">
                                <?= e($empresa['correo']) ?>
                            </a>
                        </dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="w-24 shrink-0 font-semibold text-white">Teléfono</dt>
                        <dd><?= e($empresa['telefono']) ?></dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="w-24 shrink-0 font-semibold text-white">Ubicación</dt>
                        <dd><?= e($empresa['ciudad']) ?></dd>
                    </div>
                </dl>
            </div>

            <form class="space-y-5" action="<?= e($vista->url('contacto')) ?>" method="post" novalidate>
                <?php if (($mensajes['aviso'] ?? null) !== null): ?>
                    <p class="rounded-lg bg-exito-400/15 px-4 py-3 text-sm text-exito-400">
                        <?= e($mensajes['aviso']) ?>
                    </p>
                <?php endif; ?>

                <div>
                    <label for="nombre" class="block text-sm font-medium text-marina-100">
                        Nombre
                    </label>
                    <input type="text" id="nombre" name="nombre" autocomplete="name"
                           value="<?= e($valoresContacto['nombre'] ?? '') ?>"
                           class="mt-1.5 w-full rounded-lg border border-white/15 bg-white/5 px-4 py-3 text-white placeholder-marina-300/60 outline-none transition focus:border-acento-400"
                           placeholder="Su nombre">
                    <?php if (isset($erroresContacto['nombre'])): ?>
                        <p class="mt-1 text-sm text-alerta-400"><?= e($erroresContacto['nombre']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="correo" class="block text-sm font-medium text-marina-100">
                        Correo corporativo
                    </label>
                    <input type="email" id="correo" name="correo" autocomplete="email"
                           value="<?= e($valoresContacto['correo'] ?? '') ?>"
                           class="mt-1.5 w-full rounded-lg border border-white/15 bg-white/5 px-4 py-3 text-white placeholder-marina-300/60 outline-none transition focus:border-acento-400"
                           placeholder="nombre@empresa.com">
                    <?php if (isset($erroresContacto['correo'])): ?>
                        <p class="mt-1 text-sm text-alerta-400"><?= e($erroresContacto['correo']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="motor" class="block text-sm font-medium text-marina-100">
                        Motor de base de datos
                    </label>
                    <select id="motor" name="motor"
                            class="mt-1.5 w-full rounded-lg border border-white/15 bg-white/5 px-4 py-3 text-white outline-none transition focus:border-acento-400">
                        <?php foreach ($motores as $motor): ?>
                            <option class="text-marina-950" value="<?= e($motor) ?>"
                                <?= ($valoresContacto['motor'] ?? '') === $motor ? 'selected' : '' ?>>
                                <?= e($motor) ?>
                            </option>
                        <?php endforeach; ?>
                        <option class="text-marina-950" value="otro"
                            <?= ($valoresContacto['motor'] ?? '') === 'otro' ? 'selected' : '' ?>>
                            Otro
                        </option>
                    </select>
                </div>

                <div>
                    <label for="mensaje" class="block text-sm font-medium text-marina-100">
                        ¿Cómo podemos ayudarle?
                    </label>
                    <textarea id="mensaje" name="mensaje" rows="4"
                              class="mt-1.5 w-full rounded-lg border border-white/15 bg-white/5 px-4 py-3 text-white placeholder-marina-300/60 outline-none transition focus:border-acento-400"
                              placeholder="Describa brevemente su situación"><?= e($valoresContacto['mensaje'] ?? '') ?></textarea>
                    <?php if (isset($erroresContacto['mensaje'])): ?>
                        <p class="mt-1 text-sm text-alerta-400"><?= e($erroresContacto['mensaje']) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-acento-500 px-6 py-3.5 font-semibold text-marina-950 transition hover:bg-acento-400">
                    Solicitar diagnóstico
                </button>
            </form>
        </div>
    </div>
</section>
