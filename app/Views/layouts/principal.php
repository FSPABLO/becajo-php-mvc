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
 * @var \App\Models\Entidades\Usuario|null $usuarioActual
 */
$hojas = $hojas ?? [];
$guiones = $guiones ?? [];
$herramientas = $herramientas ?? [];
$usuarioActual = $usuarioActual ?? null;
$rutaActual = $rutaActual ?? '/';
?>
<!DOCTYPE html>
<html lang="<?= e($vista->idiomaActual() === 'en' ? 'en' : 'es-CR') ?>">
<head>
    <?= $vista->renderizar('partials/head', compact('meta', 'empresa', 'hojas')) ?>
</head>
<body class="bg-fondo font-sans text-texto antialiased">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-rv focus:bg-primario focus:px-4 focus:py-2 focus:font-semibold focus:text-primario-texto">
        <?= e($vista->t('nav.saltar_contenido')) ?>
    </a>

    <?= $vista->renderizar('partials/encabezado', compact('empresa', 'navegacion', 'herramientas', 'usuarioActual', 'rutaActual')) ?>

    <main id="contenido"><?= $contenido ?></main>

    <?php
    /*
     * El pie recibe los datos del formulario de contacto, que ahora vive
     * dentro de él. Solo la portada los provee; en el resto de las páginas
     * llegan nulos y el pie se pinta sin formulario. Ya no recibe 'navegacion'
     * ni 'herramientas': el panel de enlaces del pie se retiró.
     */
    ?>
    <?= $vista->renderizar('partials/pie', [
        'empresa'         => $empresa,
        'contacto'        => $contacto ?? null,
        'motores'         => $motores ?? [],
        'mensajes'        => $mensajes ?? [],
        'erroresContacto' => $erroresContacto ?? [],
        'valoresContacto' => $valoresContacto ?? [],
    ]) ?>

    <script src="<?= e($vista->recurso('assets/js/principal.js')) ?>" defer></script>
    <?php foreach ($guiones as $guion): ?>
    <script src="<?= e($vista->recurso($guion)) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
