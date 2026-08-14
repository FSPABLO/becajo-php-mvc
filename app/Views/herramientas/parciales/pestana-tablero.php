<?php

declare(strict_types=1);

/**
 * Pestaña Tablero: resultado calculado en vivo.
 *
 * Esta vista dibuja el esqueleto con los valores en cero; el guion los rellena
 * a cada cambio. Las celdas llevan data-celda para que el guion no dependa de
 * la posición de las columnas.
 *
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var list<\App\Models\Entidades\Proceso> $procesos
 * @var array<int, list<\App\Models\Entidades\Control>> $controlesPorProceso
 */
$nombreDominio = [];
foreach ($dominios as $dominio) {
    $nombreDominio[$dominio->clave] = $dominio->nombre;
}

$tarjetas = [
    ['clave' => 'evaluados',   'etiqueta' => 'Controles evaluados', 'ayuda' => 'Con estado registrado, sobre 75'],
    ['clave' => 'cumplimiento', 'etiqueta' => 'Cumplimiento',       'ayuda' => 'Sí ÷ (sí + no); «no aplica» queda fuera'],
    ['clave' => 'madurez',     'etiqueta' => 'Madurez promedio',    'ayuda' => 'Solo controles con madurez registrada'],
    ['clave' => 'brechas',     'etiqueta' => 'Brechas abiertas',    'ayuda' => 'Controles marcados como inexistentes'],
    ['clave' => 'pendientes',  'etiqueta' => 'Pendientes de evaluar', 'ayuda' => 'Sin estado registrado'],
];

$proporciones = [
    ['clave' => 'si',  'etiqueta' => 'Existe',      'fondo' => 'bg-ok'],
    ['clave' => 'no',  'etiqueta' => 'No existe',   'fondo' => 'bg-bad'],
    ['clave' => 'na',  'etiqueta' => 'No aplica',   'fondo' => 'bg-texto-2'],
    /* El segmento «sin evaluar» iba en bg-elevado, que es el color de la propia
       pista: el tramo pendiente no se distinguía del hueco vacío. El borde es
       el siguiente escalón de la misma familia neutra, sin inventar color. */
    ['clave' => 'sin', 'etiqueta' => 'Sin evaluar', 'fondo' => 'bg-borde'],
];

$dimensionesRiesgo = [
    ['clave' => 'confidencialidad', 'etiqueta' => 'Confidencialidad'],
    ['clave' => 'integridad',       'etiqueta' => 'Integridad'],
    ['clave' => 'disponibilidad',   'etiqueta' => 'Disponibilidad'],
];
?>

