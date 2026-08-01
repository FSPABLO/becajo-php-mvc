<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Proceso;

/**
 * Lee el instrumento desde config/instrumento-bd.php.
 *
 * Además de leer, VALIDA. El catálogo se edita a mano y un control huérfano
 * (apuntando a un proceso que no existe) rompería silenciosamente el conteo del
 * tablero. Es preferible un error explícito al arrancar que un porcentaje mal
 * calculado en el informe entregado al cliente.
 */
final class RepositorioInstrumentoArreglo implements RepositorioInstrumento
{
    /** @var array<string, mixed> */
    private array $datos;

    public function __construct(string $rutaContenido)
    {
        if (!is_file($rutaContenido)) {
            throw new \RuntimeException("Archivo del instrumento no encontrado: {$rutaContenido}");
        }

        /** @var array<string, mixed> $datos */
        $datos = require $rutaContenido;
        $this->datos = $datos;

        $this->validar();
    }

    /** @return array{titulo: string, descripcion: string, version: string} */
    public function meta(): array
    {
        return $this->datos['meta'];
    }

    /** @return list<Dominio> */
    public function dominios(): array
    {
        return array_map(
            static fn (array $fila): Dominio => Dominio::desdeArreglo($fila),
            $this->datos['dominios'],
        );
    }

    /** @return list<Proceso> */
    public function procesos(): array
    {
        return array_map(
            static fn (array $fila): Proceso => Proceso::desdeArreglo($fila),
            $this->datos['procesos'],
        );
    }

    /** @return list<Control> */
    public function controles(): array
    {
        return array_map(
            static fn (array $fila): Control => Control::desdeArreglo($fila),
            $this->datos['controles'],
        );
    }

    /** @return list<array{nivel: int, nombre: string, descripcion: string}> */
    public function escala(): array
    {
        return $this->datos['escala'];
    }

    /** @return list<array{norma: string, titulo: string, aporte: string}> */
    public function marco(): array
    {
        return $this->datos['marco'];
    }

    /** @return list<array{titulo: string, fuente: string, enlace: string}> */
    public function referencias(): array
    {
        return $this->datos['referencias'];
    }

    /**
     * Comprueba la integridad referencial del catálogo.
     *
     * Tres reglas: todo proceso pertenece a un dominio declarado, todo control
     * apunta a un proceso declarado y ningún identificador se repite.
     */
    private function validar(): void
    {
        $clavesDominio = array_column($this->datos['dominios'], 'clave');
        $numerosProceso = array_column($this->datos['procesos'], 'numero');

        foreach ($this->datos['procesos'] as $proceso) {
            if (!in_array($proceso['dominio'], $clavesDominio, true)) {
                throw new \RuntimeException(
                    "El proceso {$proceso['numero']} apunta a un dominio inexistente: {$proceso['dominio']}"
                );
            }
        }

        $vistos = [];

        foreach ($this->datos['controles'] as $control) {
            if (!in_array($control['proceso'], $numerosProceso, true)) {
                throw new \RuntimeException(
                    "El control {$control['id']} apunta a un proceso inexistente: {$control['proceso']}"
                );
            }

            if (isset($vistos[$control['id']])) {
                throw new \RuntimeException("Identificador de control repetido: {$control['id']}");
            }

            $vistos[$control['id']] = true;
        }
    }
}
