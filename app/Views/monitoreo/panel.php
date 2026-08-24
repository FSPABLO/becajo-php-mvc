<?php

declare(strict_types=1);

/**
 * Monitor de salud — pantalla principal (maqueta del frente 4).
 *
 * Es la ruta `/monitoreo` del §9 del plan de la parte 2, reordenada como PANEL
 * DE OPERACIÓN: primero el instrumento (medidor, los tres índices y la
 * tendencia), después la evidencia (mediciones y alertas). Antes la pantalla
 * abría con una rejilla de fichas, que es un índice de instancias y no un
 * instrumento — se leía como un menú, no como un tablero.
 *
 * ── El lienzo de la consola ──────────────────────────────────────────────────
 *
 * La consola NO tiene relleno propio: se delimita solo con un borde dorado
 * (`border-oro/45`), el mismo token con el que el plan destacado de la portada
 * se distingue del gratuito. Sus tarjetas quedan en `bg-superficie` sobre el
 * pergamino de la página, igual que las de fuera.
 *
 * DESVIACIÓN DECLARADA (§5.2), la misma que ya está anotada en
 * `home/secciones/planes.php`: el oro está reservado a la referencia normativa
 * y aquí el borde es ORNAMENTO. Dentro de la consola el oro sigue significando
 * lo que debe —los códigos de métrica en `.rv-id`—, y no compite con el borde
 * porque uno es contorno y el otro es tinta.
 *
 * Sin relleno tampoco lleva relieve: el neumorfismo extruye una SUPERFICIE, y
 * una sombra alrededor de algo transparente se lee como un error de pintado.
 * El borde solo, que es lo que queda, basta para decir dónde empieza y termina.
 *
 * ── Lo que esta vista NO hace ────────────────────────────────────────────────
 *
 * No normaliza, no promedia, no aplica topes y no decide bandas. Recibe una
 * MUESTRA YA EVALUADA y la pinta. Esa frontera es del contrato de la muestra
 * —el recolector no razona, el motor no consulta, la vista no calcula—. Lo
 * único que decide aquí es el ORDEN del selector, y ordenar por una banda que
 * ya viene dada no es calcular salud: es poner delante lo que hay que atender.
 *
 * @var \App\Core\Vista $vista
 * @var array<string, array<string, mixed>> $instancias
 * @var array<string, mixed> $seleccionada
 * @var array<string, list<array<string, mixed>>> $procesos
 * @var float $pisoCobertura
 * @var array<string, float> $pesos
 * @var string|null $noEncontrada  Instancia pedida en la URL que no existe.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$noEncontrada = $noEncontrada ?? null;

/*
 * Ayudantes de formato, declarados una vez y repartidos a los componentes.
 * Van como closures y no como funciones globales porque son de esta pantalla:
 * «hace 4 min» solo significa algo junto a una muestra.
 */

// Formato español: coma decimal, un decimal fijo. El guion largo es el hueco.
$cifra = static fn (?float $v): string =>
    $v === null ? '—' : number_format($v, 1, ',', '');

$porciento = static fn (?float $v): string =>
    $v === null ? '—' : number_format($v, 1, ',', '') . ' %';

/*
 * Antigüedad del último dato. Por debajo de una hora, minutos; por encima,
 * horas y minutos, porque «hace 134 min» obliga a dividir de cabeza justo
 * cuando la cifra importa.
 */
$antiguedad = static function (int $minutos) use ($vista): string {
    if ($minutos < 60) {
        return $vista->t('mon.hace_min', (string) $minutos);
    }

    return $vista->t('mon.hace_horas', (string) intdiv($minutos, 60), (string) ($minutos % 60));
};

// Etiqueta traducida de una banda. El tono lo da tonoBanda(), en funciones.php.
$etiquetaBanda = static fn (?string $banda): string =>
    $banda === null ? $vista->t('mon.banda_sin_dato') : $vista->t('mon.banda_' . strtolower($banda));

/*
 * Orden del selector: primero lo que hay que atender.
 *
 * La severidad es un orden declarado, no calculado. Una muestra que no publica
 * ISBD (caída o incompleta) va PRIMERO y no al final: «no sé cómo está» es más
 * urgente que «está degradada», porque la segunda al menos se está midiendo.
 */
