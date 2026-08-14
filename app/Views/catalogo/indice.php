<?php

declare(strict_types=1);

/**
 * Índice del catálogo maestro.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var list<\App\Models\Entidades\Proceso> $procesos
 * @var list<\App\Models\Entidades\Control> $controles
 * @var array<string, int> $usosControl  Código de control => evaluaciones que lo responden.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

// Recuentos para saber, de un vistazo, qué se puede borrar y qué no.
$procesosPorDominio = [];
foreach ($procesos as $proceso) {
    $procesosPorDominio[$proceso->dominio] = ($procesosPorDominio[$proceso->dominio] ?? 0) + 1;
}

$controlesPorProceso = [];
foreach ($controles as $control) {
    $controlesPorProceso[$control->proceso] = ($controlesPorProceso[$control->proceso] ?? 0) + 1;
}

$nombreDominio = [];
foreach ($dominios as $dominio) {
    $nombreDominio[$dominio->clave] = $dominio->corto;
}

$botonBorrar = static function (\App\Core\Vista $vista, string $ruta, string $etiqueta): string {
    return '<form method="post" action="' . e($vista->url($ruta)) . '"'
        . ' onsubmit="return confirm(\'¿Eliminar ' . e($etiqueta) . '? Esta acción no se puede deshacer.\')">'
        . $vista->campoToken()
        . '<button type="submit" class="text-xs font-semibold text-bad hover:underline">Eliminar</button>'
        . '</form>';
};
?>
<section class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-8">

    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-widest text-primario">
                Administración
            </p>
            <h1 class="mt-2 text-3xl font-extrabold text-texto">Catálogo de controles</h1>
            <p class="mt-1 text-sm text-texto-2">
                <?= e((string) count($dominios)) ?> dominios ·
                <?= e((string) count($procesos)) ?> procesos ·
                <?= e((string) count($controles)) ?> controles
            </p>
        </div>
        <?php
        /*
         * "Ir a mis auditorías" se retiró de aquí: la barra lateral del módulo
         * ya lleva a esa sección desde cualquier pantalla, y un enlace de
         * navegación repetido en la cabecera compite con el que sí es propio de
         * esta página. El mapa se queda porque es la otra cara de este mismo
         * catálogo, no otro destino del menú.
         */
        ?>
        <div class="flex items-center gap-4">
            <a href="<?= e($vista->url('catalogo/matriz')) ?>"
               class="text-sm font-semibold text-primario hover:underline">
                Mapa de procesos vs C-I-D →
            </a>
        </div>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <p class="mb-10 rounded-rv-lg border border-warn/10 bg-warn/10 px-4 py-3 text-sm text-warn">
        Editar un control cambia el significado de las respuestas ya guardadas en
        auditorías anteriores. Las claves (clave de dominio, número de proceso,
        código de control) no se pueden modificar una vez creadas.
    </p>

    <!-- Dominios -->
    <div class="mb-12">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="rv-titulo text-xl font-semibold text-texto">Dominios</h2>
            <a href="<?= e($vista->url('catalogo/dominios/nuevo')) ?>"
               class="rv-extruido rv-interactivo rounded-rv bg-primario px-3.5 py-2 text-sm font-semibold text-primario-texto">
                Nuevo dominio
            </a>
        </div>

        <div class="rv-extruido rv-relieve-sutil rv-tabla overflow-x-auto rounded-rv-lg border border-borde bg-superficie">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="bg-elevado text-xs uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Orden</th>
                        <th class="px-4 py-3 font-semibold">Clave</th>
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Procesos</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                <?php foreach ($dominios as $dominio): ?>
                    <?php $usos = $procesosPorDominio[$dominio->clave] ?? 0; ?>
                    <tr class="hover:bg-elevado">
                        <td class="px-4 py-3 tabular text-texto-2"><?= e((string) $dominio->orden) ?></td>
                        <td class="px-4 py-3">
                            <a href="<?= e($vista->url('catalogo/dominios/' . $dominio->clave)) ?>"
                               class="font-semibold text-primario hover:underline"><?= e($dominio->clave) ?></a>
                        </td>
                        <td class="px-4 py-3 text-texto"><?= e($dominio->nombre) ?></td>
                        <td class="px-4 py-3 tabular text-texto-2"><?= e((string) $usos) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($usos === 0): ?>
                                <?= $botonBorrar($vista, 'catalogo/dominios/' . $dominio->clave . '/eliminar', 'el dominio ' . $dominio->clave) ?>
                            <?php else: ?>
                                <span class="text-xs text-texto-2">en uso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Procesos -->
    <div class="mb-12">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="rv-titulo text-xl font-semibold text-texto">Procesos</h2>
            <a href="<?= e($vista->url('catalogo/procesos/nuevo')) ?>"
               class="rv-extruido rv-interactivo rounded-rv bg-primario px-3.5 py-2 text-sm font-semibold text-primario-texto">
                Nuevo proceso
            </a>
        </div>

        <div class="rv-extruido rv-relieve-sutil rv-tabla overflow-x-auto rounded-rv-lg border border-borde bg-superficie">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="bg-elevado text-xs uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Orden</th>
                        <th class="px-4 py-3 font-semibold">Nº</th>
                        <th class="px-4 py-3 font-semibold">Nombre</th>
                        <th class="px-4 py-3 font-semibold">Dominio</th>
                        <th class="px-4 py-3 font-semibold">Controles</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                <?php foreach ($procesos as $proceso): ?>
                    <?php $usos = $controlesPorProceso[$proceso->numero] ?? 0; ?>
                    <tr class="hover:bg-elevado">
                        <td class="px-4 py-3 tabular text-texto-2"><?= e((string) $proceso->orden) ?></td>
                        <td class="px-4 py-3">
                            <a href="<?= e($vista->url('catalogo/procesos/' . $proceso->numero)) ?>"
                               class="font-semibold text-primario hover:underline"><?= e((string) $proceso->numero) ?></a>
                        </td>
                        <td class="px-4 py-3 text-texto"><?= e($proceso->nombre) ?></td>
                        <td class="px-4 py-3 text-texto-2"><?= e($nombreDominio[$proceso->dominio] ?? $proceso->dominio) ?></td>
                        <td class="px-4 py-3 tabular text-texto-2"><?= e((string) $usos) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($usos === 0): ?>
                                <?= $botonBorrar($vista, 'catalogo/procesos/' . $proceso->numero . '/eliminar', 'el proceso ' . $proceso->numero) ?>
                            <?php else: ?>
                                <span class="text-xs text-texto-2">en uso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Controles -->
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h2 class="rv-titulo text-xl font-semibold text-texto">Controles</h2>
            <a href="<?= e($vista->url('catalogo/controles/nuevo')) ?>"
               class="rv-extruido rv-interactivo rounded-rv bg-primario px-3.5 py-2 text-sm font-semibold text-primario-texto">
                Nuevo control
            </a>
        </div>

        <div class="rv-extruido rv-relieve-sutil rv-tabla overflow-x-auto rounded-rv-lg border border-borde bg-superficie">
            <table class="w-full min-w-[46rem] text-left text-sm">
                <thead class="bg-elevado text-xs uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Código</th>
                        <th class="px-4 py-3 font-semibold">Proceso</th>
                        <th class="px-4 py-3 font-semibold">Enunciado</th>
                        <th class="px-4 py-3 font-semibold">Evaluado</th>
                        <th class="px-4 py-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                <?php foreach ($controles as $control): ?>
                    <?php $usos = $usosControl[$control->id] ?? 0; ?>
                    <tr class="align-top hover:bg-elevado">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="<?= e($vista->url('catalogo/controles/' . $control->id)) ?>"
                               class="font-semibold text-primario hover:underline"><?= e($control->id) ?></a>
                        </td>
                        <td class="px-4 py-3 tabular text-texto-2"><?= e((string) $control->proceso) ?></td>
                        <td class="px-4 py-3 text-texto-2"><?= e(mb_strimwidth($control->enunciado, 0, 90, '…')) ?></td>
                        <td class="px-4 py-3 tabular text-texto-2"><?= e((string) $usos) ?></td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($usos === 0): ?>
                                <?= $botonBorrar($vista, 'catalogo/controles/' . $control->id . '/eliminar', 'el control ' . $control->id) ?>
                            <?php else: ?>
                                <span class="text-xs text-texto-2">en uso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
