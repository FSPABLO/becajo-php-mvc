<?php

declare(strict_types=1);

/**
 * Un turno de Lembas: su texto y las fichas con los datos.
 *
 * LA MISMA PIEZA en los tres sitios que lo pintan, para que un turno se vea
 * igual venga de donde venga:
 *
 *   - la respuesta JSON de POST /asistente (AsistenteController::turno());
 *   - la transcripción repintada al cambiar de pantalla, que guarda este HTML;
 *   - las dos <template> del panel: `plantilla` (el guion rellena el texto de
 *     un error de red) y `pensando` (el globo mientras se espera la respuesta).
 *
 * El TEXTO es del modelo y las FICHAS son del servidor, y se ve: el texto va en
 * un globo y cada ficha en su tarjeta. No es decoración — lo que dice una ficha
 * salió de Oracle con los permisos de la sesión; lo que dice el globo lo
 * escribió un modelo que nunca vio esos datos.
 *
 * El texto del modelo se imprime escapado y con pre-line: se le pide texto
 * plano, y si aun así escribe marcado, se ve como texto y no se interpreta.
 *
 * @var \App\Core\Vista $vista
 * @var string|null     $texto
 * @var list<array{tipo: string, datos: array<string, mixed>}>|null $fichas
 * @var bool|null       $plantilla
 * @var bool|null       $pensando
 */
$texto     = $texto ?? '';
$fichas    = $fichas ?? [];
$plantilla = $plantilla ?? false;
$pensando  = $pensando ?? false;

/*
 * Solo estos tipos tienen ficha. Una lista cerrada y no el nombre que venga en
 * los datos concatenado a una ruta: el tipo lo pone Lembas, pero la ruta de una
 * plantilla no debe poder salir de un valor que viaja por una sesión.
 */
$tiposFicha = ['auditorias', 'resumen', 'riesgo', 'control', 'vencidas', 'aviso'];

$globo = 'rv-extruido-sm inline-block max-w-full rounded-rv-lg rounded-tl-sm bg-elevado px-3.5 py-2.5 text-[13.5px] leading-relaxed text-texto';
?>
<div class="flex items-start gap-2.5" <?= $pensando ? 'data-asistente-pensando' : '' ?>>
    <span class="grid h-7 w-7 flex-none place-items-center rounded-full bg-elevado text-texto-2" aria-hidden="true">
        <?= icono('asistente', 'h-4 w-4') ?>
    </span>

    <div class="min-w-0 flex-1 space-y-2">
        <span class="sr-only"><?= e($vista->t('asistente.nombre')) ?>:</span>

        <?php if ($pensando): ?>
            <?php /* El pulso es solo un refuerzo: la frase ya dice que espera. */ ?>
            <p class="<?= $globo ?> animate-pulse text-texto-2"><?= e($vista->t('asistente.pensando', $vista->t('asistente.nombre'))) ?></p>
        <?php elseif ($plantilla): ?>
            <p class="<?= $globo ?>"><span class="whitespace-pre-line break-words" data-asistente-texto></span></p>
        <?php elseif ($texto !== ''): ?>
            <p class="<?= $globo ?>"><span class="whitespace-pre-line break-words"><?= e($texto) ?></span></p>
        <?php endif; ?>

        <?php foreach ($fichas as $ficha): ?>
            <?php if (in_array($ficha['tipo'] ?? '', $tiposFicha, true)): ?>
                <?= $vista->renderizar('partials/panel/asistente/ficha-' . $ficha['tipo'], $ficha['datos'] ?? []) ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