$severidad = static function (array $ins): int {
    if ($ins['muestra'] === 'FALLIDA') {
        return 0;
    }

    if ($ins['isbd'] === null) {
        return 1;
    }

    return match ($ins['banda']) {
        'CRITICO'     => 2,
        'DEGRADADO'   => 3,
        'ADVERTENCIA' => 4,
        'SALUDABLE'   => 5,
        default       => 6,
    };
};

$cartera = array_values($instancias);
usort($cartera, static fn (array $a, array $b): int => $severidad($a) <=> $severidad($b));

$sel     = $seleccionada;
$publica = $sel['isbd'] !== null;
$caida   = $sel['muestra'] === 'FALLIDA';

/*
 * Los tres sumandos del ISBD. El filtro por `en_isbd` deja fuera a CONSULTAS,
 * que la muestra sigue trayendo —se recolecta y se evalúa— pero que esta
 * pantalla ya no pinta en ninguna parte.
 */
$indices = array_values(array_filter($sel['componentes'], static fn (array $c): bool => $c['en_isbd']));

/*
 * El motivo por el que no hay índice. Se resuelve UNA vez y viaja al medidor y
 * al selector: si cada pieza lo dedujera por su cuenta, una podría decir
 * «muestra incompleta» y la otra «sin respuesta» sobre la misma muestra.
 */
$motivoSinIndice = $caida
    ? $vista->t('mon.sin_respuesta')
    : $vista->t('mon.muestra_incompleta');

/*
 * Qué ficha nace desplegada: la PEOR de las tres, no la primera.
 *
 * Quien abre un monitor no viene a leer los tres índices en orden; viene a ver
 * qué está mal. Abrir siempre por PROCESOS obligaría a un clic extra justo en
 * el caso en que hay prisa, y ese clic se paga siempre aunque el fallo esté en
 * memoria. Con todo en verde da igual cuál se abra, así que no se pierde nada.
 */
$peorIndice = null;
$peorRango  = -1;
$rango = ['CRITICO' => 5, 'DEGRADADO' => 4, 'ADVERTENCIA' => 3, 'SALUDABLE' => 2, 'OPTIMO' => 1];

foreach ($indices as $comp) {
    // Sin dato pesa más que saludable: no saber es peor que estar bien.
    $peso = $comp['publicado'] === null ? 6 : ($rango[$comp['banda']] ?? 0);

    if ($peso > $peorRango) {
        $peorRango  = $peso;
        $peorIndice = (string) $comp['clave'];
    }
}

$indiceActivo = $peorIndice ?? 'PROCESOS';

/*
 * La luz del ISBD sale de las luces de sus tres índices, no de su propio
 * número: rojo si alguno está en rojo, verde solo si los tres están en verde.
 *
 * Hoy esto coincide siempre con la banda del ISBD, y no por casualidad: la
 * regla del eslabón más débil (§5.4) ya topa el índice en la frontera del peor
 * componente, así que un componente en rojo arrastra el número al rojo. Se
 * calcula igual y aparte porque son dos afirmaciones distintas —una sobre el
 * número y otra sobre el color— y que hoy coincidan es una comprobación, no
 * una razón para escribir solo una.
 */
$luzGeneral = semaforoGeneral(array_map(
    static fn (array $comp): string => semaforo(
        $comp['publicado'] === null ? null : (string) $comp['banda'],
    ),
    $indices,
));
?>
<?php
/*
 * A TODO EL ANCHO, sin el `max-w-6xl` que centra el resto del módulo.
 *
 * El tablero es una matriz de seis columnas de métrica más el detalle: es el
 * contenido de este módulo que peor lleva que le recorten el ancho, y con la
 * caja centrada la tabla terminaba pidiendo barra horizontal en pantallas donde
 * sobraba sitio a los lados.
 *
 * Lo que NO se estira es la prosa: los párrafos largos llevan su propio tope de
 * medida, porque una línea de doscientos caracteres deja de leerse por mucho
 * ancho que haya.
 */
