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
<html lang="es-CR">
<head>
    <?= $vista->renderizar('partials/head', compact('meta', 'empresa')) ?>
    <style>
        @media print {
            .no-imprimir { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased print:bg-white">
    <div class="no-imprimir sticky top-0 z-10 border-b border-slate-200 bg-white px-6 py-3">
        <div class="mx-auto flex max-w-3xl items-center justify-between">
            <a href="javascript:history.back()" class="text-sm font-medium text-slate-500 hover:text-marina-950">
                ← Volver
            </a>
            <button type="button" onclick="window.print()"
                    class="rounded-lg bg-acento-500 px-4 py-2 text-sm font-semibold text-marina-950 transition hover:bg-acento-400">
                Descargar / imprimir PDF
            </button>
        </div>
    </div>

    <main class="mx-auto max-w-3xl px-8 py-10 print:px-0 print:py-0"><?= $contenido ?></main>
</body>
</html>
