<?php

declare(strict_types=1);

/**
 * Catálogo del monitor de salud, en arreglo.
 *
 * Espejo de `Scripts/07_datos_semilla_monitor.sql`. Mismo patrón que
 * `config/instrumento-bd.php` frente a `Scripts/02_datos_semilla.sql`: el dato
 * vive versionado en el control de código fuente, separado del comportamiento
 * de la aplicación, y el esquema de Oracle lo recibe por un script equivalente.
 *
 * Cada entrada tiene deliberadamente la **forma de una fila** de la tabla, con
 * los mismos nombres de columna en minúscula y los booleanos como 0 y 1. Así
 * `Metrica::desdeFila()` e `Instancia::desdeFila()` son las mismas de siempre y
 * no hace falta una segunda ruta de construcción que pudiera divergir de la de
 * Oracle sin que nadie lo note.
 *
 * Al tocar el catálogo, tóquense los dos archivos. `RepositorioMonitorArregloTest`
 * compara este arreglo contra el `.sql` y falla si se desincronizan.
 */

$vigenteDesde = '2026-01-01 00:00:00';

/** Un juego de umbrales con la forma de una fila de `umbral`. */
$umbral = static function (
    int $id,
    string $codigo,
    float $uOpt,
    float $uAdv,
    float $uDeg,
    float $uCrit,
) use ($vigenteDesde): array {
    return [
        'id_umbral'       => $id,
        'codigo_metrica'  => $codigo,
        'clave_instancia' => null,
        'u_opt'           => $uOpt,
        'u_adv'           => $uAdv,
        'u_deg'           => $uDeg,
        'u_crit'          => $uCrit,
        'valido_desde'    => $vigenteDesde,
        'valido_hasta'    => null,
    ];
};

/** Una métrica con la forma de una fila de `metrica`, con su umbral vigente. */
$metrica = static function (
    string $codigo,
    string $componente,
    string $nombre,
    ?string $unidad,
    string $vistaOrigen,
    string $sentido,
    string $ambito,
    ?int $peso,
    float $uMax,
    int $acumulada,
    int $esIdentidad,
    int $entraIsbd,
    ?string $anclaIso,
    ?array $umbral,
): array {
    return [
        'codigo'       => $codigo,
        'componente'   => $componente,
        'nombre'       => $nombre,
        'unidad'       => $unidad,
        'vista_origen' => $vistaOrigen,
        'sentido'      => $sentido,
        'ambito'       => $ambito,
        'peso'         => $peso,
        'u_max'        => $uMax,
        'acumulada'    => $acumulada,
        'es_identidad' => $esIdentidad,
        'entra_isbd'   => $entraIsbd,
        'ancla_iso'    => $anclaIso,
    ] + ($umbral ?? []);
};

