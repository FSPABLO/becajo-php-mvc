<?php

declare(strict_types=1);

namespace App\Models\Contratos;

use App\Models\Entidades\Integrante;
use App\Models\Entidades\Servicio;

/**
 * Contrato que debe cumplir cualquier fuente de contenido del sitio.
 *
 * Esta interfaz es la pieza clave de la arquitectura. El controlador depende de
 * ELLA, no de una implementación concreta. Por eso hoy los datos salen de un
 * arreglo de PHP y mañana pueden salir de Oracle sin reescribir el controlador
 * ni las vistas: basta con crear una clase nueva que implemente esta interfaz.
 *
 * Es el principio de inversión de dependencias, la "D" de SOLID.
 */
interface RepositorioContenido
{
    /** @return array<string, mixed> */
    public function empresa(): array;

    /** @return array<string, string> */
    public function meta(): array;

    /** @return list<array{etiqueta: string, destino: string}> */
    public function navegacion(): array;

    /** @return array<string, mixed> */
    public function hero(): array;

    /** @return array<string, mixed> */
    public function retos(): array;

    /** @return array{titulo: string, texto: string} */
    public function encabezadoServicios(): array;

    /** @return list<Servicio> */
    public function servicios(): array;

    /** @return list<array{valor: string, etiqueta: string}> */
    public function metricas(): array;

    /** @return list<string> */
    public function motores(): array;

    /** @return array<string, mixed> */
    public function caso(): array;

    /** @return list<Integrante> */
    public function equipo(): array;

    /** @return array{titulo: string, texto: string} */
    public function contacto(): array;
}
