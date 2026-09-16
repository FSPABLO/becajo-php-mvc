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
 * LA CONVERSACIÓN la responde POST /asistente (AsistenteController), que
 * devuelve el turno YA DIBUJADO: el guion pinta la pregunta, un globo de espera
 * y lo sustituye por el HTML que llega. Todas las piezas —pregunta, turno,
 * fichas— viven en partials/panel/asistente/ y las usan por igual la respuesta
 * JSON, la transcripción repintada y las <template> de abajo: no hay una copia
 * de ningún globo en JavaScript.
 *
 * Qué viaja a Anthropic y qué no lo decide App\Models\Asistente\Lembas, no esta
 * vista. Lo que sí hace la vista es pedirlo: el aviso de la bienvenida dice que
 * en el chat no se escriben hallazgos, porque eso es lo único que la frontera
 * no puede filtrar.
 *
 * @var \App\Core\Vista                    $vista
 * @var \App\Models\Entidades\Usuario      $usuarioActual
 * @var string|null                        $contexto  Nombre de la pantalla actual.
 * @var string|null                        $rutaActual
 * @var bool|null                          $asistenteAbierto
 * @var list<array{pregunta: string, html: string}>|null $transcripcion
 *      Turnos anteriores guardados en sesión: la pregunta original y el HTML
 *      de la respuesta, que ya pasó por e() al dibujarse.
 */
$contexto         = $contexto ?? null;
$rutaActual       = $rutaActual ?? '/';
$asistenteAbierto = $asistenteAbierto ?? false;
$transcripcion    = is_array($transcripcion ?? null) ? $transcripcion : [];

$esAdministrador = $usuarioActual->esAdministrador();

/*
 * Las sugerencias dependen del ROL por la misma razón que el menú: ofrecerle a
 * un auditor «¿qué remediaciones están vencidas?» es anunciarle una pantalla a
 * la que no puede entrar. Solo rellenan el campo; no envían nada.
 *
 * Y son las cosas que Lembas SABE hacer, una por herramienta: una sugerencia
 * que el asistente no puede cumplir es la peor primera impresión posible.
 */
$sugerencias = [
    $vista->t('asistente.sugerencia_auditorias'),
    $vista->t('asistente.sugerencia_resumen'),
    $vista->t('asistente.sugerencia_riesgo'),
    $vista->t('asistente.sugerencia_llenar'),
    $vista->t('asistente.sugerencia_catalogo'),
];

if ($esAdministrador) {
    $sugerencias[] = $vista->t('asistente.sugerencia_vencidas');
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
       data-asistente-error-red="<?= e($vista->t('asistente.error_red')) ?>"
       data-asistente-olvidar="<?= e($vista->url('asistente/olvidar')) ?>"
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

        <?php
        /*
         * Nueva conversación: olvida el historial en la sesión —lo que se le
         * reenvía al modelo— y vacía el panel. Sirve para cambiar de tema sin
         * que la charla anterior condicione la respuesta, y para no pagar en
         * cada pregunta los turnos que ya no importan.
         */
        ?>
        <button type="button"
                data-asistente-nueva
                class="rv-lateral-enlace flex-none rounded-rv p-2 text-texto-2"
                aria-label="<?= e($vista->t('asistente.nueva_conversacion')) ?>"
                title="<?= e($vista->t('asistente.nueva_conversacion')) ?>">
            <?= icono('basura', 'h-[18px] w-[18px]') ?>
        </button>

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

        <?php
        /*
         * Estado vacío: hundido, como todo lo que espera recibir algo. Con una
         * conversación en curso nace oculto; «Nueva conversación» lo devuelve.
         */
        ?>
        <div class="rv-hundido rounded-rv-lg bg-fondo p-5" data-asistente-bienvenida <?= $transcripcion !== [] ? 'hidden' : '' ?>>
            <p class="rv-titulo text-[19px] leading-snug text-texto">
                <?= e($vista->t('asistente.bienvenida', $nombrePila, $nombre)) ?>
            </p>
            <p class="mt-2 text-[13px] leading-relaxed text-texto-2">
                <?= e($vista->t($esAdministrador ? 'asistente.alcance_admin' : 'asistente.alcance_auditor')) ?>
            </p>

            <?php
            /*
             * Lo único que la frontera de privacidad no puede filtrar es lo que
             * alguien escribe a mano. Por eso se dice ANTES de la primera
             * pregunta, con el ícono del candado, y no en la letra pequeña del pie.
             */
            ?>
            <p class="mt-3 flex items-start gap-2 rounded-rv bg-primario/10 px-3 py-2 text-[12.5px] leading-relaxed text-texto">
                <?= icono('candado', 'mt-0.5 h-4 w-4 flex-none text-texto-2') ?>
                <span><?= e($vista->t('asistente.privacidad', $nombre)) ?></span>
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

        <?php /* La conversación guardada en sesión, repintada tal cual. */ ?>
        <?php foreach ($transcripcion as $turnoGuardado): ?>
            <?php if (is_array($turnoGuardado) && is_string($turnoGuardado['pregunta'] ?? null) && is_string($turnoGuardado['html'] ?? null)): ?>
                <?= $vista->renderizar('partials/panel/asistente/pregunta', ['texto' => $turnoGuardado['pregunta']]) ?>
                <?php
                /*
                 * El HTML se imprime sin e() porque YA es HTML escapado: lo
                 * dibujó partials/panel/asistente/turno en el servidor y vive
                 * en la sesión, que el navegador no puede escribir.
                 */
                ?>
                <?= $turnoGuardado['html'] ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php
    /*
     * El guion lo manda por fetch con la cabecera X-Becajo-Asincrona, igual que
     * la tarjeta de un control, y el token viaja como en cualquier formulario.
     * Tiene method y action de verdad aunque sin guion no se pueda llegar a él:
     * así el envío que arma el guion sale del formulario (FormData) y no de una
     * segunda lista de campos escrita en JavaScript.
     *
     * La ruta es una PISTA de qué pantalla se mira, no una autorización: Lembas
     * solo menciona el número de una auditoría si es de la sesión.
     */
    ?>
    <form method="post"
          action="<?= e($vista->url('asistente')) ?>"
          class="flex-none border-t border-borde px-5 pb-4 pt-3"
          data-asistente-formulario
          novalidate>
        <?= $vista->campoToken() ?>
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
     * Las tres piezas que clona el guion, dibujadas con los MISMOS parciales
     * que usa el servidor: la pregunta, el globo de espera y un turno vacío
     * para el único texto que pone el navegador (un fallo de red, cuando no
     * llegó ninguna respuesta que pintar).
     */
    ?>
    <template data-asistente-plantilla="usuario">
        <?= $vista->renderizar('partials/panel/asistente/pregunta', ['texto' => '']) ?>
    </template>

    <template data-asistente-plantilla="pensando">
        <?= $vista->renderizar('partials/panel/asistente/turno', ['pensando' => true]) ?>
    </template>

    <template data-asistente-plantilla="asistente">
        <?= $vista->renderizar('partials/panel/asistente/turno', ['plantilla' => true]) ?>
    </template>
</aside>
