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
        ];

        $trazo = $trazos[$nombre] ?? $trazos['check'];

        return '<svg class="' . e($clases) . '" viewBox="0 0 24 24" fill="none" '
             . 'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" '
             . 'stroke-linejoin="round" aria-hidden="true">' . $trazo . '</svg>';
    }
}
