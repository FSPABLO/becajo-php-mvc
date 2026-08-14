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
        $contexto = $this->contexto();

        /*
         * Misma página, dos marcos. El instrumento es un documento PÚBLICO —se
         * llega a él desde el menú del sitio sin cuenta— pero es además la
         * referencia que el auditor consulta mientras responde el cuestionario,
         * y por eso aparece en la barra lateral del módulo. Devolver al marco
         * público a quien tiene sesión abierta le quitaría esa barra de debajo
         * de los pies a mitad de trabajo, así que el marco lo decide la sesión
         * y no la ruta.
         */
        $plantilla = $contexto['usuarioActual'] !== null ? 'panel' : 'principal';

        $this->ver('herramientas/instrumento-bd', [
            ...$contexto,
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
            // La vista ajusta su portadilla según el marco: la barra fija del
            // sitio público pide un hueco arriba que la del panel no necesita.
            'enPanel'      => $plantilla === 'panel',
        ], $plantilla);
    }
}
