<?php

declare(strict_types=1);

/**
 * Un control dentro de una auditoría: enunciado y captura, en la misma tarjeta.
 *
 * Es la gemela de `tarjeta-control` —la del instrumento público— y a propósito:
 * el auditor recorre las dos, y que la misma pregunta cambie de forma según
 * desde dónde se mire lo obliga a reaprender la pantalla. Mismo contorno, mismo
 * borde izquierdo teñido, mismos badges de norma, mismo enunciado en la voz de
 * títulos, misma fila de captura debajo.
 *
 * La diferencia está debajo del capó, y no se ve:
 *
 *   - El instrumento es una herramienta pública sin sesión. Sus campos no
 *     tienen `name`, no hay formulario, y lo que se teclea vive en localStorage
 *     hasta que el navegador lo olvide.
 *   - Aquí cada tarjeta es un FORMULARIO de verdad, con su token y su POST a
 *     `/evaluacion/{id}/controles/{codigo}`, que es el mismo endpoint que usa
 *     la pantalla de un solo control. Los `name=` son los que lee
 *     `AuditoriaController::leerRespuesta()`: no los cambie sin actualizar ese
 *     método y `evaluacion/control.php`, que comparten los tres.
 *
 * Se guarda SIN salir de la página. Con guion, `mostrar.php` intercepta el
 * envío, lo manda por fetch y sustituye esta tarjeta por la que devuelve el
 * servidor —así el resumen, el borde y la pastilla los sigue pintando PHP, y no
 * hay una segunda copia de esa lógica en JavaScript—. Sin guion, el formulario
 * se envía solo y el controlador redirige de vuelta a `/evaluacion/{id}` con el
 * ancla de esta tarjeta: más lento, pero tampoco se va a ninguna otra página.
 *
 * Los campos que el instrumento no tiene —impacto, probabilidad, evidencia
 * revisada y su calidad— están aquí porque el servidor los exige: sin evidencia
 * un «Sí» no se puede guardar (ISO/IEC 27007 y `ck_evalctrl_evidencia_si`), y
 * sin impacto y probabilidad no hay matriz de riesgo.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Control $control
 * @var \App\Models\Entidades\EvaluacionControl|null $evaluacion
 * @var int    $idAuditoria
 * @var bool   $abierta   ¿La auditoría admite cambios?
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 * @var array<string, string> $errores   Del último intento fallido de ESTE control.
 * @var array<string, mixed>  $valores   Lo que se envió en ese intento.
 * @var string|null $claveDominio  Para que el guion sepa a qué contador suma.
 * @var \App\Models\Entidades\ArchivoEvidencia|null $archivo
 *      El adjunto YA GUARDADO de este control, si lo hay. Solo la ficha: el
 *      binario se pide aparte, al abrirlo.
 * @var int|null $limiteArchivo  Tope real de subida en bytes, ya cruzado con php.ini.
 */
$evaluacion = $evaluacion ?? null;
$archivo    = $archivo ?? null;
$errores    = $errores ?? [];
$valores    = $valores ?? [];
$escala     = $escala ?? [];
$claveDominio = $claveDominio ?? null;

/*
 * De dónde sale lo que se pinta en cada campo: del intento fallido si lo hubo,
 * y si no, de lo guardado. Volver del error con el formulario repoblado desde
 * la base borraría lo que el auditor acababa de escribir, que es justo lo que
 * tiene que corregir.
 */
$hayIntento = $valores !== [];

$valor = static function (string $campo, mixed $guardado) use ($valores, $hayIntento): mixed {
    return $hayIntento ? ($valores[$campo] ?? null) : $guardado;
};

$vEstado       = $valor('estado', $evaluacion?->estado);
$vMadurez      = $valor('madurez', $evaluacion?->madurez);
$vCriterio     = $valor('criterio', $evaluacion?->criterio);
$vImpacto      = $valor('impacto', $evaluacion?->impacto);
$vProbabilidad = $valor('probabilidad', $evaluacion?->probabilidad);
$vEvidencia    = $valor('evidencia', $evaluacion?->evidenciaVerificada);
$vCalidad      = $valor('calidad', $evaluacion?->calidadEvidencia);
$vHallazgo     = $valor('hallazgo', $evaluacion?->hallazgo);
$vRecomendacion = $valor('recomendacion', $evaluacion?->recomendacion);

