<?php

declare(strict_types=1);

/**
 * Pestaña Instrumento: captura de los 75 controles.
 *
 * Un solo contenedor: los tabs de dominio arriba y, dentro, los controles de
 * ese dominio. Se dibujan los siete dominios completos y el guion muestra uno
 * a la vez, de modo que cambiar de dominio no pide nada al servidor y la
 * impresión puede incluir todo el instrumento.
 *
 * @var \App\Core\Vista                     $vista
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var array<string, list<\App\Models\Entidades\Proceso>> $procesosPorDominio
 * @var array<int, list<\App\Models\Entidades\Control>>    $controlesPorProceso
 * @var array<string, int>                  $totalPorDominio
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 */
?>
<div class="overflow-hidden rounded-rv-lg border border-borde bg-superficie">

    <?= $vista->renderizar('herramientas/parciales/tabs-dominios', [
        'ambito'          => 'instrumento',
        'etiquetaLista'   => 'Dominios del instrumento',
        'dominios'        => $dominios,
        'totalPorDominio' => $totalPorDominio,
    ]) ?>

    <div class="bg-elevado p-5 sm:p-6">
        <?php foreach ($dominios as $indice => $dominio): ?>
            <section id="seccion-instrumento-<?= e($dominio->clave) ?>"
                     role="tabpanel"
                     aria-labelledby="tab-instrumento-<?= e($dominio->clave) ?>"
                     tabindex="0"
                     data-seccion-dominio="<?= e($dominio->clave) ?>"
                     class="space-y-8"
                     <?= $indice === 0 ? '' : 'hidden' ?>>

                <?php foreach ($procesosPorDominio[$dominio->clave] ?? [] as $proceso): ?>
                    <div data-grupo-proceso="<?= e((string) $proceso->numero) ?>">

                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-borde pb-3">
                            <h3 class="text-base font-bold text-texto">
                                <span class="text-primario">Proceso <?= e((string) $proceso->numero) ?>.</span>
                                <?= e($proceso->nombre) ?>
                            </h3>
                            <span class="text-xs font-medium text-texto-2"><?= e($proceso->ancla) ?></span>
                        </div>

                        <div class="mt-4 space-y-4">
                            <?php foreach ($controlesPorProceso[$proceso->numero] ?? [] as $control): ?>
                                <?= $vista->componente('tarjeta-control', [
                                    'control' => $control,
                                    'dominio' => $dominio->clave,
                                    'escala'  => $escala,
                                ]) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <!-- Navegación entre dominios -->
        <nav class="mt-8 flex items-center justify-between gap-3 border-t border-borde pt-6 no-imprimir"
             aria-label="Navegación entre dominios">
            <button type="button" data-nav="anterior"
                    class="inline-flex max-w-[45%] items-center gap-2 rounded-rv border border-borde bg-superficie px-4 py-2.5 text-sm font-semibold text-texto transition hover:border-borde disabled:cursor-not-allowed disabled:opacity-40">
                <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span>
                <span class="truncate" data-nav-etiqueta>Dominio anterior</span>
            </button>
            <button type="button" data-nav="siguiente"
                    class="inline-flex max-w-[45%] items-center gap-2 rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto disabled:cursor-not-allowed disabled:opacity-40">
                <span class="truncate" data-nav-etiqueta>Dominio siguiente</span>
                <?= icono('flecha', 'h-4 w-4') ?>
            </button>
        </nav>
    </div>
</div>

<?php
/*
 * Colores que el guion aplica al borde izquierdo de cada tarjeta según su
 * estado. Se declaran aquí, en un elemento oculto, porque Tailwind genera las
 * clases a partir del HTML: si solo apareciesen dentro del guion, no existirían
 * en la hoja de estilos.
 */
?>
<span hidden aria-hidden="true"
      class="border-l-ok border-l-bad border-l-warn border-l-na border-l-borde
             bg-ok bg-bad bg-warn bg-elevado text-na text-primario-texto
             bg-primario text-texto text-texto-2 border-primario hover:bg-elevado
             hover:text-texto hover:border-borde rv-id"></span>
