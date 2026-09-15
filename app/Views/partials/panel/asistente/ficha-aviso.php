<?php

declare(strict_types=1);

/**
 * Ficha de aviso: lo que Lembas no pudo hacer, dicho por el servidor.
 *
 * Dos sabores con la misma pieza: el aviso corriente («esa auditoría no está
 * entre las suyas») y el error (`error` en los datos: sin conexión, límite
 * diario). El error lleva el octógono y la tinta de `bad`, y además su
 * palabra: el color nunca va solo.
 *
 * @var \App\Core\Vista    $vista
 * @var string             $clave
 * @var list<string>|null  $args
 * @var bool|null          $error
 */
$args  = $args ?? [];
$error = $error ?? false;
?>
<div class="flex items-start gap-2.5 rounded-rv-lg border px-3.5 py-2.5 text-[13px] leading-relaxed <?= $error ? 'border-bad/50 text-texto' : 'border-borde text-texto-2' ?>"
     <?= $error ? 'role="alert"' : '' ?>>
    <?= icono($error ? 'alert-octagon' : 'alert-circle', 'mt-0.5 h-4 w-4 flex-none ' . ($error ? 'text-bad' : 'text-texto-2')) ?>
    <p><?= e($vista->t($clave, ...array_map('strval', $args))) ?></p>
</div>
