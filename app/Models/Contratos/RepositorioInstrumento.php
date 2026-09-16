<?php

declare(strict_types=1);

namespace App\Models\Contratos;

use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Estandar;
use App\Models\Entidades\Proceso;

/**
 * Contrato de la fuente de contenido del instrumento de consultoría.
 *
 * Misma idea que RepositorioContenido: el controlador depende de esta interfaz
 * y no del archivo config/instrumento-bd.php. El día que el catálogo de
 * controles se administre desde una tabla, se escribe otra implementación.
 */
interface RepositorioInstrumento
{
    /** @return array{titulo: string, descripcion: string, version: string} */
    public function meta(): array;

    /**
     * Normas que el instrumento sabe evaluar.
     *
     * @return list<Estandar>
     */
    public function estandares(): array;

    /*
     * Los cuatro métodos siguientes reciben la norma. Por omisión es ISO,
     * que es lo que muestran el sitio público y el catálogo administrable;
     * las pantallas de una auditoría pasan la norma de esa auditoría.
     */

    /** @return list<Dominio> */
    public function dominios(string $estandar = Estandar::ISO): array;

    /** @return list<Proceso> */
    public function procesos(string $estandar = Estandar::ISO): array;

    /** @return list<Control> */
    public function controles(string $estandar = Estandar::ISO): array;

    /** @return list<array{nivel: int, nombre: string, descripcion: string}> */
    public function escala(string $estandar = Estandar::ISO): array;

    /** @return list<array{norma: string, titulo: string, aporte: string}> */
    public function marco(): array;

    /** @return list<array{titulo: string, fuente: string, enlace: string}> */
    public function referencias(): array;
}
