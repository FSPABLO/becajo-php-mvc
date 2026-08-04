<?php

declare(strict_types=1);

/**
 * Funciones globales disponibles en las vistas.
 *
 * Se mantienen al mínimo a propósito: la lógica vive en clases, no aquí.
 */

if (!function_exists('e')) {
    /**
     * Escapa una cadena antes de imprimirla en HTML.
     *
     * Regla del proyecto: TODO dato que se imprima pasa por aquí. Con contenido
     * fijo es una formalidad; con datos venidos de la base de datos es lo que
     * impide una inyección de HTML o JavaScript (XSS).
     */
    function e(?string $valor): string
    {
        return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /**
     * Lee una variable de entorno con un valor por defecto.
     *
     * La usa config/base_datos.php para que las credenciales vivan en el
     * entorno (docker-compose.yml, panel del hosting) y no en el código.
     * getenv() devuelve false cuando la variable no existe y '' cuando existe
     * pero está vacía: ambos casos se tratan igual, porque una credencial en
     * blanco no es una credencial.
     */
    function env(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = getenv($clave);

        return ($valor === false || $valor === '') ? $porDefecto : $valor;
    }
}

if (!function_exists('icono')) {
    /**
     * Devuelve el SVG de un ícono del catálogo interno.
     *
     * Los íconos van en línea (no como fuente ni imagen externa) para que el
     * sitio funcione sin conexión y sin peticiones adicionales.
     */
    function icono(string $nombre, string $clases = 'h-6 w-6'): string
    {
        static $trazos = [
            'servidor' => '<rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01"/>',
            'rayo'     => '<path d="M13 2 4.5 13.5H11l-1 8.5 8.5-11.5H12l1-8.5Z"/>',
            'escudo'   => '<path d="M12 3 4 6v6c0 4.5 3.2 8.4 8 9.5 4.8-1.1 8-5 8-9.5V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
            'respaldo' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>',
            'grafica'  => '<path d="M3 3v18h18"/><path d="m7 14 3-4 3 3 4-6"/>',
            'usuarios' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
            'flecha'   => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            'check'    => '<path d="m5 12 5 5L20 7"/>',
            'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',

            // Catálogo del instrumento de consultoría
            'herramienta' => '<path d="M14.7 6.3a4 4 0 0 1-5 5L5 16v3h3l4.7-4.7a4 4 0 0 0 5-5l-2.4 2.4-2.1-2.1 2.5-2.3Z"/>',
            'chevron'     => '<path d="m6 9 6 6 6-6"/>',
            'buscar'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'llave'       => '<circle cx="7.5" cy="15.5" r="3.5"/><path d="m10 13 8-8"/><path d="m15 8 2 2"/><path d="m18 5 2 2"/>',
            'disco'       => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
            'documento'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/>',
            'tablero'     => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
            'libro'       => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v15H6.5A2.5 2.5 0 0 0 4 19.5Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20v5H6.5A2.5 2.5 0 0 1 4 19.5Z"/>',
            'enlace'      => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>',
            'descargar'   => '<path d="M12 3v12"/><path d="m7.5 10.5 4.5 4.5 4.5-4.5"/><path d="M4 19h16"/>',
            'importar'    => '<path d="M12 15V3"/><path d="m7.5 7.5 4.5-4.5 4.5 4.5"/><path d="M4 19h16"/>',
            'imprimir'    => '<path d="M7 8V3h10v5"/><path d="M7 18H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><rect x="7" y="14" width="10" height="7" rx="1"/>',
            'basura'      => '<path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M9 7V4h6v3"/>',
            'chispa'      => '<path d="M12 3v5M12 16v5M3 12h5M16 12h5"/><path d="m6.5 6.5 3 3M14.5 14.5l3 3M17.5 6.5l-3 3M9.5 14.5l-3 3"/>',
            'alerta'      => '<path d="M12 4 2.5 20h19L12 4Z"/><path d="M12 10v4"/><path d="M12 17h.01"/>',
        ];

        $trazo = $trazos[$nombre] ?? $trazos['check'];

        return '<svg class="' . e($clases) . '" viewBox="0 0 24 24" fill="none" '
             . 'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" '
             . 'stroke-linejoin="round" aria-hidden="true">' . $trazo . '</svg>';
    }
}
