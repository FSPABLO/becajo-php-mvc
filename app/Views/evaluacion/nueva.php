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
<section class="mx-auto w-full max-w-xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-primario hover:underline">
            <?= e($vista->t('eval.volver_auditorias')) ?>
        </a>
    </nav>

    <h1 class="mb-8 text-3xl font-extrabold text-texto"><?= e($vista->t('eval.nueva_auditoria')) ?></h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <form method="post" action="<?= e($vista->url('evaluacion/nueva')) ?>" class="space-y-5">
        <?= $vista->campoToken() ?>

        <?= $vista->renderizar('evaluacion/_encabezado-form', compact('errores', 'valores', 'administradores')) ?>

        <button type="submit"
                class="w-full rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-3 font-semibold text-primario-texto">
            <?= e($vista->t('eval.crear_auditoria')) ?>
        </button>
    </form>
</section>
