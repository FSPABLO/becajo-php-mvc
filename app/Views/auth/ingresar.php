<?php

declare(strict_types=1);

/**
 * Formulario de ingreso al módulo de evaluación de riesgo.
 *
 * @var \App\Core\Vista       $vista
 * @var array<string, mixed>  $empresa
 * @var string|null           $correo    Correo del intento anterior.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
?>
<section class="mx-auto flex min-h-[70vh] w-full max-w-md flex-col justify-center px-6 pt-24 pb-16">

    <header class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-widest text-acento-500">
            <?= e($vista->t('auth.eyebrow')) ?>
        </p>
        <h1 class="mt-2 text-3xl font-extrabold text-marina-950"><?= e($vista->t('auth.ingresar_titulo')) ?></h1>
        <p class="mt-2 text-sm text-slate-600">
            <?= e($vista->t('auth.ingresar_texto', $empresa['nombre'])) ?>
        </p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <form method="post" action="<?= e($vista->url('ingresar')) ?>" class="space-y-5">

        <?= $vista->campoToken() ?>

        <div>
            <label for="correo" class="block text-sm font-semibold text-marina-950">
                <?= e($vista->t('auth.correo')) ?>
            </label>
            <input type="email" id="correo" name="correo" required autofocus
                   autocomplete="username"
                   value="<?= e($correo ?? '') ?>"
                   class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-acento-500 focus:ring-2 focus:ring-acento-500/30">
        </div>

        <div>
            <label for="clave" class="block text-sm font-semibold text-marina-950">
                <?= e($vista->t('auth.clave')) ?>
            </label>
            <input type="password" id="clave" name="clave" required
                   autocomplete="current-password"
                   class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-slate-900 outline-none transition focus:border-acento-500 focus:ring-2 focus:ring-acento-500/30">
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-marina-950 px-4 py-3 font-semibold text-white transition hover:bg-marina-900">
            <?= e($vista->t('auth.entrar')) ?>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        <?= e($vista->t('auth.sin_cuenta')) ?>
        <a href="<?= e($vista->url('registrarse')) ?>"
           class="font-semibold text-acento-600 underline-offset-2 hover:underline">
            <?= e($vista->t('auth.registrarse')) ?>
        </a>
    </p>
</section>
