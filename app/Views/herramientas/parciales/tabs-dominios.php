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
 * Ícono desde `sm:` hacia arriba, no antes: con los siete dominios, ícono +
 * etiqueta + contador es lo que impedía que la fila cupiera de una sola vez
 * en un teléfono. En pantallas angostas se cae el ícono y queda solo la
 * etiqueta corta; el nombre completo viaja en aria-label y en el tooltip de
 * todas formas. iconoDominio() (app/Core/funciones.php) es el único lugar
 * donde vive el mapeo dominio → ícono, así que esta pestaña, la tabla del
 * catálogo y la cabecera de cada sección del instrumento nunca se
 * desincronizan entre sí.
 *
 * Lo comparten la pestaña Instrumento y la pestaña Cuestionario; $ambito es lo
 * único que las distingue, porque cada una recuerda su propio dominio activo.
 *
 * @var string                              $ambito
 * @var string                              $etiquetaLista
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var array<string, int>                  $totalPorDominio
 * @var array<string, int>|null             $respondidoPorDominio  Numerador
 *      inicial del contador. Sin este dato (el caso del instrumento público,
 *      que no tiene servidor donde consultarlo) el contador nace en 0 y un
 *      guion en assets/js/instrumento.js lo va corrigiendo con lo que haya en
 *      localStorage. Con dato real de servidor (evaluacion/mostrar.php) nace
 *      ya correcto y no hace falta que ningún script lo toque.
 */
$respondidoPorDominio = $respondidoPorDominio ?? [];
?>
<div class="border-b border-borde no-imprimir">
    <div class="flex gap-1 overflow-x-auto px-2"
         role="tablist"
         data-tabs-dominio="<?= e($ambito) ?>"
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
                               ? 'border-primario text-texto'
                               : 'border-transparent text-texto-2 hover:border-borde hover:text-texto' ?>">
                <?= iconoDominio($dominio->clave, 'hidden h-4 w-4 shrink-0 sm:block') ?>
                <span><?= e($dominio->corto) ?></span>
                <?php /* Contador de avance: ficha elevada, y en oro relleno
                         cuando el dominio queda completo. Verde sería el color
                         de «cumple», y esto solo dice «contestado». */ ?>
                <span data-avance-dominio="<?= e($dominio->clave) ?>"
                      class="rv-extruido-xs tabular rounded-full bg-superficie px-2 py-0.5 text-xs font-bold text-texto-2">
                    <?= e((string) ($respondidoPorDominio[$dominio->clave] ?? 0)) ?>/<?= e((string) ($totalPorDominio[$dominio->clave] ?? 0)) ?>
                </span>
            </button>
        <?php endforeach; ?>
    </div>
</div>
