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

/*
 * Los tres estados se marcan con CONTORNO y texto teñido, no con relleno
 * sólido: en una rejilla de 75 tarjetas, tres rellenos saturados compitiendo
 * destruyen la jerarquía de lectura (§5.5 del sistema visual).
 */
$estados = [
    ['valor' => 'si', 'etiqueta' => 'Sí',        'activo' => 'peer-checked:border-ok peer-checked:text-ok'],
    ['valor' => 'no', 'etiqueta' => 'No',        'activo' => 'peer-checked:border-bad peer-checked:text-bad'],
    ['valor' => 'na', 'etiqueta' => 'No aplica', 'activo' => 'peer-checked:border-na peer-checked:text-na'],
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
         class="rv-extruido rv-interactivo rounded-rv-lg border border-l-4 border-borde border-l-borde bg-superficie p-5 sm:p-6">

    <div class="flex flex-wrap items-center gap-2">
        <span class="rv-badge-norma">
            <?= e($control->id) ?>
        </span>
        <span class="rv-badge-norma">
            <?= e($control->iso) ?>
        </span>
    </div>

    <h4 class="rv-titulo mt-3 text-[1.1rem] font-semibold leading-relaxed text-texto">
        <?= e($control->enunciado) ?>
    </h4>

    <p class="mt-2.5 text-sm leading-relaxed text-texto-2">
        <span class="font-semibold text-texto-2">Evidencia solicitada:</span>
        <?= e($control->evidencia) ?>
    </p>

    <div data-referencia-cuestionario hidden
             class="rv-hundido mt-4 rounded-rv border border-borde bg-elevado p-4">
            <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-texto-2">
                <?= icono('documento', 'h-3.5 w-3.5') ?>
                Referencia del cuestionario
            </p>

            <div class="mt-2.5 grid gap-3 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-medium text-texto-2">Respuesta registrada</p>
                    <p data-ref="respuesta" class="mt-0.5 text-sm font-semibold text-texto">—</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-texto-2">Persona entrevistada</p>
                    <p data-ref="entrevistado" class="mt-0.5 text-sm text-texto">—</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-texto-2">Evidencia aportada</p>
                    <p data-ref="evidenciaAportada" class="mt-0.5 text-sm text-texto">—</p>
                </div>
            </div>

            <div class="mt-3 border-t border-borde/70 pt-3">
                <p class="text-xs font-medium text-texto-2">Notas de la entrevista</p>
                <p data-ref="notas" class="mt-0.5 text-sm leading-relaxed text-texto">—</p>
            </div>
        </div>


    <!-- Fila de captura: se apila en pantallas angostas. -->
    <div class="mt-4 grid gap-4 border-t border-borde pt-4 sm:grid-cols-2 xl:grid-cols-4">

        <fieldset>
            <legend class="text-xs font-medium text-texto-2">Riesgo asociado</legend>
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
                              class="rv-opcion grid h-9 w-9 place-items-center rounded-rv border border-borde bg-superficie text-sm font-bold text-texto-2 peer-checked:border-primario peer-checked:bg-elevado peer-checked:text-primario peer-focus-visible:ring-2 peer-focus-visible:ring-primario peer-focus-visible:ring-offset-2">
                            <?= e($riesgo['letra']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div>
            <label for="madurez-<?= e($control->id) ?>" class="block text-xs font-medium text-texto-2">
                Madurez (0 a 5)
            </label>
            <select id="madurez-<?= e($control->id) ?>"
                    data-control="<?= e($control->id) ?>"
                    data-campo="madurez"
                    class="mt-1.5 h-9 rv-hundido w-full rounded-rv border border-borde bg-elevado px-2 text-sm text-texto focus:border-primario focus:outline-none focus:ring-1 focus:ring-primario">
                <option value="">Sin calificar</option>
                <?php foreach ($escala as $nivel): ?>
                    <option value="<?= e((string) $nivel['nivel']) ?>">
                        <?= e((string) $nivel['nivel']) ?> — <?= e($nivel['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="criterio-<?= e($control->id) ?>" class="block text-xs font-medium text-texto-2">
                Criterio de verificación
            </label>
            <select id="criterio-<?= e($control->id) ?>"
                    data-control="<?= e($control->id) ?>"
                    data-campo="criterio"
                    class="mt-1.5 h-9 rv-hundido w-full rounded-rv border border-borde bg-elevado px-2 text-sm text-texto focus:border-primario focus:outline-none focus:ring-1 focus:ring-primario">
                <?php foreach ($criterios as $criterio): ?>
                    <option value="<?= e($criterio['valor']) ?>"><?= e($criterio['etiqueta']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <fieldset>
            <legend class="text-xs font-medium text-texto-2">¿El control existe?</legend>
            <?php /* Cubeta hundida con las tres teclas dentro: el hundido dice
                     «aquí se recibe algo» y da a las teclas dónde apoyarse. */ ?>
            <div class="rv-hundido mt-1.5 grid grid-cols-3 gap-1.5 rounded-rv bg-elevado p-1.5">
                <?php foreach ($estados as $estado): ?>
                    <label class="cursor-pointer">
                        <input type="radio"
                               class="peer sr-only"
                               name="estado-<?= e($control->id) ?>"
                               data-control="<?= e($control->id) ?>"
                               data-campo="estado"
                               value="<?= e($estado['valor']) ?>">
                        <?php /* El borde base es transparente para que al marcar
                                 aparezca el contorno sin mover el layout. */ ?>
                        <span class="rv-opcion block rounded-md border border-transparent bg-superficie px-1 py-1.5 text-center text-xs font-semibold text-texto-2 <?= e($estado['activo']) ?> peer-focus-visible:ring-2 peer-focus-visible:ring-primario">
                            <?= e($estado['etiqueta']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="hallazgo-<?= e($control->id) ?>" class="block text-xs font-medium text-texto-2">
                Hallazgo
            </label>
            <textarea id="hallazgo-<?= e($control->id) ?>"
                      rows="2"
                      data-control="<?= e($control->id) ?>"
                      data-campo="hallazgo"
                      placeholder="Lo observado durante la verificación"
                      class="mt-1.5 rv-hundido w-full rounded-rv border border-borde bg-elevado px-3 py-2 text-sm leading-relaxed text-texto placeholder:text-texto-2 focus:border-primario focus:outline-none focus:ring-1 focus:ring-primario"></textarea>
        </div>
        <div>
            <label for="recomendacion-<?= e($control->id) ?>" class="block text-xs font-medium text-texto-2">
                Recomendación
            </label>
            <textarea id="recomendacion-<?= e($control->id) ?>"
                      rows="2"
                      data-control="<?= e($control->id) ?>"
                      data-campo="recomendacion"
                      placeholder="Acción sugerida y su prioridad"
                      class="mt-1.5 rv-hundido w-full rounded-rv border border-borde bg-elevado px-3 py-2 text-sm leading-relaxed text-texto placeholder:text-texto-2 focus:border-primario focus:outline-none focus:ring-1 focus:ring-primario"></textarea>
        </div>
    </div>

    <!--
        Fricción deliberada: un "no aplica" sin justificación escrita sale del
        denominador del cumplimiento y, sin este aviso, sería la salida fácil
        para inflar el resultado.
    -->
    <p data-aviso-na hidden
       class="mt-3 flex items-start gap-2 rounded-rv border border-warn/10 bg-warn/10 px-3 py-2 text-xs font-medium text-warn">
        <?= icono('alerta', 'h-4 w-4 shrink-0') ?>
        <span>
            Este control quedó marcado como «no aplica» y sale del cálculo de cumplimiento.
            Escriba en el hallazgo la justificación de la exclusión (ISO/IEC 27001, cl. 6.1.3).
        </span>
    </p>
</article>
