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
?>
<div class="bg-slate-50 pb-24 pt-16">

    <?= $vista->renderizar('herramientas/parciales/encabezado-instrumento', [
        'instrumento' => $instrumento,
        'controles'   => $controles,
        'procesos'    => $procesos,
        'dominios'    => $dominios,
    ]) ?>

    <!-- Barra de pestañas: adherida bajo el encabezado fijo del sitio (h-16). -->
    <div class="sticky top-16 z-30 border-b border-slate-200 bg-white/95 backdrop-blur no-imprimir">
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
                            class="flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition
                                   <?= $indice === 0
                                       ? 'bg-acento-500 text-marina-950'
                                       : 'text-slate-600 hover:bg-slate-100 hover:text-marina-950' ?>">
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
