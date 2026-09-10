<?php

declare(strict_types=1);

/**
 * Indicadores de la auditoría. Todo lo que se muestra aquí sale de
 * pkg_indicadores: ni una cifra se calcula en PHP.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var array<string, mixed> $resumen
 * @var list<array<string, mixed>> $dominios
 * @var list<\App\Models\Entidades\ResultadoRiesgo> $exposicion
 * @var list<array<string, mixed>> $menorMadurez
 * @var list<array<string, mixed>> $mayorRiesgo
 * @var list<\App\Models\Entidades\EvaluacionControl> $evaluaciones
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
/*
 * Zona -> tono de la escala semántica, y zona -> etiqueta legible.
 *
 * La etiqueta nombra el NIVEL DE RIESGO y no el color: «ROJO» es inútil para
 * quien no distingue el color o lee el informe impreso en gris. La base sigue
 * guardando ROJO/AMARILLO/VERDE (así lo define el CHECK de resultado_riesgo);
 * la traducción a algo legible ocurre aquí, en la vista.
 */
$tonoZona = [
    'ROJO'     => 'crit',
    'AMARILLO' => 'warn',
    'VERDE'    => 'ok',
];

$etiquetaZona = [
    'ROJO'     => $vista->t('eval.zona_alta'),
    'AMARILLO' => $vista->t('eval.zona_media'),
    'VERDE'    => $vista->t('eval.zona_baja'),
];

// Mismos cortes que fn_zona en pkg_indicadores, para que el color del
// dominio coincida con el de las tarjetas de exposición.
$zonaDe = static function (mixed $frac): ?string {
    if ($frac === null) {
        return null;
    }

    $frac = (float) $frac;

    return match (true) {
        $frac < 0.5 => 'ROJO',
        $frac < 0.8 => 'AMARILLO',
        default     => 'VERDE',
    };
};

$fondoZona = [
    'ROJO'     => 'bg-bad',
    'AMARILLO' => 'bg-warn',
    'VERDE'    => 'bg-ok',
];

$porcentaje = static fn (mixed $v): string =>
    $v === null ? '—' : number_format((float) $v * 100, 1, ',', '') . ' %';

$hayRiesgoCritico = array_filter($exposicion, static fn ($r) => $r->zona === 'ROJO') !== [];

// $riesgo->etiqueta() devuelve el nombre en español (viene del dominio, no
// de la BD); se traduce aquí por tipo para no tocar la entidad.
$etiquetaTipo = [
    \App\Models\Entidades\ResultadoRiesgo::CONFIDENCIALIDAD => $vista->t('eval.confidencialidad'),
    \App\Models\Entidades\ResultadoRiesgo::INTEGRIDAD       => $vista->t('eval.integridad'),
    \App\Models\Entidades\ResultadoRiesgo::DISPONIBILIDAD   => $vista->t('eval.disponibilidad'),
];

?>
<?php
/*
 * La pantalla se ordena como un TABLERO y no como un informe: primero la cifra
 * de cabecera, después el desglose que la explica y al final el detalle que se
 * recorre. Antes eran siete secciones apiladas a lo largo de una columna
 * estrecha, cada una con su <h2> del mismo tamaño, y el cumplimiento por
 * dominio aparecía TRES veces seguidas —barras horizontales, mosaico de
 * tarjetas y tabla— diciendo lo mismo con tres formas distintas.
 *
 * Ahora el dato de dominio vive en dos sitios y cada uno responde algo que el
 * otro no: el gráfico compara los siete de un vistazo, la tabla da los
 * recuentos por respuesta. El mosaico se retiró — era el gráfico otra vez, en
 * párrafos.
 *
 * El ancho es max-w-7xl, el mismo de la auditoría y del instrumento, y no el
 * max-w-5xl de antes: un tablero de dos columnas dentro de una caja de 64rem
 * deja cada mitad demasiado angosta para su gráfico.
 */
