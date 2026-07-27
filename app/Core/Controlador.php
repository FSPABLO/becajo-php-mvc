<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contratos\RepositorioContenido;

/**
 * Clase base de todos los controladores.
 *
 * Un controlador NO consulta datos por su cuenta ni imprime HTML: pide datos al
 * modelo y se los pasa a una vista. Si algún día un controlador empieza a
 * contener SQL o etiquetas HTML, es señal de que la separación se rompió.
 */
abstract class Controlador
{
    public function __construct(protected readonly Contenedor $contenedor)
    {
    }

    protected function repositorio(): RepositorioContenido
    {
        return $this->contenedor->repositorio();
    }

    /**
     * Renderiza una vista dentro del diseño principal y la envía al navegador.
     *
     * @param array<string, mixed> $datos
     */
    protected function ver(string $vista, array $datos = [], string $plantilla = 'principal'): void
    {
        echo $this->contenedor->vista()->renderizarConPlantilla($vista, $datos, $plantilla);
    }
}
