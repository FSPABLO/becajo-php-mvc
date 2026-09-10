<?php

declare(strict_types=1);

/**
 * Avisos de la petición anterior (patrón destello).
 *
 * Los inyecta Controlador::ver() en toda vista, así que basta con incluir
 * este parcial donde se quieran mostrar.
 *
 * Los dos mensajes NO se presentan igual, y la diferencia no es de estilo:
 *
 *   error — franja fija, en el sitio donde se incluya el parcial. Dice qué
 *           salió mal y hay que poder volver a leerlo mientras se corrige.
 *   aviso — confirmación flotante abajo a la derecha, dos segundos y se va.
 *           Confirma algo que YA pasó; una vez leído no aporta nada, y
 *           dejarlo empujaba el contenido hacia abajo en cada guardado.
 *
 * role="alert" en el error hace que un lector de pantalla lo interrumpa;
 * role="status" en el aviso hace que lo anuncie sin cortar lo que esté
 * diciendo. Los dos se anuncian completos aunque el aviso desaparezca de la
 * vista: la lectura no depende de la animación.
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
<?php
/*
 * La confirmación, en verde relleno. Es la única pieza del producto que llena
 * de color un mensaje —las pastillas de estado van con contorno y tinta— y se
 * lo puede permitir porque flota sobre la página y no compite con nada: dentro
 * de una lista de 75 tarjetas, un relleno saturado destruiría la jerarquía,
 * pero aquí es lo único que hay encima.
 *
 * Sobre el verde, la tinta es text-primario-texto. El texto del cuerpo no
 * alcanza 4,5:1 sobre el primario en ninguno de los dos lienzos.
 *
 * El ícono se queda aunque el fondo ya diga «bien»: en la esquina, de reojo y
 * durante dos segundos, la forma se reconoce antes que el color, y quien no
 * distingue el verde no tiene otro canal.
 *
 * El movimiento y los tiempos viven en .rv-aviso-flotante (rivendel.css), no
 * aquí: son la mecánica, y repetirla en cada vista que muestre un destello era
 * garantizar que un día dos avisos duraran distinto.
 */
?>
<div role="status"
     class="rv-aviso-flotante rv-extruido flex items-start gap-2.5 rounded-rv-lg bg-primario px-4 py-3 text-sm font-semibold text-primario-texto">
    <?= icono('circle-check', 'h-5 w-5 shrink-0') ?>
    <span><?= e($mensajes['aviso']) ?></span>
</div>
<?php endif; ?>
