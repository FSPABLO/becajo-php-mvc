<?php

declare(strict_types=1);

/**
 * Monitor de salud — la ANTESALA: elegir la base de datos.
 *
 * Una ficha por instancia vigilada, y la consola entera vive en
 * /monitoreo/{clave}. Es el mismo par que el histórico de auditorías
 * (/evaluacion/comparar y /evaluacion/comparar/{empresa}), con la MISMA
 * distribución —components/rejilla-facetas—: son el mismo gesto sobre otro
 * sujeto, y dos antesalas que se recorren distinto obligan a aprender dos veces
 * dónde está el buscador.
 *
 * ── Lo que esta vista NO hace ────────────────────────────────────────────────
 *
 * Igual que la consola: no normaliza, no promedia y no decide bandas. La cifra
 * y la banda de cada ficha son las que trae la muestra evaluada. Lo único que
 * se decide aquí es qué RÓTULO lleva cada valor.
 *
 * ── Es una maqueta ───────────────────────────────────────────────────────────
 *
 * Las fichas salen de config/monitor-mockup.php, como todo el módulo. Ver la
 * advertencia de CLAUDE.md antes de enseñar esto como producto.
 *
 * @var \App\Core\Vista $vista
 * @var int $total  Instancias vigiladas, sin filtrar.
 * @var list<array<string, mixed>> $instancias  Las que pasan el filtro, ordenadas.
 * @var array<string, array<string, int>> $facetas
 * @var array<string, list<string>> $seleccion
 * @var string|null $buscar
 * @var string $orden
 * @var list<string> $ordenes
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

/*
 * Rótulos de los grupos. Los VALORES viajan en la URL sin traducir
 * (`banda[]=CRITICO`), para que una dirección compartida valga en los dos
 * idiomas; solo lo que se lee se traduce.
 */
$titulosGrupo = [
    'banda'    => $vista->t('mon.filtro_banda'),
    'conexion' => $vista->t('mon.filtro_conexion'),
    'entorno'  => $vista->t('mon.filtro_entorno'),
    'motor'    => $vista->t('mon.filtro_motor'),
];

/*
 * El estado de la conexión usa las MISMAS tres palabras que el selector de la
 * consola (components/monitor-selector): una base no puede estar «Sin conexión»
 * en la ficha y «Sin respuesta» un clic después.
 */
$etiquetaConexion = [
    'COMPLETA' => $vista->t('mon.conectada'),
    'PARCIAL'  => $vista->t('mon.muestra_incompleta'),
    'FALLIDA'  => $vista->t('mon.sin_conexion'),
];

// Etiqueta traducida de una banda. El tono lo da tonoBanda(), en funciones.php.
$etiquetaBanda = static fn (?string $banda): string =>
    $banda === null ? $vista->t('mon.banda_sin_dato') : $vista->t('mon.banda_' . strtolower($banda));

// Entorno y motor no se traducen: son el dato de la instancia tal como se
// registró, y traducirlo sería inventarle un nombre.
$etiquetaValor = static fn (string $grupo, string $valor): string => match ($grupo) {
    'banda'    => $valor === 'SIN' ? $vista->t('mon.filtro_sin_indice') : $etiquetaBanda($valor),
    'conexion' => $etiquetaConexion[$valor] ?? $valor,
    default    => $valor,
};

$etiquetasOrden = [
    'atencion' => $vista->t('mon.orden_atencion'),
    'indice'   => $vista->t('mon.orden_indice'),
    'reciente' => $vista->t('mon.orden_reciente'),
    'clave'    => $vista->t('mon.orden_clave'),
];

// Formato español, un decimal fijo: el mismo que la consola. El guion es el hueco.
$cifra = static fn (?float $v): string => $v === null ? '—' : number_format($v, 1, ',', '');

/*
 * Antigüedad de la última muestra. Por debajo de una hora, minutos; por encima,
 * horas y minutos, porque «hace 134 min» obliga a dividir de cabeza.
 */
$antiguedad = static function (int $minutos) use ($vista): string {
    if ($minutos < 60) {
        return $vista->t('mon.hace_min', (string) $minutos);
    }

    return $vista->t('mon.hace_horas', (string) intdiv($minutos, 60), (string) ($minutos % 60));
};

/*
 * LA FICHA ES MINIMALISTA, como la de una empresa: aquí solo se ELIGE. Los
 * componentes, los procesos y las series de tiempo son lectura de consola, y la
 * consola está a un clic. Para elegir bastan cuatro cosas: qué base es, de qué
 * motor y entorno, cuándo se la miró por última vez y cómo quedó.
 */