<!-- Tarjetas de resumen -->
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
    <?php foreach ($tarjetas as $tarjeta): ?>
        <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-texto-2">
                <?= e($tarjeta['etiqueta']) ?>
            </p>
            <p class="tabular mt-2 text-3xl font-extrabold tracking-tight text-texto"
               data-resumen="<?= e($tarjeta['clave']) ?>">—</p>
            <p class="mt-1.5 text-xs leading-relaxed text-texto-2"><?= e($tarjeta['ayuda']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<!--
    E. Semáforos de exposición al riesgo por dimensión: (madurez promedio de los controles marcados con esa dimensión, normalizada entre 0 y 1)
-->
<div class="rv-extruido mt-6 rounded-rv-lg border border-borde bg-superficie p-6">
    <h2 class="text-sm font-semibold uppercase tracking-wider text-texto-2">
        Exposición al riesgo por dimensión
    </h2>
    <p class="mt-1 text-xs leading-relaxed text-texto-2">
        Madurez promedio normalizada de los controles marcados con cada dimensión.
        Rojo por debajo de 50&nbsp;%, amarillo entre 50&nbsp;% y 80&nbsp;%, verde de 80&nbsp;% en adelante.
    </p>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <?php foreach ($dimensionesRiesgo as $dimension): ?>
            <div class="rv-hundido rounded-rv-lg border border-borde bg-elevado p-4 text-center">
                <?php /* El disco conserva bg-elevado como fondo base: esa misma
                         clase es el relleno de la zona «sin datos» que el guion
                         quita y pone, y una segunda clase de fondo debajo haría
                         que el color dependiera del orden de las utilidades. */ ?>
                <div class="rv-extruido-sm tabular mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-elevado text-lg font-extrabold text-texto-2 transition-colors duration-300"
                     data-semaforo="<?= e($dimension['clave']) ?>">—</div>
                <p class="mt-3 text-sm font-semibold text-texto"><?= e($dimension['etiqueta']) ?></p>
                <p class="mt-0.5 text-xs uppercase tracking-wide text-texto-2"
                   data-semaforo-zona="<?= e($dimension['clave']) ?>">Sin datos</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- F. Mapa de calor: dominio × dimensión de riesgo, misma fórmula que los semáforos, desagregada por dominio. -->
<div class="rv-extruido mt-6 rounded-rv-lg border border-borde bg-superficie p-6">
    <h2 class="text-sm font-semibold uppercase tracking-wider text-texto-2">
        Mapa de calor: dominio × dimensión de riesgo
    </h2>
    <p class="mt-1 text-xs leading-relaxed text-texto-2">
        Cada celda es la madurez promedio normalizada de los controles de ese dominio marcados con esa dimensión.
    </p>

    <div class="mt-4 overflow-x-auto">
        <table class="tabular w-full min-w-[36rem] text-sm">
            <caption class="sr-only">Mapa de calor de riesgo por dominio y dimensión</caption>
            <thead>
                <tr class="text-left text-xs uppercase tracking-wider text-texto-2">
                    <th scope="col" class="px-3 py-2 font-semibold">Dominio</th>
                    <th scope="col" class="px-3 py-2 text-center font-semibold">Confidencialidad</th>
                    <th scope="col" class="px-3 py-2 text-center font-semibold">Integridad</th>
                    <th scope="col" class="px-3 py-2 text-center font-semibold">Disponibilidad</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dominios as $dominio): ?>
                    <tr data-fila-mapa-calor="<?= e($dominio->clave) ?>" class="border-t border-borde">
                        <th scope="row" class="px-3 py-2 text-left font-medium text-texto">
                            <?= e($dominio->nombre) ?>
                        </th>
                        <td class="px-3 py-3 text-center">
                            <span class="rv-extruido-xs tabular inline-flex min-w-[3.75rem] justify-center rounded-md px-2.5 py-1 font-bold text-texto-2 transition-colors duration-300"
                                  data-celda-calor="confidencialidad">—</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="rv-extruido-xs tabular inline-flex min-w-[3.75rem] justify-center rounded-md px-2.5 py-1 font-bold text-texto-2 transition-colors duration-300"
                                  data-celda-calor="integridad">—</span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="rv-extruido-xs tabular inline-flex min-w-[3.75rem] justify-center rounded-md px-2.5 py-1 font-bold text-texto-2 transition-colors duration-300"
                                  data-celda-calor="disponibilidad">—</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <ul class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-texto-2">
        <li class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-bad"></span> Menos de 50&nbsp;%
        </li>
        <li class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-warn"></span> Entre 50&nbsp;% y 80&nbsp;%
        </li>
        <li class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-ok"></span> 80&nbsp;% o más
        </li>
        <li class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-full bg-elevado"></span> Sin controles calificados
        </li>
    </ul>
</div>

<!-- G. Ranking de los controles con menor madurez entre los ya calificados. -->
<div class="rv-extruido mt-6 rounded-rv-lg border border-borde bg-superficie p-6">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-texto-2">
            Controles con menor madurez
        </h2>
        <span class="text-xs text-texto-2">Los más bajos entre los ya calificados, máximo 8</span>
    </div>

    <ol class="mt-4 space-y-3" data-ranking-debiles>
        <li class="text-sm text-texto-2" data-ranking-vacio>
            Todavía no hay controles con madurez registrada.
        </li>
    </ol>
</div>






<!-- Barra apilada de proporción entre estados -->
<div class="rv-extruido mt-6 rounded-rv-lg border border-borde bg-superficie p-6">
    <h2 class="text-sm font-semibold uppercase tracking-wider text-texto-2">
        Proporción entre estados
    </h2>

    <div class="rv-hundido mt-4 flex h-4 w-full overflow-hidden rounded-full bg-elevado"
         role="img" aria-label="Distribución de los 75 controles entre los cuatro estados"
         data-barra-apilada>
        <?php foreach ($proporciones as $proporcion): ?>
            <div class="h-full transition-all duration-300 <?= e($proporcion['fondo']) ?>"
                 data-segmento="<?= e($proporcion['clave']) ?>" style="width: 0%"></div>
        <?php endforeach; ?>
    </div>

    <ul class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm">
        <?php foreach ($proporciones as $proporcion): ?>
            <li class="inline-flex items-center gap-2 text-texto-2">
                <span class="h-2.5 w-2.5 rounded-full <?= e($proporcion['fondo']) ?>"></span>
                <?= e($proporcion['etiqueta']) ?>
                <span class="font-bold text-texto" data-conteo="<?= e($proporcion['clave']) ?>">0</span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Tabla de los 25 procesos -->
<?php /* Relieve sutil: en una tabla de 25 filas el relieve pleno compite con
         la lectura de la cifra (§4 del diseño general). */ ?>
