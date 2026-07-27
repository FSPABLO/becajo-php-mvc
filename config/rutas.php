<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Core\Enrutador;

/**
 * Tabla de rutas del sitio.
 *
 * Cada línea asocia una URL con el método de un controlador. Para agregar una
 * página nueva se añade una línea aquí y se crea el método correspondiente:
 *
 *     $enrutador->get('/servicios', [ServiciosController::class, 'index']);
 */
return static function (Enrutador $enrutador): void {
    $enrutador->get('/', [HomeController::class, 'index']);
};
