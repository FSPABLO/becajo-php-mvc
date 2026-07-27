<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Representa la petición HTTP entrante.
 *
 * Encapsula el acceso a $_SERVER para que el resto del sistema no lo toque
 * directamente. También resuelve la "ruta base", que es lo que permite que el
 * sitio funcione tanto en la raíz del servidor como dentro de una subcarpeta
 * (por ejemplo http://localhost/becajo/public/).
 */
final class Peticion
{
    private string $metodo;
    private string $ruta;
    private string $rutaBase;

    public function __construct()
    {
        $this->metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Carpeta donde vive el front controller, vista desde el navegador.
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $this->rutaBase = rtrim($base, '/');

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if ($this->rutaBase !== '' && str_starts_with($uri, $this->rutaBase)) {
            $uri = substr($uri, strlen($this->rutaBase));
        }

        $this->ruta = '/' . trim($uri, '/');
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    /** Ruta solicitada, ya sin la subcarpeta de instalación. Ej.: "/" */
    public function ruta(): string
    {
        return $this->ruta;
    }

    /** Prefijo para construir enlaces y rutas de recursos estáticos. */
    public function rutaBase(): string
    {
        return $this->rutaBase;
    }
}