?>
<?php
/*
 * LA MISMA REGIÓN QUE EL INSTRUMENTO Y QUE /evaluacion/{id}: pergamino con el
 * acento en oro (`rv-claro rv-oro`) sobre lienzo `bg-elevado`. Las tres
 * pantallas son el mismo recorrido —la referencia, la captura y el resultado— y
 * saltar entre ellas cambiando de color las hacía parecer tres productos.
 *
 * `rv-oro` no es una paleta nueva: es `.rv-claro` con el acento repuntado, y
 * las dos clases van juntas porque sola no trae lienzo. Ver rivendel.css.
 *
 * AQUÍ HAY UNA COLISIÓN QUE CONVIENE TENER PRESENTE. En el pergamino,
 * --rv-warn y --rv-primary de rv-oro valen el MISMO #7A5D00, y esta pantalla es
 * donde más se nota: con la auditoría 152, cuatro de las siete columnas y las
 * tres pastillas de exposición caen en zona media, o sea en warn. El acento de
 * acción y el estado «advertencia» se ven iguales.
 *
 * No rompe ninguna lectura —el estado nunca viaja solo en el color: las
 * pastillas llevan ícono y etiqueta, y las columnas su cifra y las dos líneas
 * de corte—, pero el oro deja de separar «esto se pulsa» de «esto avisa». Si
 * algún día molesta, se arregla en rv-oro moviendo --rv-primary a otro paso del
 * oro, no aquí: la paleta se toca en un solo archivo.
 *
 * La franja va a TODO EL ANCHO con la caja centrada dentro; si el color fuera
 * del <section>, el lienzo del módulo asomaría por los lados.
 */
