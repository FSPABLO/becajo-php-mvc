<?php

declare(strict_types=1);

/**
 * El retrato de un usuario: su fotografía, o sus iniciales si no tiene.
 *
 * Existe como componente porque lo pintan dos sitios —el botón de la barra
 * lateral y la ficha de /perfil— y con dos copias la primera que cambiara
 * dejaría a la otra con un retrato distinto de la misma persona.
 *
 * LAS INICIALES NO SON UN RESPALDO DE EMERGENCIA: son el estado normal. Casi
 * ninguna cuenta tiene foto, y la fotografía es opcional siempre. Por eso el
 * disco extruido con las iniciales es la pieza de siempre y la imagen se
 * limita a taparlo cuando existe.
 *
 * La imagen va con `object-cover`: un retrato en 4:3 metido en un disco se
 * deformaría, y recortar por el centro es lo que hace cualquier avatar.
 * `alt=""` y `aria-hidden` porque el nombre está SIEMPRE al lado en texto —en
 * la barra y en la ficha—: leerlo dos veces no informa, molesta.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var \App\Models\Entidades\FotoPerfil|null $foto
 * @var string $tamano   Utilidades de alto/ancho, p. ej. 'h-9 w-9'.
 * @var string $texto    Utilidad de tamaño de letra para las iniciales.
 * @var string $forma    Utilidad de redondeo. Por omisión, disco.
 */
$foto   = $foto ?? null;
$tamano = $tamano ?? 'h-9 w-9';
$texto  = $texto ?? 'text-[12.5px]';

/*
 * La FORMA es un parámetro porque los dos usos la quieren distinta, y ninguno
 * de los dos por capricho:
 *
 *   - En la barra lateral es un DISCO. Va en una fila de navegación de 36 px
 *     junto al nombre y el rol, y ahí un rectángulo vertical o rompe el alto de
 *     la fila o se queda tan bajo que el retrato no se distingue.
 *   - En la ficha es un RECTÁNGULO VERTICAL con las esquinas del sistema. Es
 *     una fotografía enseñada como tal, no un identificador al lado de un
 *     nombre, y el retrato humano es más alto que ancho.
 *
 * Lo que NO cambia es el recorte: `object-cover` en los dos, así que una imagen
 * apaisada se recorta por el centro en vez de deformarse.
 */
$forma  = $forma ?? 'rounded-full';
?>
<span class="rv-extruido grid <?= e($tamano) ?> flex-none place-items-center overflow-hidden <?= e($forma) ?> bg-elevado <?= e($texto) ?> font-semibold text-texto"
      aria-hidden="true">
    <?php if ($foto !== null): ?>
        <?php
        /*
         * El sello de tiempo de la carga en la URL. La foto se sirve con
         * `no-cache`, pero un navegador que ya la tuviera en memoria puede
         * enseñar la anterior justo después de cambiarla, que es el único
         * momento en que alguien mira su propio avatar con atención.
         */
        ?>
        <img src="<?= e($vista->url('perfil/foto') . '?v=' . rawurlencode((string) ($foto->fechaCarga ?? $foto->id))) ?>"
             alt="" class="h-full w-full object-cover">
    <?php else: ?>
        <?= e(iniciales($usuario->nombre)) ?>
    <?php endif; ?>
</span>
