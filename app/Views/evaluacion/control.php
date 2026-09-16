<?php

declare(strict_types=1);

/**
 * La plantilla en pantalla: donde el auditor califica UN control.
 *
 * Es la pantalla central del sistema. Los name= de los campos son los que lee
 * AuditoriaController::leerRespuesta(), no cambiarlos sin actualizar ese método.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var \App\Models\Entidades\Control $control
 * @var \App\Models\Entidades\Proceso|null $proceso
 * @var \App\Models\Entidades\EvaluacionControl|null $evaluacion
 * @var \App\Models\Entidades\ArchivoEvidencia|null $archivo  El adjunto ya guardado.
 * @var int|null $limiteArchivo  Tope real de subida en bytes, ya cruzado con php.ini.
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 * @var list<string> $estados
 * @var list<string> $criterios
 * @var array{anterior: ?\App\Models\Entidades\Control, siguiente: ?\App\Models\Entidades\Control} $vecinos
 * @var array<string, string> $errores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$abierta = !$auditoria->estaFinalizada();
$base = 'evaluacion/' . $auditoria->id;
$archivo = $archivo ?? null;

// El tope que se anuncia es el que el servidor va a respetar; ver la nota
// gemela en components/tarjeta-control-auditoria.
$limiteArchivoMb = number_format(($limiteArchivo ?? \App\Models\Entidades\ArchivoEvidencia::MAXIMO_BYTES) / (1024 * 1024), 1, ',', '');

$etiquetaEstado = ['SI' => $vista->t('eval.estado_si'), 'NO' => $vista->t('eval.estado_no'), 'NA' => $vista->t('eval.estado_na')];
$etiquetaCriterio = [
    'DOCUMENTADO' => $vista->t('eval.criterio_documentado'),
    'REPETIBLE'   => $vista->t('eval.criterio_repetible'),
    'EVIDENCIA'   => $vista->t('eval.criterio_evidencia'),
];
$etiquetaCalidad = [
    'BIEN_IMPLEMENTADO' => $vista->t('eval.calidad_bien'),
    'REQUIERE_MEJORA'   => $vista->t('eval.calidad_mejora'),
    'DECLARATIVO'       => $vista->t('eval.calidad_declarativo'),
];
?>
<section class="mx-auto w-full max-w-3xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url($base)) ?>" class="text-primario hover:underline">
            ← <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
        </a>
    </nav>

    <header class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-widest text-primario">
            <?= e($control->id) ?> · <?= e($proceso?->nombre ?? $vista->t('eval.sin_proceso')) ?>
        </p>
        <h1 class="mt-2 text-2xl font-extrabold leading-snug text-texto">
            <?= e($control->enunciado) ?>
        </h1>
        <p class="mt-2 text-sm text-texto-2"><?= e($control->iso) ?></p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <div class="mb-8 rounded-rv-lg border border-borde bg-elevado p-5">
        <p class="text-sm font-semibold text-texto"><?= e($vista->t('eval.pregunta_auditoria')) ?></p>
        <p class="mt-1.5 text-texto-2"><?= e($evaluacion?->preguntaPersonalizada ?? $control->pregunta) ?></p>

        <?php if ($control->evidencia !== ''): ?>
            <p class="mt-4 text-sm font-semibold text-texto"><?= e($vista->t('eval.evidencia_esperada')) ?></p>
            <p class="mt-1 text-sm text-texto-2"><?= e($control->evidencia) ?></p>
        <?php endif; ?>
    </div>

    <?php /* enctype multipart: sin él $_FILES llega vacío y el adjunto se
             pierde sin ningún error. Ver la nota de la tarjeta del panel. */ ?>
    <form method="post" action="<?= e($vista->url($base . '/controles/' . $control->id)) ?>"
          enctype="multipart/form-data" class="space-y-7">
        <?= $vista->campoToken() ?>
        <fieldset <?= $abierta ? '' : 'disabled' ?> class="space-y-7">

            <!-- Respuesta -->
            <div>
                <span class="block text-sm font-semibold text-texto"><?= e($vista->t('eval.respuesta')) ?></span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <?php foreach ($estados as $opcion): ?>
                        <label class="cursor-pointer rounded-rv border border-borde px-4 py-2 text-sm font-medium text-texto-2 transition hover:border-primario has-[:checked]:border-primario has-[:checked]:bg-primario/10">
                            <input type="radio" name="estado" value="<?= e($opcion) ?>" class="sr-only"
                                <?= $evaluacion?->estado === $opcion ? 'checked' : '' ?>>
                            <?= e($etiquetaEstado[$opcion] ?? $opcion) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errores['estado'])): ?>
                    <p class="mt-1.5 text-sm text-bad"><?= e($errores['estado']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Madurez -->
            <div>
                <label for="madurez" class="block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.nivel_madurez')) ?>
                </label>
                <select id="madurez" name="madurez"
                        class="mt-1.5 w-full rounded-rv border px-3.5 py-2.5 text-texto outline-none transition <?= isset($errores['madurez']) ? 'border-bad' : 'border-borde focus:border-primario' ?>">
                    <option value=""><?= e($vista->t('eval.sin_calificar')) ?></option>
                    <?php foreach ($escala as $nivel): ?>
                        <option value="<?= e((string) $nivel['nivel']) ?>"
                            <?= $evaluacion?->madurez === $nivel['nivel'] ? 'selected' : '' ?>>
                            <?= e((string) $nivel['nivel']) ?> — <?= e($nivel['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['madurez'])): ?>
                    <p class="mt-1.5 text-sm text-bad"><?= e($errores['madurez']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Criterio -->
            <div>
                <label for="criterio" class="block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.criterio_comprobacion')) ?>
                </label>
                <select id="criterio" name="criterio"
                        class="mt-1.5 rv-hundido w-full rounded-rv border border-borde px-3.5 py-2.5 text-texto outline-none transition focus:border-primario">
                    <option value=""><?= e($vista->t('eval.ninguno')) ?></option>
                    <?php foreach ($criterios as $opcion): ?>
                        <option value="<?= e($opcion) ?>" <?= $evaluacion?->criterio === $opcion ? 'selected' : '' ?>>
                            <?= e($etiquetaCriterio[$opcion] ?? $opcion) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dimensiones CID -->
            <div>
                <span class="block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.que_compromete')) ?>
                </span>
                <div class="mt-2 flex flex-wrap gap-2">
                    <?php
                    $dimensiones = [
                        'confidencialidad' => [$vista->t('eval.confidencialidad'), $evaluacion?->afectaConfidencialidad],
                        'integridad'       => [$vista->t('eval.integridad'),       $evaluacion?->afectaIntegridad],
                        'disponibilidad'   => [$vista->t('eval.disponibilidad'),   $evaluacion?->afectaDisponibilidad],
                    ];
                    ?>
                    <?php foreach ($dimensiones as $campo => [$etiqueta, $marcada]): ?>
                        <label class="cursor-pointer rounded-rv border border-borde px-4 py-2 text-sm font-medium text-texto-2 transition hover:border-primario has-[:checked]:border-primario has-[:checked]:bg-primario/10">
                            <input type="checkbox" name="<?= e($campo) ?>" value="1" class="sr-only"
                                <?= $marcada ? 'checked' : '' ?>>
                            <?= e($etiqueta) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Impacto y probabilidad -->
            <div class="grid gap-5 sm:grid-cols-2">
                <?php foreach (['impacto' => $vista->t('eval.impacto'), 'probabilidad' => $vista->t('eval.probabilidad')] as $campo => $etiqueta): ?>
                    <div>
                        <label for="<?= e($campo) ?>" class="block text-sm font-semibold text-texto">
                            <?= e($etiqueta) ?> (1 a 5)
                        </label>
                        <select id="<?= e($campo) ?>" name="<?= e($campo) ?>"
                                class="mt-1.5 w-full rounded-rv border px-3.5 py-2.5 text-texto outline-none transition <?= isset($errores[$campo]) ? 'border-bad' : 'border-borde focus:border-primario' ?>">
                            <option value="">—</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= e((string) $i) ?>"
                                    <?= ($campo === 'impacto' ? $evaluacion?->impacto : $evaluacion?->probabilidad) === $i ? 'selected' : '' ?>>
                                    <?= e((string) $i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <?php if (isset($errores[$campo])): ?>
                            <p class="mt-1.5 text-sm text-bad"><?= e($errores[$campo]) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($evaluacion?->nivelRiesgo !== null): ?>
                <p class="rounded-rv-lg border border-borde bg-elevado px-4 py-3 text-sm text-texto-2">
                    <?= e($vista->t('eval.nivel_riesgo_reg')) ?>
                    <strong class="text-texto tabular"><?= e(number_format($evaluacion->nivelRiesgo, 2)) ?></strong>
                    <?= e($vista->t('eval.promedio_impacto')) ?>
                </p>
            <?php endif; ?>

            <!-- Hallazgo y recomendación -->
            <div>
                <label for="hallazgo" class="block text-sm font-semibold text-texto"><?= e($vista->t('eval.hallazgo')) ?></label>
                <textarea id="hallazgo" name="hallazgo" rows="4"
                          class="mt-1.5 rv-hundido w-full rounded-rv border border-borde px-3.5 py-2.5 text-texto outline-none transition focus:border-primario"><?= e($evaluacion?->hallazgo ?? '') ?></textarea>
            </div>

            <div>
                <label for="recomendacion" class="block text-sm font-semibold text-texto"><?= e($vista->t('eval.recomendacion')) ?></label>
                <textarea id="recomendacion" name="recomendacion" rows="3"
                          class="mt-1.5 rv-hundido w-full rounded-rv border border-borde px-3.5 py-2.5 text-texto outline-none transition focus:border-primario"><?= e($evaluacion?->recomendacion ?? '') ?></textarea>
            </div>

            <?php
            /*
             * La evidencia, al FINAL: es lo que respalda todo lo anterior, así
             * que se llena cuando ya se sabe qué se está respaldando. Antes iba
             * entre el criterio y las dimensiones, partiendo en dos la
             * valoración. Mismo orden que la tarjeta del panel — las dos
             * pantallas recorren el mismo control y verlo ordenado distinto
             * según desde dónde se abra obliga a reaprender la ficha.
             *
             * Dentro: descripción -> calidad -> adjunto. La calidad CALIFICA la
             * descripción y por eso la sigue; el adjunto es lo opcional y
             * cierra. La pareja descripción + calidad es la que ISO/IEC 27007
             * exige para poder decir «Si».
             */
            ?>
            <div data-requiere-evidencia="<?= $evaluacion?->estado === 'SI' ? '1' : '0' ?>">
                <label for="evidencia" class="block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.evidencia_verificada')) ?>
                    <span class="font-normal text-texto-2">(<?= e($vista->t('eval.estado_si')) ?>)</span>
                </label>
                <p class="mt-1 text-xs text-texto-2"><?= e($vista->t('eval.evidencia_ayuda')) ?></p>
                <textarea id="evidencia" name="evidencia" rows="3"
                          class="mt-1.5 w-full rounded-rv border px-3.5 py-2.5 text-texto outline-none transition <?= isset($errores['evidencia']) ? 'border-bad' : 'border-borde focus:border-primario' ?>"><?= e($evaluacion?->evidenciaVerificada ?? '') ?></textarea>
                <?php if (isset($errores['evidencia'])): ?>
                    <p class="mt-1.5 text-sm text-bad"><?= e($errores['evidencia']) ?></p>
                <?php endif; ?>

                <label for="calidad" class="mt-3 block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.calidad_evidencia')) ?>
                </label>
                <select id="calidad" name="calidad"
                        class="mt-1.5 w-full rounded-rv border px-3.5 py-2.5 text-texto outline-none transition <?= isset($errores['calidad']) ? 'border-bad' : 'border-borde focus:border-primario' ?>">
                    <option value=""><?= e($vista->t('eval.ninguno')) ?></option>
                    <?php foreach ($etiquetaCalidad as $valor => $etiqueta): ?>
                        <option value="<?= e($valor) ?>" <?= $evaluacion?->calidadEvidencia === $valor ? 'selected' : '' ?>>
                            <?= e($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['calidad'])): ?>
                    <p class="mt-1.5 text-sm text-bad"><?= e($errores['calidad']) ?></p>
                <?php endif; ?>
                <?php
                /*
                 * El adjunto. El MISMO componente que pinta la tarjeta del
                 * panel —la otra cara de este endpoint—, así que los `name` no
                 * pueden separarse: viven en un solo archivo.
                 */
                ?>
                <?= $vista->componente('campo-archivo-evidencia', [
                    'vista'           => $vista,
                    'idAuditoria'     => $auditoria->id,
                    'codigoControl'   => $control->id,
                    'archivo'         => $archivo,
                    'limiteArchivoMb' => $limiteArchivoMb,
                    'error'           => $errores['archivo'] ?? null,
                ]) ?>

            </div>

            <?php if ($abierta): ?>
            <div class="flex flex-wrap gap-3">
                <button type="submit"
                        class="rv-extruido rv-interactivo rounded-rv bg-primario px-5 py-2.5 text-sm font-semibold text-primario-texto">
                    <?= e($vista->t('eval.guardar')) ?>
                </button>
                <?php if ($vecinos['siguiente'] !== null): ?>
                    <button type="submit" name="siguiente" value="1"
                            class="rounded-rv border border-borde px-5 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                        <?= e($vista->t('eval.guardar_siguiente')) ?>
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </fieldset>
    </form>

    <nav class="mt-10 flex justify-between border-t border-borde pt-5 text-sm">
        <?php if ($vecinos['anterior'] !== null): ?>
            <a href="<?= e($vista->url($base . '/controles/' . $vecinos['anterior']->id)) ?>"
               class="text-primario hover:underline">← <?= e($vecinos['anterior']->id) ?></a>
        <?php else: ?><span></span><?php endif; ?>

        <?php if ($vecinos['siguiente'] !== null): ?>
            <a href="<?= e($vista->url($base . '/controles/' . $vecinos['siguiente']->id)) ?>"
               class="text-primario hover:underline"><?= e($vecinos['siguiente']->id) ?> →</a>
        <?php endif; ?>
    </nav>
</section>
