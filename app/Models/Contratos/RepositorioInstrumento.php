<?php

declare(strict_types=1);

namespace App\Models\Contratos;

use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
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

    /** @return list<Dominio> */
    public function dominios(): array;

    /** @return list<Proceso> */
    public function procesos(): array;

    /** @return list<Control> */
    public function controles(): array;

    /** @return list<array{nivel: int, nombre: string, descripcion: string}> */
    public function escala(): array;

    /** @return list<array{norma: string, titulo: string, aporte: string}> */
    public function marco(): array;

    /** @return list<array{titulo: string, fuente: string, enlace: string}> */
    public function referencias(): array;
}
