<?php

declare(strict_types=1);

/**
 * Tarjeta de la pregunta de auditoría de un control (ISO/IEC 27007:2020).
 *
 * Comparte identificador y objeto de estado con components/tarjeta-control.php:
 * responder aquí mueve el tablero y viceversa.
 *
 * @var \App\Models\Entidades\Control $control
 * @var string                        $dominio
 */
$respuestas = [
    ['valor' => 'si',      'etiqueta' => 'Sí',        'activo' => 'peer-checked:bg-ok peer-checked:text-texto'],
    ['valor' => 'parcial', 'etiqueta' => 'Parcial',   'activo' => 'peer-checked:bg-warn peer-checked:text-texto'],
    ['valor' => 'no',      'etiqueta' => 'No',        'activo' => 'peer-checked:bg-bad peer-checked:text-texto'],
    ['valor' => 'na',      'etiqueta' => 'No aplica', 'activo' => 'peer-checked:bg-texto-2 peer-checked:text-texto'],
];
?>
<article data-tarjeta="<?= e($control->id) ?>"
         data-dominio="<?= e($dominio) ?>"
         data-proceso="<?= e((string) $control->proceso) ?>"
         data-respuesta=""
         class="rv-extruido rv-interactivo rounded-rv-lg border border-l-4 border-borde border-l-borde bg-superficie p-5">

    <div class="flex flex-wrap items-center gap-2">
        <span class="rv-badge-norma">
            <?= e($control->id) ?>
        </span>
        <span class="rv-badge-norma">
            <?= e($control->iso) ?>
        </span>
    </div>

    <p class="mt-3 font-semibold leading-relaxed text-texto">
        <?= e($control->pregunta) ?>
    </p>

    <div class="mt-4 border-t border-borde pt-4">
        <fieldset>
            <legend class="text-xs font-medium text-texto-2">Respuesta</legend>
            <div class="mt-1.5 grid grid-cols-2 gap-1 rounded-rv bg-elevado p-1 sm:grid-cols-4">
                <?php foreach ($respuestas as $respuesta): ?>
                    <label class="cursor-pointer">
                        <input type="radio"
                               class="peer sr-only"
                               name="respuesta-<?= e($control->id) ?>"
                               data-control="<?= e($control->id) ?>"
                               data-campo="respuesta"
                               value="<?= e($respuesta['valor']) ?>">
                        <span class="block rounded-md px-1 py-1.5 text-center text-xs font-semibold text-texto-2 transition <?= e($respuesta['activo']) ?> peer-focus-visible:ring-2 peer-focus-visible:ring-primario">
                            <?= e($respuesta['etiqueta']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="entrevistado-<?= e($control->id) ?>" class="block text-xs font-medium text-texto-2">
                Persona entrevistada
            </label>
            <input type="text"
                   id="entrevistado-<?= e($control->id) ?>"
                   data-control="<?= e($control->id) ?>"
                   data-campo="entrevistado"
                   placeholder="Nombre y puesto"
                   class="mt-1.5 rv-hundido w-full rounded-rv border border-borde bg-superficie px-3 py-2 text-sm text-texto placeholder:text-texto-2 focus:border-primario focus:outline-none focus:ring-1 focus:ring-primario">
        </div>
        <div>
            <label for="evidencia-<?= e($control->id) ?>" class="block text-xs font-medium text-texto-2">
                Evidencia aportada
            </label>
            <input type="text"
                   id="evidencia-<?= e($control->id) ?>"
                   data-control="<?= e($control->id) ?>"
                   data-campo="evidenciaAportada"
                   placeholder="Documento, consulta o captura recibida"
                   class="mt-1.5 rv-hundido w-full rounded-rv border border-borde bg-superficie px-3 py-2 text-sm text-texto placeholder:text-texto-2 focus:border-primario focus:outline-none focus:ring-1 focus:ring-primario">
        </div>
    </div>

    <div class="mt-4">
        <label for="notas-<?= e($control->id) ?>" class="block text-xs font-medium text-texto-2">
            Notas de la entrevista
        </label>
        <textarea id="notas-<?= e($control->id) ?>"
                  rows="2"
                  data-control="<?= e($control->id) ?>"
                  data-campo="notas"
                  placeholder="Citas textuales, matices y compromisos adquiridos"
                  class="mt-1.5 rv-hundido w-full rounded-rv border border-borde bg-superficie px-3 py-2 text-sm leading-relaxed text-texto placeholder:text-texto-2 focus:border-primario focus:outline-none focus:ring-1 focus:ring-primario"></textarea>
    </div>
</article>
