<?php

declare(strict_types=1);

/**
 * Asistente del módulo: el lanzador de la esquina y el panel que se abre a la
 * derecha.
 *
 * DOS PIEZAS, y cada una con su propia regla de visibilidad:
 *
 *   - El LANZADOR vive escondido abajo a la derecha y aparece cuando el cursor
 *     se ACERCA, trazando su anillo despacio. Escondido porque es una
 *     herramienta de consulta y no una acción de la pantalla: siempre a la
 *     vista competiría con cada botón «Guardar» del pie de una tarjeta. La
 *     cercanía la mide asistente.js; con teclado aparece al recibir el foco, y
 *     en pantallas táctiles —donde no hay cursor que acercar— está siempre a la
 *     vista. Ver «Asistente» en rivendel.css.
 *   - El PANEL parte la ventana: en pantalla ancha la columna del módulo se le
 *     aparta con padding, igual que se aparta de la barra lateral, y el
 *     contenido centrado (max-w-*) se vuelve a centrar en el ancho que queda.
 *     Por debajo de lg no hay sitio que partir y el panel cubre la pantalla.
 *
 * El estado abierto en escritorio lo escribe el SERVIDOR en <html>
 * (data-asistente), a partir de la cookie que deja el guion. En pantalla angosta
 * es otro atributo (data-asistente-cajon), lo pone solo el guion y no se
 * recuerda: es el mismo reparto que la barra lateral, por las mismas razones.
 *
 * SIN JAVASCRIPT NO HAY ASISTENTE, y por eso el lanzador nace con `hidden` y lo
 * descubre el guion. Un botón que no abre nada es peor que ningún botón.
 *
 * SE LLAMA LEMBAS —el pan élfico del camino, que es de donde sale todo el
 * vocabulario de Rivendel—, pero el nombre es TEXTO y no código: vive en la
 * clave `asistente.nombre` de los archivos de idioma y aquí se lee una sola vez.
 * Clases, atributos, cookie y archivos siguen diciendo «asistente» porque
 * nombran la función; rebautizarlo no debe obligar a tocar ninguno.
 *
 * Esta es la VISTA. La conversación todavía no está conectada a ningún
 * servicio: el guion pinta la pregunta y una respuesta fija que lo dice. Los
 * globos salen de las dos <template> de abajo y no de cadenas en JavaScript,
 * para que el día que el servidor devuelva la respuesta ya dibujada no haya
 * dos copias del mismo globo.
 *
 * @var \App\Core\Vista                    $vista
 * @var \App\Models\Entidades\Usuario      $usuarioActual
 * @var string|null                        $contexto  Nombre de la pantalla actual.
 * @var string|null                        $rutaActual
 * @var bool|null                          $asistenteAbierto
 */
$contexto         = $contexto ?? null;
$rutaActual       = $rutaActual ?? '/';
$asistenteAbierto = $asistenteAbierto ?? false;

$esAdministrador = $usuarioActual->esAdministrador();

/*
 * Las sugerencias dependen del ROL por la misma razón que el menú: ofrecerle a
 * un auditor «¿qué remediaciones están vencidas?» es anunciarle una pantalla a
 * la que no puede entrar. Solo rellenan el campo; no envían nada.
 */
$sugerencias = [
    $vista->t('asistente.sugerencia_resumen'),
    $vista->t('asistente.sugerencia_hallazgos'),
    $vista->t('asistente.sugerencia_informe'),
];

if ($esAdministrador) {
    $sugerencias[] = $vista->t('asistente.sugerencia_vencidas');
    $sugerencias[] = $vista->t('asistente.sugerencia_catalogo');
}

$rol = $esAdministrador ? $vista->t('panel.rol_admin') : $vista->t('panel.rol_auditor');

$nombre = $vista->t('asistente.nombre');

$nombrePila = strtok(trim($usuarioActual->nombre), ' ') ?: $usuarioActual->nombre;
?>
<div class="rv-asistente-lanzador" data-asistente-lanzador hidden>
    <button type="button"
            data-asistente-abrir
            class="rv-asistente-boton"
            aria-expanded="<?= $asistenteAbierto ? 'true' : 'false' ?>"
            aria-controls="panel-asistente"
            aria-label="<?= e($vista->t('asistente.abrir', $nombre)) ?>"
            title="<?= e($vista->t('asistente.abrir', $nombre)) ?>">
        <?php
        /*
         * El anillo es el trazo que se dibuja al aparecer. pathLength="100" deja
         * la longitud del contorno en una cifra redonda, así que la hoja de
         * estilos anima de 100 a 0 sin saber el radio del círculo.
         */
        ?>
        <svg class="rv-asistente-anillo" viewBox="0 0 64 64" aria-hidden="true">
            <circle cx="32" cy="32" r="30" pathLength="100"/>
        </svg>
        <span class="rv-asistente-nucleo rv-extruido grid h-12 w-12 place-items-center rounded-full bg-primario text-primario-texto">
            <?= icono('asistente', 'h-6 w-6') ?>
        </span>
    </button>
