<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista       $vista
 * @var array<string, string> $meta
 * @var array<string, mixed>  $empresa
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($meta['titulo']) ?></title>
<meta name="description" content="<?= e($meta['descripcion']) ?>">
<meta name="author" content="<?= e($empresa['nombre']) ?>">

<!-- Open Graph: cómo se ve el enlace al compartirlo en redes o Telegram -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($meta['titulo']) ?>">
<meta property="og:description" content="<?= e($meta['descripcion']) ?>">
<meta property="og:locale" content="es_MX">

<!--
    Tailwind se carga por CDN para que el proyecto corra sin instalar Node.
    Para producción se compila una hoja estática (ver README, "Producción").
-->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    marina: {
                        50:  '#f0f6ff', 100: '#dbe9fe', 200: '#bed6fd', 300: '#90bafb',
                        400: '#5b93f7', 500: '#366df2', 600: '#204fe6', 700: '#1a3dd3',
                        800: '#1c34ab', 900: '#0f1f4d', 950: '#080f26'
                    },
                    acento: { 400: '#38dcf0', 500: '#16bdd6', 600: '#0897b3' }
                },
                fontFamily: { sans: ['Inter', 'Segoe UI', 'system-ui', 'sans-serif'] }
            }
        }
    };
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">

<link rel="stylesheet" href="<?= e($vista->url('assets/css/estilos.css')) ?>">
