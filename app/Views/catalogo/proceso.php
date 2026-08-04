<?php

declare(strict_types=1);

/**
 * Alta y edición de un proceso. Vista funcional, no definitiva.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Proceso|null $proceso  null = alta.
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var int $siguienteOrden
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$esNuevo = $proceso === null;
$accion = 'catalogo/procesos/' . ($esNuevo ? 'nuevo' : (string) $proceso->numero);

$v = static fn (string $campo, string $porDefecto = ''): string =>
    (string) ($valores[$campo] ?? $porDefecto);

$opcionesDominio = array_map(
    static fn ($d): array => ['valor' => $d->clave, 'texto' => $d->nombre],
    $dominios,
);
?>
<section class="mx-auto w-full max-w-xl px-6 py-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('catalogo')) ?>" class="text-acento-600 hover:underline">← Catálogo</a>
    </nav>

    <h1 class="mb-8 text-3xl font-extrabold text-marina-950">
        <?= $esNuevo ? 'Nuevo proceso' : 'Editar proceso' ?>
    </h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <form method="post" action="<?= e($vista->url($accion)) ?>" class="space-y-5">
        <?= $vista->campoToken() ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'numero',
            'etiqueta'    => 'Número de catálogo',
            'valor'       => $v('numero', (string) ($proceso->numero ?? '')),
            'error'       => $errores['numero'] ?? null,
            'tipo'        => 'number',
            'obligatorio' => true,
            'bloqueado'   => !$esNuevo,
            'ayuda'       => $esNuevo
                ? 'Número original del catálogo, para trazabilidad con el marco de referencia. No es el orden de presentación.'
                : 'El número no se puede cambiar: identifica al proceso en el resto del catálogo.',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'dominio',
            'etiqueta'    => 'Dominio',
            'valor'       => $v('dominio', $proceso->dominio ?? ''),
            'error'       => $errores['dominio'] ?? null,
            'tipo'        => 'select',
            'opciones'    => $opcionesDominio,
            'obligatorio' => true,
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'nombre',
            'etiqueta'    => 'Nombre',
            'valor'       => $v('nombre', $proceso->nombre ?? ''),
            'error'       => $errores['nombre'] ?? null,
            'obligatorio' => true,
            'maximo'      => 200,
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'    => 'ancla',
            'etiqueta' => 'Anclaje normativo',
            'valor'    => $v('ancla', $proceso->ancla ?? ''),
            'error'    => $errores['ancla'] ?? null,
            'maximo'   => 300,
            'ayuda'    => 'Cláusulas de la norma a las que responde el proceso. Ej.: A.8.14, A.8.16',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'orden',
            'etiqueta'    => 'Orden de presentación',
            'valor'       => $v('orden', (string) ($proceso->orden ?? $siguienteOrden)),
            'error'       => $errores['orden'] ?? null,
            'tipo'        => 'number',
            'obligatorio' => true,
        ]) ?>

        <button type="submit"
                class="w-full rounded-lg bg-marina-950 px-4 py-3 font-semibold text-white transition hover:bg-marina-900">
            <?= $esNuevo ? 'Crear proceso' : 'Guardar cambios' ?>
        </button>
    </form>
</section>
