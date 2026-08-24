<?php

declare(strict_types=1);

/**
 * Selector de base de datos vigilada.
 *
 * Sustituye a la rejilla de fichas: con cuatro instancias la rejilla cabía, con
 * veinte no, y el tablero de operación necesita el ancho para el instrumento,
 * no para el índice de instancias.
 *
 * ── El punto verde nunca va solo ─────────────────────────────────────────────
 *
 * Cada fila lleva punto, nombre y **la palabra que dice el estado de la
 * conexión** («Conectada», «Muestra incompleta», «Sin conexión»). Un punto de
 * color como único canal se pierde con daltonismo, impreso en gris y leído en
 * voz alta — que es la regla del sistema visual, y aquí aplica igual que en
 * pill(). El punto es refuerzo; la palabra es el dato.
 *
 * Verde es «la muestra se completó»: las dos conexiones respondieron y las
 * quince métricas se recolectaron. Gris cubre los dos casos que el enunciado
 * junta —desconectada y muestra incompleta— porque en los dos la respuesta a
 * «¿puedo fiarme de la cifra de al lado?» es la misma: no del todo.
 *
 * ── Por qué <details> y no <select> ──────────────────────────────────────────
 *
 * Elegir una instancia NAVEGA a otra dirección (/monitoreo/{clave}), así que las
 * opciones son enlaces de verdad: se pueden abrir en otra pestaña, se comparten
 * y funcionan sin JavaScript. Un <select> exigiría un guion para navegar al
 * cambiar y, sobre todo, no puede pintar el punto ni el estado de cada opción —
 * el navegador ignora el marcado dentro de <option>.
 *
 * @var \App\Core\Vista $vista
 * @var list<array<string, mixed>> $cartera  Ya ordenada por severidad.
 * @var array<string, mixed> $activa
 * @var \Closure(?float): string $cifra
 * @var \Closure(?string): string $etiquetaBanda
 */

/*
 * Estado de la conexión, en una sola función para que la lista y el resumen no
 * puedan discrepar. Devuelve la clave del texto y si el punto va encendido.
 *
 * @return array{0: bool, 1: string}
 */
$conexion = static function (array $ins): array {
    return match ($ins['muestra']) {
        'COMPLETA' => [true,  'mon.conectada'],
        'PARCIAL'  => [false, 'mon.muestra_incompleta'],
        default    => [false, 'mon.sin_conexion'],
    };
};

$punto = static function (bool $viva) use ($vista): string {
    return '<span class="h-2 w-2 shrink-0 rounded-full ' . ($viva ? 'bg-ok' : 'bg-na') . '"'
         . ' aria-hidden="true"></span>';
};

[$activaViva, $activaEstado] = $conexion($activa);
?>
<div class="min-w-0">
<label id="etiqueta-selector-bd" class="block text-xs font-medium uppercase tracking-wider text-texto-2">
        <?= e($vista->t('mon.base_vigilada')) ?>
    </label>

    <details class="group relative mt-1.5 w-[19rem] max-w-full">
        <?php
        /*
         * El resumen repite el estado de la instancia abierta. Sin eso, con
         * el desplegable cerrado el punto sería lo único que distingue una
         * base conectada de una caída.
         */
        ?>
        <summary class="rv-extruido flex cursor-pointer list-none items-center gap-2.5 rounded-rv border border-borde bg-superficie px-3.5 py-2.5"
                 aria-labelledby="etiqueta-selector-bd">
            <?= $punto($activaViva) ?>
            <span class="rv-id truncate text-sm font-semibold text-oro-texto">
                <?= e((string) $activa['clave']) ?>
            </span>
            <span class="truncate text-xs text-texto-2">
                <?= e($vista->t($activaEstado)) ?>
            </span>
            <span class="ml-auto shrink-0 text-texto-2 transition group-open:rotate-180">
                <?= icono('chevron', 'h-4 w-4') ?>
            </span>
        </summary>

        <ul class="rv-extruido-lg absolute left-0 z-30 mt-1.5 max-h-80 w-full overflow-y-auto rounded-rv border border-borde bg-superficie py-1">
            <?php foreach ($cartera as $ins): ?>
                <?php
                [$viva, $estado] = $conexion($ins);
                $esActiva = $ins['clave'] === $activa['clave'];
                $publica  = $ins['isbd'] !== null;
                ?>
                <li>
                    <a href="<?= e($vista->url('monitoreo/' . $ins['clave'])) ?>"
                       <?= $esActiva ? 'aria-current="true"' : '' ?>
                       class="flex items-center gap-2.5 px-3.5 py-2.5 transition hover:bg-elevado
                              <?= $esActiva ? 'bg-elevado' : '' ?>">

                        <?= $punto($viva) ?>

                        <span class="min-w-0 flex-1">
                            <span class="rv-id block truncate text-sm font-semibold text-oro-texto">
                                <?= e((string) $ins['clave']) ?>
                            </span>
                            <span class="block truncate text-xs text-texto-2">
                                <?= e((string) $ins['entorno']) ?> · <?= e($vista->t($estado)) ?>
                            </span>
                        </span>

                        <?php
                        /*
                         * El índice de cada opción, para que elegir no sea a
                         * ciegas: es la información que daba la rejilla de
                         * fichas y que no se quiere perder al plegarla.
                         */
                        ?>
                        <span class="tabular shrink-0 text-right text-sm font-semibold <?= $publica ? 'text-texto' : 'text-na' ?>">
                            <?= e($publica ? $cifra((float) $ins['isbd']) : '—') ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </details>
</div>