/*
 * Estado → tono de la escala semántica (§4). 'NA' va en gris neutro y nunca en
 * verde: teñir de verde un control excluido inflaría visualmente el
 * cumplimiento, cuando en realidad sale del denominador.
 *
 * La pastilla y el borde se leen de lo GUARDADO, no del intento: mientras haya
 * un error en pantalla, la tarjeta sigue valiendo lo que vale en la base.
 */
$tonos = [
    'SI' => ['ok',  $vista->t('eval.estado_si')],
    'NO' => ['bad', $vista->t('eval.estado_no')],
    'NA' => ['na',  $vista->t('eval.estado_na')],
];

$tono = $tonos[$evaluacion?->estado ?? ''] ?? null;

$bordeIzquierdo = match ($evaluacion?->estado) {
    'SI'    => 'border-l-ok',
    'NO'    => 'border-l-bad',
    'NA'    => 'border-l-na',
    default => 'border-l-borde',
};

$estados = [
    'SI' => $vista->t('eval.estado_si'),
    'NO' => $vista->t('eval.estado_no'),
    'NA' => $vista->t('eval.estado_na'),
];

$criterios = [
    'DOCUMENTADO' => $vista->t('eval.criterio_documentado'),
    'REPETIBLE'   => $vista->t('eval.criterio_repetible'),
    'EVIDENCIA'   => $vista->t('eval.criterio_evidencia'),
];

$calidades = [
    'BIEN_IMPLEMENTADO' => $vista->t('eval.calidad_bien'),
    'REQUIERE_MEJORA'   => $vista->t('eval.calidad_mejora'),
    'DECLARATIVO'       => $vista->t('eval.calidad_declarativo'),
];

/*
 * Las tres dimensiones con su letra, igual que en el instrumento: cuadros de
 * 36 px con la inicial, no tres casillas con su nombre. Cabe en una columna de
 * la fila de captura y es la misma pieza que el auditor ya conoce.
 */
$dimensiones = [
    ['campo' => 'confidencialidad', 'letra' => 'C', 'etiqueta' => $vista->t('eval.confidencialidad'), 'guardado' => (bool) $evaluacion?->afectaConfidencialidad],
    ['campo' => 'integridad',       'letra' => 'I', 'etiqueta' => $vista->t('eval.integridad'),       'guardado' => (bool) $evaluacion?->afectaIntegridad],
    ['campo' => 'disponibilidad',   'letra' => 'D', 'etiqueta' => $vista->t('eval.disponibilidad'),   'guardado' => (bool) $evaluacion?->afectaDisponibilidad],
];

// Clases de los <select> y <textarea>, con su variante en rojo. Se declaran una
// vez porque son siete campos y copiarlas era garantizar que uno se quedara sin
// el estado de error.
$campo = static fn (bool $mal): string =>
    'rv-hundido mt-1.5 w-full rounded-rv border bg-superficie px-3 py-2 text-sm text-texto outline-none transition '
    . ($mal
        ? 'border-bad focus:border-bad focus:ring-1 focus:ring-bad'
        : 'border-borde focus:border-primario focus:ring-1 focus:ring-primario');

/** El mensaje de error de un campo, o nada. */
$error = static function (string $clave) use ($errores): string {
    return isset($errores[$clave])
        ? '<p class="mt-1.5 text-sm text-bad">' . e($errores[$clave]) . '</p>'
        : '';
};

// Sufijo de los id= : hay 75 tarjetas en la página y un id repetido rompe la
// asociación de cada <label> con su campo.
$suf = $control->id;

$riesgo = $evaluacion?->nivelRiesgoCalculado();

/*
 * El tope que se ANUNCIA es el que el servidor va a respetar de verdad: el
 * controlador ya cruzó el máximo de la aplicación con lo que permite este PHP
 * (ver docker/php.ini). Prometer 5 MB donde el servidor corta en 2 es mandar al
 * auditor a un error evitable.
 */
