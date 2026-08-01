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
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

    <?= $vista->renderizar('herramientas/parciales/tabs-dominios', [
        'ambito'          => 'instrumento',
        'etiquetaLista'   => 'Dominios del instrumento',
        'dominios'        => $dominios,
        'totalPorDominio' => $totalPorDominio,
    ]) ?>

    <div class="bg-slate-50 p-5 sm:p-6">
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

                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-slate-200 pb-3">
                            <h3 class="text-base font-bold text-marina-950">
                                <span class="text-acento-600">Proceso <?= e((string) $proceso->numero) ?>.</span>
                                <?= e($proceso->nombre) ?>
                            </h3>
                            <span class="text-xs font-medium text-slate-500"><?= e($proceso->ancla) ?></span>
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
        <nav class="mt-8 flex items-center justify-between gap-3 border-t border-slate-200 pt-6 no-imprimir"
             aria-label="Navegación entre dominios">
            <button type="button" data-nav="anterior"
                    class="inline-flex max-w-[45%] items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-marina-950 transition hover:border-marina-300 disabled:cursor-not-allowed disabled:opacity-40">
                <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span>
                <span class="truncate" data-nav-etiqueta>Dominio anterior</span>
            </button>
            <button type="button" data-nav="siguiente"
                    class="inline-flex max-w-[45%] items-center gap-2 rounded-lg bg-marina-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-marina-900 disabled:cursor-not-allowed disabled:opacity-40">
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
      class="border-l-exito-500 border-l-alerta-500 border-l-marina-300 border-l-aviso-500 border-l-slate-200"></span>
