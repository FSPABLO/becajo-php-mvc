<?php

declare(strict_types=1);

/**
 * @var string               $ruta
 * @var array<string, mixed> $empresa
 * @var string                $rutaBase
 */
?>
<!DOCTYPE html>
<html lang="es-CR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada | <?= e($empresa['nombre']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="grid min-h-screen place-items-center bg-[#080f26] px-6 font-sans text-texto">
    <div class="text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-[#38dcf0]">Error 404</p>
        <h1 class="mt-3 text-4xl font-extrabold">Página no encontrada</h1>
        <p class="mt-4 text-texto-2">
            La ruta <code class="rounded bg-elevado px-2 py-0.5"><?= e($ruta) ?></code>
            no existe en este sitio.
        </p>
        <a href="<?= e($rutaBase) ?>/"
           class="mt-8 inline-block rounded-rv bg-[#16bdd6] px-6 py-3 font-semibold text-[#080f26] transition hover:bg-[#38dcf0]">
            Volver al inicio
        </a>
    </div>
</body>
</html>
