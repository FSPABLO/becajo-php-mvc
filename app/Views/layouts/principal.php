<?php

declare(strict_types=1);

/**
 * Diseño principal (layout).
 *
 * Es el "marco" que envuelve a toda página: <html>, <head>, encabezado y pie.
 * La vista concreta llega ya renderizada en $contenido.
 *
 * @var \App\Core\Vista        $vista
 * @var string                 $contenido
 * @var array<string, mixed>   $empresa
 * @var array<string, string>  $meta
 * @var list<array{etiqueta: string, destino: string}> $navegacion
 * @var list<array{etiqueta: string, descripcion: string, destino: string, icono: string}> $herramientas
 * @var list<string>|null     $hojas    Hojas de estilo propias de la página.
 * @var list<string>|null     $guiones  Guiones (scripts) propios de la página.
 */
$hojas = $hojas ?? [];
$guiones = $guiones ?? [];
$herramientas = $herramientas ?? [];
?>
<!DOCTYPE html>
<html lang="es-CR" class="scroll-smooth">
<head>
    <?= $vista->renderizar('partials/head', compact('meta', 'empresa', 'hojas')) ?>
</head>
<body class="bg-white font-sans antialiased">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-acento-500 focus:px-4 focus:py-2 focus:font-semibold focus:text-marina-950">
        Saltar al contenido
    </a>

    <?= $vista->renderizar('partials/encabezado', compact('empresa', 'navegacion', 'herramientas')) ?>

    <main id="contenido"><?= $contenido ?></main>

    <?= $vista->renderizar('partials/pie', compact('empresa', 'navegacion', 'herramientas')) ?>

    <script src="<?= e($vista->url('assets/js/principal.js')) ?>" defer></script>
    <?php foreach ($guiones as $guion): ?>
    <script src="<?= e($vista->url($guion)) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
