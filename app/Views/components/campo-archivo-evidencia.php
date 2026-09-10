<?php

declare(strict_types=1);

/**
 * El adjunto de la evidencia de un control: estado, zona de soltar y error.
 *
 * Es UN componente y no dos copias porque lo pintan las dos pantallas que
 * capturan —la tarjeta del panel y `/evaluacion/{id}/controles/{codigo}`— y
 * las dos mandan al MISMO endpoint. Los `name` (`archivo` y `quitar_archivo`)
 * son los que lee `AuditoriaController::leerRespuesta()`: mientras vivan aquí,
 * no hay manera de cambiarlos en una pantalla y no en la otra.
 *
 * Tres cosas que la zona resuelve sin JavaScript, porque la pieza es un <label>
 * que envuelve al campo:
 *
 *   - Pulsar en cualquier punto abre el diálogo de archivos. Eso es lo que hace
 *     un label sobre su campo; no hace falta un `onclick`.
 *   - El campo va en `.sr-only`, o sea oculto a la VISTA pero no al lector de
 *     pantalla ni al teclado: se tabula hasta él y se abre con la barra
 *     espaciadora como cualquier otro campo de archivo.
 *   - El foco se dibuja alrededor de la zona (`:focus-within` en rivendel.css),
 *     que es lo que se ve; el anillo sobre el campo oculto no se vería.
 *
 * Lo único que necesita guion es ARRASTRAR: soltar un archivo sobre un elemento
 * y que acabe dentro del campo no tiene equivalente en HTML. Sin guion la zona
 * queda como un botón grande que abre el diálogo, y el nombre del archivo
 * elegido no se ve hasta guardar — que es cuando la tarjeta lo dice de todos
 * modos. Se pierde una comodidad, no una función.
 *
 * @var \App\Core\Vista $vista
 * @var int    $idAuditoria
 * @var string $codigoControl
 * @var \App\Models\Entidades\ArchivoEvidencia|null $archivo  El ya guardado.
 * @var string $limiteArchivoMb  Tope real, ya formateado ("5,0").
 * @var string|null $error
 * @var bool   $compacto  Dentro de una tarjeta del panel (75 en la página).
 */
$archivo  = $archivo ?? null;
$error    = $error ?? null;
$compacto = $compacto ?? false;

// Hay 75 tarjetas en el panel: un id repetido rompe la relación label/campo.
$suf = $codigoControl;

$rutaArchivo = 'evaluacion/' . $idAuditoria . '/controles/' . $codigoControl . '/evidencia';
?>
<div class="<?= $compacto ? 'mt-3 border-t border-borde/70 pt-3' : 'mt-4 border-t border-borde pt-4' ?>">

    <span class="flex flex-wrap items-center gap-1.5 <?= $compacto
        ? 'text-xs font-semibold uppercase tracking-wide text-texto-2'
        : 'text-sm font-semibold text-texto' ?>">
        <?= icono('importar', 'h-3.5 w-3.5') ?>
        <?= e($vista->t('eval.evidencia_archivo')) ?>
    </span>

    <?php
    /*
     * La línea de estado, SIEMPRE. Un campo de archivo vacío no distingue «no
     * hay ninguno» de «hay uno y el navegador no puede repoblarlo», y esa
     * ambigüedad es justo la que haría dudar de si la evidencia quedó
     * respaldada.
     */
    ?>
    <?php if ($archivo !== null): ?>
        <?php
        /*
         * El enlace se abre en otra pestaña: esta tarjeta puede llevar media
         * hora de trabajo sin guardar, y navegar fuera a ver una captura lo
         * perdería.
         */
        ?>
        <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
            <a href="<?= e($vista->url($rutaArchivo)) ?>" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 font-semibold text-primario hover:underline">
                <?= icono($archivo->esPdf() ? 'documento' : 'ojo', 'h-4 w-4') ?>
                <?= e($archivo->nombre) ?>
            </a>
            <span class="tabular text-texto-2"><?= e($archivo->tamanoLegible()) ?></span>
        </p>

        <label class="mt-2 inline-flex cursor-pointer items-center gap-2 <?= $compacto ? 'text-xs' : 'text-sm' ?> text-texto-2">
            <input type="checkbox" name="quitar_archivo" value="1"
                   class="h-3.5 w-3.5 rounded border-borde text-primario focus:ring-primario">
            <?= e($vista->t('eval.evidencia_archivo_quitar')) ?>
        </label>
    <?php else: ?>
        <p class="mt-2 text-sm text-texto-2"><?= e($vista->t('eval.evidencia_archivo_sin')) ?></p>
    <?php endif; ?>

    <?php
    /*
     * `accept` es una comodidad del diálogo de archivos y NADA más: quien
     * decide es el controlador, mirando el contenido con finfo. Un `accept` se
     * salta arrastrando el archivo o cambiando el filtro del diálogo.
     */
    ?>
    <label class="rv-soltar rv-hundido mt-2" data-soltar>
        <input type="file" id="archivo-<?= e($suf) ?>" name="archivo" class="sr-only"
               accept="image/png,image/jpeg,image/webp,image/gif,application/pdf"
               data-soltar-campo>

        <span class="block px-4 <?= $compacto ? 'py-4' : 'py-6' ?> text-center">
            <span class="mx-auto grid <?= $compacto ? 'h-9 w-9' : 'h-11 w-11' ?> place-items-center rounded-full bg-primario/10 text-primario">
                <?= icono('importar', $compacto ? 'h-4 w-4' : 'h-5 w-5') ?>
            </span>

            <span class="mt-2 block" data-soltar-pedir>
                <span class="block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.evidencia_archivo_soltar')) ?>
                </span>
                <span class="mt-0.5 block text-xs text-texto-2">
                    <?= e($vista->t('eval.evidencia_archivo_o')) ?>
                </span>
                <span class="mt-0.5 block text-sm font-semibold text-primario underline underline-offset-2">
                    <?= e($vista->t('eval.evidencia_archivo_examinar')) ?>
                </span>
            </span>

            <?php
            /*
             * El nombre del archivo recién elegido, que rellena el guion. Nace
             * oculto por CSS y no por un atributo: el estado de partida —sin
             * guion, o antes de que arranque— tiene que ser el correcto sin que
             * nadie lo ponga.
             */
            ?>
            <span class="mt-2 block break-all text-sm font-semibold text-texto" data-soltar-nombre></span>
        </span>
    </label>

    <?php
    /*
     * Los tipos y el tope, FUERA del recuadro. Dentro competían con la
     * invitación a soltar, que es lo único que la zona tiene que decir a
     * primera vista; aquí abajo es la letra pequeña que se consulta cuando algo
     * no entra. El tope que se anuncia es el que el servidor va a respetar de
     * verdad — ver limiteArchivoEvidencia() en el controlador.
     */
    ?>
    <p class="mt-1.5 text-xs text-texto-2">
        <?= e($vista->t(
            $archivo === null ? 'eval.evidencia_archivo_ayuda' : 'eval.evidencia_archivo_sustituir',
            $limiteArchivoMb,
        )) ?>
    </p>

    <?php if ($error !== null): ?>
        <p class="mt-1.5 text-sm text-bad"><?= e($error) ?></p>
    <?php endif; ?>
</div>
