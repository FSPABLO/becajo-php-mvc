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
use App\Core\BaseDatos;
use App\Core\Contenedor;
use App\Core\Enrutador;
use App\Core\Peticion;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\RepositorioArreglo;
use App\Models\RepositorioAuditoriasOracle;
use App\Models\RepositorioInstrumentoArreglo;
use App\Models\RepositorioInstrumentoOracle;

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
$sesion = new Sesion();

// ── Fuente de datos ──────────────────────────────────────────────────────────
//
// El contenido del sitio (portada, servicios, equipo) vive en un arreglo de
// PHP y ahí se queda: es texto de la página, no datos de auditoría.
$repositorio = new RepositorioArreglo(RAIZ . '/config/contenido.php');

// El catálogo del instrumento (7 dominios, 25 procesos, 75 controles) y las
// auditorías sí vienen de Oracle... cuando hay Oracle.
//
// La presencia de config/base_datos.php es el interruptor. Sin ese archivo el
// sitio arranca igual, con el catálogo leído del arreglo y sin módulo de
// auditorías: así nadie del equipo queda bloqueado por no tener la base
// levantada para trabajar en una vista. Ver config/base_datos.ejemplo.php.
$instrumento = new RepositorioInstrumentoArreglo(RAIZ . '/config/instrumento-bd.php');
$auditorias = null;

$archivoBaseDatos = RAIZ . '/config/base_datos.php';

if (is_file($archivoBaseDatos)) {
    /** @var array<string, mixed> $configuracionBd */
    $configuracionBd = require $archivoBaseDatos;

    // La conexión es perezosa: construir estos objetos no abre ningún socket.
    // Oracle solo se contacta cuando alguien pide datos de verdad.
    $bd = new BaseDatos($configuracionBd);
    $auditorias = new RepositorioAuditoriasOracle($bd);

    if (($configuracionBd['instrumento_en_oracle'] ?? true) === true) {
        // El repositorio de arreglo pasa como complemento: sigue sirviendo la
        // escala de madurez, el marco normativo y las referencias, que no
        // tienen tabla en el esquema.
        $instrumento = new RepositorioInstrumentoOracle($bd, $instrumento);
    }
}
// ─────────────────────────────────────────────────────────────────────────────

$contenedor = new Contenedor($peticion, $vista, $repositorio, $instrumento, $sesion, $auditorias);

// 4. Rutas.
$enrutador = new Enrutador();
(require RAIZ . '/config/rutas.php')($enrutador);

// 5. Atender la petición.
$enrutador->despachar($peticion, $contenedor);
