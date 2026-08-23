<?php

declare(strict_types=1);

/**
 * Instrumento de consultoría para la administración de bases de datos.
 *
 * Esta vista solo ORGANIZA y DIBUJA. No calcula cumplimiento ni madurez: eso
 * ocurre en public/assets/js/instrumento.js, porque el consultor evalúa en vivo
 * y los números deben moverse mientras escribe, sin recargar la página.
 *
 * @var \App\Core\Vista                          $vista
 * @var array<string, string>                    $instrumento
 * @var list<\App\Models\Entidades\Dominio>      $dominios
 * @var list<\App\Models\Entidades\Proceso>      $procesos
 * @var list<\App\Models\Entidades\Control>      $controles
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 * @var list<array{norma: string, titulo: string, aporte: string}>   $marco
 * @var list<array{titulo: string, fuente: string, enlace: string}>  $referencias
 * @var \App\Models\Entidades\Usuario|null $usuarioActual
 */

/*
 * Agrupaciones de presentación. Se arman una sola vez aquí y se reparten a los
 * parciales: la pestaña Instrumento y la pestaña Cuestionario recorren
 * exactamente la misma estructura, y así no pueden desincronizarse.
 */
$procesosPorDominio = [];
foreach ($procesos as $proceso) {
    $procesosPorDominio[$proceso->dominio][] = $proceso;
}

$controlesPorProceso = [];
foreach ($controles as $control) {
    $controlesPorProceso[$control->proceso][] = $control;
}

/** Total de controles por dominio, para el contador de avance de las pastillas. */
$totalPorDominio = [];
foreach ($dominios as $dominio) {
    $totalPorDominio[$dominio->clave] = 0;

    foreach ($procesosPorDominio[$dominio->clave] ?? [] as $proceso) {
        $totalPorDominio[$dominio->clave] += count($controlesPorProceso[$proceso->numero] ?? []);
    }
}

$agrupacion = [
    'dominios'            => $dominios,
    'procesosPorDominio'  => $procesosPorDominio,
    'controlesPorProceso' => $controlesPorProceso,
    'totalPorDominio'     => $totalPorDominio,
];

$pestanas = [
    ['clave' => 'instrumento',  'etiqueta' => 'Instrumento',  'icono' => 'documento'],
    ['clave' => 'cuestionario', 'etiqueta' => 'Cuestionario', 'icono' => 'usuarios'],
    ['clave' => 'tablero',      'etiqueta' => 'Tablero',      'icono' => 'tablero'],
    ['clave' => 'marco',        'etiqueta' => 'Marco ISO',    'icono' => 'escudo'],
    ['clave' => 'referencias',  'etiqueta' => 'Referencias',  'icono' => 'libro'],
];

/*
 * Esta página se sirve en dos marcos (ver HerramientasController). El relleno
 * superior de la portadilla existe SOLO para dejar pasar la barra fija del
 * sitio público; la barra del panel es pegajosa y ocupa su propio sitio, así
 * que ahí ese hueco sería una franja vacía bajo el encabezado.
 */
$enPanel = $enPanel ?? false;
?>
<?php
/*
 * SheetJS por CDN, igual que Tailwind en partials/head.php: el proyecto no
 * usa Composer a propósito (CLAUDE.md), pero eso no dice nada de scripts de
 * navegador, y "Exportar Excel" abajo lo necesita. Va con "defer" y no
 * bloqueante, y SOLO en esta página — nadie más del sitio exporta a Excel,
 * así que no tiene sentido pagar el peso del script en el resto del sitio.
 */
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" defer></script>
<?php
/*
 * El instrumento vive SIEMPRE en pergamino con acento de oro, venga por el
 * marco público (lienzo de noche) o por el del módulo (que ya es claro). Es una
 * región declarada, no un tema: rv-claro pone el lienzo y rv-oro solo repunta
 * el acento al oro y retira el tinte de menta de --rv-elev, para que el único
 * verde de la pantalla siga siendo el de la escala de estado. Ver rivendel.css.
 *
 * Que se declaren las dos clases juntas es el contrato de rv-oro: sola no trae
 * paleta, y en el marco público no habría ninguna clara que heredar.
 */
