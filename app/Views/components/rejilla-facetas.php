<?php

declare(strict_types=1);

/**
 * Antesala con filtros por facetas: panel a la izquierda, rejilla de fichas a
 * la derecha.
 *
 * Es la DISTRIBUCIÓN de las pantallas en las que solo se elige a dónde entrar:
 * /evaluacion/comparar (una ficha por empresa) y /monitoreo (una ficha por base
 * de datos vigilada). Vivía dentro de la primera y salió a componente al llegar
 * la segunda: las dos se tienen que recorrer igual, y la forma de garantizarlo
 * es que no haya dos copias que puedan separarse.
 *
 * Lo que el componente NO sabe es qué hay en cada ficha. La pinta quien llama,
 * con el cierre `$ficha`: una empresa y una instancia no enseñan lo mismo, y
 * meter las dos aquí convertiría el componente en un `if` por pantalla.
 *
 * Todo funciona sin JavaScript: el panel es un formulario GET con su botón, los
 * grupos son <details> nativos y el orden son enlaces. Cada combinación deja su
 * propia URL, que se puede guardar y compartir. Con guion, assets/js/facetas.js
 * refiltra mientras se escribe pidiendo ESTA MISMA VISTA al servidor y
 * sustituyendo las regiones marcadas con `data-facetas-*`.
 *
 * @var \App\Core\Vista $vista
 * @var string $accion  Ruta de la pantalla, sin barra inicial («monitoreo»).
 * @var string|null $buscar
 * @var string $etiquetaBusqueda
 * @var string $marcadorBusqueda
 * @var array<string, array<string, int>> $facetas  Grupo → valor → cuántas.
 * @var array<string, list<string>> $seleccion  Grupo → valores marcados.
 * @var array<string, string> $titulosGrupo
 * @var \Closure(string, string): string $etiquetaValor  Grupo y valor → rótulo.
 * @var list<string> $gruposCerrados  Grupos que nacen plegados si nada está marcado.
 * @var string $orden
 * @var list<string> $ordenes  El primero es el de por defecto.
 * @var array<string, string> $etiquetasOrden
 * @var string $recuento  «Empresas: 3 de 3», ya traducido.
 * @var list<array<string, mixed>> $filas  Las que pasan el filtro, ordenadas.
 * @var \Closure(array<string, mixed>): string $ficha  Dibuja una ficha entera.
 * @var string $vacioTitulo  Ninguna fila pasa el filtro.
 * @var string $vacioTexto
 */

$ordenPorDefecto = $ordenes[0];

/*
 * Toda URL de esta pantalla sale de aquí: el estado completo —búsqueda, grupos
 * marcados y orden— viaja en la dirección, así que quitar un filtro es enlazar
 * al mismo sitio con una lista de menos. Escrito a mano en cada enlace, el
 * primero que olvidara un parámetro borraría en silencio los otros filtros.
 */
$enlace = static function (array $marcadas, ?string $texto, string $ordenElegido) use ($vista, $accion, $ordenPorDefecto): string {
    $parametros = [];

    if ($texto !== null && $texto !== '') {
        $parametros['buscar'] = $texto;
    }

    foreach ($marcadas as $grupo => $valores) {
        if ($valores !== []) {
            $parametros[$grupo] = array_values($valores);
        }
    }

    // El orden de por defecto no se escribe: una URL con menos ruido es una URL
    // que se puede leer, y el controlador cae en él igualmente.
    if ($ordenElegido !== $ordenPorDefecto) {
        $parametros['orden'] = $ordenElegido;
    }

    $consulta = http_build_query($parametros);

    return $vista->url($accion) . ($consulta === '' ? '' : '?' . $consulta);
};

$hayFiltro = $buscar !== null;

