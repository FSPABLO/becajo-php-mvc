<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Motor de vistas.
 *
 * Ejecuta un archivo de plantilla capturando su salida en memoria en lugar de
 * enviarla directo al navegador. Eso permite renderizar primero el contenido y
 * después inyectarlo dentro del diseño principal.
 *
 * Las variables se pasan con extract(), de modo que dentro de la vista se
 * escribe $servicios y no $datos['servicios'].
 */
final class Vista
{
    public function __construct(
        private readonly string $directorioVistas,
        private readonly string $rutaBase,
    ) {
    }

    /**
     * Renderiza una vista suelta (sin diseño envolvente).
     *
     * @param array<string, mixed> $datos
     */
    public function renderizar(string $vista, array $datos = []): string
    {
        $ruta = $this->directorioVistas . '/' . $vista . '.php';

        if (!is_file($ruta)) {
            throw new \RuntimeException("Vista no encontrada: {$vista} ({$ruta})");
        }

        // Disponible en toda vista para construir enlaces y rutas de recursos.
        $datos['rutaBase'] = $this->rutaBase;
        $datos['vista'] = $this;

        extract($datos, EXTR_SKIP);

        ob_start();
        require $ruta;

        return (string) ob_get_clean();
    }

    /**
     * Renderiza una vista y la coloca dentro de un diseño de app/Views/layouts.
     *
     * @param array<string, mixed> $datos
     */
    public function renderizarConPlantilla(string $vista, array $datos, string $plantilla): string
    {
        $datos['contenido'] = $this->renderizar($vista, $datos);

        return $this->renderizar('layouts/' . $plantilla, $datos);
    }

    /**
     * Renderiza un componente reutilizable de app/Views/components.
     *
     * @param array<string, mixed> $datos
     */
    public function componente(string $nombre, array $datos = []): string
    {
        return $this->renderizar('components/' . $nombre, $datos);
    }

    /** Construye una URL respetando la subcarpeta donde está instalado el sitio. */
    public function url(string $ruta = ''): string
    {
        return $this->rutaBase . '/' . ltrim($ruta, '/');
    }
}
