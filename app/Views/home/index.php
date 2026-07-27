<?php

declare(strict_types=1);

/**
 * Vista de la página principal.
 *
 * No contiene maquetación propia: solo ordena las secciones. Cada sección es un
 * archivo independiente en app/Views/home/secciones/, lo que permite reordenar
 * la página moviendo una línea.
 *
 * @var \App\Core\Vista $vista
 */
?>
<?= $vista->renderizar('home/secciones/portada',    compact('hero', 'motores')) ?>
<?= $vista->renderizar('home/secciones/retos',      compact('retos')) ?>
<?= $vista->renderizar('home/secciones/servicios',  ['encabezado' => $encabezadoServicios, 'servicios' => $servicios]) ?>
<?= $vista->renderizar('home/secciones/resultados', compact('metricas', 'caso')) ?>
<?= $vista->renderizar('home/secciones/equipo',     compact('equipo')) ?>
<?= $vista->renderizar('home/secciones/contacto',   compact('contacto', 'empresa', 'motores')) ?>
