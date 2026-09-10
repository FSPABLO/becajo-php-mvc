<?php

declare(strict_types=1);

/**
 * Detalle de una auditoría: encabezado, avance y los 75 controles.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var list<\App\Models\Entidades\Control> $controles
 * @var array<int, \App\Models\Entidades\Proceso> $procesos
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var array<string, int> $totalPorDominio
 * @var array<string, int> $respondidoPorDominio
 * @var array<string, \App\Models\Entidades\EvaluacionControl> $evaluaciones
 * @var array<string, \App\Models\Entidades\ArchivoEvidencia> $archivos
 *      Adjuntos de la evidencia, por código de control. Sin el binario.
 * @var int $limiteArchivo  Tope real de subida en bytes, ya cruzado con php.ini.
 * @var int $evaluados
 * @var int $total
 * @var list<\App\Models\Entidades\Usuario> $administradores
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores  Intento fallido, si lo hubo.
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 * @var string|null $controlConError  Cuál de las 75 tarjetas volvió con error.
 * @var array<string, string> $erroresControl
 * @var array<string, mixed>  $valoresControl
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$abierta = !$auditoria->estaFinalizada();
$porcentaje = $total > 0 ? round($evaluados / $total * 100) : 0;

/*
 * El encabezado editable parte de la auditoría, salvo que se venga de un
 * intento fallido: entonces manda lo que el auditor tecleó ($valores llega del
 * controlador con valoresGuardados()). Antes daba igual —eran un desplegable y
 * dos campos cortos— pero el entrevistado escrito a mano son dos textos libres,
 * y volver del error con el formulario en blanco es hacerlos teclear otra vez.
 *
 * El lado del formulario se deduce de la auditoría cuando no hay intento: una
 * abierta con el entrevistado a mano vuelve a abrirse en «a mano», no en el
 * desplegable con la persona equivocada preseleccionada.
 */
$valores = ($valores ?? []) + [
    'origen'        => $auditoria->entrevistadoEscritoAMano() ? 'manual' : 'registrado',
    'administrador' => (string) ($auditoria->idAdministradorBd ?? ''),
    'nombre'        => $auditoria->entrevistadoEscritoAMano() ? $auditoria->nombreAdministradorBd : '',
    'organizacion'  => $auditoria->entrevistadoEscritoAMano() ? $auditoria->organizacion : '',
    'area'          => $auditoria->areaEvaluada,
    'fecha'         => $auditoria->fecha,
];

/*
 * El ancho es el del INSTRUMENTO (max-w-7xl), no el max-w-5xl del resto del
 * módulo. Esta pantalla es hoy la misma lista de tarjetas, y a 5xl la fila de
 * cuatro datos del resumen se apretaba contra el enunciado. Dos páginas que
 * enseñan lo mismo y miden distinto se leen como dos diseños.
 */
?>
<?php
/*
 * LA MISMA REGIÓN QUE EL INSTRUMENTO: pergamino con el acento en oro
 * (`rv-claro rv-oro`) sobre lienzo `bg-elevado`. Las dos pantallas recorren la
 * misma batería de 75 controles y ya compartían forma y ancho; les faltaba
 * compartir color, que es lo primero que se nota al saltar de una a la otra.
 *
 * `rv-oro` NO es una paleta nueva: es `.rv-claro` con el acento repuntado, y
 * por eso las dos clases van juntas —sola no trae lienzo—. Ver rivendel.css.
 *
 * Y aquí resuelve lo mismo que resolvía allá, que es la razón por la que
 * existe: en el módulo la barra lateral ya es verde de noche, y esta pantalla
 * repetía el verde de marca RELLENO en el botón de guardar, en la cubeta de
 * las tres respuestas, en el carril del avance y en la caja de evidencia. Dos
 * verdes distintos a un palmo compiten sin decir nada. Con el acento en oro, el
 * único verde que queda en pantalla es el de la escala de estado —la pastilla
 * del control, el borde teñido, la tecla «Sí»—, que sí significa algo.
 *
 * Ninguna vista de dentro cambia: `bg-primario`, `ring-primario` y compañía
 * siguen diciendo «acento», y aquí ese acento es oro sin que se enteren. Es lo
 * mismo que pasó al invertir el lienzo del módulo.
 *
 * La franja va a TODO EL ANCHO y la caja centrada queda dentro: si el color
 * fuera del <section>, el lienzo del módulo asomaría por los lados.
 */
