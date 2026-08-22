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
 *
 * NO hay aquí host/puerto/servicio: esas señas son POR INSTANCIA, no
 * globales del agente, y ya viven en la tabla `instancia`
 * (host/puerto/servicio_raiz/servicio_contenedor, Scripts/06). Este archivo
 * solo tiene lo que SÍ es igual para todas las instancias que vigila el
 * mismo agente: la cuenta, y su propio comportamiento operativo.
 */

return [

    'usuario' => env('BD_MONITOR_USUARIO', 'C##RIVENDEL_MONITOR'),

    'clave' => env('BD_MONITOR_CLAVE', 'RivendelMonitor2026'),

    'charset' => env('BD_MONITOR_CHARSET', 'AL32UTF8'),

    // Cada cuántos minutos corre bin/monitor.php — lo usa el servicio
    // programador de docker-compose.yml (Fase 5), no el script en sí.
    'intervalo_minutos' => (int) env('BD_MONITOR_INTERVALO_MINUTOS', '5'),

    // Tiempo límite por consulta individual y por la muestra completa de una
    // instancia (§8.2 del plan): una instancia que no responde marca la
    // muestra FALLIDA y el agente sigue con la siguiente, en vez de
    // quedarse esperando indefinidamente.
    'tiempo_limite_consulta_seg' => (int) env('BD_MONITOR_TIMEOUT_CONSULTA', '10'),

    'tiempo_limite_muestra_seg' => (int) env('BD_MONITOR_TIMEOUT_MUESTRA', '60'),

    // Cortacircuitos (§8.2 del plan): tras esta cantidad de muestras
    // FALLIDA consecutivas para una instancia, el agente la salta ese
    // ciclo en vez de insistir, para no martillar una base ya en problemas.
    'fallos_consecutivos_para_pausar' => (int) env('BD_MONITOR_FALLOS_PAUSA', '3'),

    // Umbral de tiempo por ejecución para M-CON-01. catalogo-metricas-v0.md
    // no fija un valor propio: es de calibración, no un dato del catálogo.
    'umbral_consulta_costosa_ms' => (float) env('BD_MONITOR_UMBRAL_CONSULTA_MS', '1000'),
];
