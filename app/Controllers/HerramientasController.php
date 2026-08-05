<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/**
 * Controlador de las herramientas internas del sitio.
 *
 * Igual que HomeController: pide datos y los entrega a la vista. Toda la
 * aritmética del instrumento (cumplimiento, madurez, cobertura) ocurre en el
 * navegador, porque el consultor evalúa en vivo durante la entrevista y no hay
 * un envío al servidor de por medio.
 */
final class HerramientasController extends Controlador
{
    public function instrumentoBd(): void
    {
        $repositorio = $this->repositorio();
        $instrumento = $this->instrumento();

        $meta = $instrumento->meta();

        $this->ver('herramientas/instrumento-bd', [
            ...$this->contexto(),
            'meta'         => [
                'titulo'      => $meta['titulo'] . ' | ' . $repositorio->empresa()['nombre'],
                'descripcion' => $meta['descripcion'],
            ],
            'instrumento'  => $meta,
            'dominios'     => $instrumento->dominios(),
            'procesos'     => $instrumento->procesos(),
            'controles'    => $instrumento->controles(),
            'escala'       => $instrumento->escala(),
            'marco'        => $instrumento->marco(),
            'referencias'  => $instrumento->referencias(),
            'hojas'        => ['assets/css/instrumento.css'],
            'guiones'      => ['assets/js/instrumento.js'],
        ]);
    }
}
