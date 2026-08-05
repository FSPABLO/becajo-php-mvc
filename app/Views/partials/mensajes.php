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
<?php if (!empty($mensajes['error'])): ?>
<div role="alert"
     class="mb-5 flex items-start gap-3 rounded-xl border border-alerta-400/40 bg-alerta-400/10 px-4 py-3 text-sm text-alerta-600">
    <?= icono('alerta', 'h-5 w-5 shrink-0') ?>
    <span><?= e($mensajes['error']) ?></span>
</div>
<?php endif; ?>

<?php if (!empty($mensajes['aviso'])): ?>
<div role="status"
     class="mb-5 flex items-start gap-3 rounded-xl border border-exito-400/40 bg-exito-400/10 px-4 py-3 text-sm text-exito-600">
    <?= icono('check', 'h-5 w-5 shrink-0') ?>
    <span><?= e($mensajes['aviso']) ?></span>
</div>
<?php endif; ?>