$limiteArchivoMb = number_format(($limiteArchivo ?? \App\Models\Entidades\ArchivoEvidencia::MAXIMO_BYTES) / (1024 * 1024), 1, ',', '');
?>
<article id="control-<?= e($control->id) ?>"
         data-control="<?= e($control->id) ?>"
         data-dominio-control="<?= e((string) $claveDominio) ?>"
         class="rv-extruido overflow-hidden rounded-rv-lg border border-l-4 border-borde <?= e($bordeIzquierdo) ?> bg-superficie scroll-mt-24">

    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 p-5 pb-0 sm:p-6 sm:pb-0">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rv-badge-norma"><?= e($control->id) ?></span>
            <span class="rv-badge-norma"><?= e($control->iso) ?></span>
        </div>

        <?= $tono === null
            ? pill('na', $vista->t('eval.sin_responder'))
            : pill($tono[0], $tono[1]) ?>
    </div>

    <h5 class="rv-titulo mt-3 px-5 text-[1.1rem] font-semibold leading-relaxed text-texto sm:px-6">
        <?= e($control->enunciado) ?>
    </h5>

    <p class="mt-2.5 px-5 text-sm leading-relaxed text-texto-2 sm:px-6">
        <span class="font-semibold text-texto-2"><?= e($vista->t('eval.evidencia_esperada')) ?>:</span>
        <?= e($control->evidencia) ?>
    </p>

    <?php
    /*
     * enctype multipart: sin él el navegador manda solo el NOMBRE del archivo y
     * $_FILES llega vacío, sin ningún error — el adjunto se pierde en silencio.
     * El fetch de mostrar.php no lo necesita (FormData ya elige multipart al
     * ver un archivo), pero el envío sin guion sí, y las dos vías tienen que
     * guardar lo mismo.
     */
    ?>
    <form method="post"
          action="<?= e($vista->url('evaluacion/' . $idAuditoria . '/controles/' . $control->id)) ?>"
          enctype="multipart/form-data"
          data-form-control="<?= e($control->id) ?>">
        <?= $vista->campoToken() ?>
        <?php
        /*
         * `origen` es lo que le dice al controlador a dónde volver cuando no hay
         * guion: al panel con el ancla de esta tarjeta, y no a la pantalla del
         * control. Sin él, el mismo endpoint no puede distinguir de cuál de las
         * dos pantallas viene el envío.
         */
        ?>
        <input type="hidden" name="origen" value="panel">

        <?php /* Una auditoría finalizada se lee, no se toca: el fieldset
                 deshabilitado apaga los campos sin esconderlos. */ ?>
        <?php
        /*
         * Todo lo que se RELLENA va sobre `bg-elevado`, separado por una línea
         * de lo que solo se lee. Es el mismo corte que el instrumento hace con
         * la línea de su fila de captura, aquí subrayado con un cambio de tono
         * porque en esta pantalla la mitad de abajo es cuatro veces más alta.
         *
         * El tono lo pone la REGIÓN, no esta vista: en `/evaluacion/{id}` es el
         * pergamino elevado del instrumento (#F0EBDE, rv-oro) y coincide con el
         * lienzo de la página — igual que allá, y no es un problema: lo que
         * separa la banda del fondo es el contorno de la tarjeta, y lo que la
         * separa del enunciado es el contraste con el `bg-superficie` de
         * arriba, que es el corte que esta franja existe para marcar.
         */
        ?>
        <fieldset <?= $abierta ? '' : 'disabled' ?>
                  class="mt-5 border-t border-borde bg-elevado p-5 sm:p-6">

            <?php
            /*
             * El orden de la captura sigue el orden en que se AUDITA, y no el
             * de las columnas de la tabla:
             *
             *   1. la respuesta y su nota   — qué se concluye
             *   2. el riesgo                — cuánto pesa si falla
             *   3. el hallazgo              — qué se observó
             *   4. la evidencia             — con qué se respalda
             *
             * Antes la evidencia partía la ficha por la mitad, entre la nota y
             * el hallazgo, y las tres entradas del riesgo estaban repartidas por
             * los dos extremos: las dimensiones C/I/D arriba del todo y el
             * impacto y la probabilidad al final, cuando las tres alimentan la
             * misma matriz.
             */
            ?>

            <?php /* 1. La respuesta. Primero porque manda sobre el resto: un
                     «No aplica» deja sin sentido la nota y la evidencia, y un
                     «Sí» obliga a la evidencia. Antes cerraba la fila por la
                     derecha, que es el último sitio donde se mira. */ ?>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

                <fieldset>
                    <legend class="text-xs font-medium text-texto-2"><?= e($vista->t('eval.respuesta')) ?></legend>
                    <?php /* Cubeta hundida con las tres teclas dentro: el hundido
                             dice «aquí se recibe algo» y da a las teclas dónde
                             apoyarse. Igual que en el instrumento. */ ?>
                    <div class="rv-hundido mt-1.5 grid grid-cols-3 gap-1.5 rounded-rv bg-primario/10 p-1.5">
                        <?php foreach ($estados as $clave => $etiqueta): ?>
                            <?php
                            $resalte = match ($clave) {
                                'SI' => 'peer-checked:border-ok peer-checked:text-ok',
                                'NO' => 'peer-checked:border-bad peer-checked:text-bad',
                                default => 'peer-checked:border-na peer-checked:text-na',
                            };
                            ?>
                            <label class="cursor-pointer">
                                <input type="radio"
                                       class="peer sr-only"
                                       name="estado"
                                       value="<?= e($clave) ?>"
                                       <?= $vEstado === $clave ? 'checked' : '' ?>>
                                <?php /* El borde base es transparente para que al
                                         marcar aparezca el contorno sin mover el
                                         layout. */ ?>
                                <span class="rv-opcion block rounded-md border border-transparent bg-superficie px-1 py-1.5 text-center text-xs font-semibold text-texto-2 <?= e($resalte) ?> peer-focus-visible:ring-2 peer-focus-visible:ring-primario">
                                    <?= e($etiqueta) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= $error('estado') ?>
                </fieldset>

                <div>
                    <label for="madurez-<?= e($suf) ?>" class="block text-xs font-medium text-texto-2">
                        <?= e($vista->t('eval.nivel_madurez')) ?>
                    </label>
                    <select id="madurez-<?= e($suf) ?>" name="madurez"
                            class="<?= e($campo(isset($errores['madurez']))) ?>">
                        <option value=""><?= e($vista->t('eval.sin_calificar')) ?></option>
                        <?php foreach ($escala as $nivel): ?>
                            <option value="<?= e((string) $nivel['nivel']) ?>"
                                <?= (string) $vMadurez === (string) $nivel['nivel'] ? 'selected' : '' ?>>
                                <?= e((string) $nivel['nivel']) ?> — <?= e($nivel['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $error('madurez') ?>
                </div>

                <div>
                    <label for="criterio-<?= e($suf) ?>" class="block text-xs font-medium text-texto-2">
                        <?= e($vista->t('eval.criterio_comprobacion')) ?>
                    </label>
                    <select id="criterio-<?= e($suf) ?>" name="criterio"
                            class="<?= e($campo(isset($errores['criterio']))) ?>">
                        <option value=""><?= e($vista->t('eval.ninguno')) ?></option>
                        <?php foreach ($criterios as $clave => $etiqueta): ?>
                            <option value="<?= e($clave) ?>" <?= $vCriterio === $clave ? 'selected' : '' ?>>
                                <?= e($etiqueta) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $error('criterio') ?>
                </div>
            </div>

            <?php
            /*
             * 2. El riesgo, sus tres entradas JUNTAS. Las dimensiones dicen qué
             * se compromete y el impacto y la probabilidad cuánto pesa: es la
             * misma pregunta, y ahora se responde sin cruzar la ficha. El
             * impacto y la probabilidad van además emparejados porque el nivel
             * es su promedio: una sola no permite calcularlo y el controlador
             * rechaza la pareja a medias.
             *
             * La cifra de la derecha es la ya GUARDADA — la nueva la calcula el
             * servidor al guardar, no esta vista.
             */
            ?>
            <div class="mt-4 flex flex-wrap items-end gap-4">
                <fieldset>
                    <legend class="text-xs font-medium text-texto-2"><?= e($vista->t('eval.dimensiones')) ?></legend>
                    <div class="mt-1.5 flex gap-1.5">
                        <?php foreach ($dimensiones as $dimension): ?>
                            <?php $marcada = (bool) $valor($dimension['campo'], $dimension['guardado']); ?>
                            <label class="cursor-pointer">
                                <input type="checkbox"
                                       class="peer sr-only"
                                       name="<?= e($dimension['campo']) ?>"
                                       value="1"
                                       <?= $marcada ? 'checked' : '' ?>
                                       aria-label="<?= e($dimension['etiqueta']) ?> — <?= e($control->id) ?>">
                                <span title="<?= e($dimension['etiqueta']) ?>"
                                      class="rv-opcion grid h-9 w-9 place-items-center rounded-rv border border-borde bg-superficie text-sm font-bold text-texto-2 peer-checked:border-primario peer-checked:bg-primario/10 peer-checked:text-primario peer-focus-visible:ring-2 peer-focus-visible:ring-primario peer-focus-visible:ring-offset-2">
                                    <?= e($dimension['letra']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <?php foreach ([
                    'impacto'      => $vista->t('eval.impacto'),
                    'probabilidad' => $vista->t('eval.probabilidad'),
                ] as $clave => $etiqueta): ?>
                    <div class="w-28">
                        <label for="<?= e($clave) ?>-<?= e($suf) ?>" class="block text-xs font-medium text-texto-2">
                            <?= e($etiqueta) ?> (1-5)
                        </label>
                        <select id="<?= e($clave) ?>-<?= e($suf) ?>" name="<?= e($clave) ?>"
                                class="<?= e($campo(isset($errores[$clave]))) ?>">
                            <option value="">—</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= e((string) $i) ?>"
                                    <?= (string) ($clave === 'impacto' ? $vImpacto : $vProbabilidad) === (string) $i ? 'selected' : '' ?>>
                                    <?= e((string) $i) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                <?php endforeach; ?>

                <?php if ($riesgo !== null): ?>
                    <p class="pb-2 text-xs text-texto-2">
                        <?= e($vista->t('eval.nivel_riesgo')) ?>:
                        <span class="tabular font-semibold text-texto"><?= e(number_format($riesgo, 1, ',', '')) ?></span>
                    </p>
                <?php endif; ?>
            </div>

            <?= $error('impacto') ?>
            <?= $error('probabilidad') ?>

            <?php /* 3. Lo observado y qué hacer con ello. */ ?>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="hallazgo-<?= e($suf) ?>" class="block text-xs font-medium text-texto-2">
                        <?= e($vista->t('eval.hallazgo')) ?>
                    </label>
                    <textarea id="hallazgo-<?= e($suf) ?>" name="hallazgo" rows="2"
                              placeholder="<?= e($vista->t('eval.hallazgo_marcador')) ?>"
                              class="<?= e($campo(false)) ?> leading-relaxed placeholder:text-texto-2"><?= e((string) ($vHallazgo ?? '')) ?></textarea>
                </div>
                <div>
                    <label for="recomendacion-<?= e($suf) ?>" class="block text-xs font-medium text-texto-2">
                        <?= e($vista->t('eval.recomendacion')) ?>
                    </label>
                    <textarea id="recomendacion-<?= e($suf) ?>" name="recomendacion" rows="2"
                              placeholder="<?= e($vista->t('eval.recomendacion_marcador')) ?>"
                              class="<?= e($campo(false)) ?> leading-relaxed placeholder:text-texto-2"><?= e((string) ($vRecomendacion ?? '')) ?></textarea>
                </div>
            </div>

            <?php
            /*
             * 4. La evidencia, al FINAL y en su propia caja hundida. Es lo que
             * respalda todo lo anterior, así que se llena cuando ya se sabe qué
             * se está respaldando; en medio de la ficha separaba la nota del
             * hallazgo y era, además, el bloque más alto de los cuatro.
             *
             * Dentro va descripción → calidad → adjunto: la calidad CALIFICA la
             * descripción y por eso la sigue — entre las dos se había colado la
             * zona de soltar, que es lo opcional y ahora cierra. La pareja
             * descripción + calidad es la que ISO/IEC 27007 exige para poder
             * decir «Sí»: sin ellas el guardado se rechaza, y la caja las señala
             * como un bloque y no como dos campos sueltos más.
             */
            ?>
            <div class="rv-hundido mt-4 rounded-rv border border-borde bg-primario/5 p-4">
                <label for="evidencia-<?= e($suf) ?>" class="flex flex-wrap items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-texto-2">
                    <?= icono('documento', 'h-3.5 w-3.5') ?>
                    <?= e($vista->t('eval.evidencia_verificada')) ?>
                    <span class="normal-case tracking-normal text-texto-2">(<?= e($vista->t('eval.estado_si')) ?>)</span>
                </label>
                <textarea id="evidencia-<?= e($suf) ?>" name="evidencia" rows="2"
                          placeholder="<?= e($vista->t('eval.evidencia_marcador')) ?>"
                          class="<?= e($campo(isset($errores['evidencia']))) ?> leading-relaxed placeholder:text-texto-2"><?= e((string) ($vEvidencia ?? '')) ?></textarea>
                <?= $error('evidencia') ?>

                <label for="calidad-<?= e($suf) ?>" class="mt-3 block text-xs font-medium text-texto-2">
                    <?= e($vista->t('eval.calidad_evidencia')) ?>
                </label>
                <select id="calidad-<?= e($suf) ?>" name="calidad"
                        class="<?= e($campo(isset($errores['calidad']))) ?>">
                    <option value=""><?= e($vista->t('eval.ninguno')) ?></option>
                    <?php foreach ($calidades as $clave => $etiqueta): ?>
                        <option value="<?= e($clave) ?>" <?= $vCalidad === $clave ? 'selected' : '' ?>>
                            <?= e($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $error('calidad') ?>

                <?php
                /*
                 * El adjunto, en un componente compartido con la pantalla de un
                 * solo control: las dos mandan al mismo endpoint y con los
                 * mismos `name`, así que tenerlo dos veces era garantizar que un
                 * día una de las dos se quedara atrás.
                 *
                 * Acompaña a la descripción, no la sustituye: lo que ISO/IEC
                 * 27007 exige para un «Sí» es que conste QUÉ se revisó, y eso lo
                 * dice el texto. Por eso el archivo es opcional incluso ahí —
                 * hay evidencia que es una entrevista o una observación en sitio
                 * y no tiene archivo que adjuntar.
                 */
                ?>
                <?= $vista->componente('campo-archivo-evidencia', [
                    'vista'           => $vista,
                    'idAuditoria'     => $idAuditoria,
                    'codigoControl'   => $control->id,
                    'archivo'         => $archivo,
                    'limiteArchivoMb' => $limiteArchivoMb,
                    'error'           => $errores['archivo'] ?? null,
                    'compacto'        => true,
                ]) ?>
            </div>

            <?php
            /*
             * La misma fricción deliberada que el instrumento: un «no aplica»
             * sin justificación escrita sale del denominador del cumplimiento y,
             * sin este aviso, sería la salida fácil para inflar el resultado.
             */
            ?>
            <?php if ($evaluacion?->estado === 'NA' && $evaluacion->hallazgo === null): ?>
                <p class="mt-3 flex items-start gap-2 rounded-rv border border-warn/10 bg-warn/10 px-3 py-2 text-xs font-medium text-warn">
                    <?= icono('alerta', 'h-4 w-4 shrink-0') ?>
                    <span><?= e($vista->t('eval.aviso_na_sin_justificar')) ?></span>
                </p>
            <?php endif; ?>

            <?php if ($abierta): ?>
                <div class="-mx-5 -mb-5 mt-5 flex items-center justify-end gap-3 border-t border-borde bg-superficie px-5 py-3.5 sm:-mx-6 sm:-mb-6 sm:px-6">
                    <?php
                    /*
                     * El acuse de guardado. Nace oculto y lo enciende el guion
                     * al recibir la respuesta: sin guion no hay nada que
                     * anunciar aquí porque la página se recarga entera y el
                     * mensaje sale arriba, en la franja de siempre.
                     *
                     * aria-live para que un lector de pantalla lo anuncie sin
                     * que el foco tenga que ir hasta él: quien guarda con el
                     * teclado se queda en el botón.
                     */
                    ?>
                    <p data-acuse hidden aria-live="polite"
                       class="flex items-center gap-1.5 text-xs font-semibold text-ok">
                        <?= icono('check', 'h-4 w-4') ?>
                        <span><?= e($vista->t('eval.guardado')) ?></span>
                    </p>

                    <button type="submit"
                            class="rv-extruido-sm rv-interactivo-sm rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
                        <?= e($vista->t('eval.guardar')) ?>
                    </button>
                </div>
            <?php endif; ?>
        </fieldset>
    </form>
</article>
