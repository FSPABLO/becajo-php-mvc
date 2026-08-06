<?php

declare(strict_types=1);

/**
 * @var array<string, mixed> $empresa
 * @var string                $rutaBase
 */
?>
<!DOCTYPE html>
<html lang="es-CR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sin permiso | <?= e($empresa['nombre']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="grid min-h-screen place-items-center bg-[#080f26] px-6 font-sans text-white">
    <div class="text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-[#f9bd4a]">Error 403</p>
        <h1 class="mt-3 text-4xl font-extrabold">No tiene permiso para esto</h1>
        <p class="mt-4 text-slate-300">
            Esta sección es solo para administradores de base de datos.
        </p>
        <a href="<?= e($rutaBase) ?>/"
           class="mt-8 inline-block rounded-lg bg-[#16bdd6] px-6 py-3 font-semibold text-[#080f26] transition hover:bg-[#38dcf0]">
            Volver al inicio
        </a>
    </div>
</body>
</html>