<div class="rv-extruido rv-relieve-sutil mt-6 rounded-rv-lg border border-borde bg-superficie">
    <div class="border-b border-borde px-6 py-5">
        <h2 class="rv-titulo text-lg font-semibold tracking-tight text-texto">
            Cumplimiento por proceso
        </h2>
        <p class="mt-1 text-sm text-texto-2">
            Un guion en la columna de cumplimiento significa que ningún control del proceso
            entra en el cálculo: o están todos sin evaluar, o todos quedaron excluidos como
            «no aplica».
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="tabular w-full min-w-[64rem] text-sm">
            <caption class="sr-only">
                Resultado por proceso: controles, estados, cumplimiento, madurez y cobertura de riesgo
            </caption>
            <thead>
                <tr class="border-b border-borde text-left text-xs uppercase tracking-wider text-texto-2">
                    <th scope="col" class="px-6 py-3 font-semibold">Proceso</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold">Ctrl.</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold">Sí</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold">No</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold">N/A</th>
                    <th scope="col" class="px-6 py-3 font-semibold">Cumplimiento</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold">Madurez</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Nivel alcanzado</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold" title="Integridad">I</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold" title="Confidencialidad">C</th>
                    <th scope="col" class="px-3 py-3 text-center font-semibold" title="Disponibilidad">D</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($procesos as $proceso): ?>
                    <tr data-fila-proceso="<?= e((string) $proceso->numero) ?>"
                        class="border-b border-borde last:border-b-0">
                        <th scope="row" class="px-6 py-3 text-left font-medium text-texto">
                            <span class="text-texto-2"><?= e((string) $proceso->numero) ?>.</span>
                            <?= e($proceso->nombre) ?>
                            <span class="mt-0.5 block text-xs font-normal text-texto-2">
                                <?= e($nombreDominio[$proceso->dominio] ?? '') ?>
                            </span>
                        </th>
                        <td class="px-3 py-3 text-center text-texto-2">
                            <?= e((string) count($controlesPorProceso[$proceso->numero] ?? [])) ?>
                        </td>
                        <td class="px-3 py-3 text-center font-semibold text-ok" data-celda="si">0</td>
                        <td class="px-3 py-3 text-center font-semibold text-bad" data-celda="no">0</td>
                        <td class="px-3 py-3 text-center font-semibold text-texto-2" data-celda="na">0</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="rv-hundido h-2 w-24 shrink-0 overflow-hidden rounded-full bg-elevado">
                                    <div class="h-full rounded-full bg-primario transition-all duration-300"
                                         data-celda="barra" style="width: 0%"></div>
                                </div>
                                <span class="w-12 shrink-0 font-semibold text-texto"
                                      data-celda="cumplimiento">—</span>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-center font-semibold text-texto" data-celda="madurez">—</td>
                        <td class="px-4 py-3 text-texto-2" data-celda="nivel">—</td>
                        <td class="px-3 py-3 text-center text-texto-2" data-celda="integridad">—</td>
                        <td class="px-3 py-3 text-center text-texto-2" data-celda="confidencialidad">—</td>
                        <td class="px-3 py-3 text-center text-texto-2" data-celda="disponibilidad">—</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr data-fila-total class="border-t-2 border-borde bg-elevado font-bold text-texto">
                    <th scope="row" class="px-6 py-4 text-left">Total general</th>
                    <td class="px-3 py-4 text-center"><?= e((string) array_sum(array_map('count', $controlesPorProceso))) ?></td>
                    <td class="px-3 py-4 text-center text-ok" data-celda="si">0</td>
                    <td class="px-3 py-4 text-center text-bad" data-celda="no">0</td>
                    <td class="px-3 py-4 text-center text-texto-2" data-celda="na">0</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-2 w-24 shrink-0 overflow-hidden rounded-full bg-elevado">
                                <?php /* bg-primario y no bg-fondo: la barra del
                                         total llevaba el color del lienzo y
                                         sobre pergamino no se veía crecer. */ ?>
                                <div class="h-full rounded-full bg-primario transition-all duration-300"
                                     data-celda="barra" style="width: 0%"></div>
                            </div>
                            <span class="w-12 shrink-0" data-celda="cumplimiento">—</span>
                        </div>
                    </td>
                    <td class="px-3 py-4 text-center" data-celda="madurez">—</td>
                    <td class="px-4 py-4" data-celda="nivel">—</td>
                    <td class="px-3 py-4 text-center" data-celda="integridad">—</td>
                    <td class="px-3 py-4 text-center" data-celda="confidencialidad">—</td>
                    <td class="px-3 py-4 text-center" data-celda="disponibilidad">—</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<p class="mt-4 text-xs leading-relaxed text-texto-2">
    Las columnas I, C y D expresan qué proporción de los controles del proceso fue marcada
    con riesgo sobre la integridad, la confidencialidad y la disponibilidad, respectivamente.
</p>
