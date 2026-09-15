<?php

declare(strict_types=1);

/**
 * Comparar histórico — la ANTESALA: elegir la empresa.
 *
 * Esta pantalla no compara nada todavía: LISTA. Antes apilaba el histórico
 * completo de cada empresa una debajo de otra, dos gráficos y una tabla por
 * tarjeta, así que para mirar una había que pasar por todas. Ahora cada empresa
 * es una tarjeta que cabe de un vistazo —zona, último índice, hacia dónde va— y
 * el histórico entero vive en /evaluacion/comparar/{empresa}.
 *
 * El panel de filtros es de FACETAS: dentro de un grupo las casillas suman
 * (verde o ámbar) y entre grupos se cruzan (verde Y con tendencia). La cifra de
 * cada casilla dice cuántas empresas quedarían al marcarla, contada con los
 * demás grupos aplicados pero sin el suyo (ver contarFacetas()).
 *
 * Todo funciona sin JavaScript: el panel es un formulario GET con su botón, los
 * grupos son <details> nativos y el orden son enlaces. Cada combinación deja su
 * propia URL, que se puede guardar y compartir.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var int $total  Empresas de la cartera, sin filtrar.
 * @var list<array<string, mixed>> $empresas  Las que pasan el filtro, ordenadas.
 * @var array<string, array<string, int>> $facetas  Grupo → valor → cuántas.
 * @var array<string, list<string>> $seleccion  Grupo → valores marcados.
 * @var string|null $buscar
 * @var string $orden
 * @var list<string> $ordenes  El primero es el de por defecto.
 * @var array{aviso: string|null, error: string|null} $mensajes
 * @var bool|null $asincrona  La pide el guion para sustituir sus regiones,
 *      no el navegador para pintar la página: entonces el guion sobra.
 */

$asincrona       = $asincrona ?? false;
$ordenPorDefecto = $ordenes[0];

/*
 * Los rótulos viven en la VISTA y los valores en el controlador. Un filtro que
 * viaja en la URL como «VERDE» no puede llamarse «Riesgo bajo» en el enlace: la
 * dirección dejaría de valer al cambiar de idioma.
 */
$titulosGrupo = [
    'zona'      => $vista->t('eval.filtro_zona'),
    'estado'    => $vista->t('eval.filtro_estado'),
    'historico' => $vista->t('eval.filtro_historico'),
    'area'      => $vista->t('eval.area_evaluada'),
];

$etiquetasValor = [
    'zona' => [
        'VERDE'    => $vista->t('eval.zona_baja'),
        'AMARILLO' => $vista->t('eval.zona_media'),
        'ROJO'     => $vista->t('eval.zona_alta'),
        'SIN'      => $vista->t('eval.filtro_sin_indice'),
    ],
    'estado' => [
        'EN_PROGRESO' => $vista->t('eval.filtro_con_progreso'),
        'FINALIZADA'  => $vista->t('eval.filtro_finalizadas'),
    ],
    'historico' => [
        'TENDENCIA' => $vista->t('eval.filtro_con_tendencia'),
        'UNICA'     => $vista->t('eval.filtro_una_sola'),
    ],
];

// 'area' no aparece arriba a propósito: sus valores SON el dato que escribió el
// auditor, y traducirlos sería inventarle un nombre a su área evaluada.
$etiquetaValor = static fn (string $grupo, string $valor): string
    => $etiquetasValor[$grupo][$valor] ?? $valor;

$etiquetasOrden = [
    'reciente'   => $vista->t('eval.orden_empresa_reciente'),
    'auditorias' => $vista->t('eval.orden_empresa_auditorias'),
    'indice'     => $vista->t('eval.orden_empresa_indice'),
    'nombre'     => $vista->t('eval.orden_empresa_nombre'),
];

/*
 * Zona → tono de pill(). Es la misma correspondencia de evaluacion/resultados:
 * la zona de fn_zona tiene UN color en todo el producto, y el color nunca viaja
 * solo —pill() obliga a llevar ícono y etiqueta—.
 */
$tonoZona = ['VERDE' => 'ok', 'AMARILLO' => 'warn', 'ROJO' => 'bad'];

