<?php

declare(strict_types=1);

/**
 * @var \App\Core\Vista       $vista
 * @var array<string, string> $meta
 * @var array<string, mixed>  $empresa
 * @var list<string>|null     $hojas  Hojas de estilo propias de la página.
 */
$hojas = $hojas ?? [];
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
<meta property="og:locale" content="es_CR">
<meta property="og:image" content="<?= e($vista->recurso('assets/images/branding/favicon.png')) ?>">

<link rel="icon" type="image/png" sizes="32x32" href="<?= e($vista->recurso('assets/images/branding/favicon-32.png')) ?>">
<link rel="icon" type="image/png" sizes="512x512" href="<?= e($vista->recurso('assets/images/branding/favicon.png')) ?>">
<link rel="apple-touch-icon" href="<?= e($vista->recurso('assets/images/branding/apple-touch-icon.png')) ?>">

<!--
    El producto tiene un solo tono, «Imladris de noche», declarado en :root de
    assets/css/rivendel.css. Por eso aquí no hay guion de tema ni destello que
    prevenir: el color llega con la hoja de estilos.
-->

<!--
    Tailwind se carga por CDN para que el proyecto corra sin instalar Node.
    Para producción se compila una hoja estática (ver README, "Producción").
-->
<script src="https://cdn.tailwindcss.com"></script>
<script>
    /*
     * La paleta NO lleva valores literales: cada color apunta al token CSS que
     * declara assets/css/rivendel.css. Así cambiar la paleta es tocar un solo
     * archivo, y las utilidades de Tailwind siguen el cambio solas.
     *
     * Los tokens se declaran como ternas de canales ("12 22 17") en vez de
     * hexadecimal justamente por esta línea: <alpha-value> necesita ese
     * formato para que funcione el modificador de opacidad (bg-primario/10).
     */
    const conAlfa = (token) => `rgb(var(${token}) / <alpha-value>)`;

    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    fondo:      conAlfa('--rv-bg'),
                    superficie: conAlfa('--rv-surface'),
                    elevado:    conAlfa('--rv-elev'),
                    borde:      conAlfa('--rv-border'),

                    /* La barra lleva su propio par de tonos de texto. */
                    nav: {
                        DEFAULT: conAlfa('--rv-nav'),
                        texto:   conAlfa('--rv-nav-text'),
                        texto2:  conAlfa('--rv-nav-text-2')
                    },

                    texto: {
                        DEFAULT: conAlfa('--rv-text'),
                        2:       conAlfa('--rv-text-2')
                    },

                    /*
                     * 'primario-texto' es el texto SOBRE el relleno primario.
                     * Usarlo en todo botón relleno: text-texto sobre el verde
                     * da 3,3:1 y no alcanza el 4,5:1 de texto normal.
                     */
                    primario: {
                        DEFAULT: conAlfa('--rv-primary'),
                        hover:   conAlfa('--rv-primary-hover'),
                        texto:   conAlfa('--rv-on-primary')
                    },

                    /*
                     * Oro = referencia normativa, y nada más (§5.2): IDs de
                     * control, badges de norma, enlaces a cláusulas.
                     */
                    oro: {
                        DEFAULT: conAlfa('--rv-gold'),
                        texto:   conAlfa('--rv-gold-text'),
                        tinte:   conAlfa('--rv-gold-tint')
                    },

                    /*
                     * Escala semántica de estado (§4). Independiente de la
                     * paleta de marca: no reutiliza el oro de acento.
                     * 'na' es gris neutro a propósito, nunca verde: teñir de
                     * verde un control excluido inflaría el cumplimiento.
                     */
                    ok:   conAlfa('--rv-ok'),
                    warn: conAlfa('--rv-warn'),
                    bad:  conAlfa('--rv-bad'),
                    na:   conAlfa('--rv-na')
                },

                /* Las cuatro voces excluyentes del sistema (§2). */
                fontFamily: {
                    marca:  ['Cinzel', 'Georgia', 'serif'],
                    titulo: ['EB Garamond', 'Georgia', 'serif'],
                    sans:   ['Inter', 'system-ui', 'Segoe UI', 'sans-serif'],
                    mono:   ['JetBrains Mono', 'Consolas', 'monospace']
                },

                borderRadius: {
                    rv:    '8px',
                    'rv-lg': '12px'
                }
            }
        }
    };
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!--
    Cuatro familias, una por voz. En producción se auto-hospedan en .woff2 con
    subconjunto latin-ext (tildes y ñ), font-display: swap y preload de Cinzel
    e Inter, para reducir la superficie de terceros — control A.5.19.
-->
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600&family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">

<link rel="stylesheet" href="<?= e($vista->recurso('assets/css/rivendel.css')) ?>">
<?php foreach ($hojas as $hoja): ?>
<link rel="stylesheet" href="<?= e($vista->recurso($hoja)) ?>">
<?php endforeach; ?>
