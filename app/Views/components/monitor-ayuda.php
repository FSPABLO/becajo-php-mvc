<?php

declare(strict_types=1);

/**
 * Botón de «?» con su panel de ayuda.
 *
 * Existe porque el monitor ya tiene cuatro —uno por tabla de procesos y otro
 * para la consola— y la mecánica no es trivial: repetirla en cada sitio es
 * garantizar que un día uno de ellos se quede sin `aria-describedby`, o con un
 * `hidden` que lo saque del árbol de accesibilidad sin que nadie lo note.
 *
 * ── Por qué funciona sin JavaScript ─────────────────────────────────────────
 *
 * Se abre con el CURSOR (`group-hover`) y con el TECLADO
 * (`group-focus-within`), y en un móvil basta con tocar el botón, porque
 * tocarlo le da el foco. No hace falta ningún guion.
 *
 * ── Por qué NO se oculta con `hidden` ni con `invisible` ────────────────────
 *
 * Las dos cosas lo sacarían del árbol de accesibilidad, y entonces el
 * `aria-describedby` del botón apuntaría a algo que no se puede anunciar. Se
 * atenúa con opacidad y se le desactiva el puntero: el lector de pantalla lo
 * lee siempre y el ojo solo cuando toca.
 *
 * ── Por qué recupera `pointer-events` al abrirse ────────────────────────────
 *
 * Es lo que lo hace HOVERABLE, que WCAG 1.4.13 exige del contenido que aparece
 * al pasar por encima: el panel cuelga del mismo `group` que el botón, así que
 * mover el cursor hacia él mantiene la ayuda abierta en vez de escapársele.
 *
 * @var \App\Core\Vista $vista
 * @var string $id           Identificador del panel; lo apunta aria-describedby.
 * @var string $etiqueta     Nombre accesible del botón («Cómo se lee…»).
 * @var list<string> $parrafos  Uno o varios; se pintan como párrafos separados.
 * @var string|null $ancho   Clase de ancho del panel. Por omisión, 26rem.
 */
$ancho = $ancho ?? 'w-[26rem]';
?>
<span class="group relative shrink-0">
    <button type="button"
            aria-label="<?= e($etiqueta) ?>"
            aria-describedby="<?= e($id) ?>"
            class="rv-extruido rv-interactivo-sm grid h-7 w-7 place-items-center rounded-full
                   border border-borde bg-superficie text-texto-2 hover:text-texto">
        <?= icono('pregunta', 'h-4 w-4') ?>
    </button>

    <?php
    /*
     * `max-w-[calc(100vw-4rem)]` para que en una pantalla estrecha el panel no
     * empuje la página a lo ancho: prefiere partirse a provocar barra
     * horizontal en todo el documento.
     */
    ?>
    <span id="<?= e($id) ?>" role="tooltip"
          class="rv-extruido-lg pointer-events-none absolute right-0 top-full z-30 block <?= e($ancho) ?>
                 max-w-[calc(100vw-4rem)] space-y-2 rounded-rv border border-borde bg-superficie p-4
                 text-left text-xs font-normal normal-case leading-relaxed tracking-normal text-texto-2
                 opacity-0 transition-opacity mt-2
                 group-hover:pointer-events-auto group-hover:opacity-100
                 group-focus-within:pointer-events-auto group-focus-within:opacity-100">
        <?php foreach ($parrafos as $parrafo): ?>
            <span class="block"><?= e($parrafo) ?></span>
        <?php endforeach; ?>
    </span>
</span>