?>
<div class="rv-claro rv-oro bg-elevado">
<section class="mx-auto w-full max-w-7xl px-6 py-8 lg:px-8">

    <?php
    /*
     * Aquí iba un «← Auditoría 152». Lo sigue diciendo la miga de pan de la
     * barra superior, que enlaza a la auditoría desde esta pantalla; dos
     * vueltas atrás obligan a decidir cuál de las dos es la buena.
     */
    ?>
    <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="rv-titulo text-3xl font-semibold text-texto"><?= e($vista->t('eval.resultados')) ?></h1>
            <p class="mt-1 text-texto-2">
                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?> · <?= e($auditoria->fecha) ?>
            </p>
        </div>
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reporte')) ?>"
           class="inline-flex shrink-0 items-center gap-2 rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
            <?= icono('imprimir', 'h-4 w-4') ?>
            <?= e($vista->t('eval.reporte_pdf')) ?>
        </a>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($hayRiesgoCritico): ?>
        <div role="alert"
             class="mb-6 flex items-start gap-3 rounded-rv-lg border border-bad/10 bg-bad/10 px-4 py-3 text-sm text-bad">
            <?= icono('alerta', 'h-5 w-5 shrink-0') ?>
            <span><?= e($vista->t('eval.aviso_riesgo_critico')) ?></span>
        </div>
    <?php endif; ?>

    <?php
    /*
     * 1. LAS CIFRAS DE CABECERA. Cuatro y no tres: el reparto Sí/No/NA era un
     * párrafo suelto debajo de las tarjetas, con las cifras en negrita dentro
     * de una frase. Es un dato de portada como los otros tres y ocupa una
     * loseta, no un renglón.
     */
    ?>
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php
        $tarjetas = [
            [$vista->t('eval.cumplimiento_general'), $porcentaje($resumen['cumplimiento'] ?? null), null],
            [$vista->t('eval.madurez_promedio'), (string) ($resumen['madurez_promedio'] ?? '—'), $vista->t('eval.de_cinco')],
            [$vista->t('eval.indice_general_riesgo'), $auditoria->indiceGeneralRiesgo === null
                ? $vista->t('eval.sin_calcular') : number_format($auditoria->indiceGeneralRiesgo, 2), null],
        ];
        ?>
        <?php foreach ($tarjetas as [$etiqueta, $valor, $sufijo]): ?>
            <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
                <p class="text-sm text-texto-2"><?= e($etiqueta) ?></p>
                <p class="mt-1 text-2xl font-extrabold tabular text-texto">
                    <?= e($valor) ?><?php if ($sufijo !== null): ?><span class="ml-1 text-sm font-medium text-texto-2"><?= e($sufijo) ?></span><?php endif; ?>
                </p>
            </div>
        <?php endforeach; ?>

        <?php
        /*
         * El reparto de respuestas, con la escala semántica de cada una: el
         * verde, el rojo y el gris son los mismos que tiñen la pastilla de cada
         * control en la auditoría, así que la loseta se lee sin leyenda. Cada
         * cifra lleva su palabra al lado — el color no comunica solo.
         */
        ?>
        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <p class="text-sm text-texto-2"><?= e($vista->t('eval.controles_respondidos')) ?></p>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-4 gap-y-1">
                <?php foreach ([
                    ['text-ok', (string) ($resumen['controles_si'] ?? 0), $vista->t('eval.si_minuscula')],
                    ['text-bad', (string) ($resumen['controles_no'] ?? 0), $vista->t('eval.no_minuscula')],
                    ['text-na', (string) ($resumen['controles_na'] ?? 0), $vista->t('eval.na_minuscula')],
                ] as [$tono, $cifra, $palabra]): ?>
                    <span class="flex items-baseline gap-1">
                        <span class="text-2xl font-extrabold tabular <?= e($tono) ?>"><?= e($cifra) ?></span>
                        <span class="text-xs font-medium text-texto-2"><?= e($palabra) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php
    /*
     * 2. LOS TRES INSTRUMENTOS, AL MISMO NIVEL. Las columnas por dominio, la
     * matriz de impacto × probabilidad y la exposición por dimensión responden
     * la misma pregunta —¿por dónde va mal?— desde tres ángulos, y en paralelo
     * se comparan de un vistazo. Apilados obligaban a recordar el anterior
     * mientras se miraba el siguiente.
     *
     * Los anchos NO son tres iguales: la matriz es una cuadrícula de lado fijo
     * y la exposición son tres cifras, así que las dos piden lo que miden y el
     * gráfico se queda con lo que sobra, que es lo único que crece bien.
     *
     * Por debajo de xl se apilan. El gráfico lleva el viewBox calculado para
     * esta columna — ver la nota de components/cumplimiento-dominios.
     */
    ?>
    <div class="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_auto_15rem] xl:items-start">

        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-texto">
                <?= e($vista->t('eval.cumplimiento_dominio')) ?>
            </h2>
            <?= $vista->componente('cumplimiento-dominios', [
                'vista'    => $vista,
                'dominios' => $dominios,
            ]) ?>
        </div>

        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-texto">
                <?= e($vista->t('eval.matriz_riesgo')) ?>
            </h2>
            <?php
            /*
             * La matriz se comparte con el panel de entrada
             * (components/matriz-riesgo). El conteo por celda y los cortes de
             * zona viven allí, en un solo sitio: son los mismos que aplica
             * fn_zona en pkg_indicadores, y dos copias se separan en cuanto
             * alguien mueva un corte en la base.
             */
            ?>
            <div class="overflow-x-auto">
                <?= $vista->componente('matriz-riesgo', [
                    'vista'        => $vista,
                    'evaluaciones' => $evaluaciones,
                ]) ?>
            </div>
        </div>

        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wider text-texto">
                <?= e($vista->t('eval.exposicion_riesgo')) ?>
            </h2>

            <?php if ($exposicion === []): ?>
                <p class="rv-hundido rounded-rv border border-borde bg-superficie px-4 py-3 text-sm text-texto-2">
                    <?= e($vista->t('eval.sin_dimensiones')) ?>
                </p>
            <?php else: ?>
                <?php
                /*
                 * Tres filas y no tres tarjetas: son tres lecturas de la misma
                 * medida y se comparan mejor alineadas en columna que repartidas
                 * en cajas del mismo tamaño. La zona no se comunica solo por
                 * color — el pill trae ícono y etiqueta, y la etiqueta nombra el
                 * NIVEL de riesgo, no el color: «ROJO» no le dice nada a quien
                 * no lo ve ni a quien lee el informe impreso en gris.
                 */
                ?>
                <ul class="divide-y divide-borde">
                    <?php foreach ($exposicion as $riesgo): ?>
                        <li class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm font-semibold text-texto">
                                    <?= e($etiquetaTipo[$riesgo->tipo] ?? $riesgo->etiqueta()) ?>
                                </span>
                                <span class="tabular shrink-0 text-lg font-bold text-texto">
                                    <?= $riesgo->porcentaje() === null
                                        ? '—'
                                        : e(number_format((float) $riesgo->porcentaje(), 1, ',', '')) . ' %' ?>
                                </span>
                            </div>
                            <p class="mt-1.5"><?= pill($tonoZona[$riesgo->zona] ?? 'na', $etiquetaZona[$riesgo->zona] ?? '—') ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php
    /*
     * 3. EL DETALLE POR DOMINIO, EN FILAS QUE SE ABREN.
     *
     * Era una tabla de siete filas seguida de dos tarjetas con los controles de
     * menor madurez y mayor riesgo, y las tres decían lo mismo desde sitios
     * distintos: la tabla contaba cuántos controles fallan en cada dominio y
     * las tarjetas nombraban CUÁLES, sin decir a qué dominio pertenecían más
     * que en una línea suelta al pie de cada ficha. Para saber qué falla en
     * Gobierno había que leer la tabla, bajar y filtrar dos listas con la vista.
     *
     * Ahora el dominio es la unidad: la fila da los recuentos y, al abrirla,
     * dentro están sus controles señalados. Se despliega solo el que interesa,
     * que es lo que convierte una lista larga en una consulta.
     *
     * Es un <details> por fila y NO una tabla con guion: se abre y se cierra
     * sin JavaScript, el navegador lo anuncia como lo que es y funciona con el
     * teclado sin que nadie escriba un `keydown`. El precio es que deja de ser
     * un <table>, así que cada cifra lleva su rótulo en sr-only — sin él, un
     * lector de pantalla leería «Accesos y privilegios, 5, 6, 1» y las columnas
     * no existirían para quien no las ve.
     */
    ?>
    <?php if ($dominios !== []): ?>
        <?php
        /*
         * Los controles señalados, agrupados por el NOMBRE del dominio, que es
         * lo que traen sp_menor_madurez y sp_mayor_riesgo (no la clave).
         */
        $senalados = [];

        foreach ($menorMadurez as $fila) {
            $senalados[(string) $fila['dominio']]['madurez'][] = $fila;
        }

        foreach ($mayorRiesgo as $fila) {
            $senalados[(string) $fila['dominio']]['riesgo'][] = $fila;
        }

        // Rejilla compartida por la cabecera y por cada fila: una sola
        // definición, para que las columnas no puedan desalinearse.
        $rejilla = 'grid grid-cols-[minmax(0,1fr)_3.5rem_3.5rem_5rem_7rem_5.5rem] items-center gap-2';

        /** Una ficha de control dentro del desplegable. */
        $ficha = static function (array $fila, string $columna, ?string $extra = null) use ($vista): string {
            $cifra = (string) ($fila[$columna] ?? '—');

            if ($extra !== null && !empty($fila[$extra])) {
                $cifra .= ' · ' . (string) $fila[$extra];
            }

            return '<li class="py-2 first:pt-0 last:pb-0">'
                . '<div class="flex items-baseline justify-between gap-3">'
                . '<span class="rv-id text-xs">' . e((string) $fila['codigo_control']) . '</span>'
                . '<span class="tabular shrink-0 text-xs font-semibold text-texto">' . e($cifra) . '</span>'
                . '</div>'
                . '<p class="mt-0.5 text-xs leading-snug text-texto-2">'
                . e(mb_strimwidth((string) $fila['enunciado'], 0, 130, '…')) . '</p>'
                . '</li>';
        };
        ?>
        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie">
            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-borde px-5 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-texto">
                    <?= e($vista->t('eval.detalle_dominio')) ?>
                </h2>
                <p class="text-xs text-texto-2"><?= e($vista->t('eval.detalle_dominio_ayuda')) ?></p>
            </div>

            <div class="overflow-x-auto">
                <div class="min-w-[44rem]">

                    <?php /* Cabecera de columnas. aria-hidden: los rótulos que
                             de verdad lee un lector de pantalla son los sr-only
                             de cada celda, y repetirlos aquí los diría dos veces. */ ?>
                    <div class="<?= e($rejilla) ?> border-b border-borde px-5 py-2.5 text-xs uppercase tracking-wide text-texto-2"
                         aria-hidden="true">
                        <span><?= e($vista->t('eval.col_dominio')) ?></span>
                        <span class="text-right"><?= e($vista->t('eval.estado_si')) ?></span>
                        <span class="text-right"><?= e($vista->t('eval.estado_no')) ?></span>
                        <span class="text-right"><?= e($vista->t('eval.estado_na')) ?></span>
                        <span class="text-right"><?= e($vista->t('eval.col_cumplimiento')) ?></span>
                        <span class="text-right"><?= e($vista->t('eval.col_madurez')) ?></span>
                    </div>

                    <?php foreach ($dominios as $fila): ?>
                        <?php
                        $nombre = (string) $fila['nombre_dominio'];
                        $suyos = $senalados[$nombre] ?? [];
                        unset($senalados[$nombre]);

                        $porMadurez = $suyos['madurez'] ?? [];
                        $porRiesgo  = $suyos['riesgo'] ?? [];
                        $cuantos    = count($porMadurez) + count($porRiesgo);

                        $celdas = [
                            [$vista->t('eval.estado_si'), (string) $fila['controles_si'], ''],
                            [$vista->t('eval.estado_no'), (string) $fila['controles_no'], ''],
                            [$vista->t('eval.estado_na'), (string) $fila['controles_na'], ''],
                            [$vista->t('eval.col_cumplimiento'), $porcentaje($fila['cumplimiento'] ?? null), 'font-semibold text-texto'],
                            [$vista->t('eval.col_madurez'), (string) ($fila['madurez_promedio'] ?? '—'), ''],
                        ];
                        ?>
                        <?php
                        /*
                         * Un dominio sin controles señalados NO se abre: un
                         * desplegable vacío es una promesa incumplida, y con
                         * siete filas se prueban todas en dos segundos. Se pinta
                         * como fila plana, con el mismo alto y las mismas
                         * columnas para que la lista no cojee.
                         */
                        ?>
                        <?php if ($cuantos === 0): ?>
                            <div class="<?= e($rejilla) ?> border-b border-borde px-5 py-3 text-sm last:border-b-0">
                                <span class="flex min-w-0 items-center gap-2 font-medium text-texto">
                                    <span class="w-4 shrink-0" aria-hidden="true"></span>
                                    <?= iconoDominio((string) $fila['clave_dominio'], 'h-4 w-4 shrink-0') ?>
                                    <span class="truncate"><?= e($nombre) ?></span>
                                </span>
                                <?php foreach ($celdas as [$rotulo, $valor, $clases]): ?>
                                    <span class="text-right tabular <?= e($clases) ?>">
                                        <span class="sr-only"><?= e($rotulo) ?>: </span><?= e($valor) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <details class="group border-b border-borde last:border-b-0">
                                <summary class="<?= e($rejilla) ?> cursor-pointer list-none px-5 py-3 text-sm marker:hidden hover:bg-elevado">
                                    <span class="flex min-w-0 items-center gap-2 font-medium text-texto">
                                        <span class="shrink-0 text-primario transition group-open:rotate-90" aria-hidden="true">
                                            <?= icono('flecha', 'h-4 w-4') ?>
                                        </span>
                                        <?= iconoDominio((string) $fila['clave_dominio'], 'h-4 w-4 shrink-0') ?>
                                        <span class="truncate"><?= e($nombre) ?></span>
                                    </span>
                                    <?php foreach ($celdas as [$rotulo, $valor, $clases]): ?>
                                        <span class="text-right tabular <?= e($clases) ?>">
                                            <span class="sr-only"><?= e($rotulo) ?>: </span><?= e($valor) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </summary>

                                <?php
                                /*
                                 * El interior va HUNDIDO: es lo que se recibe al
                                 * abrir la fila, y el inset lo separa de la lista
                                 * sin gastar otro borde.
                                 *
                                 * Las dos listas solo se pintan si tienen algo.
                                 * Un dominio puede aparecer en «menor madurez» y
                                 * no en «mayor riesgo», y una columna con un
                                 * título y nada debajo se lee como un fallo de
                                 * carga.
                                 */
                                ?>
                                <div class="rv-hundido grid gap-x-8 gap-y-4 border-t border-borde bg-elevado px-5 py-4 sm:grid-cols-2">
                                    <?php foreach ([
                                        [$vista->t('eval.menor_madurez'), $porMadurez, 'madurez', null],
                                        [$vista->t('eval.mayor_riesgo'), $porRiesgo, 'nivel_riesgo', 'dimensiones'],
                                    ] as [$titulo, $lista, $columna, $extra]): ?>
                                        <?php if ($lista !== []): ?>
                                            <div>
                                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-texto-2">
                                                    <?= e($titulo) ?>
                                                </p>
                                                <ul class="divide-y divide-borde">
                                                    <?php foreach ($lista as $control): ?>
                                                        <?= $ficha($control, $columna, $extra) ?>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            /*
             * Red de seguridad: si un control señalado trae un nombre de dominio
             * que no está en la lista de arriba, se queda sin fila donde caber.
             * No debería pasar —los dos vienen del mismo catálogo— pero
             * perderlo EN SILENCIO sí sería grave: es justo el control que la
             * pantalla existe para señalar.
             */
            ?>
            <?php if ($senalados !== []): ?>
                <div class="border-t border-borde px-5 py-4">
                    <?php foreach ($senalados as $nombreSuelto => $grupos): ?>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-texto-2">
                            <?= e((string) $nombreSuelto) ?>
                        </p>
                        <ul class="mb-3 divide-y divide-borde">
                            <?php foreach (($grupos['madurez'] ?? []) as $control): ?>
                                <?= $ficha($control, 'madurez', null) ?>
                            <?php endforeach; ?>
                            <?php foreach (($grupos['riesgo'] ?? []) as $control): ?>
                                <?= $ficha($control, 'nivel_riesgo', 'dimensiones') ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
</div>