?>
<div class="rv-claro rv-oro bg-elevado">
<section class="mx-auto w-full max-w-7xl px-6 py-8 lg:px-8">

    <?php
    /*
     * Aquí iba un «← Mis auditorías». Se retiró: la miga de pan de la barra
     * superior ya enlaza a la lista desde cualquier pantalla que cuelgue de
     * ella, y dos vueltas atrás en la misma vista —una dentro del contenido,
     * otra fuera— obligan a decidir cuál de las dos es la buena.
     */
    ?>
    <?php
    /*
     * Portadilla, con la misma pieza que la del instrumento
     * (herramientas/parciales/encabezado-instrumento.php): distintivo de norma
     * arriba, título, subtítulo y una fila de losetas con las cifras. Las dos
     * pantallas recorren la misma batería de controles y hasta ahora se
     * presentaban distinto — allá una portada con datos, aquí tres líneas
     * sueltas y una barra de avance en una caja aparte.
     *
     * El avance vive DENTRO de la portadilla y ya no en su propia caja: es una
     * cifra de la auditoría como las otras cuatro, y en una tarjeta suya
     * empujaba los controles media pantalla hacia abajo.
     */
    ?>
    <header class="rv-extruido rv-relieve-pleno mb-6 rounded-rv-lg border border-borde bg-superficie p-6 sm:p-8">

        <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <?php /* Distintivo de norma: oro, porque el oro solo
                             significa referencia normativa. El mismo de la
                             portada del instrumento. */ ?>
                    <span class="rv-extruido-xs inline-flex items-center gap-2 rounded-full border border-oro/40 bg-oro-tinte px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wider text-oro-texto">
                        <?= icono('escudo', 'h-3.5 w-3.5') ?>
                        ISO/IEC 27002
                    </span>
                    <?= $abierta
                        ? pill('warn', $vista->t('eval.en_progreso'))
                        : pill('ok', $vista->t('eval.finalizada')) ?>
                </div>

                <h1 class="rv-titulo mt-4 text-3xl font-semibold leading-tight text-texto sm:text-4xl">
                    <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
                </h1>

                <p class="mt-2 text-texto-2">
                    <span class="font-semibold text-texto"><?= e($auditoria->organizacion) ?></span>
                    · <?= e($auditoria->areaEvaluada) ?>
                </p>
                <p class="mt-0.5 text-sm text-texto-2">
                    <?= e($auditoria->fecha) ?> · <?= e($vista->t('eval.entrevistado')) ?>
                    <?= e($auditoria->nombreAdministradorBd) ?>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <?php
                /*
                 * Las dos salidas de consulta, con el mismo botón elevado que
                 * las acciones del instrumento. «Finalizar» se queda relleno:
                 * es la única de las tres que cambia el estado de la auditoría.
                 */
                ?>
                <?php foreach ([
                    ['evaluacion/' . $auditoria->id . '/resultados', $vista->t('eval.ver_resultados'), 'tablero'],
                    ['evaluacion/' . $auditoria->id . '/remediaciones', $vista->t('eval.remediaciones'), 'documento'],
                ] as [$ruta, $etiqueta, $ico]): ?>
                    <a href="<?= e($vista->url($ruta)) ?>"
                       class="rv-extruido-sm rv-interactivo-sm inline-flex items-center gap-2 rounded-rv border border-borde bg-superficie px-3.5 py-2 text-sm font-semibold text-texto hover:border-primario">
                        <?= icono($ico, 'h-4 w-4 text-primario') ?>
                        <?= e($etiqueta) ?>
                    </a>
                <?php endforeach; ?>

                <?php if ($abierta): ?>
                    <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/finalizar')) ?>">
                        <?= $vista->campoToken() ?>
                        <button type="submit"
                                class="rv-extruido-sm rv-interactivo-sm rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
                            <?= e($vista->t('eval.finalizar')) ?>
                        </button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reabrir')) ?>">
                        <?= $vista->campoToken() ?>
                        <button type="submit"
                                class="rv-extruido-sm rv-interactivo-sm rounded-rv border border-borde bg-superficie px-4 py-2 text-sm font-semibold text-texto hover:border-primario">
                            <?= e($vista->t('eval.reabrir')) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php
        /*
         * Las losetas van sobre `bg-elevado`, que en esta región es el
         * pergamino elevado #F0EBDE —rv-oro le quita el tinte de menta que
         * tiene en el módulo—. La portada del instrumento las pone en blanco
         * porque su fondo es el lienzo, y aquí el fondo ya es la tarjeta. Sin
         * ese cambio de tono las cifras se perderían dentro de la caja.
         */
        $losetas = [
            [$vista->t('eval.dominios_palabra'), (string) count($dominios)],
            [$vista->t('eval.controles_palabra'), (string) $total],
            [$vista->t('eval.respondidos'), (string) $evaluados],
        ];
        ?>
        <dl class="mt-7 flex flex-wrap gap-3">
            <?php foreach ($losetas as [$etiqueta, $valor]): ?>
                <div class="rv-extruido-sm min-w-[7rem] rounded-rv bg-elevado px-4 py-2.5">
                    <dt class="text-xs uppercase tracking-wider text-texto-2"><?= e($etiqueta) ?></dt>
                    <dd class="tabular mt-0.5 text-lg font-bold text-texto"><?= e($valor) ?></dd>
                </div>
            <?php endforeach; ?>

            <?php
            /*
             * El avance es la loseta ancha, y la única con barra: es la cifra
             * que se mueve mientras se trabaja, y las otras tres son el tamaño
             * del instrumento, que no cambia.
             *
             * La plantilla del texto viaja en un data-* para que el guion la
             * rellene sin tener que saber español ni inglés: si armara la frase
             * concatenando, el panel en inglés diría «12 de 75 controles».
             */
            $plantillaAvance = '{n} ' . $vista->t('eval.de_controles') . ' {total} '
                . $vista->t('eval.controles_palabra') . ' ({pct}%)';
            ?>
            <div class="rv-extruido-sm flex min-w-[16rem] flex-1 flex-col justify-center rounded-rv bg-elevado px-4 py-2.5">
                <div class="flex items-baseline justify-between gap-3">
                    <dt class="text-xs uppercase tracking-wider text-texto-2"><?= e($vista->t('eval.avance')) ?></dt>
                    <dd class="tabular text-xs text-texto-2"
                        data-avance-cifra
                        data-plantilla="<?= e($plantillaAvance) ?>">
                        <?= e(str_replace(
                            ['{n}', '{total}', '{pct}'],
                            [(string) $evaluados, (string) $total, (string) $porcentaje],
                            $plantillaAvance,
                        )) ?>
                    </dd>
                </div>
                <?php /* El carril va HUNDIDO y en verde claro: es una pista
                         labrada con el avance apoyado dentro, el mismo par de
                         gestos que el medidor del monitor. */ ?>
                <div class="rv-hundido mt-2 h-2.5 w-full overflow-hidden rounded-full bg-primario/15">
                    <div class="h-full rounded-full bg-primario transition-[width] duration-300"
                         data-avance-barra
                         style="width: <?= e((string) $porcentaje) ?>%"></div>
                </div>
            </div>
        </dl>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if (!$abierta): ?>
        <p class="mb-6 rounded-rv-lg border border-borde bg-elevado px-4 py-3 text-sm text-texto-2">
            <?= e($vista->t('eval.aviso_finalizada')) ?>
        </p>
    <?php endif; ?>

    <?php
    /*
     * El encabezado editable, en la misma caja elevada que la
     * «Identificación de la consultoría» del instrumento: las dos son el
     * bloque de datos de la cabecera que se rellena antes de bajar a los
     * controles. Sigue siendo un <details> porque aquí ya está relleno y
     * casi nunca se toca; allá se llena en cada uso y por eso va abierto.
     *
     * El triángulo por omisión se retira (marker:hidden) y en su lugar va un
     * ícono nuestro que gira al abrir: el del navegador no responde a los
     * tokens y en pergamino se ve como una mota negra.
     */
    ?>
    <?php if ($abierta): ?>
    <details class="rv-extruido group mb-8 rounded-rv-lg border border-borde bg-superficie p-5"
             <?= $errores !== [] ? 'open' : '' ?>>
        <summary class="flex cursor-pointer list-none items-center gap-2.5 text-sm font-semibold uppercase tracking-wider text-texto marker:hidden">
            <span class="grid h-7 w-7 place-items-center rounded-rv bg-elevado text-primario transition group-open:rotate-90">
                <?= icono('flecha', 'h-4 w-4') ?>
            </span>
            <?= e($vista->t('eval.editar_encabezado')) ?>
        </summary>
        <?php
        /*
         * Ritmo corto (space-y-4 y no 5): son cuatro datos ya rellenos dentro
         * de un <details> que casi nunca se abre, no un formulario que se
         * recorre de arriba abajo. El alta (evaluacion/nueva) conserva el
         * ritmo largo a propósito: allá el formulario ES la pantalla.
         */
        ?>
        <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="mt-4 space-y-4">
            <?= $vista->campoToken() ?>
            <?= $vista->renderizar('evaluacion/_encabezado-form', compact('errores', 'valores', 'administradores')) ?>
            <button type="submit"
                    class="rv-extruido-sm rv-interactivo-sm rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
                <?= e($vista->t('eval.guardar_encabezado')) ?>
            </button>
        </form>
    </details>
    <?php endif; ?>

    <?php
    /*
     * Rótulo de la lista, con la misma voz que los de la portada del
     * instrumento: versalitas espaciadas y no un título grande. El título
     * grande de esta pantalla es «Auditoría 152»; un segundo del mismo
     * tamaño competiría con él.
     */
    ?>
    <h2 class="mb-4 flex items-center gap-3 text-sm font-semibold uppercase tracking-wider text-texto">
        <?= icono('documento', 'h-4 w-4 text-primario') ?>
        <?= e($vista->t('eval.controles_instrumento')) ?>
    </h2>

    <?php
    /*
     * La lista de controles tiene la MISMA forma que el instrumento
     * (herramientas/parciales/pestana-instrumento.php): tabs de dominio arriba
     * y, dentro de cada uno, la cabecera del dominio, los procesos como
     * subtítulos y una tarjeta por control.
     *
     * No es un capricho de estilo. El auditor trabaja con las dos pantallas a
     * la vez —el instrumento es la referencia y esto es la captura de la misma
     * batería de controles—, y hasta ahora una era tarjetas y la otra una tabla
     * de cinco columnas: el mismo C-014, con el mismo enunciado, cambiaba de
     * sitio y de aspecto según desde dónde se mirara. Con la tabla, además, el
     * enunciado iba recortado a 110 caracteres y el proceso se repetía en cada
     * fila en vez de encabezar su grupo.
     *
     * Lo que aquí NO se copia del instrumento son los campos de captura — ver
     * la cabecera de components/tarjeta-control-auditoria.
     *
     * Las agrupaciones se arman igual que en herramientas/instrumento-bd.php,
     * a partir de $procesos y $controles, que el controlador ya trae.
     */
    $procesosPorDominio = [];

    foreach ($procesos as $proceso) {
        $procesosPorDominio[$proceso->dominio][] = $proceso;
    }

    $controlesPorProceso = [];

    foreach ($controles as $control) {
        $controlesPorProceso[$control->proceso][] = $control;
    }

    /*
     * $dominioActivo es el primero de la lista, igual que en tabs-dominios.php
     * (el primer tab nace seleccionado). Las secciones de los demás dominios
     * salen ya marcadas hidden desde el servidor para que no haya parpadeo al
     * cargar, y el guion de más abajo se limita a alternar esa marca.
     */
    $dominioActivo = $dominios[0]->clave ?? null;

    /*
     * Salvo que una tarjeta haya vuelto con un error: entonces manda su
     * dominio. Devolver el intento con la pestaña equivocada abierta deja el
     * mensaje de error hablando de un control que no está a la vista, y el
     * ancla de la redirección apuntando a algo oculto.
     */
    if ($controlConError !== null) {
        foreach ($controles as $conError) {
            if ($conError->id === $controlConError) {
                $dominioActivo = $procesos[$conError->proceso]->dominio ?? $dominioActivo;
                break;
            }
        }
    }
    ?>

    <div class="rv-extruido overflow-hidden rounded-rv-lg border border-borde bg-superficie">
        <?= $vista->renderizar('herramientas/parciales/tabs-dominios', [
            'ambito'                => 'auditoria',
            'etiquetaLista'         => $vista->t('eval.dominios_lista'),
            'dominios'              => $dominios,
            'totalPorDominio'       => $totalPorDominio,
            'respondidoPorDominio'  => $respondidoPorDominio,
        ]) ?>

        <div class="bg-elevado p-5 sm:p-6" data-controles>
            <?php foreach ($dominios as $dominio): ?>
                <section id="seccion-auditoria-<?= e($dominio->clave) ?>"
                         role="tabpanel"
                         aria-labelledby="tab-auditoria-<?= e($dominio->clave) ?>"
                         tabindex="0"
                         data-seccion-dominio="<?= e($dominio->clave) ?>"
                         class="space-y-8"
                         <?= $dominio->clave === $dominioActivo ? '' : 'hidden' ?>>

                    <div class="flex items-start gap-3 rounded-rv-lg border border-borde bg-superficie px-4 py-3.5">
                        <span class="rv-extruido-xs grid h-9 w-9 flex-none place-items-center rounded-rv bg-elevado text-primario">
                            <?= iconoDominio($dominio->clave, 'h-5 w-5') ?>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-texto"><?= e($dominio->nombre) ?></h3>
                            <p class="mt-0.5 text-xs text-texto-2"><?= e($dominio->descripcion) ?></p>
                        </div>
                    </div>

                    <?php foreach ($procesosPorDominio[$dominio->clave] ?? [] as $proceso): ?>
                        <div data-grupo-proceso="<?= e((string) $proceso->numero) ?>">

                            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-borde pb-3">
                                <h4 class="text-base font-bold text-texto">
                                    <span class="text-primario"><?= e($vista->t('eval.col_proceso')) ?> <?= e((string) $proceso->numero) ?>.</span>
                                    <?= e($proceso->nombre) ?>
                                </h4>
                                <span class="text-xs font-medium text-texto-2"><?= e($proceso->ancla) ?></span>
                            </div>

                            <div class="mt-4 space-y-4">
                                <?php foreach ($controlesPorProceso[$proceso->numero] ?? [] as $control): ?>
                                    <?php
                                    /*
                                     * Los errores solo se le pasan a la
                                     * tarjeta que falló. Repartirlos a las 75
                                     * pintaría el mismo mensaje setenta y
                                     * cinco veces.
                                     */
                                    $fallo = $control->id === $controlConError;
                                    ?>
                                    <?= $vista->componente('tarjeta-control-auditoria', [
                                        'vista'        => $vista,
                                        'control'      => $control,
                                        'evaluacion'   => $evaluaciones[$control->id] ?? null,
                                        'idAuditoria'  => $auditoria->id,
                                        'abierta'      => $abierta,
                                        'escala'       => $escala,
                                        'claveDominio' => $dominio->clave,
                                        'archivo'      => $archivos[$control->id] ?? null,
                                        'limiteArchivo' => $limiteArchivo,
                                        'errores'      => $fallo ? $erroresControl : [],
                                        'valores'      => $fallo ? $valoresControl : [],
                                    ]) ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>

            <?php
            /*
             * Navegación entre dominios, igual que en el instrumento: con siete
             * dominios y la tira de tabs arriba del todo, terminar el último
             * control de «Gobierno» y tener que subir a buscar la pestaña
             * siguiente es el recorrido que esta pareja de botones ahorra.
             *
             * Nace oculta y la muestra el guion. Las etiquetas dicen el nombre
             * del dominio vecino, y sin guion no hay vecino que nombrar ni nada
             * que ocultar: los siete dominios quedarían visibles a la vez y dos
             * botones que no llevan a ninguna parte serían un callejón.
             */
            ?>
            <nav class="mt-8 hidden items-center justify-between gap-3 border-t border-borde pt-6 no-imprimir"
                 data-nav-dominios
                 aria-label="<?= e($vista->t('eval.dominios_lista')) ?>">
                <button type="button" data-nav="anterior"
                        class="rv-extruido-sm rv-interactivo-sm inline-flex max-w-[45%] items-center gap-2 rounded-rv border border-borde bg-superficie px-4 py-2.5 text-sm font-semibold text-texto hover:border-primario disabled:cursor-not-allowed disabled:opacity-40">
                    <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span>
                    <span class="truncate" data-nav-etiqueta><?= e($vista->t('eval.dominio_anterior')) ?></span>
                </button>
                <button type="button" data-nav="siguiente"
                        class="rv-extruido-sm rv-interactivo-sm inline-flex max-w-[45%] items-center gap-2 rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto disabled:cursor-not-allowed disabled:opacity-40">
                    <span class="truncate" data-nav-etiqueta><?= e($vista->t('eval.dominio_siguiente')) ?></span>
                    <?= icono('flecha', 'h-4 w-4') ?>
                </button>
            </nav>
        </div>
    </div>
