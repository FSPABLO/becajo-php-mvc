<?php

declare(strict_types=1);

/**
 * Avisos de la petición anterior (patrón destello).
 *
 * Los inyecta Controlador::ver() en toda vista, así que basta con incluir
 * este parcial donde se quieran mostrar.
 *
 * role="alert" hace que un lector de pantalla lo anuncie al aparecer: quien no
 * ve la pantalla también tiene que enterarse de que su contraseña falló.
 *
 * @var array{aviso: string|null, error: string|null}|null $mensajes
 */
$mensajes = $mensajes ?? ['aviso' => null, 'error' => null];
?>
<?php
/*
 * Franja de mensajes de sistema (§7.3 del diseño general): relieve HUNDIDO y
 * borde izquierdo de 3 px en el color del estado. El hundido señala «aquí se
 * recibe algo»; el borde da el segundo canal junto al ícono, para que el
 * aviso no dependa solo del color.
 */
?>
<?php if (!empty($mensajes['error'])): ?>
<div role="alert"
     class="rv-hundido mb-5 flex items-start gap-3 rounded-rv-lg border-l-[3px] border-bad bg-superficie px-4 py-3 text-sm text-bad">
    <?= icono('alert-triangle', 'h-5 w-5 shrink-0') ?>
    <span><?= e($mensajes['error']) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($mensajes['aviso'])): ?>
<div role="status"
     class="rv-hundido mb-5 flex items-start gap-3 rounded-rv-lg border-l-[3px] border-ok bg-superficie px-4 py-3 text-sm text-ok">
    <?= icono('circle-check', 'h-5 w-5 shrink-0') ?>
    <span><?= e($mensajes['aviso']) ?></span>
</div>
<?php endif; ?>
