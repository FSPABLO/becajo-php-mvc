<?php

declare(strict_types=1);

/**
 * Arranque de las pruebas.
 *
 * El autoloader de Composer se carga solo para traer PHPUnit. Las clases de
 * `App\` las sigue resolviendo `app/Core/Autoloader.php`, el mismo que usa
 * `public/index.php`: asi las pruebas ejercitan el cargador real del proyecto
 * y no una version paralela que podria divergir de el sin que nadie lo note.
 *
 * `vendor/` no participa en ninguna peticion web (frente 3, plan de la parte 2).
 */

$vendor = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($vendor)) {
    require_once $vendor;
}

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

(new App\Core\Autoloader('App\\', dirname(__DIR__) . '/app'))->registrar();

(new App\Core\Autoloader('Pruebas\\', __DIR__))->registrar();