</section>
</div>

<script>
/*
 * Dos cosas en esta pantalla, y las dos son mejoras sobre algo que ya funciona
 * sin JavaScript:
 *
 *   1. Cambiar de dominio sin recargar. Misma mecánica que el instrumento
 *      (assets/js/instrumento.js), escrita aparte porque aquel archivo lleva su
 *      propio almacén en localStorage para el instrumento público sin sesión.
 *      El servidor ya deja pintada la sección del primer dominio y ocultas las
 *      demás; si el guion no carga se ven los siete seguidos, que es más largo
 *      pero no es un error.
 *
 *   2. Guardar un control sin recargar. Cada tarjeta es un <form> de verdad con
 *      su action y su token: sin guion se envía sola y el controlador redirige
 *      de vuelta a esta misma página con el ancla de la tarjeta. Con guion, el
 *      envío va por fetch y el servidor devuelve LA TARJETA YA DIBUJADA, que
 *      aquí solo se sustituye. Es deliberado que el HTML lo arme PHP: el borde
 *      teñido, la pastilla y los mensajes de error son reglas del producto, y
 *      tenerlas también en JavaScript sería garantizar que un día las dos
 *      copias dijeran cosas distintas.
 */
(function () {
    'use strict';

    var lista = document.querySelector('[data-tabs-dominio="auditoria"]');

    if (!lista) {
        return;
    }

    var tabs = Array.prototype.slice.call(lista.querySelectorAll('[data-tab-dominio]'));
    var secciones = Array.prototype.slice.call(document.querySelectorAll('[data-seccion-dominio]'));
    var nav = document.querySelector('[data-nav-dominios]');
    var anterior = nav && nav.querySelector('[data-nav="anterior"]');
    var siguiente = nav && nav.querySelector('[data-nav="siguiente"]');

    if (!tabs.length || !secciones.length) {
        return;
    }

    /* El dominio que el servidor dejó visible, no siempre el primero: si una
       tarjeta volvió con error, manda el suyo. */
    var actual = 0;

    secciones.forEach(function (seccion) {
        if (seccion.hidden) {
            return;
        }

        tabs.forEach(function (tab, i) {
            if (tab.dataset.tabDominio === seccion.dataset.seccionDominio) {
                actual = i;
            }
        });
    });

    /* El nombre completo del dominio, no la etiqueta corta de la pestaña: en el
       botón hay sitio de sobra y «Continuidad del servicio» dice más que
       «Continuidad». */
    function nombreDe(tab) {
        return tab.getAttribute('aria-label') || tab.textContent.trim();
    }

    function activar(indice, moverFoco) {
        actual = indice;

        var clave = tabs[indice].dataset.tabDominio;

        tabs.forEach(function (tab, i) {
            var activo = i === indice;

            tab.setAttribute('aria-selected', String(activo));
            tab.tabIndex = activo ? 0 : -1;
            tab.classList.toggle('border-primario', activo);
            tab.classList.toggle('text-texto', activo);
            tab.classList.toggle('border-transparent', !activo);
            tab.classList.toggle('text-texto-2', !activo);
            tab.classList.toggle('hover:border-borde', !activo);

            if (activo && moverFoco) {
                tab.focus();
            }
        });

        secciones.forEach(function (seccion) {
            seccion.hidden = seccion.dataset.seccionDominio !== clave;
        });

        if (!nav) {
            return;
        }

        /* En los extremos el botón se desactiva en vez de desaparecer: si se
           va, el otro se corre de sitio y se acaba pulsando sin querer. */
        anterior.disabled = indice === 0;
        siguiente.disabled = indice === tabs.length - 1;

        anterior.querySelector('[data-nav-etiqueta]').textContent =
            indice === 0 ? '' : nombreDe(tabs[indice - 1]);
        siguiente.querySelector('[data-nav-etiqueta]').textContent =
            indice === tabs.length - 1 ? '' : nombreDe(tabs[indice + 1]);
    }

    tabs.forEach(function (tab, indice) {
        tab.addEventListener('click', function () {
            activar(indice, false);
        });

        tab.addEventListener('keydown', function (evento) {
            var saltos = { ArrowRight: 1, ArrowLeft: -1 };
            var destino = null;

            if (saltos[evento.key] !== undefined) {
                destino = (indice + saltos[evento.key] + tabs.length) % tabs.length;
            } else if (evento.key === 'Home') {
                destino = 0;
            } else if (evento.key === 'End') {
                destino = tabs.length - 1;
            }

            if (destino !== null) {
                evento.preventDefault();
                activar(destino, true);
            }
        });
    });

    if (nav) {
        nav.classList.remove('hidden');
        nav.classList.add('flex');

        /* Al cambiar de dominio con los botones, la lista vuelve arriba: el
           siguiente dominio empieza por su primer control, no por la altura a
           la que se quedó el anterior. */
        [[anterior, -1], [siguiente, 1]].forEach(function (par) {
            par[0].addEventListener('click', function () {
                var destino = actual + par[1];

                if (destino < 0 || destino >= tabs.length) {
                    return;
                }

                activar(destino, false);
                lista.scrollIntoView({ block: 'start', behavior: 'smooth' });
            });
        });
    }

    activar(actual, false);

    // ── Guardado de una tarjeta ─────────────────────────────────────────────

    var contenedor = document.querySelector('[data-controles]');

    if (!contenedor || !window.fetch) {
        return;
    }

    var barra = document.querySelector('[data-avance-barra]');
    var cifra = document.querySelector('[data-avance-cifra]');

    /* Delegación en el contenedor y no un oyente por formulario: al guardar,
       la tarjeta se sustituye entera y con ella se irían sus oyentes. */
    contenedor.addEventListener('submit', function (evento) {
        var form = evento.target.closest('[data-form-control]');

        if (!form) {
            return;
        }

        evento.preventDefault();
        guardar(form);
    });

    function guardar(form) {
        var tarjeta = form.closest('[data-control]');
        var boton = form.querySelector('button[type="submit"]');

        if (!tarjeta || form.dataset.enviando === '1') {
            return;
        }

        /* Un doble clic no puede mandar dos veces la misma respuesta: la
           segunda llegaría con el mismo token y sobre una tarjeta que ya está
           siendo sustituida. */
        form.dataset.enviando = '1';

        if (boton) {
            boton.disabled = true;
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'X-Becajo-Asincrona': '1' }
        }).then(function (respuesta) {
            /* 401/302 = la sesión caducó. Recargar deja que el servidor mande
               al login, en vez de dejar la tarjeta muda para siempre. */
            if (respuesta.status === 401 || respuesta.redirected) {
                window.location.reload();
                return null;
            }

            return respuesta.json();
        }).then(function (datos) {
            if (!datos || !datos.html) {
                return;
            }

            var nueva = document.createRange().createContextualFragment(datos.html).firstElementChild;

            tarjeta.replaceWith(nueva);

            if (datos.ok) {
                acusar(nueva);
                refrescarAvance(datos.avance);
            } else {
                /* Al fallar, el foco va al primer campo con problema: quien
                   guarda con el teclado no tiene por qué buscarlo. */
                var malo = nueva.querySelector('.border-bad');

                if (malo) {
                    malo.focus({ preventScroll: true });
                    malo.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            }
        }).catch(function () {
            /* Sin red no se puede fingir que se guardó. Se devuelve el botón y
               se deja que lo intente otra vez; el envío normal del formulario
               sigue ahí como salida. */
            form.dataset.enviando = '';

            if (boton) {
                boton.disabled = false;
            }
        });
    }

    /* El acuse dura tres segundos: es una confirmación, no un estado. Dejarlo
       fijo haría que setenta y cinco tarjetas guardadas dijeran «guardado» a la
       vez, y entonces no lo diría ninguna. */
    function acusar(tarjeta) {
        var acuse = tarjeta.querySelector('[data-acuse]');

        if (!acuse) {
            return;
        }

        acuse.hidden = false;

        window.setTimeout(function () {
            acuse.hidden = true;
        }, 3000);
    }

    function refrescarAvance(avance) {
        if (!avance) {
            return;
        }

        if (barra) {
            barra.style.width = avance.porcentaje + '%';
        }

        if (cifra) {
            cifra.textContent = cifra.dataset.plantilla
                .replace('{n}', avance.respondidos)
                .replace('{total}', avance.total)
                .replace('{pct}', avance.porcentaje);
        }

        Object.keys(avance.porDominio).forEach(function (clave) {
            var contador = document.querySelector('[data-avance-dominio="' + clave + '"]');

            if (!contador) {
                return;
            }

            /* Solo el numerador: el denominador es el número de controles del
               dominio, que no cambia porque alguien responda uno. */
            contador.textContent = avance.porDominio[clave]
                + '/' + contador.textContent.split('/')[1].trim();
        });
    }
})();
</script>
