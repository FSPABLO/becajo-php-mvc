<?php

declare(strict_types=1);

/**
 * Tabs de dominio: la única elección que hace el consultor para moverse por el
 * instrumento.
 *
 * Van dentro del contenedor, pegados al contenido que gobiernan, y con estilo
 * de subrayado para que se lean como un nivel por debajo de las pestañas
 * principales (Instrumento · Cuestionario · Tablero…), que son pastillas
 * rellenas. Dos niveles de navegación con la misma forma se confunden.
 *
 * Sin ícono, a diferencia de las pestañas principales: repetirlo aquí duplica
 * el ruido y, con siete dominios, es justo lo que impedía que la fila cupiera
 * de una sola vez. La etiqueta corta se muestra; el nombre completo viaja en
 * aria-label y en el tooltip.
 *
 * Lo comparten la pestaña Instrumento y la pestaña Cuestionario; $ambito es lo
 * único que las distingue, porque cada una recuerda su propio dominio activo.
 *
 * @var string                              $ambito
 * @var string                              $etiquetaLista
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var array<string, int>                  $totalPorDominio
 */
?>
<div class="border-b border-slate-200 no-imprimir">
    <div class="flex gap-1 overflow-x-auto px-2"
         role="tablist"
         aria-label="<?= e($etiquetaLista) ?>">
        <?php foreach ($dominios as $indice => $dominio): ?>
            <?php $activo = $indice === 0; ?>
            <button type="button"
                    role="tab"
                    id="tab-<?= e($ambito) ?>-<?= e($dominio->clave) ?>"
                    data-tab-dominio="<?= e($dominio->clave) ?>"
                    aria-selected="<?= $activo ? 'true' : 'false' ?>"
                    aria-controls="seccion-<?= e($ambito) ?>-<?= e($dominio->clave) ?>"
                    tabindex="<?= $activo ? '0' : '-1' ?>"
                    title="<?= e($dominio->nombre) ?> — <?= e($dominio->descripcion) ?>"
                    aria-label="<?= e($dominio->nombre) ?>"
                    class="-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-3.5 text-sm font-semibold transition
                           <?= $activo
                               ? 'border-acento-500 text-marina-950'
                               : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-marina-950' ?>">
                <span><?= e($dominio->corto) ?></span>
                <span data-avance-dominio="<?= e($dominio->clave) ?>"
                      class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-500">
                    0/<?= e((string) ($totalPorDominio[$dominio->clave] ?? 0)) ?>
                </span>
            </button>
        <?php endforeach; ?>
    </div>
</div>
