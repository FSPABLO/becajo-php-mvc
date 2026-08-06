<?php

declare(strict_types=1);

/**
 * Alta de una auditoría.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Usuario> $administradores
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
?>
<section class="mx-auto w-full max-w-xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-acento-600 hover:underline">
            ← Mis auditorías
        </a>
    </nav>

    <h1 class="mb-8 text-3xl font-extrabold text-marina-950">Nueva auditoría</h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <form method="post" action="<?= e($vista->url('evaluacion/nueva')) ?>" class="space-y-5">
        <?= $vista->campoToken() ?>

        <?= $vista->renderizar('evaluacion/_encabezado-form', compact('errores', 'valores', 'administradores')) ?>

        <button type="submit"
                class="w-full rounded-lg bg-marina-950 px-4 py-3 font-semibold text-white transition hover:bg-marina-900">
            Crear auditoría
        </button>
    </form>
</section>
