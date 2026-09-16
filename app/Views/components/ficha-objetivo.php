<?php

declare(strict_types=1);

/**
 * Cabecera de un objetivo COBIT dentro del panel de la auditoría: la
 * capacidad que el auditor declara y su justificación. Ocupa el lugar del
 * título del proceso en ISO; las prácticas del objetivo van debajo.
 *
 * SE LEE EN TRES FRANJAS separadas por una línea —el objetivo, su captura y la
 * acción—, que es el orden de una tarjeta de control
 * (components/tarjeta-control-auditoria): qué se evalúa, qué se responde y qué
 * se hace con ello.
 *
 * PERO ES UNA BANDA PLANA, NO UNA TARJETA, y esa es la diferencia que marca la
 * jerarquía. El objetivo no es una pieza más de la lista: es la SECCIÓN que
 * agrupa a las prácticas que vienen debajo. En el vocabulario del relieve, lo
 * extruido es lo que se manipula —cada práctica es una tarjeta blanca que
 * sobresale, con su borde izquierdo teñido por el estado— y esto se queda al
 * ras del lienzo de la región, con su contorno. Con relieve y fondo blanco, el
 * objetivo se leía como una práctica más y había que buscar dónde empezaba el
 * grupo. Los campos sí invierten a `bg-superficie`: claro = campo, tono = zona
 * que se rellena, la misma regla del resto del módulo.
 *
 * Antes era un formulario de tres columnas alineadas al pie —capacidad,
 * justificación y botón en una sola línea—, y eso dejaba los dos rótulos a
 * alturas distintas (el `<select>` es más bajo que el `<textarea>`) con el
 * botón colgando del borde inferior. Ahora los dos campos empiezan arriba
 * (`items-start`), así que sus rótulos se alinean entre sí, y el botón tiene su
 * propia franja al pie en vez de competir con ellos por la misma línea.
 *
 * La capacidad va PRIMERA y la justificación a su lado porque ese es el orden
 * en que se decide: primero el nivel, después en qué se sustenta. Es el mismo
 * criterio por el que en una tarjeta de control la respuesta va antes que su
 * nota.
 *
 * La pastilla y el `<select>` dicen los dos la capacidad, y no sobra ninguno:
 * la pastilla es el estado guardado y el selector es lo que se está por
 * guardar. Igual que la pastilla y las tres teclas Sí/No/No aplica de un
 * control.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Proceso $proceso
 * @var \App\Models\Entidades\EvaluacionObjetivo|null $evaluacion
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 * @var int  $idAuditoria
 * @var bool $abierta
 * @var array<string, string> $errores  Del último intento de ESTE objetivo.
 * @var array<string, mixed>  $valores
 */

$evaluacion = $evaluacion ?? null;
$errores    = $errores ?? [];
$valores    = $valores ?? [];
$hayIntento = $valores !== [];

$vCapacidad     = $hayIntento ? ($valores['capacidad'] ?? null) : $evaluacion?->capacidad;
$vJustificacion = $hayIntento ? ($valores['justificacion'] ?? '') : ($evaluacion?->justificacion ?? '');

$nombreNivel = null;

foreach ($escala as $nivel) {
    if ($evaluacion !== null && $nivel['nivel'] === $evaluacion->capacidad) {
        $nombreNivel = $nivel['nombre'];
    }
}

$suf = (string) $proceso->numero;

$campo = static fn (bool $mal): string =>
    'rv-hundido mt-1.5 w-full rounded-rv border bg-superficie px-3 py-2 text-sm text-texto outline-none transition '
    . ($mal
        ? 'border-bad focus:border-bad focus:ring-1 focus:ring-bad'
        : 'border-borde focus:border-primario focus:ring-1 focus:ring-primario');

