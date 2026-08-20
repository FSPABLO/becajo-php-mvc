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
<?= $vista->renderizar('home/secciones/portada',    compact(
    'hero', 'dominios', 'procesos', 'controles', 'usuarioActual', 'auditoriasAuditor'
)) ?>
<?= $vista->renderizar('home/secciones/retos',      compact('retos')) ?>
<?= $vista->renderizar('home/secciones/servicios',  ['encabezado' => $encabezadoServicios, 'servicios' => $servicios]) ?>
<?= $vista->renderizar('home/secciones/stack',      compact('stack')) ?>
<?= $vista->renderizar('home/secciones/resultados', compact('metricas', 'caso')) ?>
<?= $vista->renderizar('home/secciones/testimonios', ['encabezado' => $encabezadoTestimonios, 'testimonios' => $testimonios]) ?>
<?= $vista->renderizar('home/secciones/equipo',     compact('equipo')) ?>
<?= $vista->renderizar('home/secciones/planes',     compact('planes')) ?>
<?php
/*
 * La sección de contacto ya no se renderiza aquí: el formulario vive dentro
 * del pie (partials/pie.php), que es quien lleva el ancla #contacto. El
 * controlador sigue enviando 'contacto', 'motores' y los valores del intento
 * porque el layout se los pasa al pie.
 */
?>
