<?php

declare(strict_types=1);

/**
 * Plantilla de configuración del agente de monitoreo.
 *
 * CÓPIESE a config/monitor.php, que está en .gitignore y por tanto nunca viaja
 * al repositorio. Este archivo (el .ejemplo) sí se versiona.
 *
 *     cp config/monitor.ejemplo.php config/monitor.php
 *
 * La presencia de config/monitor.php es lo que ACTIVA el módulo de
 * monitoreo (Contenedor::hayMonitor()): sin él, /monitoreo muestra su estado
 * vacío y nada más se rompe — mismo criterio que config/base_datos.php.
 *
 * La cuenta C##RIVENDEL_MONITOR la crea Scripts/00_usuario_monitor.sql, como
 * SYS contra CDB$ROOT (no como becajo@FREEPDB1 — ver la cabecera de ese
 * script). Es un usuario COMÚN: la misma cuenta sirve para las dos
 * conexiones que abre el agente por muestra (§10.1 del plan de la parte 2),
 * solo cambia el servicio al que se conecta.
 */

return [

    'usuario' => env('BD_MONITOR_USUARIO', 'C##RIVENDEL_MONITOR'),

    'clave' => env('BD_MONITOR_CLAVE', 'RivendelMonitor2026'),

    // Mismo host/puerto para las dos conexiones (§10.1 del plan): lo único
    // que cambia entre RAIZ y CONTENEDOR es el nombre de servicio.
    'host' => env('BD_MONITOR_HOST', 'oracle'),

    'puerto' => (int) env('BD_MONITOR_PUERTO', '1521'),

    // CDB$ROOT — donde vive V$RESOURCE_LIMIT de verdad (dentro del PDB
    // devuelve 0 filas sin error).
    'servicio_raiz' => env('BD_MONITOR_SERVICIO_RAIZ', 'FREE'),

    // El PDB auditado — tablespaces, datafiles, V$SQLSTATS de esa base.
    'servicio_contenedor' => env('BD_MONITOR_SERVICIO_CONTENEDOR', 'FREEPDB1'),

    'charset' => env('BD_MONITOR_CHARSET', 'AL32UTF8'),

    // Las claves de bin/monitor.php (intervalo, timeouts, cortacircuitos)
    // se suman aquí cuando se escriba el agente — no antes, para no fijar
    // un valor que todavía no se probó.
];
