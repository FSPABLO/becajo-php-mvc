<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contratos\RepositorioContenido;
use App\Models\Entidades\Integrante;
use App\Models\Entidades\Servicio;

/**
 * Implementación del repositorio que lee el contenido de config/contenido.php.
 *
 * Es la fuente de datos ACTUAL del sitio. Cuando el proyecto se conecte a
 * Oracle, se creará RepositorioPdo con estos mismos métodos —pero ejecutando
 * consultas SQL— y se cambiará una sola línea en public/index.php.
 *
 * Ese es el beneficio concreto de programar contra una interfaz.
 */
final class RepositorioArreglo implements RepositorioContenido
{
    /** @var array<string, mixed> */
    private array $datos;

    public function __construct(string $rutaContenido)
    {
        if (!is_file($rutaContenido)) {
            throw new \RuntimeException("Archivo de contenido no encontrado: {$rutaContenido}");
        }

        /** @var array<string, mixed> $datos */
        $datos = require $rutaContenido;
        $this->datos = $datos;
    }

    /** @return array<string, mixed> */
    public function empresa(): array
    {
        return $this->datos['empresa'];
    }

    /** @return array<string, string> */
    public function meta(): array
    {
        return $this->datos['meta'];
    }

    /** @return list<array{etiqueta: string, destino: string}> */
    public function navegacion(): array
    {
        return $this->datos['navegacion'];
    }

    /** @return list<array{etiqueta: string, descripcion: string, destino: string, icono: string}> */
    public function herramientas(): array
    {
        return $this->datos['herramientas'];
    }

    /** @return array<string, mixed> */
    public function hero(): array
    {
        return $this->datos['hero'];
    }

    /** @return array<string, mixed> */
    public function retos(): array
    {
        return $this->datos['retos'];
    }

    /** @return array{titulo: string, texto: string} */
    public function encabezadoServicios(): array
    {
        return [
            'titulo' => $this->datos['servicios']['titulo'],
            'texto'  => $this->datos['servicios']['texto'],
        ];
    }

    /** @return list<Servicio> */
    public function servicios(): array
    {
        return array_map(
            static fn (array $fila): Servicio => Servicio::desdeArreglo($fila),
            $this->datos['servicios']['lista'],
        );
    }

    /** @return list<array{valor: string, etiqueta: string}> */
    public function metricas(): array
    {
        return $this->datos['metricas'];
    }

    /** @return list<string> */
    public function motores(): array
    {
        return $this->datos['motores'];
    }

    /** @return array<string, mixed> */
    public function caso(): array
    {
        return $this->datos['caso'];
    }

    /** @return list<Integrante> */
    public function equipo(): array
    {
        return array_map(
            static fn (array $fila): Integrante => Integrante::desdeArreglo($fila),
            $this->datos['equipo'],
        );
    }

    /** @return array{titulo: string, texto: string} */
    public function contacto(): array
    {
        return $this->datos['contacto'];
    }
}