$etiquetaZona = [
    'VERDE'    => $vista->t('eval.zona_baja'),
    'AMARILLO' => $vista->t('eval.zona_media'),
    'ROJO'     => $vista->t('eval.zona_alta'),
];

/*
 * Toda URL de esta pantalla sale de aquí: el estado completo —búsqueda, grupos
 * marcados y orden— viaja en la dirección, así que quitar un filtro es enlazar
 * al mismo sitio con una lista de menos. Escrito a mano en cada enlace, el
 * primero que olvidara un parámetro borraría en silencio los otros filtros.
 */
$enlace = static function (array $marcadas, ?string $texto, string $ordenElegido) use ($vista, $ordenPorDefecto): string {
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

    return $vista->url('evaluacion/comparar') . ($consulta === '' ? '' : '?' . $consulta);
};

$hayFiltro = $buscar !== null;

foreach ($seleccion as $valores) {
    $hayFiltro = $hayFiltro || $valores !== [];
}

$cifra = static fn (?float $v): string => $v === null ? '—' : number_format($v, 2, ',', '');
?>
<section class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-8">

    <?php
    /*
     * La pantalla no tiene encabezado VISIBLE: el distintivo de norma y la
     * frase de entrada se retiraron por decisión de diseño. La miga de la barra
     * superior ya dice «Auditorías / Comparar histórico», y qué hacer aquí lo
     * dicen el panel de filtros y las fichas sin necesidad de anunciarlo.
     *
     * El <h1> NO desaparece: se queda en sr-only, igual que en «Mis
     * auditorías». La miga es navegación, no encabezado, y sin el h1 quien no
     * ve el diseño se queda sin saber en qué página entró —y se rompe el salto
     * por encabezados—. No cuesta un píxel.
     */
    ?>
    <h1 class="sr-only"><?= e($vista->t('eval.comparar_historico')) ?></h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($total === 0): ?>
        <?php
        /*
         * Sin cartera no se pinta el panel de filtros: filtrar una lista vacía
         * no lleva a ninguna parte, y cuatro grupos de casillas en cero se leen
         * como un error de carga. Lo que hace falta aquí es la primera
         * auditoría.
         */
        ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto"><?= e($vista->t('eval.sin_auditorias_comparar')) ?></p>
            <a href="<?= e($vista->url('evaluacion/nueva')) ?>"
               class="rv-extruido rv-interactivo mt-6 inline-block rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto">
                <?= e($vista->t('eval.nueva_auditoria')) ?>
            </a>
        </div>
    <?php else: ?>
        <div class="grid gap-6 lg:grid-cols-4">

            <?php
            /*
             * EL PANEL DE FILTROS ES UN FORMULARIO GET, y solo él. El orden se
             * queda fuera —son enlaces— para que pulsarlo no dependa de este
             * botón; a cambio, el orden vigente viaja aquí escondido, o aplicar
             * un filtro devolvería la rejilla al orden de por defecto.
             *
             * Se envía con un botón y no al marcar cada casilla: sin guion no
             * hay forma de enviar al cambiar, y con recarga por clic marcar
             * tres condiciones costaría tres viajes al servidor.
             */
            ?>
            <form method="get" action="<?= e($vista->url('evaluacion/comparar')) ?>" class="lg:col-span-1" data-comparar>
                <?php if ($orden !== $ordenPorDefecto): ?>
                    <input type="hidden" name="orden" value="<?= e($orden) ?>">
                <?php endif; ?>

                <?php
                /*
                 * El panel acompaña la rejilla al bajar, pero solo en pantalla
                 * ancha: por debajo de lg va encima del resultado y pegarlo
                 * dejaría la rejilla asomando por una rendija. El desplazamiento
                 * es el alto de la barra superior (h-16) más un respiro.
                 */
                ?>
                <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie lg:sticky lg:top-20">

                    <header class="flex items-baseline justify-between gap-3 border-b border-borde px-4 py-3">
                        <h2 class="text-sm font-semibold text-texto"><?= e($vista->t('eval.filtros')) ?></h2>

