<?php

declare(strict_types=1);

/**
 * Diseño del módulo interno (panel de trabajo).
 *
 * El sitio público y el módulo de auditorías dejan de compartir marco. Ahí
 * fuera la navegación es una barra superior que acompaña una lectura vertical;
 * aquí dentro la navegación es una BARRA LATERAL fija, porque el trabajo del
 * auditor no es leer una página: es saltar entre auditorías, catálogo y
 * remediaciones sin perder de vista dónde está. Una barra lateral deja los
 * destinos visibles todo el tiempo y libera el ancho superior para el contexto
 * de la pantalla actual.
 *
 * El menú se arma AQUÍ, no en cada parcial, y se reparte ya resuelto a los dos
 * que lo necesitan: la barra lateral lo pinta y la superior saca de él la miga
 * de pan. Así el elemento activo se calcula una sola vez y las dos piezas no
 * pueden contradecirse.
 *
 * @var \App\Core\Vista        $vista
 * @var string                 $contenido
 * @var array<string, mixed>   $empresa
 * @var array<string, string>  $meta
 * @var list<array{etiqueta: string, descripcion: string, destino: string, icono: string}> $herramientas
 * @var list<string>|null      $hojas    Hojas de estilo propias de la página.
 * @var list<string>|null      $guiones  Guiones (scripts) propios de la página.
 * @var \App\Models\Entidades\Usuario|null $usuarioActual
 * @var string|null            $rutaActual
 * @var bool|null              $lateralOculta  Barra lateral plegada (cookie).
 */
$lateralOculta = $lateralOculta ?? false;
$hojas         = $hojas ?? [];
$guiones       = $guiones ?? [];
$herramientas  = $herramientas ?? [];
$usuarioActual = $usuarioActual ?? null;
$rutaActual    = $rutaActual ?? '/';

$esAdministrador = $usuarioActual !== null && $usuarioActual->esAdministrador();

/*
 * Los grupos del menú. El orden es el del trabajo real: primero lo que hace un
 * auditor todos los días, después lo que solo toca quien administra el
 * catálogo, y al final la documentación de consulta.
 */
$grupos = [[
    'titulo'    => $vista->t('panel.grupo_auditorias'),
    'elementos' => [
        ['etiqueta' => $vista->t('nav.mis_auditorias'),      'ruta' => '/evaluacion',          'icono' => 'tablero'],
        ['etiqueta' => $vista->t('eval.nueva_auditoria'),    'ruta' => '/evaluacion/nueva',    'icono' => 'chispa'],
        ['etiqueta' => $vista->t('eval.comparar_historico'), 'ruta' => '/evaluacion/comparar', 'icono' => 'grafica'],
    ],
]];

if ($esAdministrador) {
    $grupos[] = [
        'titulo'    => $vista->t('panel.grupo_administracion'),
        'elementos' => [
            ['etiqueta' => $vista->t('panel.catalogo_controles'),    'ruta' => '/catalogo',               'icono' => 'disco'],
            ['etiqueta' => $vista->t('panel.matriz_cid'),            'ruta' => '/catalogo/matriz',        'icono' => 'escudo'],
            ['etiqueta' => $vista->t('panel.remediaciones_vencidas'), 'ruta' => '/remediaciones/vencidas', 'icono' => 'alerta'],
        ],
    ];
}

/*
 * Las herramientas salen del mismo arreglo que alimenta el menú del sitio
 * público, así que aquí hay que descartar las que ya están arriba: "Diagnóstico
 * de salud" apunta a /evaluacion, que es la primera entrada del menú. Filtrar
 * por destino —y no por etiqueta— es lo que mantiene esto correcto si mañana se
 * traduce o se renombra la entrada en config/contenido.php.
 */
$yaEnMenu = [];
foreach ($grupos as $grupo) {
    foreach ($grupo['elementos'] as $elemento) {
        $yaEnMenu[$elemento['ruta']] = true;
    }
}

$referencia = [];
foreach ($herramientas as $herramienta) {
    $ruta = '/' . ltrim($herramienta['destino'], '/');

    if (str_starts_with($ruta, '/') && !isset($yaEnMenu[$ruta]) && !str_starts_with($ruta, '/#')) {
        $referencia[] = [
            'etiqueta' => $herramienta['etiqueta'],
            'ruta'     => $ruta,
            'icono'    => $herramienta['icono'],
        ];
    }
}

if ($referencia !== []) {
    $grupos[] = ['titulo' => $vista->t('panel.grupo_referencia'), 'elementos' => $referencia];
}