return [

    // ── Instancias vigiladas ────────────────────────────────────────────────
    //
    // FREEPDB1 es la instancia propia del entorno de desarrollo, la misma que
    // siembra el script. Sin ella `bin/monitor.php` no tiene qué recolectar.

    'instancias' => [
        [
            'clave'               => 'FREEPDB1',
            'nombre'              => 'FREEPDB1 · becajo',
            'motor'               => 'Oracle',
            'host'                => 'oracle',
            'puerto'              => 1521,
            'servicio_raiz'       => 'FREE',
            'servicio_contenedor' => 'FREEPDB1',
            'entorno'             => 'Aplicación',
            'criticidad'          => 'MEDIA',
            'activa'              => 1,
            'demostrativa'        => 0,
        ],
    ],

    // ── Catálogo de métricas ────────────────────────────────────────────────
    //
    // Orden: el mismo que devuelve `RepositorioMonitorOracle::metricas()`,
    // es decir por componente y luego por código. ARCHIVOS, CONSULTAS,
    // MEMORIA, PROCESOS.

    'metricas' => [

        // ARCHIVOS
        $metrica('M-ARC-01', 'ARCHIVOS', 'Utilización del peor tablespace', '%', 'dba_tablespace_usage_metrics', 'MENOR_MEJOR', 'CONTENEDOR', 3, 100.0, 0, 0, 1, 'A.8.6', $umbral(8, 'M-ARC-01', 50, 70, 85, 95)),
        $metrica('M-ARC-02', 'ARCHIVOS', 'Datafiles en estado válido', null, 'v$datafile', 'ESTADO', 'CONTENEDOR', null, 100.0, 0, 0, 1, 'A.5.30; A.8.6', null),
        $metrica('M-ARC-03', 'ARCHIVOS', 'Grupos de redo sin miembros inválidos', null, 'v$log + v$logfile', 'ESTADO', 'RAIZ', null, 100.0, 0, 0, 1, 'A.5.30; A.8.15', null),
        $metrica('M-ARC-04', 'ARCHIVOS', 'Utilización del peor tablespace temporal', '%', 'dba_temp_free_space', 'MENOR_MEJOR', 'CONTENEDOR', 2, 100.0, 0, 0, 1, 'A.8.6', $umbral(9, 'M-ARC-04', 50, 70, 85, 95)),
        $metrica('M-ARC-05', 'ARCHIVOS', 'Utilización del peor tablespace sin crecimiento automático', '%', 'dba_tablespace_usage_metrics + dba_data_files', 'MENOR_MEJOR', 'CONTENEDOR', 2, 100.0, 0, 0, 1, 'A.8.6', $umbral(10, 'M-ARC-05', 50, 70, 85, 95)),

        // CONSULTAS — se mide, se muestra y alerta, pero no suma al ISBD
        // (§3.1 del plan). Por eso `entra_isbd` en 0 y su peso no se usa.
        $metrica('M-CON-01', 'CONSULTAS', 'Sentencias del top-N que superan el umbral de tiempo por ejecución', 'sentencias', 'v$sqlstats', 'MENOR_MEJOR', 'CONTENEDOR', 1, 20.0, 0, 0, 0, 'A.8.6; C-066', $umbral(11, 'M-CON-01', 0, 1, 3, 5)),

        // MEMORIA
        $metrica('M-MEM-01', 'MEMORIA', 'Aciertos de caché de PGA', '%', 'v$pgastat', 'MAYOR_MEJOR', 'RAIZ', 2, 100.0, 0, 0, 1, 'A.8.6', $umbral(5, 'M-MEM-01', 5, 10, 20, 30)),
        $metrica('M-MEM-02', 'MEMORIA', 'PGA asignada sobre el objetivo', '%', 'v$pgastat', 'MENOR_MEJOR', 'RAIZ', 3, 150.0, 0, 0, 1, 'A.8.6', $umbral(6, 'M-MEM-02', 70, 90, 100, 120)),
        $metrica('M-MEM-03', 'MEMORIA', 'Memoria libre de la shared pool', '%', 'v$sgastat', 'MAYOR_MEJOR', 'RAIZ', 2, 100.0, 0, 0, 1, 'A.8.6', $umbral(7, 'M-MEM-03', 90, 95, 98, 99)),

        // PROCESOS
        $metrica('M-PRO-01', 'PROCESOS', 'Utilización de sesiones', '%', 'v$resource_limit', 'MENOR_MEJOR', 'RAIZ', 3, 100.0, 0, 0, 1, 'A.8.6', $umbral(1, 'M-PRO-01', 50, 70, 85, 95)),
        $metrica('M-PRO-02', 'PROCESOS', 'Utilización de procesos', '%', 'v$resource_limit', 'MENOR_MEJOR', 'RAIZ', 2, 100.0, 0, 0, 1, 'A.8.6', $umbral(2, 'M-PRO-02', 50, 70, 85, 95)),
        $metrica('M-PRO-03', 'PROCESOS', 'Procesos de fondo obligatorios presentes', null, 'v$bgprocess', 'ESTADO', 'RAIZ', null, 100.0, 0, 0, 1, 'A.8.16; A.5.30', null),
        $metrica('M-PRO-04', 'PROCESOS', 'Espera media de escritura de redo', 'ms', 'v$system_event', 'MENOR_MEJOR', 'RAIZ', 2, 20.0, 1, 0, 1, 'A.8.16', $umbral(3, 'M-PRO-04', 10, 14, 17, 19)),
        $metrica('M-PRO-05', 'PROCESOS', 'Reinicio de proceso de fondo detectado', null, 'v$bgprocess + v$process', 'ESTADO', 'RAIZ', null, 100.0, 0, 1, 1, 'A.8.16; A.5.30', null),
        $metrica('M-PRO-06', 'PROCESOS', 'Antigüedad del punto de control', '%', 'v$instance_recovery', 'MENOR_MEJOR', 'RAIZ', 2, 100.0, 0, 0, 1, 'A.8.6; A.5.30', $umbral(4, 'M-PRO-06', 50, 70, 85, 95)),
    ],

    // ── Precedencias declaradas ─────────────────────────────────────────────
    //
    // Materia prima de la agrupación en episodios (§6.1 del plan). Conocimiento
    // declarado y discutible, no correlación calculada. El motor de alertas las
    // consume en el paso 12; se siembran aquí para que el catálogo del arreglo
    // no quede a medias frente al de Oracle.

    'precedencias' => [
        ['origen' => 'M-PRO-02', 'consecuencia' => 'M-PRO-01'],
        ['origen' => 'M-ARC-01', 'consecuencia' => 'M-ARC-02'],
        ['origen' => 'M-MEM-03', 'consecuencia' => 'M-MEM-01'],
        ['origen' => 'M-MEM-01', 'consecuencia' => 'M-ARC-04'],
        ['origen' => 'M-ARC-05', 'consecuencia' => 'M-ARC-02'],
        ['origen' => 'M-CON-01', 'consecuencia' => 'M-MEM-02'],
    ],
];