$dibujarFicha = static function (array $ins) use ($vista, $cifra, $antiguedad, $etiquetaBanda, $etiquetaConexion): string {
    $publica = $ins['isbd'] !== null;

    ob_start();
    ?>
    <?php
    /*
     * La tarjeta ENTERA es el enlace: nada más se pulsa dentro. La clave viaja
     * con rawurlencode() aunque hoy sean mayúsculas y dígitos — cuando las
     * instancias salgan de la base, su clave será texto de quien las registró.
     */
    ?>
    <a href="<?= e($vista->url('monitoreo/' . rawurlencode((string) $ins['clave']))) ?>"
       class="rv-extruido rv-interactivo flex h-full flex-col items-center gap-2 rounded-rv-lg border border-borde bg-superficie px-5 py-6 text-center">

        <?php
        /*
         * El ícono es IDENTIDAD, no estado: va en el acento y es el mismo en las
         * cinco bandas y en una base caída. Teñirlo sería un segundo canal de
         * estado sin etiqueta al lado, que es lo que pill() existe para evitar.
         */
        ?>
        <?= icono('base-datos', 'h-12 w-12 shrink-0 text-primario') ?>

        <h3 class="rv-titulo mt-1 text-base font-semibold leading-tight text-texto">
            <?= e((string) $ins['clave']) ?>
        </h3>

        <p class="text-xs text-texto-2">
            <?= e((string) $ins['motor']) ?> · <?= e((string) $ins['entorno']) ?>
        </p>

        <?php
        /*
         * La antigüedad va SIEMPRE a la vista (§9): un tablero que enseña una
         * cifra de hace dos horas como si fuera de ahora es la falla más común
         * de los monitores caseros. En la consola se retiró; en la ficha es lo
         * que dice si el número de abajo se puede creer.
         */
        ?>
        <p class="text-xs text-texto-2">
            <?= e($vista->t('mon.ultima_muestra', $antiguedad((int) $ins['hace_min']))) ?>
        </p>

        <?php
        /*
         * Sin índice publicado la cifra es un guion y la pastilla dice POR QUÉ
         * —muestra incompleta o sin conexión— en gris. Nunca un 0 ni un color
         * de estado: una base que no respondió no está en CRÍTICO, está sin
         * medir (invariante 3).
         *
         * La cifra lleva su rótulo en sr-only, igual que en la ficha de empresa:
         * quien no ve la tarjeta oye «ISBD 40,0» y no un número suelto.
         */
        ?>
        <div class="mt-auto flex flex-wrap items-center justify-center gap-2 pt-3">
            <span class="tabular text-sm font-semibold <?= $publica ? 'text-texto' : 'text-na' ?>">
                <span class="sr-only"><?= e($vista->t('mon.isbd')) ?>:</span>
                <?= e($cifra($publica ? (float) $ins['isbd'] : null)) ?>
            </span>
            <?= $publica
                ? pill(tonoBanda((string) $ins['banda']), $etiquetaBanda((string) $ins['banda']))
                : pill('na', $etiquetaConexion[$ins['muestra']] ?? $vista->t('mon.banda_sin_dato')) ?>
        </div>
    </a>
    <?php

    return (string) ob_get_clean();
};
?>
<?php
/*
 * En la caja centrada de las demás pantallas del módulo, y no a todo el ancho
 * como la consola: aquí no hay matriz que se recorte, y una rejilla de tres
 * fichas estirada a dos mil píxeles deja cada tarjeta flotando en un campo vacío.
 */
?>
<section class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-8">

    <?php
    /*
     * Sin encabezado visible, como la antesala del histórico: la miga ya dice
     * «Monitoreo / Monitor». El <h1> se queda en sr-only para quien no ve el
     * diseño.
     */
    ?>
    <h1 class="sr-only"><?= e($vista->t('mon.titulo')) ?></h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?= $vista->componente('rejilla-facetas', [
        'vista'            => $vista,
        'accion'           => 'monitoreo',
        'buscar'           => $buscar,
        'etiquetaBusqueda' => $vista->t('mon.buscar_etiqueta'),
        'marcadorBusqueda' => $vista->t('mon.buscar_marcador'),
        'facetas'          => $facetas,
        'seleccion'        => $seleccion,
        'titulosGrupo'     => $titulosGrupo,
        'etiquetaValor'    => $etiquetaValor,
        /*
         * El motor es el grupo que más crece con la cartera —cada versión es
         * una opción—, así que nace plegado, como las áreas del histórico. El
         * entorno se queda abierto: en la práctica son cuatro o cinco.
         */
        'gruposCerrados'   => ['motor'],
        'orden'            => $orden,
        'ordenes'          => $ordenes,
        'etiquetasOrden'   => $etiquetasOrden,
        'recuento'         => $vista->t('mon.instancias_rango', (string) count($instancias), (string) $total),
        'filas'            => $instancias,
        'ficha'            => $dibujarFicha,
        'vacioTitulo'      => $vista->t('mon.sin_instancias_filtro'),
        'vacioTexto'       => $vista->t('eval.sin_empresas_filtro_texto'),
    ]) ?>
</section>
