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
 */
?>
<!DOCTYPE html>
<html lang="es-MX" class="scroll-smooth">
<head>
    <?= $vista->renderizar('partials/head', compact('meta', 'empresa')) ?>
</head>
<body class="bg-white font-sans antialiased">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-acento-500 focus:px-4 focus:py-2 focus:font-semibold focus:text-marina-950">
        Saltar al contenido
    </a>

    <?= $vista->renderizar('partials/encabezado', compact('empresa', 'navegacion')) ?>

    <main id="contenido"><?= $contenido ?></main>

    <?= $vista->renderizar('partials/pie', compact('empresa', 'navegacion')) ?>

    <script src="<?= e($vista->url('assets/js/principal.js')) ?>" defer></script>
</body>
</html>
