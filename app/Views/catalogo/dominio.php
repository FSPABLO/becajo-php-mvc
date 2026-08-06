<?php

declare(strict_types=1);

/**
 * Alta y edición de un dominio.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Dominio|null $dominio  null = alta.
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores  Del intento anterior, si lo hubo.
 * @var int $siguienteOrden
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$esNuevo = $dominio === null;
$accion = 'catalogo/dominios/' . ($esNuevo ? 'nuevo' : $dominio->clave);

// El intento fallido manda sobre lo guardado: si el administrador escribió
// algo y falló la validación, no se le puede borrar lo que tecleó.
$v = static fn (string $campo, string $porDefecto = ''): string =>
    (string) ($valores[$campo] ?? $porDefecto);
?>
<section class="mx-auto w-full max-w-xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('catalogo')) ?>" class="text-acento-600 hover:underline">
            ← Catálogo
        </a>
    </nav>

    <h1 class="mb-8 text-3xl font-extrabold text-marina-950">
        <?= $esNuevo ? 'Nuevo dominio' : 'Editar dominio' ?>
    </h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <form method="post" action="<?= e($vista->url($accion)) ?>" class="space-y-5">
        <?= $vista->campoToken() ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'clave',
            'etiqueta'    => 'Clave',
            'valor'       => $v('clave', $dominio->clave ?? ''),
            'error'       => $errores['clave'] ?? null,
            'obligatorio' => true,
            'bloqueado'   => !$esNuevo,
            'maximo'      => 20,
            'ayuda'       => $esNuevo
                ? 'Minúsculas sin acentos, dígitos o guion bajo. Viaja en las URL del instrumento.'
                : 'La clave no se puede cambiar: identifica al dominio en el resto del catálogo.',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'nombre',
            'etiqueta'    => 'Nombre',
            'valor'       => $v('nombre', $dominio->nombre ?? ''),
            'error'       => $errores['nombre'] ?? null,
            'obligatorio' => true,
            'maximo'      => 100,
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'corto',
            'etiqueta'    => 'Nombre corto',
            'valor'       => $v('corto', $dominio->corto ?? ''),
            'error'       => $errores['corto'] ?? null,
            'obligatorio' => true,
            'maximo'      => 30,
            'ayuda'       => 'El que se usa en las pestañas, donde no cabe el nombre completo.',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'    => 'descripcion',
            'etiqueta' => 'Descripción',
            'valor'    => $v('descripcion', $dominio->descripcion ?? ''),
            'error'    => $errores['descripcion'] ?? null,
            'tipo'     => 'textarea',
            'filas'    => 3,
            'maximo'   => 500,
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'orden',
            'etiqueta'    => 'Orden de presentación',
            'valor'       => $v('orden', (string) ($dominio->orden ?? $siguienteOrden)),
            'error'       => $errores['orden'] ?? null,
            'tipo'        => 'number',
            'obligatorio' => true,
            'ayuda'       => 'Posición en que aparece el dominio dentro del instrumento.',
        ]) ?>

        <button type="submit"
                class="w-full rounded-lg bg-marina-950 px-4 py-3 font-semibold text-white transition hover:bg-marina-900">
            <?= $esNuevo ? 'Crear dominio' : 'Guardar cambios' ?>
        </button>
    </form>
</section>
