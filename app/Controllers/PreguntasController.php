<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/**
 * Controlador de las preguntas frecuentes.
 *
 * Página propia y no una sección más de la portada: son quince respuestas
 * largas, y quien llega buscando una de ellas viene con la pregunta hecha —no
 * bajando por la página. Aparte, una URL propia se puede enlazar desde una
 * cotización o un correo, cosa que un ancla de la portada solo hace a medias.
 *
 * Igual de corto que HomeController: pide el bloque de contenido y lo entrega.
 * El texto vive en config/contenido.php, así que agregar una pregunta no toca
 * ni este archivo ni la vista.
 */
final class PreguntasController extends Controlador
{
    public function index(): void
    {
        $preguntas = $this->repositorio()->preguntas();

        $this->ver('preguntas/index', [
            ...$this->contexto(),
            'meta'      => $this->meta($preguntas['titulo'], $preguntas['texto']),
            'preguntas' => $preguntas,
        ]);
    }
}