<?php
                        /*
                         * Envuelto aunque pueda ir vacío: es una de las cuatro
                         * regiones que el guion sustituye, y una región que
                         * a veces no existe no se puede sustituir.
                         */
                        ?>
                        <span data-comparar-limpiar>
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
                                <?= e($vista->t('eval.buscar_etiqueta')) ?>
                            </label>
                            <input type="search"
                                   id="buscar"
                                   name="buscar"
                                   data-comparar-busqueda
                                   value="<?= e($buscar ?? '') ?>"
                                   placeholder="<?= e($vista->t('eval.buscar_marcador')) ?>"
                                   class="rv-hundido mt-1.5 w-full rounded-rv border border-borde bg-fondo px-3 py-2 text-sm text-texto placeholder-texto-2/60 outline-none transition focus:border-primario-hover">
                        </div>

                        <div data-comparar-grupos>
                        <?php foreach ($facetas as $grupo => $opciones): ?>
                            <?php
                            /*
                             * Un grupo sin ninguna opción no se pinta: un
                             * título con nada debajo parece algo que no cargó.
                             * Pasa con las áreas cuando el buscador deja la
                             * rejilla en una sola empresa.
                             */
                            if ($opciones === []) {
                                continue;
                            }
                            ?>

                            <?php
                            /*
                             * <details> nativo: se abre y se cierra sin guion,
                             * el navegador lo anuncia como lo que es y responde
                             * al teclado sin escribir un keydown. Nace ABIERTO
                             * —son cuatro grupos cortos y el panel entero cabe—
                             * salvo el de áreas, que es el único que crece con
                             * la cartera.
                             */
                            $abierto = $grupo !== 'area' || $seleccion[$grupo] !== [];
                            ?>
                            <details class="group mt-4 border-t border-borde pt-3"
                                     data-grupo="<?= e((string) $grupo) ?>" <?= $abierto ? 'open' : '' ?>>
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 text-xs font-semibold uppercase tracking-wider text-texto-2">
                                    <span><?= e($titulosGrupo[$grupo] ?? $grupo) ?></span>
                                    <?= icono('chevron', 'h-4 w-4 shrink-0 transition group-open:rotate-180') ?>
                                </summary>

                                <ul class="mt-2 max-h-56 space-y-0.5 overflow-y-auto">
                                    <?php foreach ($opciones as $valor => $cuantas): ?>
                                        <?php $marcada = in_array((string) $valor, $seleccion[$grupo], true); ?>
                                        <li>
                                            <label class="flex cursor-pointer items-center gap-2.5 rounded-rv px-2 py-1.5 transition hover:bg-elevado">
                                                <?php
                                                /*
                                                 * La casilla real va en sr-only
                                                 * —oculta a la vista, no al
                                                 * teclado ni al lector de
                                                 * pantalla— y lo que se ve es
                                                 * su hermano, igual que las
                                                 * teclas C/I/D de una tarjeta
                                                 * de control. El foco se dibuja
                                                 * en la pieza visible, que es
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
                 * La cabecera del resultado: cuántas quedan, qué filtros están
                 * puestos y en qué orden. Las tres cosas responden a «¿por qué
                 * veo esto?», y por eso van juntas y encima de la rejilla.
                 */
                ?>
                <div class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
                    <p class="text-xs text-texto-2" data-comparar-recuento aria-live="polite">
                        <?= e($vista->t('eval.empresas_rango', (string) count($empresas), (string) $total)) ?>
                    </p>

                    <?php
                    /*
                     * El orden es un <details> con ENLACES y no un <select>:
                     * elegir un orden navega, así que las opciones son enlaces
                     * de verdad —se abren en otra pestaña, se comparten,
                     * funcionan sin guion— y cada uno deja su URL. Es la misma
                     * pieza que el selector de instancia del monitor.
                     */
                    ?>
                    <div data-comparar-orden>
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

                <div data-comparar-resultados>
                <?php if ($hayFiltro): ?>
                    <?php
                    /*
                     * Las pastillas de lo aplicado. Son ENLACES que quitan su
                     * propia condición: el panel dice qué se puede marcar y
                     * esto dice qué está marcado, que no es lo mismo cuando el
                     * grupo de áreas va cerrado o hay que bajar para verlo.
                     * Cada una lleva su aspa y su etiqueta; el aria-label dice
                     * el verbo, porque «Riesgo bajo ×» no anuncia qué hace.
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

                <?php if ($empresas === []): ?>
                    <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-14 text-center">
                        <p class="font-semibold text-texto"><?= e($vista->t('eval.sin_empresas_filtro')) ?></p>
                        <p class="mt-1 text-sm text-texto-2"><?= e($vista->t('eval.sin_empresas_filtro_texto')) ?></p>
                        <a href="<?= e($enlace([], null, $orden)) ?>"
                           class="mt-4 inline-block text-sm font-semibold text-primario hover:underline">
                            <?= e($vista->t('eval.limpiar_filtros')) ?>
                        </a>
                    </div>
                <?php else: ?>
                    <?php
                    /*
                     * Tres columnas y no dos: la ficha adelgazó a ícono, nombre
                     * y estado, y a media hoja de ancho quedaba un cuerpo casi
                     * vacío con el nombre flotando en el centro. Con tres, la
                     * rejilla se recorre como un cajón de carpetas, que es lo
                     * que esta pantalla es.
                     */
                    ?>
                    <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <?php
                        /*
                         * La variable del bucle NO se llama $empresa: ese
                         * nombre es el de la consultora —el marco del módulo
                         * recibe sus datos y los pinta en el encabezado y el
                         * pie—, y reutilizarlo lo dejaría pisado para el resto
                         * de la vista. Es el mismo cuidado que tiene el panel
                         * con su lista de organizaciones.
                         */
                        ?>
                        <?php foreach ($empresas as $ficha): ?>
                            <?php
                            $ultima = $ficha['ultima'];
                            $indice = $ficha['indice'];
                            $zona   = $ficha['zona'];
                            ?>
                            <li>
                                <?php
                                /*
                                 * LA FICHA ES MINIMALISTA a propósito, y lo que
                                 * deja fuera lo deja fuera por una razón: la
                                 * variación, la miniatura de la serie y las
                                 * áreas evaluadas son lectura de histórico, y el
                                 * histórico está a un clic. Aquí solo se ELIGE,
                                 * y para elegir bastan cuatro cosas: de quién es
                                 * la carpeta, cuánta hay dentro, cuándo se tocó
                                 * por última vez y en qué estado quedó.
                                 *
                                 * La tarjeta ENTERA es el enlace, y por eso no
                                 * hay nada más que se pueda pulsar dentro: un
                                 * enlace dentro de otro no es HTML válido y el
                                 * navegador lo desarma por su cuenta. Sin un
                                 * «ver histórico» al pie: lo que anuncia que se
                                 * pulsa es el relieve de .rv-interactivo, y el
                                 * nombre ya es el texto del enlace.
                                 */
                                ?>
                                <a href="<?= e($vista->url('evaluacion/comparar/' . rawurlencode((string) $ficha['organizacion']))) ?>"
                                   class="rv-extruido rv-interactivo flex h-full flex-col items-center gap-2 rounded-rv-lg border border-borde bg-superficie px-5 py-6 text-center">

                                    <?php
                                    /*
                                     * El ícono es IDENTIDAD, no estado: va en el
                                     * acento y es el mismo en las tres zonas. Si
                                     * se tiñera de verde o de rojo sería un
                                     * segundo canal de estado sin etiqueta al
                                     * lado, que es justo lo que pill() existe
                                     * para evitar.
                                     */
                                    ?>
                                    <?= icono('expediente', 'h-12 w-12 shrink-0 text-primario') ?>

                                    <h3 class="rv-titulo mt-1 text-base font-semibold leading-tight text-texto">
                                        <?= e((string) $ficha['organizacion']) ?>
                                    </h3>

                                    <p class="text-xs text-texto-2">
                                        <?= e($vista->t(
                                            'eval.auditorias_de_organizacion',
                                            (string) $ficha['total'],
                                            $ultima->fecha,
                                        )) ?>
                                    </p>

                                    <?php
                                    /*
                                     * El pie va anclado abajo (mt-auto) para que
                                     * el estado quede a la misma altura en toda
                                     * la fila: con nombres de una y de tres
                                     * líneas, las pastillas bailaban.
                                     *
                                     * La cifra sola sería un número sin nombre,
                                     * así que lleva su rótulo en sr-only: lo que
                                     * se ahorra es TINTA, no el dato — quien no
                                     * ve la ficha oye «último índice 0,70».
                                     */
                                    ?>
                                    <div class="mt-auto flex flex-wrap items-center justify-center gap-2 pt-3">
                                        <span class="tabular text-sm font-semibold <?= $indice === null ? 'text-na' : 'text-texto' ?>">
                                            <span class="sr-only"><?= e($vista->t('eval.ultimo_indice')) ?>:</span>
                                            <?= e($cifra($indice)) ?>
                                        </span>
                                        <?= pill(
                                            $zona === null ? 'na' : ($tonoZona[$zona] ?? 'na'),
                                            $zona === null
                                                ? $vista->t('eval.filtro_sin_indice')
                                                : ($etiquetaZona[$zona] ?? $zona),
                                        ) ?>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php if (!$asincrona): ?>
