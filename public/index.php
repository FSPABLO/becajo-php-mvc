<?php

declare(strict_types=1);

/**
 * Becajo — Front controller (controlador frontal)
 *
 * ÚNICO archivo PHP accesible desde el navegador. Toda petición pasa por aquí.
 *
 * Por eso la raíz del servidor apunta a /public y no a la raíz del proyecto:
 * el código de la aplicación, la configuración y los datos quedan FUERA del
 * alcance del navegador. Es la práctica estándar (Laravel, Symfony) y una
 * medida de seguridad concreta, no un capricho de organización.
 *
 * Secuencia: cargar clases -> construir servicios -> resolver ruta -> responder.
 */

use App\Core\Autoloader;
use App\Core\Contenedor;
use App\Core\Enrutador;
use App\Core\Peticion;
use App\Core\Vista;
use App\Models\RepositorioArreglo;

const RAIZ = __DIR__ . '/..';

// 1. Errores: visibles en desarrollo, ocultos en producción.
$entorno = getenv('APP_ENV') ?: 'desarrollo';
error_reporting(E_ALL);
ini_set('display_errors', $entorno === 'desarrollo' ? '1' : '0');

// 2. Carga automática de clases y funciones globales.
require RAIZ . '/app/Core/Autoloader.php';
(new Autoloader('App\\', RAIZ . '/app'))->registrar();
require RAIZ . '/app/Core/funciones.php';

// 3. Servicios compartidos.
$peticion = new Peticion();
$vista = new Vista(RAIZ . '/app/Views', $peticion->rutaBase());

// ── Fuente de datos ──────────────────────────────────────────────────────────
// Hoy: un arreglo de PHP.
// Para conectar Oracle, se sustituye ÚNICAMENTE esta línea por:
//     $repositorio = new RepositorioPdo(require RAIZ . '/config/base_datos.php');
// Ni el controlador ni las vistas cambian.
$repositorio = new RepositorioArreglo(RAIZ . '/config/contenido.php');
// ─────────────────────────────────────────────────────────────────────────────

$contenedor = new Contenedor($peticion, $vista, $repositorio);

// 4. Rutas.
$enrutador = new Enrutador();
(require RAIZ . '/config/rutas.php')($enrutador);

// 5. Atender la petición.
$enrutador->despachar($peticion, $contenedor);