?>
<div class="rv-claro rv-oro bg-elevado pb-24 <?= $enPanel ? 'pt-10' : 'pt-16' ?>">

    <?= $vista->renderizar('herramientas/parciales/encabezado-instrumento', [
        'instrumento' => $instrumento,
        'controles'   => $controles,
        'procesos'    => $procesos,
        'dominios'    => $dominios,
    ]) ?>

    <?php if ($usuarioActual === null): ?>
        <div class="mx-auto max-w-7xl px-6 pt-6 lg:px-8 no-imprimir">
            <?php /* Franja de mensaje: hundida, que es como el sistema marca
                     «aquí se recibe algo» (§4 del diseño general). */ ?>
            <div class="rv-hundido flex flex-wrap items-center justify-between gap-3 rounded-rv-lg border border-oro/30 bg-oro-tinte px-4 py-3 text-sm text-texto">
                <span>
                    Esto queda solo en este navegador y se puede perder. Si va a dar seguimiento a esta
                    consultoría, cree una cuenta y guarde sus auditorías de verdad.
                </span>
                <span class="flex shrink-0 gap-3">
                    <a href="<?= e($vista->url('ingresar')) ?>" class="font-semibold text-oro-texto hover:underline">
                        Iniciar sesión
                    </a>
                    <a href="<?= e($vista->url('registrarse')) ?>" class="font-semibold text-oro-texto hover:underline">
                        Crear cuenta
                    </a>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Barra de pestañas: adherida bajo el encabezado fijo del sitio (h-16). -->
    <div class="sticky top-16 z-30 border-b border-borde bg-elevado/95 backdrop-blur no-imprimir">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="flex gap-1.5 overflow-x-auto py-3" role="tablist"
                 aria-label="Secciones del instrumento">
                <?php foreach ($pestanas as $indice => $pestana): ?>
                    <button type="button"
                            role="tab"
                            id="pestana-<?= e($pestana['clave']) ?>"
                            data-pestana="<?= e($pestana['clave']) ?>"
                            aria-controls="panel-<?= e($pestana['clave']) ?>"
                            aria-selected="<?= $indice === 0 ? 'true' : 'false' ?>"
                            tabindex="<?= $indice === 0 ? '0' : '-1' ?>"
                            <?php /* Tecla: suelta sobresale, elegida se hunde y se
                                     llena de oro. El hundido lo dispara
                                     aria-selected, que el guion ya mantiene. */ ?>
                            class="rv-opcion flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold
                                   <?= $indice === 0
                                       ? 'bg-primario text-primario-texto'
                                       : 'bg-superficie text-texto-2 hover:text-texto' ?>">
                        <?= icono($pestana['icono'], 'h-4 w-4') ?>
                        <?= e($pestana['etiqueta']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-6 pt-8 lg:px-8">

        <div id="panel-instrumento" role="tabpanel" aria-labelledby="pestana-instrumento"
             data-panel="instrumento" tabindex="0">
            <?= $vista->renderizar('herramientas/parciales/pestana-instrumento', $agrupacion + [
                'escala' => $escala,
            ]) ?>
        </div>

        <div id="panel-cuestionario" role="tabpanel" aria-labelledby="pestana-cuestionario"
             data-panel="cuestionario" tabindex="0" hidden>
            <?= $vista->renderizar('herramientas/parciales/pestana-cuestionario', $agrupacion) ?>
        </div>

        <div id="panel-tablero" role="tabpanel" aria-labelledby="pestana-tablero"
             data-panel="tablero" tabindex="0" hidden>
            <?= $vista->renderizar('herramientas/parciales/pestana-tablero', [
                'procesos'            => $procesos,
                'dominios'            => $dominios,
                'controlesPorProceso' => $controlesPorProceso,
            ]) ?>
        </div>

        <div id="panel-marco" role="tabpanel" aria-labelledby="pestana-marco"
             data-panel="marco" tabindex="0" hidden>
            <?= $vista->renderizar('herramientas/parciales/pestana-marco', [
                'marco'  => $marco,
                'escala' => $escala,
            ]) ?>
        </div>

        <div id="panel-referencias" role="tabpanel" aria-labelledby="pestana-referencias"
             data-panel="referencias" tabindex="0" hidden>
            <?= $vista->renderizar('herramientas/parciales/pestana-referencias', [
                'referencias' => $referencias,
            ]) ?>
        </div>
    </div>
</div>

<?php
/*
 * Catálogo mínimo que el guion necesita para exportar el CSV y para nombrar los
 * dominios en el tablero. Va como JSON en un <script type="application/json">
 * y no como variables sueltas: así el navegador no ejecuta nada y el escapado
 * lo hace json_encode, no una concatenación de cadenas.
 */
$catalogo = [
    'dominios'  => array_map(
        static fn ($dominio): array => ['clave' => $dominio->clave, 'nombre' => $dominio->nombre],
        $dominios,
    ),
    'procesos'  => array_map(
        static fn ($proceso): array => [
            'numero'  => $proceso->numero,
            'nombre'  => $proceso->nombre,
            'dominio' => $proceso->dominio,
        ],
        $procesos,
    ),
    'controles' => array_map(
        static fn ($control): array => [
            'id'        => $control->id,
            'proceso'   => $control->proceso,
            'iso'       => $control->iso,
            'enunciado' => $control->enunciado,
            'pregunta'  => $control->pregunta,
        ],
        $controles,
    ),
    'escala'    => $escala,
];
?>
<script type="application/json" id="catalogo-instrumento">
    <?= json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
