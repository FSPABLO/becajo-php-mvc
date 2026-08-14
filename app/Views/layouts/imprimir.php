<?php

declare(strict_types=1);

/**
 * Diseño para el reporte ejecutivo: sin encabezado ni menú, para que al
 * imprimir (Ctrl/Cmd+P -> Guardar como PDF) solo salga el contenido.
 *
 * @var \App\Core\Vista       $vista
 * @var string                $contenido
 * @var array<string, mixed>  $empresa
 * @var array<string, string> $meta
 */
?>
<!DOCTYPE html>
<?php
/*
 * El reporte se ve en el mismo tono oscuro que el resto del producto; es al
 * IMPRIMIRLO cuando cambia, por la regla @media print de rivendel.css, que
 * fuerza fondo claro. Eso no es un tema: es no gastar tinta en el fondo.
 */
?>
<html lang="<?= e($vista->idiomaActual() === 'en' ? 'en' : 'es-CR') ?>">
<head>
    <?= $vista->renderizar('partials/head', compact('meta', 'empresa')) ?>
    <style>
        @media print {
            .no-imprimir { display: none !important; }
        }
    </style>
</head>
<body class="bg-fondo font-sans text-texto antialiased print:bg-superficie">
    <div class="no-imprimir sticky top-0 z-10 border-b border-borde bg-superficie px-6 py-3">
        <div class="mx-auto flex max-w-3xl items-center justify-between">
            <a href="javascript:history.back()" class="text-sm font-medium text-texto-2 hover:text-texto">
                ← <?= e($vista->t('eval.volver')) ?>
            </a>
            <button type="button" onclick="window.print()"
                    class="rv-extruido rv-interactivo inline-flex items-center gap-2 rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
                <?= icono('imprimir', 'h-4 w-4') ?>
                <?= e($vista->t('eval.reporte_pdf')) ?>
            </button>
        </div>
    </div>

    <main class="mx-auto max-w-3xl px-8 py-10 print:px-0 print:py-0"><?= $contenido ?></main>
</body>
</html>
