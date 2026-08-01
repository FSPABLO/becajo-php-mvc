<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/**
 * Controlador de la página principal.
 *
 * Note lo corto que es. Un controlador solo hace tres cosas:
 *   1. Pide los datos al modelo (el repositorio).
 *   2. Los arma en un arreglo.
 *   3. Se los entrega a la vista.
 *
 * No consulta la base de datos directamente, no calcula reglas de negocio y no
 * imprime HTML. Si empieza a crecer, es señal de que algo debe moverse al
 * modelo o a la vista.
 */
final class HomeController extends Controlador
{
    public function index(): void
    {
        $repositorio = $this->repositorio();

        $this->ver('home/index', [
            'empresa'             => $repositorio->empresa(),
            'meta'                => $repositorio->meta(),
            'navegacion'          => $repositorio->navegacion(),
            'herramientas'        => $repositorio->herramientas(),
            'hero'                => $repositorio->hero(),
            'retos'               => $repositorio->retos(),
            'encabezadoServicios' => $repositorio->encabezadoServicios(),
            'servicios'           => $repositorio->servicios(),
            'metricas'            => $repositorio->metricas(),
            'motores'             => $repositorio->motores(),
            'caso'                => $repositorio->caso(),
            'equipo'              => $repositorio->equipo(),
            'contacto'            => $repositorio->contacto(),
        ]);
    }
}
