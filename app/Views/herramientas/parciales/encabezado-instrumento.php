<?php

declare(strict_types=1);

/**
 * Franja de encabezado del instrumento: identificación de la consultoría y
 * acciones sobre el avance capturado.
 *
 * @var array<string, string>                $instrumento
 * @var list<\App\Models\Entidades\Control>  $controles
 * @var list<\App\Models\Entidades\Proceso>  $procesos
 * @var list<\App\Models\Entidades\Dominio>  $dominios
 */
$campos = [
    ['clave' => 'organizacion', 'etiqueta' => 'Organización',        'marcador' => 'Nombre de la organización auditada', 'tipo' => 'text'],
    ['clave' => 'base',         'etiqueta' => 'Base de datos e instancia', 'marcador' => 'Ej.: CORE / PRODCORE1',       'tipo' => 'text'],
    ['clave' => 'motor',        'etiqueta' => 'Motor y versión',     'marcador' => 'Ej.: Oracle 19c',                    'tipo' => 'text'],
    ['clave' => 'consultor',    'etiqueta' => 'Consultor responsable', 'marcador' => 'Nombre de quien evalúa',           'tipo' => 'text'],
    ['clave' => 'fecha',        'etiqueta' => 'Fecha de la evaluación', 'marcador' => '',                                'tipo' => 'date'],
];

$acciones = [
    ['clave' => 'exportar-csv',  'etiqueta' => 'Exportar CSV',     'icono' => 'descargar', 'titulo' => 'Descarga los 75 controles con sus respuestas, separados por punto y coma'],
    ['clave' => 'exportar-json', 'etiqueta' => 'Guardar avance',   'icono' => 'documento', 'titulo' => 'Descarga el estado completo como archivo JSON'],
    ['clave' => 'importar-json', 'etiqueta' => 'Cargar avance',    'icono' => 'importar',  'titulo' => 'Restaura un avance guardado previamente'],
    ['clave' => 'ejemplo',       'etiqueta' => 'Datos de ejemplo', 'icono' => 'chispa',    'titulo' => 'Llena el instrumento con datos de demostración'],
    ['clave' => 'imprimir',      'etiqueta' => 'Imprimir',         'icono' => 'imprimir',  'titulo' => 'Imprime el informe sin los controles de navegación'],
    ['clave' => 'limpiar',       'etiqueta' => 'Limpiar todo',     'icono' => 'basura',    'titulo' => 'Borra todas las respuestas capturadas'],
];
?>
<section class="border-b border-white/10 bg-marina-950">
    <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">

        <p class="inline-flex items-center gap-2 rounded-full border border-acento-400/30 bg-acento-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-acento-400">
            <?= icono('escudo', 'h-3.5 w-3.5') ?>
            ISO/IEC 27000 a 27011
        </p>

        <h1 class="mt-5 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl">
            <?= e($instrumento['titulo']) ?>
        </h1>

        <p class="mt-4 max-w-3xl leading-relaxed text-marina-200">
            <?= e($instrumento['descripcion']) ?>
        </p>

        <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-3 text-sm">
            <?php
            $resumen = [
                'Dominios'  => (string) count($dominios),
                'Procesos'  => (string) count($procesos),
                'Controles' => (string) count($controles),
                'Versión'   => $instrumento['version'],
            ];
            ?>
            <?php foreach ($resumen as $etiqueta => $valor): ?>
                <div>
                    <dt class="text-xs uppercase tracking-wider text-marina-400"><?= e($etiqueta) ?></dt>
                    <dd class="mt-0.5 text-lg font-bold text-white"><?= e($valor) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <!-- Identificación de la consultoría -->
        <div class="mt-9 rounded-xl border border-white/10 bg-white/5 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-white">
                Identificación de la consultoría
            </h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($campos as $campo): ?>
                    <div>
                        <label for="ident-<?= e($campo['clave']) ?>"
                               class="block text-xs font-medium text-marina-300">
                            <?= e($campo['etiqueta']) ?>
                        </label>
                        <input type="<?= e($campo['tipo']) ?>"
                               id="ident-<?= e($campo['clave']) ?>"
                               data-identificacion="<?= e($campo['clave']) ?>"
                               <?php if ($campo['marcador'] !== ''): ?>placeholder="<?= e($campo['marcador']) ?>"<?php endif; ?>
                               class="mt-1.5 w-full rounded-lg border border-white/10 bg-marina-950/60 px-3 py-2 text-sm text-white placeholder:text-marina-400 focus:border-acento-500 focus:outline-none focus:ring-1 focus:ring-acento-500">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Acciones -->
        <div class="mt-6 flex flex-wrap items-center gap-2.5 no-imprimir">
            <?php foreach ($acciones as $accion): ?>
                <button type="button"
                        data-accion="<?= e($accion['clave']) ?>"
                        title="<?= e($accion['titulo']) ?>"
                        class="inline-flex items-center gap-2 rounded-lg border border-white/15 px-3.5 py-2 text-sm font-semibold text-white transition hover:border-acento-400 hover:bg-white/5">
                    <?= icono($accion['icono'], 'h-4 w-4 text-acento-400') ?>
                    <?= e($accion['etiqueta']) ?>
                </button>
            <?php endforeach; ?>

            <!-- Selector oculto: lo dispara el botón "Cargar avance". -->
            <input type="file" accept="application/json,.json" class="sr-only"
                   id="archivo-avance" data-archivo-avance
                   aria-label="Seleccionar archivo de avance en formato JSON">

            <p class="w-full pt-1 text-xs text-marina-400" role="status" data-estado-guardado>
                El avance se guarda en este navegador de forma automática.
            </p>
        </div>
    </div>
</section>
