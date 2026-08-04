<?php

declare(strict_types=1);

use App\Controllers\AutenticacionController;
use App\Controllers\HerramientasController;
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

    // Herramientas internas (menú "Herramientas" del encabezado).
    $enrutador->get('/herramientas/instrumento-bd', [HerramientasController::class, 'instrumentoBd']);

    // ── Autenticación (Bloque 3) ─────────────────────────────────────────────
    //
    // Solo las acciones POST. Los formularios GET /ingresar y GET /registrarse
    // se declararán cuando existan sus vistas; declararlos ahora daría un error
    // de "vista no encontrada" en vez de un 404 honesto.
    $enrutador->post('/ingresar', [AutenticacionController::class, 'ingresar']);
    $enrutador->post('/registrarse', [AutenticacionController::class, 'registrar']);
    $enrutador->post('/salir', [AutenticacionController::class, 'salir']);
};