?>
<section class="w-full px-6 py-8 lg:px-8">

    <?php
    /*
     * El <h1> se queda en sr-only aunque ya no haya rótulo visible: la miga de
     * pan de la barra superior es navegación, no encabezado, y una página sin
     * h1 rompe el salto por encabezados de quien no ve el diseño. El rótulo
     * «Monitoreo continuo» que iba aquí se retiró: la miga ya dice dónde está
     * uno y el nombre de la instancia manda en la pantalla.
     */
    ?>
    <h1 class="sr-only"><?= e($vista->t('mon.titulo')) ?></h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($noEncontrada !== null): ?>
        <p class="rv-hundido mb-6 rounded-rv border border-borde bg-superficie px-4 py-3 text-sm text-texto-2">
            <?= e($vista->t('mon.instancia_no_encontrada', $noEncontrada)) ?>
        </p>
    <?php endif; ?>

    <?php /* ── Qué base se está mirando ── */ ?>
    <?= $vista->componente('monitor-selector', [
        'vista'         => $vista,
        'cartera'       => $cartera,
        'activa'        => $sel,
        'cifra'         => $cifra,
        'etiquetaBanda' => $etiquetaBanda,
    ]) ?>

    <?php
    /*
     * Cómo fue cada conexión. El contrato de la muestra guarda `contextos`
     * justo para esto: sin ese campo, cinco lecturas en error no dirían si
     * fallaron cinco métricas o una sola conexión. Solo se pinta cuando hay
     * algo que contar — si las dos respondieron, la fila sobra.
     */
    $contextosMalos = array_filter(
        $sel['contextos'],
        static fn (array $c): bool => ($c['estado'] ?? 'OK') !== 'OK',
    );
    ?>
    <?php if ($contextosMalos !== []): ?>
        <ul class="mt-4 space-y-2">
            <?php foreach ($contextosMalos as $nombre => $contexto): ?>
                <li class="rv-hundido flex flex-wrap items-center gap-x-3 gap-y-1 rounded-rv border border-borde bg-superficie px-4 py-2.5 text-sm">
                    <?= pill('crit', $vista->t('mon.contexto_' . strtolower((string) $nombre))) ?>
                    <span class="rv-id text-xs text-texto-2"><?= e((string) ($contexto['motivo'] ?? '')) ?></span>
                    <?php
                    /*
                     * La duración va AQUÍ y no en la cabecera: treinta segundos
                     * clavados es la firma de un tiempo límite agotado, y esa
                     * cifra solo significa algo junto al error que la explica.
                     */
                    ?>
                    <span class="tabular ml-auto text-xs text-texto-2">
                        <?= e($vista->t('mon.duracion')) ?>:
                        <?= e(number_format((int) $contexto['duracion_ms'], 0, ',', ' ')) ?> ms
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php
    /*
     * ── CONSOLA DE OPERACIÓN ────────────────────────────────────────────────
     *
     * Sin relleno propio: solo el borde dorado (ver la cabecera del archivo).
     */
    ?>
    <section class="mt-5 rounded-rv-lg border border-oro/45 p-5 sm:p-6">

        <div class="mb-5 flex flex-wrap items-start justify-between gap-x-6 gap-y-1">
            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-texto-2">
                <?= e($vista->t('mon.consola')) ?>
            </h2>

            <div class="flex items-center gap-3">
                <p class="text-xs text-texto-2">
                    <span class="rv-id text-oro-texto"><?= e((string) $sel['clave']) ?></span>
                    · <?= e((string) $sel['motor']) ?>
                    · <?= e((string) $sel['entorno']) ?>
                </p>

                <?php
                /*
                 * Ayuda de TODA la consola, y por eso vive en su cabecera y no
                 * junto a las fichas: los dos textos explican cómo se colorea el
                 * medidor y cómo se compone su cifra, que es material del panel
                 * entero. Estaban siempre visibles bajo las tarjetas —dos
                 * párrafos de letra pequeña que se leen una vez y después solo
                 * empujan el resto hacia abajo—.
                 */
                ?>
                <?= $vista->componente('monitor-ayuda', [
                    'vista'    => $vista,
                    'id'       => 'ayuda-consola',
                    'etiqueta' => $vista->t('mon.ayuda_consola'),
                    'ancho'    => 'w-[30rem]',
                    'parrafos' => [
                        $vista->t('mon.semaforo_leyenda'),
                        $vista->t('mon.formula'),
                    ],
                ]) ?>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,17rem)_minmax(0,1fr)]">

            <?php /* Columna izquierda: el medidor y, si no hay cifra, por qué falta. */ ?>
            <div>
                <?= $vista->componente('monitor-medidor', [
                    'vista'         => $vista,
                    'isbd'          => $sel['isbd'],
                    'banda'         => $sel['banda'],
                    'luz'           => $luzGeneral,
                    'motivo'        => $motivoSinIndice,
                    'cifra'         => $cifra,
                    'etiquetaBanda' => $etiquetaBanda,
                ]) ?>

                <?php if ($caida): ?>
                    <div class="rv-hundido mt-5 rounded-rv border border-borde bg-superficie px-4 py-3">
                        <p class="text-sm leading-relaxed text-texto-2">
                            <?= e($vista->t('mon.caida_explicacion')) ?>
                        </p>

                        <?php if (($sel['ultimo_conocido'] ?? null) !== null): ?>
                            <?php
                            /*
                             * El último valor conocido va SEPARADO y con su
                             * antigüedad pegada. No se recicla como si fuera de
                             * ahora: es información distinta y hay que poder
                             * distinguirla de un vistazo.
                             */
                            ?>
                            <div class="mt-3 border-t border-borde pt-3">
                                <p class="text-xs font-medium uppercase tracking-wider text-texto-2">
                                    <?= e($vista->t('mon.ultimo_conocido')) ?>
                                </p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="tabular text-2xl font-semibold text-texto">
                                        <?= e($cifra((float) $sel['ultimo_conocido']['isbd'])) ?>
                                    </span>
                                    <?= pill(
                                        tonoBanda((string) $sel['ultimo_conocido']['banda']),
                                        $etiquetaBanda((string) $sel['ultimo_conocido']['banda']),
                                    ) ?>
                                    <span class="text-xs text-texto-2">
                                        <?= e($antiguedad((int) $sel['ultimo_conocido']['hace_min'])) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif (!$publica): ?>
                    <?php
                    /*
                     * INVARIANTE 4 — cobertura mínima. Con la evidencia a medias
                     * no se publica un número: un índice calculado sobre la
                     * mitad de las métricas es peor que ningún índice, porque
                     * parece uno bueno.
                     */
                    ?>
                    <div class="rv-hundido mt-5 rounded-rv border border-borde bg-superficie px-4 py-3">
                        <p class="text-sm leading-relaxed text-texto-2">
                            <?= e($vista->t(
                                'mon.incompleta_explicacion',
                                $porciento((float) $sel['cobertura']['pct']),
                                $porciento($pisoCobertura),
                            )) ?>
                        </p>
                    </div>
                <?php endif; ?>

            </div>

            <?php
            /*
             * Columna derecha: SOLO las tres fichas. La tabla de procesos y la
             * tendencia salieron de aquí al ancho completo de la consola —esta
             * columna mide diecisiete rems menos que la sección y la columna de
             * «detalle y recomendación» se cortaba a media frase, que es perder
             * justo la parte que hace útil la tabla—.
             */
            ?>
            <div>

                <div>
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-texto-2">
                        <?= e($vista->t('mon.componentes')) ?>
                    </h3>

                    <?php if ($indices === []): ?>
                        <p class="rv-hundido rounded-rv border border-borde bg-superficie px-4 py-8 text-center text-sm text-texto-2">
                            <?= e($vista->t('mon.componentes_vacio')) ?>
                        </p>
                    <?php else: ?>
                        <?= $vista->componente('monitor-indices', [
                            'vista'         => $vista,
                            'indices'       => $indices,
                            'procesos'      => $procesos,
                            'activo'        => $indiceActivo,
                            'cifra'         => $cifra,
                            'etiquetaBanda' => $etiquetaBanda,
                        ]) ?>

                    <?php endif; ?>
                </div>

            </div>
        </div>

            <?php
            /*
             * ── Procesos del índice elegido ─────────────────────────────
             *
             * Un panel por índice, justo DEBAJO de las fichas que los
             * abren: la tabla tiene que salir donde la mano acaba de
             * pulsar, no al final de la página. Los tres se pintan y el
             * guion oculta dos; sin guion quedan los tres visibles, que
             * es más largo pero no es un error.
             */
            ?>
            <?php foreach ($indices as $comp): ?>
                <?php $claveIndice = (string) $comp['clave']; ?>
                <div id="procesos-<?= e(strtolower($claveIndice)) ?>"
                     role="tabpanel"
                     aria-labelledby="ficha-<?= e(strtolower($claveIndice)) ?>"
                     data-panel-indice="<?= e($claveIndice) ?>"
                     tabindex="0"
                     <?= $claveIndice === $indiceActivo ? '' : 'hidden' ?>
                     class="rv-extruido mt-5 rounded-rv-lg border border-borde bg-superficie p-5">

                    <?php
                    /*
                     * El componente trae su propio encabezado y su botón de
                     * ayuda: título y tabla son una pieza, y tenerlos aquí
                     * obligaba a esta vista a saber cómo se rotula aquello.
                     */
                    ?>
                    <?= $vista->componente('monitor-procesos', [
                        'vista'    => $vista,
                        'columnas' => $procesos[$claveIndice]['columnas'] ?? [],
                        'fichas'   => $procesos[$claveIndice]['fichas'] ?? [],
                        'filas'    => $procesos[$claveIndice]['filas'] ?? [],
                        'indice'   => $claveIndice,
                    ]) ?>
                </div>
            <?php endforeach; ?>

            <?php
            /*
             * ── LAS DOS SERIES DE TIEMPO, UNA AL LADO DE LA OTRA ─────────────
             *
             * Tendencia del ISBD a la izquierda y memoria a la derecha: son la
             * misma instancia mirada en el tiempo —cómo va el índice y cómo va
             * la memoria— y en paralelo se comparan de un vistazo, que es lo que
             * un DBA hace con ellas: ¿el índice cae cuando la memoria sube?
             *
             * Los dos lienzos se dibujan a una PROPORCIÓN distinta de la que
             * tenían a todo el ancho —ver el `$ancho` de cada componente—. Un
             * viewBox de 3,2:1 metido en media columna sale espachurrado, y su
             * texto, que escala con el dibujo, se queda en letra de nueve
             * píxeles: es exactamente el motivo por el que la memoria había
             * salido de la columna la vez anterior.
             *
             * Por debajo de `lg` vuelven a apilarse. Y sin memoria la tendencia
             * ocupa las dos columnas: media tarjeta con el otro medio en blanco
             * se lee como algo que no cargó.
             */
            ?>
            <?php $hayMemoria = ($sel['memoria'] ?? null) !== null; ?>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">

                <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5<?= $hayMemoria ? '' : ' lg:col-span-2' ?>">
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-texto-2">
                        <?= e($vista->t('mon.tendencia')) ?>
                    </h3>

                    <?php if (($sel['tendencia'] ?? null) === null): ?>
                        <p class="rv-hundido rounded-rv border border-borde bg-fondo px-4 py-8 text-center text-sm text-texto-2">
                            <?= e($vista->t('mon.sin_tendencia')) ?>
                        </p>
                    <?php else: ?>
                        <?= $vista->componente('monitor-tendencia', [
                            'vista'      => $vista,
                            'tendencia'  => $sel['tendencia'],
                            'cifra'      => $cifra,
                        ]) ?>
                    <?php endif; ?>
                </div>

                <?php /* La memoria trae su propia tarjeta: entra directa a la rejilla. */ ?>
                <?php if ($hayMemoria): ?>
                    <?= $vista->componente('monitor-memoria', [
                        'vista'     => $vista,
                        'memoria'   => $sel['memoria'],
                        'instancia' => (string) $sel['clave'],
                    ]) ?>
                <?php endif; ?>
            </div>
    </section>

</section>
