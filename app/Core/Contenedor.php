<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contratos\RepositorioContenido;
use App\Models\Contratos\RepositorioInstrumento;

/**
 * Contenedor de dependencias.
 *
 * Guarda los objetos compartidos (la petición, el motor de vistas, el
 * repositorio) y se los entrega a quien los necesite. Su valor real está en el
 * método repositorio(): el resto del sistema pide "el repositorio" sin saber si
 * detrás hay un arreglo de PHP o una consulta a Oracle.
 *
 * Cambiar la fuente de datos es cambiar UNA línea en public/index.php.
 */
final class Contenedor
{
    public function __construct(
        private readonly Peticion $peticion,
        private readonly Vista $vista,
        private readonly RepositorioContenido $repositorio,
        private readonly RepositorioInstrumento $instrumento,
    ) {
    }

    public function peticion(): Peticion
    {
        return $this->peticion;
    }

    public function vista(): Vista
    {
        return $this->vista;
    }

    public function repositorio(): RepositorioContenido
    {
        return $this->repositorio;
    }

    public function instrumento(): RepositorioInstrumento
    {
        return $this->instrumento;
    }
}
