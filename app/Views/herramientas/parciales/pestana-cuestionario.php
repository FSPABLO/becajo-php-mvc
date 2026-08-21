<?php

declare(strict_types=1);

/**
 * Pestaña Cuestionario: las 75 preguntas de auditoría (ISO/IEC 27007:2020).
 *
 * Mismo contenedor y misma agrupación que la pestaña Instrumento, y el mismo
 * objeto de estado por identificador. Contestar aquí mueve el tablero.
 *
 * @var \App\Core\Vista                     $vista
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var array<string, list<\App\Models\Entidades\Proceso>> $procesosPorDominio
 * @var array<int, list<\App\Models\Entidades\Control>>    $controlesPorProceso
 * @var array<string, int>                  $totalPorDominio
 */
?>
<div class="rv-extruido overflow-hidden rounded-rv-lg border border-borde bg-superficie">

    <?= $vista->renderizar('herramientas/parciales/tabs-dominios', [
        'ambito'          => 'cuestionario',
        'etiquetaLista'   => 'Dominios del cuestionario',
        'dominios'        => $dominios,
        'totalPorDominio' => $totalPorDominio,
    ]) ?>

    <div class="bg-elevado p-5 sm:p-6">
        <?php foreach ($dominios as $indice => $dominio): ?>
            <section id="seccion-cuestionario-<?= e($dominio->clave) ?>"
                     role="tabpanel"
                     aria-labelledby="tab-cuestionario-<?= e($dominio->clave) ?>"
                     tabindex="0"
                     data-seccion-dominio="<?= e($dominio->clave) ?>"
                     class="space-y-8"
                     <?= $indice === 0 ? '' : 'hidden' ?>>

                <div class="flex items-start gap-3 rounded-rv-lg border border-borde bg-superficie px-4 py-3.5">
                    <span class="rv-extruido-xs grid h-9 w-9 flex-none place-items-center rounded-rv bg-elevado text-primario">
                        <?= iconoDominio($dominio->clave, 'h-5 w-5') ?>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-texto"><?= e($dominio->nombre) ?></h2>
                        <p class="mt-0.5 text-xs text-texto-2"><?= e($dominio->descripcion) ?></p>
                    </div>
                </div>

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
                                <?= $vista->componente('tarjeta-pregunta', [
                                    'control' => $control,
                                    'dominio' => $dominio->clave,
                                ]) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <nav class="mt-8 flex items-center justify-between gap-3 border-t border-borde pt-6 no-imprimir"
             aria-label="Navegación entre dominios del cuestionario">
            <button type="button" data-nav="anterior"
                    class="rv-extruido-sm rv-interactivo-sm inline-flex max-w-[45%] items-center gap-2 rounded-rv border border-borde bg-superficie px-4 py-2.5 text-sm font-semibold text-texto hover:border-primario disabled:cursor-not-allowed disabled:opacity-40">
                <span class="rotate-180"><?= icono('flecha', 'h-4 w-4') ?></span>
                <span class="truncate" data-nav-etiqueta>Dominio anterior</span>
            </button>
            <button type="button" data-nav="siguiente"
                    class="inline-flex max-w-[45%] items-center gap-2 rv-extruido-sm rv-interactivo-sm rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto disabled:cursor-not-allowed disabled:opacity-40">
                <span class="truncate" data-nav-etiqueta>Dominio siguiente</span>
                <?= icono('flecha', 'h-4 w-4') ?>
            </button>
        </nav>
    </div>
</div>
