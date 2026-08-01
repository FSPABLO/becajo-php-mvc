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
    ['valor' => 'si',      'etiqueta' => 'Sí',        'activo' => 'peer-checked:bg-exito-500 peer-checked:text-white'],
    ['valor' => 'parcial', 'etiqueta' => 'Parcial',   'activo' => 'peer-checked:bg-aviso-500 peer-checked:text-white'],
    ['valor' => 'no',      'etiqueta' => 'No',        'activo' => 'peer-checked:bg-alerta-500 peer-checked:text-white'],
    ['valor' => 'na',      'etiqueta' => 'No aplica', 'activo' => 'peer-checked:bg-marina-500 peer-checked:text-white'],
];
?>
<article data-tarjeta="<?= e($control->id) ?>"
         data-dominio="<?= e($dominio) ?>"
         data-proceso="<?= e((string) $control->proceso) ?>"
         data-respuesta=""
         class="rounded-xl border border-l-4 border-slate-200 border-l-slate-200 bg-white p-5 transition">

    <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-md bg-marina-950 px-2 py-1 font-mono text-xs font-bold text-acento-400">
            <?= e($control->id) ?>
        </span>
        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
            <?= e($control->iso) ?>
        </span>
    </div>

    <p class="mt-3 font-semibold leading-relaxed text-marina-950">
        <?= e($control->pregunta) ?>
    </p>

    <div class="mt-4 border-t border-slate-200 pt-4">
        <fieldset>
            <legend class="text-xs font-medium text-slate-500">Respuesta</legend>
            <div class="mt-1.5 grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1 sm:grid-cols-4">
                <?php foreach ($respuestas as $respuesta): ?>
                    <label class="cursor-pointer">
                        <input type="radio"
                               class="peer sr-only"
                               name="respuesta-<?= e($control->id) ?>"
                               data-control="<?= e($control->id) ?>"
                               data-campo="respuesta"
                               value="<?= e($respuesta['valor']) ?>">
                        <span class="block rounded-md px-1 py-1.5 text-center text-xs font-semibold text-slate-600 transition <?= e($respuesta['activo']) ?> peer-focus-visible:ring-2 peer-focus-visible:ring-acento-500">
                            <?= e($respuesta['etiqueta']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="entrevistado-<?= e($control->id) ?>" class="block text-xs font-medium text-slate-500">
                Persona entrevistada
            </label>
            <input type="text"
                   id="entrevistado-<?= e($control->id) ?>"
                   data-control="<?= e($control->id) ?>"
                   data-campo="entrevistado"
                   placeholder="Nombre y puesto"
                   class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-marina-950 placeholder:text-slate-400 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500">
        </div>
        <div>
            <label for="evidencia-<?= e($control->id) ?>" class="block text-xs font-medium text-slate-500">
                Evidencia aportada
            </label>
            <input type="text"
                   id="evidencia-<?= e($control->id) ?>"
                   data-control="<?= e($control->id) ?>"
                   data-campo="evidenciaAportada"
                   placeholder="Documento, consulta o captura recibida"
                   class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-marina-950 placeholder:text-slate-400 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500">
        </div>
    </div>

    <div class="mt-4">
        <label for="notas-<?= e($control->id) ?>" class="block text-xs font-medium text-slate-500">
            Notas de la entrevista
        </label>
        <textarea id="notas-<?= e($control->id) ?>"
                  rows="2"
                  data-control="<?= e($control->id) ?>"
                  data-campo="notas"
                  placeholder="Citas textuales, matices y compromisos adquiridos"
                  class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm leading-relaxed text-marina-950 placeholder:text-slate-400 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500"></textarea>
    </div>
</article>