foreach ($seleccion as $valores) {
    $hayFiltro = $hayFiltro || $valores !== [];
}
?>
<div class="grid gap-6 lg:grid-cols-4">

    <?php
    /*
     * EL PANEL DE FILTROS ES UN FORMULARIO GET, y solo él. El orden se queda
     * fuera —son enlaces— para que pulsarlo no dependa de este botón; a cambio,
     * el orden vigente viaja aquí escondido, o aplicar un filtro devolvería la
     * rejilla al orden de por defecto.
     *
     * Se envía con un botón y no al marcar cada casilla: sin guion no hay forma
     * de enviar al cambiar, y con recarga por clic marcar tres condiciones
     * costaría tres viajes al servidor.
     */
    ?>
    <form method="get" action="<?= e($vista->url($accion)) ?>" class="lg:col-span-1" data-facetas>
        <?php if ($orden !== $ordenPorDefecto): ?>
            <input type="hidden" name="orden" value="<?= e($orden) ?>">
        <?php endif; ?>

        <?php
        /*
         * El panel acompaña la rejilla al bajar, pero solo en pantalla ancha:
         * por debajo de lg va encima del resultado y pegarlo dejaría la rejilla
         * asomando por una rendija. El desplazamiento es el alto de la barra
         * superior (h-16) más un respiro.
         */
        ?>
        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie lg:sticky lg:top-20">

            <header class="flex items-baseline justify-between gap-3 border-b border-borde px-4 py-3">
                <h2 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.filtros')) ?></h2>

                <?php
                /*
                 * Envuelto aunque pueda ir vacío: es una de las cuatro regiones
                 * que el guion sustituye, y una región que a veces no existe no
                 * se puede sustituir.
                 */
                ?>
                <span data-facetas-limpiar>
                    <?php if ($hayFiltro): ?>
                        <a href="<?= e($enlace([], null, $orden)) ?>"
                           class="text-xs font-medium text-texto-2 hover:text-texto">
                            <?= e($vista->t('eval.limpiar_filtros')) ?>
                        </a>
                    <?php endif; ?>
                </span>
            </header>

            <div class="p-4">
                <div>
                    <label for="buscar" class="block text-xs font-medium text-texto-2">
                        <?= e($etiquetaBusqueda) ?>
                    </label>
                    <input type="search"
                           id="buscar"
                           name="buscar"
                           data-facetas-busqueda
                           value="<?= e($buscar ?? '') ?>"
                           placeholder="<?= e($marcadorBusqueda) ?>"
                           class="rv-hundido mt-1.5 w-full rounded-rv border border-borde bg-fondo px-3 py-2 text-sm text-texto placeholder-texto-2/60 outline-none transition focus:border-primario-hover">
                </div>

                <div data-facetas-grupos>
                <?php foreach ($facetas as $grupo => $opciones): ?>
                    <?php
                    /*
                     * Un grupo sin ninguna opción no se pinta: un título con
                     * nada debajo parece algo que no cargó. Pasa con los grupos
                     * de datos cuando el buscador deja la rejilla en una sola
                     * ficha.
                     */
                    if ($opciones === []) {
                        continue;
                    }

                    /*
                     * <details> nativo: se abre y se cierra sin guion, el
                     * navegador lo anuncia como lo que es y responde al teclado
                     * sin escribir un keydown. Nace ABIERTO salvo los grupos que
                     * crecen con la cartera, que quien llama declara cerrados; y
                     * aun esos se abren si tienen algo marcado, porque un filtro
                     * aplicado y escondido es un filtro que nadie encuentra.
                     */
                    $abierto = !in_array((string) $grupo, $gruposCerrados, true) || $seleccion[$grupo] !== [];
                    ?>
                    <details class="group mt-4 border-t border-borde pt-3"
                             data-grupo="<?= e((string) $grupo) ?>" <?= $abierto ? 'open' : '' ?>>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-xs font-semibold uppercase tracking-wider text-texto-2">
                            <span><?= e($titulosGrupo[$grupo] ?? (string) $grupo) ?></span>
                            <?= icono('chevron', 'h-4 w-4 shrink-0 transition group-open:rotate-180') ?>
                        </summary>

                        <ul class="mt-2 max-h-56 space-y-0.5 overflow-y-auto">
                            <?php foreach ($opciones as $valor => $cuantas): ?>
                                <?php $marcada = in_array((string) $valor, $seleccion[$grupo], true); ?>
                                <li>
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-rv px-2 py-1.5 transition hover:bg-elevado">
                                        <?php
                                        /*
                                         * La casilla real va en sr-only —oculta
                                         * a la vista, no al teclado ni al lector
                                         * de pantalla— y lo que se ve es su
                                         * hermano, igual que las teclas C/I/D de
                                         * una tarjeta de control. El foco se
                                         * dibuja en la pieza visible, que es
                                         * donde el ojo lo busca.
                                         */
                                        ?>
                                        <input type="checkbox"
                                               class="peer sr-only"
                                               name="<?= e((string) $grupo) ?>[]"
                                               value="<?= e((string) $valor) ?>"
                                               <?= $marcada ? 'checked' : '' ?>>
                                        <span class="rv-extruido-xs grid h-[18px] w-[18px] shrink-0 place-items-center rounded-[6px] border border-borde bg-superficie text-transparent transition peer-checked:border-primario peer-checked:bg-primario peer-checked:text-primario-texto peer-focus-visible:ring-2 peer-focus-visible:ring-primario peer-focus-visible:ring-offset-2"
                                              aria-hidden="true">
                                            <?= icono('check', 'h-3 w-3') ?>
                                        </span>
                                        <span class="min-w-0 flex-1 text-sm text-texto"><?= e($etiquetaValor((string) $grupo, (string) $valor)) ?></span>
                                        <span class="tabular shrink-0 text-xs text-texto-2"><?= e((string) $cuantas) ?></span>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endforeach; ?>
                </div>

                <button type="submit"
                        class="rv-extruido rv-interactivo mt-5 w-full rounded-rv bg-primario px-3.5 py-2 text-sm font-semibold text-primario-texto">
                    <?= e($vista->t('eval.aplicar_filtros')) ?>
                </button>
            </div>
        </div>
    </form>

    <div class="lg:col-span-3">

        <?php
        /*
         * La cabecera del resultado: cuántas quedan, qué filtros están puestos y
         * en qué orden. Las tres cosas responden a «¿por qué veo esto?», y por
         * eso van juntas y encima de la rejilla.
         */
        ?>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
            <p class="text-xs text-texto-2" data-facetas-recuento aria-live="polite">
                <?= e($recuento) ?>
            </p>

            <?php
            /*
             * El orden es un <details> con ENLACES y no un <select>: elegir un
             * orden navega, así que las opciones son enlaces de verdad —se abren
             * en otra pestaña, se comparten, funcionan sin guion— y cada uno deja
             * su URL. Es la misma pieza que el selector de instancia del monitor.
             */
            ?>
            <div data-facetas-orden>
            <details class="relative">
                <summary class="rv-extruido rv-interactivo flex cursor-pointer list-none items-center gap-2 rounded-rv border border-borde bg-superficie px-3 py-1.5 text-xs font-medium text-texto">
                    <span class="text-texto-2"><?= e($vista->t('eval.ordenar_por')) ?>:</span>
                    <span class="font-semibold"><?= e($etiquetasOrden[$orden] ?? $orden) ?></span>
                    <?= icono('chevron', 'h-3.5 w-3.5 shrink-0') ?>
                </summary>

                <ul class="rv-extruido absolute right-0 top-full z-20 mt-1 w-56 overflow-hidden rounded-rv border border-borde bg-superficie py-1 text-sm shadow-lg">
                    <?php foreach ($ordenes as $clave): ?>
                        <?php $activo = $clave === $orden; ?>
                        <li>
                            <a href="<?= e($enlace($seleccion, $buscar, $clave)) ?>"
                               <?= $activo ? 'aria-current="true"' : '' ?>
                               class="flex items-center gap-2 px-3 py-2 transition hover:bg-elevado <?= $activo ? 'font-semibold text-texto' : 'text-texto-2' ?>">
                                <span class="w-4 shrink-0">
                                    <?= $activo ? icono('check', 'h-4 w-4 text-primario') : '' ?>
                                </span>
                                <?= e($etiquetasOrden[$clave] ?? $clave) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </details>
            </div>
        </div>

        <div data-facetas-resultados>
        <?php if ($hayFiltro): ?>
            <?php
            /*
             * Las pastillas de lo aplicado. Son ENLACES que quitan su propia
             * condición: el panel dice qué se puede marcar y esto dice qué está
             * marcado, que no es lo mismo cuando un grupo va cerrado o hay que
             * bajar para verlo. Cada una lleva su aspa y su etiqueta; el
             * aria-label dice el verbo, porque «Riesgo bajo ×» no anuncia qué
             * hace.
             */
            ?>
            <ul class="mb-4 flex flex-wrap items-center gap-2"
                aria-label="<?= e($vista->t('eval.filtros_activos')) ?>">
                <?php if ($buscar !== null): ?>
                    <li>
                        <a href="<?= e($enlace($seleccion, null, $orden)) ?>"
                           aria-label="<?= e($vista->t('eval.quitar_filtro', $buscar)) ?>"
                           class="rv-pill rv-pill--na transition hover:text-texto">
                            <?= icono('buscar', 'h-3.5 w-3.5 shrink-0') ?>
                            <span><?= e($buscar) ?></span>
                            <?= icono('aspa', 'h-3.5 w-3.5 shrink-0') ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php foreach ($seleccion as $grupo => $valores): ?>
                    <?php foreach ($valores as $valor): ?>
                        <?php
                        $sinEsta = $seleccion;
                        $sinEsta[$grupo] = array_values(array_diff($valores, [$valor]));
                        $rotulo = $etiquetaValor((string) $grupo, $valor);
                        ?>
                        <li>
                            <a href="<?= e($enlace($sinEsta, $buscar, $orden)) ?>"
                               aria-label="<?= e($vista->t('eval.quitar_filtro', $rotulo)) ?>"
                               class="rv-pill rv-pill--na transition hover:text-texto">
                                <span><?= e($rotulo) ?></span>
                                <?= icono('aspa', 'h-3.5 w-3.5 shrink-0') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($filas === []): ?>
            <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-14 text-center">
                <p class="font-semibold text-texto"><?= e($vacioTitulo) ?></p>
                <p class="mt-1 text-sm text-texto-2"><?= e($vacioTexto) ?></p>
                <a href="<?= e($enlace([], null, $orden)) ?>"
                   class="mt-4 inline-block text-sm font-semibold text-primario hover:underline">
                    <?= e($vista->t('eval.limpiar_filtros')) ?>
                </a>
            </div>
        <?php else: ?>
            <?php
            /*
             * Tres columnas y no dos: la ficha es ícono, nombre y estado, y a
             * media hoja de ancho quedaba un cuerpo casi vacío con el nombre
             * flotando en el centro. Con tres, la rejilla se recorre como un
             * cajón de carpetas, que es lo que estas pantallas son.
             */
            ?>
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($filas as $fila): ?>
                    <li><?= $ficha($fila) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </div>
    </div>
</div>