/*
 * Cuál es el elemento activo.
 *
 * Gana la ruta declarada MÁS LARGA que la actual empiece por ella. Sin esa
 * regla, /evaluacion/nueva encendería a la vez "Mis auditorías" y "Nueva
 * auditoría", porque la primera es prefijo de la segunda. Con ella,
 * /evaluacion/81/resultados —que no tiene entrada propia— ilumina "Mis
 * auditorías", que es de donde se llegó.
 */
$rutaActiva   = '';
$migaGrupo    = null;
$migaElemento = null;

foreach ($grupos as $grupo) {
    foreach ($grupo['elementos'] as $elemento) {
        $ruta = $elemento['ruta'];
        $bajoEsaRuta = $rutaActual === $ruta || str_starts_with($rutaActual, $ruta . '/');

        if ($bajoEsaRuta && strlen($ruta) > strlen($rutaActiva)) {
            $rutaActiva   = $ruta;
            $migaGrupo    = $grupo['titulo'];
            $migaElemento = $elemento['etiqueta'];
        }
    }
}
?>
<?php
/*
 * data-lateral lo escribe el servidor, no el guion: la página tiene que nacer
 * con la barra en el estado en que el auditor la dejó. Va en <html> y no en
 * <body> porque las reglas de rivendel.css cuelgan de él.
 */
?>
<!DOCTYPE html>
<html lang="<?= e($vista->idiomaActual() === 'en' ? 'en' : 'es-CR') ?>"
      <?= $lateralOculta ? 'data-lateral="oculta"' : '' ?>>
<head>
    <?= $vista->renderizar('partials/head', compact('meta', 'empresa', 'hojas')) ?>
</head>
<?php
/*
 * El módulo invierte el lienzo respecto del sitio público: pergamino
 * (.rv-claro) para el trabajo, y la barra lateral se queda de noche. No es un
 * tema conmutable —nadie puede poner el producto en claro desde la interfaz—,
 * es que un panel de trabajo se lee durante horas seguidas y el contraste
 * oscuro/claro entre navegación y contenido separa las dos cosas sin gastar ni
 * un borde ni un color de acento.
 *
 * Todo el marcado del módulo está escrito con tokens, así que la inversión la
 * siguen las vistas solas. Un color fijado a mano se rompería aquí.
 */
?>
<body class="rv-claro bg-fondo font-sans text-texto antialiased">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-rv focus:bg-primario focus:px-4 focus:py-2 focus:font-semibold focus:text-primario-texto">
        <?= e($vista->t('nav.saltar_contenido')) ?>
    </a>

    <?= $vista->renderizar('partials/panel/barra-lateral', [
        'empresa'       => $empresa,
        'grupos'        => $grupos,
        'rutaActiva'    => $rutaActiva,
        'usuarioActual' => $usuarioActual,
    ]) ?>

    <?php
    /*
     * La columna se aparta el ancho de la barra solo a partir de lg, y vuelve a
     * ocupar todo cuando la barra se pliega. Las dos cosas las resuelve
     * .rv-panel-columna en rivendel.css; ver ahí por qué no son utilidades.
     */
    ?>
    <div class="rv-panel-columna">

        <?= $vista->renderizar('partials/panel/barra-superior', [
            'migaGrupo'     => $migaGrupo,
            'migaElemento'  => $migaElemento,
            'usuarioActual' => $usuarioActual,
            'rutaActual'    => $rutaActual,
            'lateralOculta' => $lateralOculta,
        ]) ?>

        <main id="contenido"><?= $contenido ?></main>

        <?php
        /*
         * Pie mínimo. El pie del sitio público NO se reutiliza: ahí abajo vive
         * la sección de contacto, y ofrecerle un formulario comercial a quien
         * ya inició sesión no tiene sentido.
         */
        ?>
        <footer class="border-t border-borde px-6 py-6 lg:px-8">
            <div class="flex flex-col gap-2 text-xs text-texto-2 sm:flex-row sm:items-center sm:justify-between">
                <p>
                    &copy; <span class="tabular"><?= e((string) $empresa['anio']) ?></span> <?= e($empresa['nombre']) ?>.
                    <?= e($vista->t('pie.proyecto')) ?>
                </p>
                <p><?= e($vista->t('pie.arquitectura')) ?> <span class="font-mono"><?= e(PHP_VERSION) ?></span></p>
            </div>
        </footer>
    </div>

    <script src="<?= e($vista->recurso('assets/js/principal.js')) ?>" defer></script>
    <?php foreach ($guiones as $guion): ?>
    <script src="<?= e($vista->recurso($guion)) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