<script>
/*
 * Filtrar mientras se escribe.
 *
 * Es una MEJORA sobre algo que ya funciona sin JavaScript: el panel es un
 * formulario GET con su botón, y sin guion se aplica pulsándolo. Con guion, cada
 * tecla —y cada casilla— vuelve a preguntar al servidor y sustituye lo que
 * cambió, sin recargar la página ni perder el cursor.
 *
 * EL GUION NO FILTRA. Pide la misma dirección con la cabecera X-Becajo-Asincrona
 * y recibe ESTA MISMA VISTA ya dibujada por PHP; de ahí saca las cuatro regiones
 * que cambian y las copia. Es la misma decisión que el guardado de un control en
 * evaluacion/mostrar: cómo se cuenta una faceta, cómo se ordena la rejilla y cómo
 * se pinta una ficha son reglas del producto, y una segunda copia en JavaScript
 * es una copia que un día dirá otra cosa. Además, filtrar aquí solo podría mirar
 * lo que ya está en el DOM —la ficha minimalista no lleva las áreas evaluadas— y
 * dejaría mintiendo los recuentos del panel.
 *
 * El campo de búsqueda queda FUERA de las regiones que se sustituyen: es donde
 * está el cursor, y reemplazarlo con cada tecla lo perdería.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-comparar]');

    if (!form || !window.fetch) {
        return;
    }

    var campo = form.querySelector('[data-comparar-busqueda]');

    /* Las cuatro regiones que el servidor vuelve a dibujar. El recuento va
       aparte: no se sustituye, solo cambia su texto, porque una región viva que
       se reemplaza entera no siempre se anuncia. */
    var REGIONES = [
        '[data-comparar-limpiar]',
        '[data-comparar-grupos]',
        '[data-comparar-orden]',
        '[data-comparar-resultados]'
    ];

    var espera = null;
    var ultima = 0;

    if (campo) {
        campo.addEventListener('input', function () {
            /* Un cuarto de segundo: lo justo para que «cooperativa» sea una
               petición y no once, sin que se note al dejar de teclear. */
            window.clearTimeout(espera);
            espera = window.setTimeout(refrescar, 250);
        });
    }

    form.addEventListener('change', function (evento) {
        /* Una casilla es una decisión tomada; una letra puede ser media palabra,
           así que esta no espera. Va por delegación porque las casillas se
           sustituyen enteras y con ellas se irían sus oyentes. */
        if (evento.target !== campo) {
            refrescar();
        }
    });

    form.addEventListener('submit', function (evento) {
        /* El botón «Aplicar» y la tecla Enter siguen existiendo —son la salida
           sin guion—, pero aquí no hace falta recargar para obedecerlos. */
        evento.preventDefault();
        refrescar();
    });

    function refrescar() {
        window.clearTimeout(espera);

        var consulta = parametros();
        var url = form.action + (consulta ? '?' + consulta : '');
        var mia = ++ultima;

        fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Becajo-Asincrona': '1' }
        }).then(function (respuesta) {
            /* 401 o una redirección = la sesión caducó. Recargar deja que el
               servidor mande al ingreso, en vez de dejar la rejilla muda. */
            if (respuesta.status === 401 || respuesta.redirected) {
                window.location.reload();
                return null;
            }

            return respuesta.json();
        }).then(function (datos) {
            /* Dos teclas seguidas son dos peticiones y no tienen por qué volver
               en orden: la respuesta vieja no puede pisar a la nueva. */
            if (!datos || !datos.html || mia !== ultima) {
                return;
            }

            pintar(datos.html);

            /* replaceState y no pushState: cada tecla dejaría una entrada en el
               historial, y volver atrás sería deshacer el nombre letra a letra.
               La dirección sigue siendo compartible, que es lo que importa. */
            window.history.replaceState(null, '', url);
        }).catch(function () {
            /* Sin red se deja en pantalla lo último que respondió el servidor:
               fingir aquí un filtrado propio sería justo la segunda copia que
               este guion existe para no tener. El formulario sigue siendo un
               GET normal y el botón lo manda entero. */
        });
    }

    /* Se manda el formulario ENTERO —búsqueda, casillas y el orden vigente— y no
       solo lo que se acaba de tocar: el servidor decide con todo a la vista, que
       es lo mismo que hace al pulsar «Aplicar». Los campos vacíos se quedan
       fuera para que la dirección se pueda leer. */
    function parametros() {
        var datos = new URLSearchParams();

        new FormData(form).forEach(function (valor, clave) {
            if (String(valor) !== '') {
                datos.append(clave, valor);
            }
        });

        return datos.toString();
    }

    function pintar(html) {
        var fresco = document.createRange().createContextualFragment(html);
        var abiertos = grupos();
        var foco = document.activeElement;
        var marca = foco && foco.name && foco !== campo ? [foco.name, foco.value] : null;

        REGIONES.forEach(function (selector) {
            var viejo = document.querySelector(selector);
            var nuevo = fresco.querySelector(selector);

            if (viejo && nuevo) {
                viejo.innerHTML = nuevo.innerHTML;
            }
        });

        var recuento = document.querySelector('[data-comparar-recuento]');
        var recuentoNuevo = fresco.querySelector('[data-comparar-recuento]');

        if (recuento && recuentoNuevo) {
            recuento.textContent = recuentoNuevo.textContent;
        }

        restaurar(abiertos);
        devolverFoco(marca);
    }

    function grupos() {
        var abiertos = {};

        form.querySelectorAll('[data-grupo]').forEach(function (grupo) {
            abiertos[grupo.getAttribute('data-grupo')] = grupo.open;
        });

        return abiertos;
    }

    function restaurar(abiertos) {
        /* El servidor decide qué grupos NACEN abiertos, y eso vale para la
           primera carga. Si el auditor desplegó «Área evaluada» y después
           escribió una letra, volvérselo a cerrar es deshacerle el gesto. */
        form.querySelectorAll('[data-grupo]').forEach(function (grupo) {
            var estado = abiertos[grupo.getAttribute('data-grupo')];

            if (estado !== undefined) {
                grupo.open = estado;
            }
        });
    }

    function devolverFoco(marca) {
        /* Las casillas se sustituyen enteras, así que quien las recorre con el
           teclado se quedaría sin foco justo al marcar una, y sin saber dónde
           estaba. Se busca por nombre y valor recorriendo, y no con un selector:
           el valor de un área evaluada es texto libre y puede traer comillas. */
        if (!marca || document.activeElement !== document.body) {
            return;
        }

        var candidatas = form.querySelectorAll('[name="' + marca[0] + '"]');

        for (var i = 0; i < candidatas.length; i++) {
            if (candidatas[i].value === marca[1]) {
                candidatas[i].focus({ preventScroll: true });
                return;
            }
        }
    }
}());
</script>
<?php endif; ?>