$rotulo = 'block text-xs font-medium text-texto-2';
?>
<div id="objetivo-<?= e($suf) ?>" class="scroll-mt-24 pb-1">
    <article class="overflow-hidden rounded-rv-lg border border-borde bg-elevado">

        <?php /* 1. Qué objetivo es, y en qué quedó. */ ?>
        <header class="flex flex-wrap items-start justify-between gap-x-3 gap-y-2 px-5 py-4 sm:px-6">
            <h4 class="min-w-0 text-base font-bold text-texto">
                <span class="text-primario"><?= e($proceso->ancla) ?>.</span>
                <?= e($proceso->nombre) ?>
            </h4>
            <?= $evaluacion === null
                ? pill('na', $vista->t('eval.capacidad_sin_declarar'))
                : pill('ok', $vista->t('eval.capacidad') . ' ' . $evaluacion->capacidad
                    . ($nombreNivel !== null ? ' — ' . $nombreNivel : '')) ?>
        </header>

        <form method="post"
              action="<?= e($vista->url('evaluacion/' . $idAuditoria . '/objetivos/' . $suf)) ?>">
            <?= $vista->campoToken() ?>

            <?php
            /*
             * 2. La captura. Dos columnas desde `sm`: el nivel es una lista
             * corta y la justificación es prosa, así que reparten el ancho
             * según lo que cada una necesita en vez de a partes iguales.
             */
            ?>
            <div class="grid gap-4 border-t border-borde px-5 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:items-start sm:px-6">
                <div>
                    <label for="capacidad-<?= e($suf) ?>" class="<?= $rotulo ?>">
                        <?= e($vista->t('eval.capacidad_objetivo')) ?>
                    </label>
                    <select id="capacidad-<?= e($suf) ?>" name="capacidad"
                            class="<?= e($campo(isset($errores['capacidad']))) ?>"
                            <?= $abierta ? '' : 'disabled' ?>>
                        <option value=""><?= e($vista->t('eval.sin_calificar')) ?></option>
                        <?php foreach ($escala as $nivel): ?>
                            <option value="<?= e((string) $nivel['nivel']) ?>"
                                    title="<?= e($nivel['descripcion']) ?>"
                                <?= (string) $vCapacidad === (string) $nivel['nivel'] ? 'selected' : '' ?>>
                                <?= e((string) $nivel['nivel']) ?> — <?= e($nivel['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['capacidad'])): ?>
                        <p class="mt-1 text-[13px] text-bad"><?= e($errores['capacidad']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="justificacion-<?= e($suf) ?>" class="<?= $rotulo ?>">
                        <?= e($vista->t('eval.justificacion_capacidad')) ?>
                    </label>
                    <?php
                    /*
                     * Tres filas y no dos: el campo admite 2000 caracteres y lo
                     * que se espera es el recuento de prácticas y la evidencia
                     * que sostienen el nivel. Con dos filas, cualquier
                     * justificación de verdad se leía por una rendija.
                     */
                    ?>
                    <textarea id="justificacion-<?= e($suf) ?>" name="justificacion" rows="3"
                              maxlength="<?= e((string) \App\Models\Entidades\EvaluacionObjetivo::JUSTIFICACION_MAXIMA) ?>"
                              class="<?= e($campo(isset($errores['justificacion']))) ?>"
                              <?= $abierta ? '' : 'disabled' ?>><?= e((string) $vJustificacion) ?></textarea>
                    <?php if (isset($errores['justificacion'])): ?>
                        <p class="mt-1 text-[13px] text-bad"><?= e($errores['justificacion']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            /*
             * 3. La acción, en su propia franja y a la derecha, como el pie de
             * una tarjeta de control —pero sin fondo propio: aquí no hay tres
             * tonos, solo tres franjas—. Con la auditoría finalizada no hay
             * franja: los campos ya están deshabilitados y un botón que no
             * guarda nada solo invita a pulsarlo.
             */
            ?>
            <?php if ($abierta): ?>
                <div class="flex items-center justify-end border-t border-borde px-5 py-3 sm:px-6">
                    <button type="submit"
                            class="rv-extruido rv-relieve-pleno rv-interactivo rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
                        <?= e($vista->t('eval.guardar_capacidad')) ?>
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </article>
</div>