</div>

<?php
/*
 * .rv-oscuro, como la barra lateral: el pergamino queda enmarcado por las dos
 * herramientas y el panel se separa del contenido sin gastar un color de acento.
 *
 * role="complementary" lo da <aside>. No es un diálogo modal en pantalla ancha
 * —la página sigue viva a su lado y se puede seguir trabajando en ella—, así que
 * no se atrapa el foco.
 */
?>
<aside id="panel-asistente"
       data-asistente-panel
       data-asistente-pendiente="<?= e($vista->t('asistente.sin_conexion')) ?>"
       class="rv-oscuro rv-asistente-panel flex flex-col border-l border-borde bg-superficie text-texto"
       aria-labelledby="asistente-titulo">

    <header class="flex flex-none items-start gap-3 border-b border-borde px-5 py-4">
        <span class="rv-extruido-sm grid h-9 w-9 flex-none place-items-center rounded-full bg-primario text-primario-texto">
            <?= icono('asistente', 'h-[18px] w-[18px]') ?>
        </span>

        <div class="min-w-0 flex-1">
            <?php
            /*
             * El nombre va en la VOZ DE MARCA (.rv-marca, Cinzel): Lembas se
             * presenta como un producto con nombre propio, al mismo nivel que
             * «Rivendel» en la cabecera de la barra lateral —y con el mismo
             * cuerpo, 15 px—, no como el rótulo de una sección más.
             *
             * DESVIACIÓN DECLARADA (§2 del sistema visual): Cinzel está
             * reservada al logotipo y al encabezado de informe. Aquí cumple
             * la otra regla de esa voz —nunca más de tres palabras: es una
             * sola, y es un nombre propio— y no se extiende a ninguna otra parte:
             * en la bienvenida, en los globos y en los rótulos «Lembas» es
             * texto corrido y va en la voz de interfaz.
             *
             * El nombre solo no dice qué es: «Lembas» a secas podría ser una
             * sección o un proyecto. La función va debajo, en la voz de
             * interfaz y la tinta secundaria, y DENTRO del <h2> para que el
             * lector de pantalla la anuncie con el nombre al entrar en el panel.
             */
            ?>
            <h2 id="asistente-titulo" class="leading-tight text-texto">
                <span class="rv-marca block text-[15px]"><?= e($nombre) ?></span><span class="sr-only">, </span><span class="mt-1 block text-[12.5px] font-medium text-texto-2"><?= e($vista->t('asistente.funcion')) ?></span>
            </h2>
            <?php
            /*
             * El rol se dice AQUÍ y no solo en la barra lateral: lo que el
             * asistente puede contestar depende de él, y quien no encuentra un
             * dato en la respuesta tiene que poder comprobar de un vistazo con
             * qué permisos preguntó.
             */
            ?>
            <p class="mt-0.5 text-[12px] leading-snug text-texto-2/80">
                <?= e($vista->t('asistente.responde_como', $rol)) ?>
            </p>
        </div>

        <?php /* El gesto de plegar de la barra lateral, en espejo: hacia su borde. */ ?>
        <button type="button"
                data-asistente-cerrar
                class="rv-lateral-enlace -mr-1 flex-none rounded-rv p-2 text-texto-2"
                aria-expanded="<?= $asistenteAbierto ? 'true' : 'false' ?>"
                aria-controls="panel-asistente"
                aria-label="<?= e($vista->t('asistente.cerrar', $nombre)) ?>"
                title="<?= e($vista->t('asistente.cerrar', $nombre)) ?>">
            <?= icono('plegar', 'h-[18px] w-[18px] -scale-x-100') ?>
        </button>
    </header>

    <?php if ($contexto !== null && $contexto !== ''): ?>
        <div class="flex flex-none items-center gap-2 border-b border-borde px-5 py-2.5 text-[12px]">
            <span class="text-texto-2"><?= e($vista->t('asistente.contexto')) ?></span>
            <span class="inline-flex min-w-0 items-center gap-1.5 rounded-rv bg-primario/10 px-2 py-1 font-semibold text-texto">
                <?= icono('documento', 'h-3.5 w-3.5 flex-none text-texto-2') ?>
                <span class="truncate"><?= e($contexto) ?></span>
            </span>
        </div>
    <?php endif; ?>

    <?php
    /*
     * role="log" con aria-live: cada globo nuevo se anuncia al llegar sin mover
     * el foco del campo, que es donde sigue quien está escribiendo.
     */
    ?>
    <div class="flex-1 space-y-4 overflow-y-auto overscroll-contain px-5 py-5"
         data-asistente-conversacion
         role="log"
         aria-live="polite"
         aria-label="<?= e($vista->t('asistente.conversacion', $nombre)) ?>">

        <?php /* Estado vacío: hundido, como todo lo que espera recibir algo. */ ?>
        <div class="rv-hundido rounded-rv-lg bg-fondo p-5" data-asistente-bienvenida>
            <p class="rv-titulo text-[19px] leading-snug text-texto">
                <?= e($vista->t('asistente.bienvenida', $nombrePila, $nombre)) ?>
            </p>
            <p class="mt-2 text-[13px] leading-relaxed text-texto-2">
                <?= e($vista->t($esAdministrador ? 'asistente.alcance_admin' : 'asistente.alcance_auditor')) ?>
            </p>

            <p class="mt-5 font-mono text-[10.5px] uppercase tracking-[0.12em] text-texto-2">
                <?= e($vista->t('asistente.para_empezar')) ?>
            </p>
            <ul class="mt-2 space-y-2">
                <?php foreach ($sugerencias as $sugerencia): ?>
                    <li>
                        <button type="button"
                                data-asistente-sugerencia
                                class="rv-extruido-sm rv-interactivo-sm flex w-full items-center gap-2 rounded-rv bg-superficie px-3 py-2 text-left text-[13px] text-texto">
                            <span class="min-w-0 flex-1"><?= e($sugerencia) ?></span>
                            <?= icono('flecha', 'h-3.5 w-3.5 flex-none text-texto-2') ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php
    /*
     * Sin action ni method todavía: no hay servicio al que mandarlo, y el guion
     * intercepta el envío. El día que exista, este formulario lleva su
     * campoToken() y apunta a su ruta, y el guion pasa a mandarlo por fetch con
     * la cabecera X-Becajo-Asincrona, igual que la tarjeta de un control.
     *
     * La ruta viaja ya: es lo que le dirá al servidor sobre qué pantalla se
     * pregunta, y el servidor la resolverá con los permisos de la sesión, nunca
     * con lo que diga este campo.
     */
    ?>
    <form class="flex-none border-t border-borde px-5 pb-4 pt-3" data-asistente-formulario novalidate>
        <input type="hidden" name="ruta" value="<?= e($rutaActual) ?>">

        <label for="asistente-mensaje" class="sr-only"><?= e($vista->t('asistente.mensaje', $nombre)) ?></label>
        <div class="rv-hundido flex items-end gap-2 rounded-rv-lg bg-fondo p-2 focus-within:ring-1 focus-within:ring-primario">
            <textarea id="asistente-mensaje"
                      name="mensaje"
                      rows="2"
                      maxlength="2000"
                      data-asistente-campo
                      class="min-w-0 flex-1 bg-transparent px-2 py-1.5 text-[13.5px] leading-relaxed text-texto placeholder:text-texto-2/70 focus:outline-none"
                      placeholder="<?= e($vista->t('asistente.marcador', $nombre)) ?>"
                      aria-describedby="asistente-ayuda"></textarea>
            <button type="submit"
                    class="rv-extruido-sm rv-interactivo-sm grid h-9 w-9 flex-none place-items-center rounded-rv bg-primario text-primario-texto transition hover:bg-primario-hover"
                    aria-label="<?= e($vista->t('asistente.enviar')) ?>"
                    title="<?= e($vista->t('asistente.enviar')) ?>">
                <?= icono('flecha', 'h-4 w-4') ?>
            </button>
        </div>

        <p id="asistente-ayuda" class="mt-2 space-y-0.5 text-[11.5px] leading-snug text-texto-2">
            <span class="block"><?= e($vista->t('asistente.ayuda_teclado')) ?></span>
            <span class="block"><?= e($vista->t('asistente.aviso_ia', $nombre)) ?></span>
        </p>
    </form>

    <?php
    /*
     * Los dos globos. Quién habla se dice en texto (sr-only) además de por el
     * lado y el tono: sin eso, el registro leído en voz alta sería una lista de
     * frases sin autor.
     */
    ?>
    <template data-asistente-plantilla="usuario">
        <div class="flex justify-end">
            <?php /* Los dos <span> pegados a propósito: el texto conserva sus
                     saltos de línea (pre-line), y un salto del marcado entre
                     ellos se pintaría como una línea vacía encima. */ ?>
            <p class="max-w-[85%] rounded-rv-lg rounded-br-sm bg-primario/15 px-3.5 py-2.5 text-[13.5px] leading-relaxed text-texto"><span class="sr-only"><?= e($vista->t('asistente.usted')) ?>: </span><span class="whitespace-pre-line break-words" data-asistente-texto></span></p>
        </div>
    </template>

    <template data-asistente-plantilla="asistente">
        <div class="flex items-start gap-2.5">
            <span class="grid h-7 w-7 flex-none place-items-center rounded-full bg-elevado text-texto-2" aria-hidden="true">
                <?= icono('asistente', 'h-4 w-4') ?>
            </span>
            <p class="rv-extruido-sm max-w-[85%] rounded-rv-lg rounded-tl-sm bg-elevado px-3.5 py-2.5 text-[13.5px] leading-relaxed text-texto"><span class="sr-only"><?= e($nombre) ?>: </span><span class="whitespace-pre-line break-words" data-asistente-texto></span></p>
        </div>
    </template>
</aside>
