<?php

declare(strict_types=1);

/**
 * Alta y edición de un control.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Control|null $control  null = alta.
 * @var list<\App\Models\Entidades\Proceso> $procesos
 * @var array<string, \App\Models\Entidades\Dominio> $dominios
 * @var int $evaluaciones  Cuántas auditorías ya respondieron este control.
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$esNuevo = $control === null;
$accion = 'catalogo/controles/' . ($esNuevo ? 'nuevo' : $control->id);

$v = static fn (string $campo, string $porDefecto = ''): string =>
    (string) ($valores[$campo] ?? $porDefecto);

$opcionesProceso = array_map(
    static fn ($p): array => [
        'valor' => (string) $p->numero,
        'texto' => $p->numero . ' · ' . $p->nombre
                 . ' (' . ($dominios[$p->dominio]->corto ?? $p->dominio) . ')',
    ],
    $procesos,
);
?>
<section class="mx-auto w-full max-w-2xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('catalogo')) ?>" class="text-acento-600 hover:underline">← Catálogo</a>
    </nav>

    <h1 class="mb-8 text-3xl font-extrabold text-marina-950">
        <?= $esNuevo ? 'Nuevo control' : 'Editar control ' . e($control->id) ?>
    </h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if (!$esNuevo && $evaluaciones > 0): ?>
        <p class="mb-6 rounded-xl border border-aviso-400/40 bg-aviso-400/10 px-4 py-3 text-sm text-aviso-600">
            <strong><?= e((string) $evaluaciones) ?> evaluación(es)</strong> de auditoría ya
            respondieron este control. Cambiar su enunciado o su pregunta altera el
            significado de esas respuestas, y por eso no se puede eliminar.
        </p>
    <?php endif; ?>

    <form method="post" action="<?= e($vista->url($accion)) ?>" class="space-y-5">
        <?= $vista->campoToken() ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'codigo',
            'etiqueta'    => 'Código',
            'valor'       => $v('codigo', $control->id ?? ''),
            'error'       => $errores['codigo'] ?? null,
            'obligatorio' => true,
            'bloqueado'   => !$esNuevo,
            'maximo'      => 6,
            'ayuda'       => $esNuevo
                ? 'Hasta 6 caracteres. El catálogo usa el formato C-001.'
                : 'El código no se puede cambiar: viaja a las evaluaciones ya respondidas.',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'proceso',
            'etiqueta'    => 'Proceso al que pertenece',
            'valor'       => $v('proceso', (string) ($control->proceso ?? '')),
            'error'       => $errores['proceso'] ?? null,
            'tipo'        => 'select',
            'opciones'    => $opcionesProceso,
            'obligatorio' => true,
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'iso',
            'etiqueta'    => 'Referencia ISO',
            'valor'       => $v('iso', $control->iso ?? ''),
            'error'       => $errores['iso'] ?? null,
            'obligatorio' => true,
            'maximo'      => 100,
            'ayuda'       => 'Ej.: ISO/IEC 27002:2022 A.8.24',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'enunciado',
            'etiqueta'    => 'Enunciado del control',
            'valor'       => $v('enunciado', $control->enunciado ?? ''),
            'error'       => $errores['enunciado'] ?? null,
            'tipo'        => 'textarea',
            'filas'       => 4,
            'obligatorio' => true,
            'ayuda'       => 'Describa un estado verificable, no una intención.',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'       => 'pregunta',
            'etiqueta'    => 'Pregunta de auditoría',
            'valor'       => $v('pregunta', $control->pregunta ?? ''),
            'error'       => $errores['pregunta'] ?? null,
            'tipo'        => 'textarea',
            'filas'       => 3,
            'obligatorio' => true,
            'ayuda'       => 'Debe pedir el cómo, el quién o el cuándo: que no se responda con un sí o un no a secas.',
        ]) ?>

        <?= $vista->renderizar('catalogo/_campo', [
            'campo'    => 'evidencia',
            'etiqueta' => 'Evidencia esperada',
            'valor'    => $v('evidencia', $control->evidencia ?? ''),
            'error'    => $errores['evidencia'] ?? null,
            'tipo'     => 'textarea',
            'filas'    => 3,
            'ayuda'    => 'Qué documento o registro debería poder mostrar la organización.',
        ]) ?>

        <button type="submit"
                class="w-full rounded-lg bg-marina-950 px-4 py-3 font-semibold text-white transition hover:bg-marina-900">
            <?= $esNuevo ? 'Crear control' : 'Guardar cambios' ?>
        </button>
    </form>
</section>
