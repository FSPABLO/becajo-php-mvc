<?php

declare(strict_types=1);

/**
 * Tarjeta de captura de un control del instrumento.
 *
 * El borde izquierdo se tiñe según el estado para que el avance se lea al
 * desplazarse, sin necesidad de leer el texto. Ese color lo cambia el guion.
 *
 * @var \App\Models\Entidades\Control $control
 * @var string                        $dominio
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 */

$riesgos = [
    ['clave' => 'integridad',      'letra' => 'I', 'etiqueta' => 'Integridad'],
    ['clave' => 'confidencialidad', 'letra' => 'C', 'etiqueta' => 'Confidencialidad'],
    ['clave' => 'disponibilidad',  'letra' => 'D', 'etiqueta' => 'Disponibilidad'],
];

$estados = [
    ['valor' => 'si', 'etiqueta' => 'Sí',        'activo' => 'peer-checked:bg-exito-500 peer-checked:text-white'],
    ['valor' => 'no', 'etiqueta' => 'No',        'activo' => 'peer-checked:bg-alerta-500 peer-checked:text-white'],
    ['valor' => 'na', 'etiqueta' => 'No aplica', 'activo' => 'peer-checked:bg-marina-500 peer-checked:text-white'],
];

$criterios = [
    ['valor' => '',            'etiqueta' => 'Sin definir'],
    ['valor' => 'documentado', 'etiqueta' => 'Documentado'],
    ['valor' => 'repetible',   'etiqueta' => 'Repetible'],
    ['valor' => 'evidencia',   'etiqueta' => 'Evidencia observada'],
];
?>
<article data-tarjeta="<?= e($control->id) ?>"
         data-dominio="<?= e($dominio) ?>"
         data-proceso="<?= e((string) $control->proceso) ?>"
         data-estado=""
         class="rounded-xl border border-l-4 border-slate-200 border-l-slate-200 bg-white p-5 transition">

    <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-md bg-marina-950 px-2 py-1 font-mono text-xs font-bold text-acento-400">
            <?= e($control->id) ?>
        </span>
        <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
            <?= e($control->iso) ?>
        </span>
    </div>

    <h4 class="mt-3 font-semibold leading-relaxed text-marina-950">
        <?= e($control->enunciado) ?>
    </h4>

    <p class="mt-2.5 text-sm leading-relaxed text-slate-600">
        <span class="font-semibold text-slate-500">Evidencia solicitada:</span>
        <?= e($control->evidencia) ?>
    </p>

    <!-- Fila de captura: se apila en pantallas angostas. -->
    <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 sm:grid-cols-2 xl:grid-cols-4">

        <fieldset>
            <legend class="text-xs font-medium text-slate-500">Riesgo asociado</legend>
            <div class="mt-1.5 flex gap-1.5">
                <?php foreach ($riesgos as $riesgo): ?>
                    <label class="cursor-pointer">
                        <input type="checkbox"
                               class="peer sr-only"
                               data-control="<?= e($control->id) ?>"
                               data-campo="riesgo"
                               value="<?= e($riesgo['clave']) ?>"
                               aria-label="<?= e($riesgo['etiqueta']) ?> — control <?= e($control->id) ?>">
                        <span title="<?= e($riesgo['etiqueta']) ?>"
                              class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-sm font-bold text-slate-500 transition peer-checked:border-marina-950 peer-checked:bg-marina-950 peer-checked:text-acento-400 peer-focus-visible:ring-2 peer-focus-visible:ring-acento-500 peer-focus-visible:ring-offset-2">
                            <?= e($riesgo['letra']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div>
            <label for="madurez-<?= e($control->id) ?>" class="block text-xs font-medium text-slate-500">
                Madurez (0 a 5)
            </label>
            <select id="madurez-<?= e($control->id) ?>"
                    data-control="<?= e($control->id) ?>"
                    data-campo="madurez"
                    class="mt-1.5 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-sm text-marina-950 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500">
                <option value="">Sin calificar</option>
                <?php foreach ($escala as $nivel): ?>
                    <option value="<?= e((string) $nivel['nivel']) ?>">
                        <?= e((string) $nivel['nivel']) ?> — <?= e($nivel['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="criterio-<?= e($control->id) ?>" class="block text-xs font-medium text-slate-500">
                Criterio de verificación
            </label>
            <select id="criterio-<?= e($control->id) ?>"
                    data-control="<?= e($control->id) ?>"
                    data-campo="criterio"
                    class="mt-1.5 h-9 w-full rounded-lg border border-slate-200 bg-white px-2 text-sm text-marina-950 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500">
                <?php foreach ($criterios as $criterio): ?>
                    <option value="<?= e($criterio['valor']) ?>"><?= e($criterio['etiqueta']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <fieldset>
            <legend class="text-xs font-medium text-slate-500">¿El control existe?</legend>
            <div class="mt-1.5 grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1">
                <?php foreach ($estados as $estado): ?>
                    <label class="cursor-pointer">
                        <input type="radio"
                               class="peer sr-only"
                               name="estado-<?= e($control->id) ?>"
                               data-control="<?= e($control->id) ?>"
                               data-campo="estado"
                               value="<?= e($estado['valor']) ?>">
                        <span class="block rounded-md px-1 py-1.5 text-center text-xs font-semibold text-slate-600 transition <?= e($estado['activo']) ?> peer-focus-visible:ring-2 peer-focus-visible:ring-acento-500">
                            <?= e($estado['etiqueta']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="hallazgo-<?= e($control->id) ?>" class="block text-xs font-medium text-slate-500">
                Hallazgo
            </label>
            <textarea id="hallazgo-<?= e($control->id) ?>"
                      rows="2"
                      data-control="<?= e($control->id) ?>"
                      data-campo="hallazgo"
                      placeholder="Lo observado durante la verificación"
                      class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm leading-relaxed text-marina-950 placeholder:text-slate-400 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500"></textarea>
        </div>
        <div>
            <label for="recomendacion-<?= e($control->id) ?>" class="block text-xs font-medium text-slate-500">
                Recomendación
            </label>
            <textarea id="recomendacion-<?= e($control->id) ?>"
                      rows="2"
                      data-control="<?= e($control->id) ?>"
                      data-campo="recomendacion"
                      placeholder="Acción sugerida y su prioridad"
                      class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm leading-relaxed text-marina-950 placeholder:text-slate-400 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500"></textarea>
        </div>
    </div>

    <!--
        Fricción deliberada: un "no aplica" sin justificación escrita sale del
        denominador del cumplimiento y, sin este aviso, sería la salida fácil
        para inflar el resultado.
    -->
    <p data-aviso-na hidden
       class="mt-3 flex items-start gap-2 rounded-lg border border-aviso-400/50 bg-aviso-400/10 px-3 py-2 text-xs font-medium text-aviso-600">
        <?= icono('alerta', 'h-4 w-4 shrink-0') ?>
        <span>
            Este control quedó marcado como «no aplica» y sale del cálculo de cumplimiento.
            Escriba en el hallazgo la justificación de la exclusión (ISO/IEC 27001, cl. 6.1.3).
        </span>
    </p>
</article>
