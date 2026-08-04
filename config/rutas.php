<?php

declare(strict_types=1);

use App\Controllers\AuditoriaController;
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
    // Cada formulario es un par GET/POST sobre la misma URL: el GET la pinta y
    // el POST la procesa. Al fallar, el POST redirige a su propio GET, así que
    // recargar nunca reenvía el formulario.
    $enrutador->get('/ingresar', [AutenticacionController::class, 'ingresarFormulario']);
    $enrutador->post('/ingresar', [AutenticacionController::class, 'ingresar']);

    $enrutador->get('/registrarse', [AutenticacionController::class, 'registrarseFormulario']);
    $enrutador->post('/registrarse', [AutenticacionController::class, 'registrar']);

    // Cerrar sesión es POST y no GET a propósito: una acción que cambia estado
    // no debe poder dispararse con un simple enlace o una etiqueta <img>.
    $enrutador->post('/salir', [AutenticacionController::class, 'salir']);

    // ── Módulo de evaluación de riesgo (Bloque 4) ────────────────────────────
    //
    // El orden de declaración no importa: el enrutador resuelve siempre la
    // coincidencia exacta antes de probar los patrones con {llaves}, así que
    // "/evaluacion/nueva" nunca se lo queda "/evaluacion/{id}".
    $enrutador->get('/evaluacion', [AuditoriaController::class, 'panel']);

    $enrutador->get('/evaluacion/nueva', [AuditoriaController::class, 'nuevaFormulario']);
    $enrutador->post('/evaluacion/nueva', [AuditoriaController::class, 'crear']);

    $enrutador->get('/evaluacion/{id}', [AuditoriaController::class, 'mostrar']);
    $enrutador->post('/evaluacion/{id}', [AuditoriaController::class, 'actualizar']);

    $enrutador->get('/evaluacion/{id}/controles/{codigo}', [AuditoriaController::class, 'plantillaControl']);
    $enrutador->post('/evaluacion/{id}/controles/{codigo}', [AuditoriaController::class, 'guardarControl']);

    $enrutador->post('/evaluacion/{id}/finalizar', [AuditoriaController::class, 'finalizar']);
    $enrutador->post('/evaluacion/{id}/reabrir', [AuditoriaController::class, 'reabrir']);

    $enrutador->get('/evaluacion/{id}/resultados', [AuditoriaController::class, 'resultados']);
};
