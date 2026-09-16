<?php

declare(strict_types=1);

/**
 * El globo de una pregunta del usuario.
 *
 * Una sola pieza para los dos sitios que lo pintan: la transcripción repintada
 * al cambiar de pantalla (con el texto) y la <template> que el guion clona al
 * enviar (vacía, y el guion rellena `data-asistente-texto` con textContent).
 *
 * Los dos <span> van pegados a propósito: el texto conserva sus saltos de línea
 * (pre-line), y un salto del marcado entre ellos se pintaría como una línea
 * vacía encima. Quién habla se dice en texto (sr-only) además de por el lado y
 * el tono: sin eso, el registro leído en voz alta sería una lista de frases sin
 * autor.
 *
 * @var \App\Core\Vista $vista
 * @var string|null     $texto
 */
$texto = $texto ?? '';
?>
<div class="flex justify-end">
    <p class="max-w-[85%] rounded-rv-lg rounded-br-sm bg-primario/15 px-3.5 py-2.5 text-[13.5px] leading-relaxed text-texto"><span class="sr-only"><?= e($vista->t('asistente.usted')) ?>: </span><span class="whitespace-pre-line break-words" data-asistente-texto><?= e($texto) ?></span></p>
</div>
