<?php

declare(strict_types=1);

/**
 * Aviso de maqueta: reponer el que existía antes de que el frente 4 lo
 * retirara (ver CLAUDE.md, «Monitor de salud — HOY ES UNA MAQUETA»).
 *
 * Comparten esta pieza la antesala y la consola para que las dos pantallas
 * digan exactamente lo mismo con las mismas palabras — es la misma razón por
 * la que `monitor-selector` y `monitor-ayuda` son componentes y no dos copias.
 *
 * Va arriba de TODO, antes incluso de los destellos: es la primera línea que
 * se lee, y el §12 del plan pide que ninguna cifra de salud se muestre sin
 * poder saber si viene de una toma real. Sin este aviso, cuatro instancias con
 * nombres verosímiles y cifras coherentes se leen como datos de verdad.
 *
 * No es un `.rv-aviso-flotante`: ese confirma algo que ya pasó y se apaga solo
 * a los dos segundos, y esta advertencia tiene que seguir a la vista mientras
 * la pantalla esté abierta. Sigue el mismo patrón que ya usan
 * `catalogo/control.php` e `indice.php` para un aviso permanente que no es un
 * error del formulario: `border-warn/10 bg-warn/10 text-warn`.
 *
 * @var \App\Core\Vista $vista
 */
?>
<p class="mb-6 flex items-start gap-2 rounded-rv-lg border border-warn/10 bg-warn/10 px-4 py-3 text-sm text-warn">
    <?= icono('alert-triangle', 'h-5 w-5 shrink-0') ?>
    <span><?= e($vista->t('mon.aviso_maqueta')) ?></span>
</p>
