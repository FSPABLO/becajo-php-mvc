<?php

declare(strict_types=1);

/**
 * Registro de bases de datos conectadas al sistema.
 *
 * Alimenta la ficha «Bases de datos conectadas» del panel, que agrupa por motor
 * y enseña cuántas conexiones hay de cada uno. Es la PREVISUALIZACIÓN del
 * monitoreo continuo: el diagnóstico de salud —latencia, espacio, bloqueos,
 * último respaldo— se leerá de cada instancia cuando el módulo exista.
 *
 * POR QUÉ UN ARCHIVO DE CONFIGURACIÓN Y NO UNA TABLA
 * --------------------------------------------------
 * Porque todavía no hay nada que consultar. El esquema no tiene entidad para
 * una base de datos vigilada —el alcance de una auditoría es texto libre en
 * auditoria.area_evaluada— y añadir una tabla para pintar una tarjeta sería
 * decidir su forma antes de saber qué va a guardar el monitoreo.
 *
 * La forma de aquí SÍ es la que tendrá esa tabla: una fila por instancia, con
 * su motor. Cuando exista, se cambia quién lee esta lista y la vista no se
 * entera — igual que RepositorioInstrumentoArreglo cedió el sitio a
 * RepositorioInstrumentoOracle sin que cambiara una sola plantilla.
 *
 * NO va en config/contenido.php a propósito: aquello es texto del sitio y tiene
 * gemelo por idioma (contenido.en.php). Un nombre de instancia no se traduce, y
 * duplicarlo en dos archivos es garantizar que se separen.
 *
 * El conteo que sale en la ficha es el largo de 'instancias'. No se escribe a
 * mano en ninguna parte: una cifra escrita al lado de una lista es una cifra
 * que deja de cuadrar con la lista.
 */

return [
    [
        'motor' => 'Oracle',
        // Las medidas son las reales del archivo. Se imprimen en el <img> para
        // que el navegador reserve el hueco y la rejilla no salte al cargar.
        'logo'  => 'assets/images/oracle.png',
        'ancho' => 221,
        'alto'  => 240,
        'instancias' => [
            // La del propio producto. La vista la marca aparte porque su estado
            // sí es comprobable: si esta página se está pintando, está viva.
            ['nombre' => 'FREEPDB1 · becajo', 'entorno' => 'Aplicación', 'propia' => true],
            ['nombre' => 'PRODCORE1',         'entorno' => 'Producción'],
            ['nombre' => 'PRODCORE2',         'entorno' => 'Producción'],
            ['nombre' => 'PREPROD1',          'entorno' => 'Preproducción'],
        ],
    ],
    [
        'motor' => 'PostgreSQL',
        'logo'  => 'assets/images/postgresql.png',
        'ancho' => 610,
        'alto'  => 280,
        'instancias' => [
            ['nombre' => 'analitica-01', 'entorno' => 'Producción'],
            ['nombre' => 'analitica-02', 'entorno' => 'Producción'],
            ['nombre' => 'reportes-01',  'entorno' => 'Preproducción'],
        ],
    ],
    [
        'motor' => 'SQL Server',
        'logo'  => 'assets/images/sql-server.png',
        'ancho' => 295,
        'alto'  => 240,
        'instancias' => [
            ['nombre' => 'FINANZAS\\SQL01', 'entorno' => 'Producción'],
            ['nombre' => 'FINANZAS\\SQL02', 'entorno' => 'Contingencia'],
        ],
    ],
    [
        'motor' => 'MySQL',
        'logo'  => 'assets/images/mysql.png',
        'ancho' => 427,
        'alto'  => 240,
        'instancias' => [
            ['nombre' => 'portal-web',  'entorno' => 'Producción'],
            ['nombre' => 'portal-test', 'entorno' => 'Pruebas'],
        ],
    ],
    [
        'motor' => 'MariaDB',
        'logo'  => 'assets/images/mariadb.png',
        'ancho' => 480,
        'alto'  => 240,
        'instancias' => [
            ['nombre' => 'intranet-01', 'entorno' => 'Producción'],
        ],
    ],
    [
        'motor' => 'MongoDB',
        'logo'  => 'assets/images/mongodb.png',
        'ancho' => 399,
        'alto'  => 240,
        'instancias' => [
            ['nombre' => 'bitacoras-rs0', 'entorno' => 'Producción'],
        ],
    ],
];
