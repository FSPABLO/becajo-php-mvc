<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatos;
use App\Models\Entidades\Instancia;

/**
 * El recolector: ejecuta las doce consultas del catálogo y arma la muestra
 * cruda (contrato-muestra.md §3). No razona: no normaliza, no clasifica, no
 * compara con umbrales, no consulta la muestra anterior. Eso es trabajo del
 * motor de cálculo (MotorCalculo, F3), al otro lado del contrato de muestra.
 *
 * Recibe DOS conexiones ya abiertas —raíz y contenedor— porque quien decide
 * a qué host/servicio conectarse es bin/monitor.php, no este recolector: así
 * se puede probar contra cualquier BaseDatos, real o no.
 *
 * Cada lectura se protege por separado (protegido()): si una consulta falla,
 * las demás siguen. Un contexto de conexión caído (probarContexto()) marca
 * de una vez todas las métricas de ese ámbito como ERROR — es la diferencia
 * entre "una vista falló" y "doce fallaron porque la conexión no abrió", que
 * contrato-muestra.md §3 exige distinguir con el campo `contextos`.
 */
final class RecolectorMonitor
{
    private const METRICAS_RAIZ = [
        'M-PRO-01', 'M-PRO-02', 'M-PRO-03', 'M-PRO-04', 'M-PRO-05', 'M-PRO-06',
        'M-MEM-01', 'M-MEM-02', 'M-MEM-03', 'M-ARC-03',
    ];

    private const METRICAS_CONTENEDOR = ['M-ARC-01', 'M-ARC-02', 'M-ARC-04', 'M-ARC-05', 'M-CON-01'];

    public function __construct(
        private readonly BaseDatos $raiz,
        private readonly BaseDatos $contenedor,
        /** Umbral de tiempo por ejecución para M-CON-01 (catalogo-metricas-v0.md no fija un valor: es de calibración). */
        private readonly float $umbralConsultaMs = 1000.0,
    ) {
    }

    /** @return array<string, mixed> */
    public function recolectar(Instancia $instancia, string $tomadaEnUtc): array
    {
        $inicio = microtime(true);

        $contextoRaiz = $this->probarContexto($this->raiz);
        $contextoContenedor = $this->probarContexto($this->contenedor);

        $lecturas = [];
        $consultasObservadas = [];

        if ($contextoRaiz['estado'] === 'OK') {
            $lecturas += $this->protegido(['M-PRO-01', 'M-PRO-02'], fn (): array => $this->leerProcesos($this->raiz));
            $lecturas += $this->protegido(['M-PRO-03', 'M-PRO-05'], fn (): array => $this->leerProcesosFondo($this->raiz));
            $lecturas += $this->protegido(['M-PRO-04'], fn (): array => $this->leerEsperaRedo($this->raiz));
            $lecturas += $this->protegido(['M-PRO-06'], fn (): array => $this->leerPuntoControl($this->raiz));
            $lecturas += $this->protegido(['M-MEM-01', 'M-MEM-02'], fn (): array => $this->leerPga($this->raiz));
            $lecturas += $this->protegido(['M-MEM-03'], fn (): array => $this->leerSharedPool($this->raiz));
            $lecturas += $this->protegido(['M-ARC-03'], fn (): array => $this->leerRedo($this->raiz));
        } else {
            $this->marcarAusentes($lecturas, self::METRICAS_RAIZ, $contextoRaiz['mensaje'] ?? null);
        }

        if ($contextoContenedor['estado'] === 'OK') {
            $lecturas += $this->protegido(['M-ARC-01'], fn (): array => $this->leerTablespace($this->contenedor));
            $lecturas += $this->protegido(['M-ARC-02'], fn (): array => $this->leerDatafiles($this->contenedor));
            $lecturas += $this->protegido(['M-ARC-04'], fn (): array => $this->leerTemporal($this->contenedor));
            $lecturas += $this->protegido(['M-ARC-05'], fn (): array => $this->leerTablespaceSinAutoextend($this->contenedor));

            try {
                $resultado = $this->leerSentenciasCostosas($this->contenedor, $this->umbralConsultaMs);
                $lecturas += $resultado['lectura'];
                $consultasObservadas = $resultado['observadas'];
            } catch (\Throwable $error) {
                $lecturas['M-CON-01'] = ['estado' => 'ERROR', 'mensaje' => $error->getMessage()];
            }
        } else {
            $this->marcarAusentes($lecturas, self::METRICAS_CONTENEDOR, $contextoContenedor['mensaje'] ?? null);
        }

        return [
            'instancia'            => $instancia->clave,
            'tomada_en'            => $tomadaEnUtc,
            'duracion_ms'          => (int) round((microtime(true) - $inicio) * 1000),
            'contextos'            => ['RAIZ' => $contextoRaiz, 'CONTENEDOR' => $contextoContenedor],
            'lecturas'             => $lecturas,
            'consultas_observadas' => $consultasObservadas,
        ];
    }

    // ── Ámbito RAIZ ──────────────────────────────────────────────────────────

    /** M-PRO-01, M-PRO-02. */
    private function leerProcesos(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT resource_name, current_utilization, limit_value
              FROM v$resource_limit
             WHERE resource_name IN ('sessions', 'processes')
               AND limit_value > 0
            SQL);

        if ($filas === []) {
            // El riesgo verificado del S10.1 del plan: 0 filas sin error
            // dentro del PDB. Cero filas nunca es cero (invariante 3).
            $mensaje = 'v$resource_limit devolvió 0 filas: ¿conexión al PDB en vez de a CDB$ROOT?';

            return [
                'M-PRO-01' => ['estado' => 'VACIA', 'mensaje' => $mensaje],
                'M-PRO-02' => ['estado' => 'VACIA', 'mensaje' => $mensaje],
            ];
        }

        $porRecurso = [];

        foreach ($filas as $fila) {
            $porRecurso[(string) $fila['resource_name']] = $fila;
        }

        $resultado = [];

        foreach (['sessions' => 'M-PRO-01', 'processes' => 'M-PRO-02'] as $recurso => $codigo) {
            if (!isset($porRecurso[$recurso])) {
                $resultado[$codigo] = ['estado' => 'VACIA', 'mensaje' => "v\$resource_limit no trajo la fila de {$recurso}"];

                continue;
            }

            $actual = (float) $porRecurso[$recurso]['current_utilization'];
            $limite = (float) $porRecurso[$recurso]['limit_value'];

            $resultado[$codigo] = [
                'estado' => 'OK',
                'valor'  => $limite > 0 ? round($actual / $limite * 100, 2) : 0.0,
                'partes' => ['actual' => (int) $actual, 'limite' => (int) $limite],
            ];
        }

        return $resultado;
    }

    /** M-PRO-03 (compuerta) y M-PRO-05 (identidad), en una sola pasada por las dos vistas. */
    private function leerProcesosFondo(BaseDatos $bd): array
    {
        $esperados = ['CKPT', 'DBW0', 'LGWR', 'PMON', 'SMON'];

        $filas = $bd->consultar(<<<'SQL'
            SELECT bg.name, p.spid
              FROM v$bgprocess bg
              JOIN v$process p ON p.addr = bg.paddr
             WHERE bg.paddr <> '00'
               AND bg.name IN ('PMON', 'SMON', 'DBW0', 'LGWR', 'CKPT')
            SQL);

        if ($filas === []) {
            $mensaje = 'v$bgprocess/v$process no devolvieron procesos de fondo';

            return [
                'M-PRO-03' => ['estado' => 'VACIA', 'mensaje' => $mensaje],
                'M-PRO-05' => ['estado' => 'VACIA', 'mensaje' => $mensaje],
            ];
        }

        $presentes = [];

        foreach ($filas as $fila) {
            $presentes[(string) $fila['name']] = (string) $fila['spid'];
        }

        $ausentes = array_values(array_diff($esperados, array_keys($presentes)));

        $resultado['M-PRO-03'] = [
            'estado'  => 'OK',
            'abierta' => $ausentes === [],
            'detalle' => ['esperados' => $esperados, 'ausentes' => $ausentes],
        ];

        // La identidad solo tiene sentido si los cinco están: si falta uno,
        // M-PRO-03 ya lo dice y no hay huella completa que comparar.
        if ($ausentes === []) {
            ksort($presentes);
            $partes = [];

            foreach ($presentes as $nombre => $spid) {
                $partes[] = "{$nombre}:{$spid}";
            }

            $resultado['M-PRO-05'] = ['estado' => 'OK', 'identidad' => implode(',', $partes)];
        } else {
            $resultado['M-PRO-05'] = ['estado' => 'ERROR', 'mensaje' => 'No se puede derivar identidad: falta algún proceso de fondo'];
        }

        return $resultado;
    }

    /** M-PRO-04 (tasa). */
    private function leerEsperaRedo(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT total_waits, time_waited_micro
              FROM v$system_event
             WHERE event = 'log file parallel write'
            SQL);

        if ($filas === []) {
            return ['M-PRO-04' => ['estado' => 'VACIA', 'mensaje' => "v\$system_event no devolvió 'log file parallel write'"]];
        }

        return ['M-PRO-04' => [
            'estado'     => 'OK',
            'acumulados' => [
                'esperas' => (int) $filas[0]['total_waits'],
                'micros'  => (int) $filas[0]['time_waited_micro'],
            ],
        ]];
    }

    /** M-PRO-06. */
    private function leerPuntoControl(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT target_mttr, estimated_mttr FROM v$instance_recovery
            SQL);

        if ($filas === []) {
            return ['M-PRO-06' => ['estado' => 'VACIA', 'mensaje' => 'v$instance_recovery no devolvió filas']];
        }

        $objetivo = (float) $filas[0]['target_mttr'];
        $estimado = (float) $filas[0]['estimated_mttr'];

        // Rama de excepción documentada en la ficha: sin FAST_START_MTTR_TARGET
        // configurado, target_mttr = 0. Los umbrales en segundos de esa rama
        // todavía no están fijados (catalogo-metricas-v0.md, M-PRO-06), así que
        // se marca como error en vez de inventar un porcentaje sin sentido.
        if ($objetivo <= 0) {
            return ['M-PRO-06' => [
                'estado'  => 'ERROR',
                'mensaje' => 'target_mttr = 0 (FAST_START_MTTR_TARGET no configurado); rama en segundos sin umbrales todavía',
            ]];
        }

        return ['M-PRO-06' => [
            'estado' => 'OK',
            'valor'  => round($estimado / $objetivo * 100, 2),
            'partes' => ['estimado_seg' => $estimado, 'objetivo_seg' => $objetivo],
        ]];
    }

    /** M-MEM-01, M-MEM-02. */
    private function leerPga(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT name, value
              FROM v$pgastat
             WHERE con_id = 0
               AND name IN ('cache hit percentage', 'total PGA allocated', 'aggregate PGA target parameter')
            SQL);

        if ($filas === []) {
            $mensaje = 'v$pgastat no devolvió filas';

            return [
                'M-MEM-01' => ['estado' => 'VACIA', 'mensaje' => $mensaje],
                'M-MEM-02' => ['estado' => 'VACIA', 'mensaje' => $mensaje],
            ];
        }

        $porNombre = [];

        foreach ($filas as $fila) {
            $porNombre[(string) $fila['name']] = (float) $fila['value'];
        }

        $resultado = [];

        $resultado['M-MEM-01'] = isset($porNombre['cache hit percentage'])
            ? ['estado' => 'OK', 'valor' => $porNombre['cache hit percentage'],
               'partes' => ['aciertos_pct' => $porNombre['cache hit percentage']]]
            : ['estado' => 'VACIA', 'mensaje' => "'cache hit percentage' no vino en v\$pgastat"];

        // Trampa verificada por el catálogo: con con_id distinto de 0 el
        // objetivo vale 0 y esto sería una división por cero esperando turno.
        if (isset($porNombre['total PGA allocated'], $porNombre['aggregate PGA target parameter'])
            && $porNombre['aggregate PGA target parameter'] > 0) {
            $asignada = $porNombre['total PGA allocated'];
            $objetivo = $porNombre['aggregate PGA target parameter'];

            $resultado['M-MEM-02'] = [
                'estado' => 'OK',
                'valor'  => round($asignada / $objetivo * 100, 2),
                'partes' => ['asignada' => (int) $asignada, 'objetivo' => (int) $objetivo],
            ];
        } else {
            $resultado['M-MEM-02'] = ['estado' => 'VACIA', 'mensaje' => 'objetivo de PGA no disponible'];
        }

        return $resultado;
    }

    /** M-MEM-03. */
    private function leerSharedPool(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT name, bytes FROM v$sgastat WHERE pool = 'shared pool'
            SQL);

        if ($filas === []) {
            return ['M-MEM-03' => ['estado' => 'VACIA', 'mensaje' => "v\$sgastat no devolvió la shared pool"]];
        }

        $libre = 0.0;
        $total = 0.0;

        foreach ($filas as $fila) {
            $bytes = (float) $fila['bytes'];
            $total += $bytes;

            if ($fila['name'] === 'free memory') {
                $libre = $bytes;
            }
        }

        if ($total <= 0) {
            return ['M-MEM-03' => ['estado' => 'VACIA', 'mensaje' => 'shared pool con total de bytes en 0']];
        }

        return ['M-MEM-03' => [
            'estado' => 'OK',
            'valor'  => round($libre / $total * 100, 2),
            'partes' => ['libre' => (int) $libre, 'total' => (int) $total],
        ]];
    }

    /** M-ARC-03. */
    private function leerRedo(BaseDatos $bd): array
    {
        $grupos = $bd->consultar(<<<'SQL'
            SELECT group#, status, members FROM v$log
            SQL);

        if ($grupos === []) {
            return ['M-ARC-03' => ['estado' => 'VACIA', 'mensaje' => 'v$log no devolvió grupos de redo']];
        }

        $miembros = $bd->consultar(<<<'SQL'
            SELECT NVL(status, 'EN LINEA') AS status, COUNT(*) AS n FROM v$logfile GROUP BY status
            SQL);

        $gruposInvalidos = [];

        foreach ($grupos as $grupo) {
            if ($grupo['status'] === 'INVALID') {
                $gruposInvalidos[] = (string) $grupo['group#'];
            }
        }

        $totalMiembros = 0;
        $miembrosInvalidos = 0;

        foreach ($miembros as $miembro) {
            $n = (int) $miembro['n'];
            $totalMiembros += $n;

            if (in_array($miembro['status'], ['INVALID', 'DELETED'], true)) {
                $miembrosInvalidos += $n;
            }
        }

        return ['M-ARC-03' => [
            'estado'  => 'OK',
            'abierta' => $gruposInvalidos === [] && $miembrosInvalidos === 0,
            'detalle' => ['grupos' => count($grupos), 'miembros' => $totalMiembros, 'invalidos' => $gruposInvalidos],
        ]];
    }

    // ── Ámbito CONTENEDOR ────────────────────────────────────────────────────

    /** M-ARC-01. */
    private function leerTablespace(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT tablespace_name, used_percent
              FROM dba_tablespace_usage_metrics
             ORDER BY used_percent DESC
             FETCH FIRST 1 ROWS ONLY
            SQL);

        if ($filas === []) {
            return ['M-ARC-01' => ['estado' => 'VACIA', 'mensaje' => 'dba_tablespace_usage_metrics no devolvió filas']];
        }

        return ['M-ARC-01' => [
            'estado' => 'OK',
            'valor'  => round((float) $filas[0]['used_percent'], 2),
            'partes' => ['tablespace' => (string) $filas[0]['tablespace_name']],
        ]];
    }

    /** M-ARC-02. */
    private function leerDatafiles(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT status, COUNT(*) AS n FROM v$datafile GROUP BY status
            SQL);

        if ($filas === []) {
            return ['M-ARC-02' => ['estado' => 'VACIA', 'mensaje' => 'v$datafile no devolvió filas']];
        }

        $total = 0;
        $validos = 0;
        $invalidos = [];

        foreach ($filas as $fila) {
            $n = (int) $fila['n'];
            $total += $n;
            $estado = (string) $fila['status'];

            // Trampa verificada: SYSTEM no es ONLINE y es igual de válido.
            if (in_array($estado, ['ONLINE', 'SYSTEM'], true)) {
                $validos += $n;
            } else {
                $invalidos[] = $estado;
            }
        }

        return ['M-ARC-02' => [
            'estado'  => 'OK',
            'abierta' => $invalidos === [],
            'detalle' => ['total' => $total, 'validos' => $validos, 'invalidos' => $invalidos],
        ]];
    }

    /** M-ARC-04. */
    private function leerTemporal(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT tablespace_name,
                   ROUND((tablespace_size - free_space) / tablespace_size * 100, 2) AS usado_pct
              FROM dba_temp_free_space
             ORDER BY usado_pct DESC
             FETCH FIRST 1 ROWS ONLY
            SQL);

        if ($filas === []) {
            return ['M-ARC-04' => ['estado' => 'VACIA', 'mensaje' => 'dba_temp_free_space no devolvió filas']];
        }

        return ['M-ARC-04' => [
            'estado' => 'OK',
            'valor'  => (float) $filas[0]['usado_pct'],
            'partes' => ['tablespace' => (string) $filas[0]['tablespace_name']],
        ]];
    }

    /** M-ARC-05. */
    private function leerTablespaceSinAutoextend(BaseDatos $bd): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT m.tablespace_name, m.used_percent
              FROM dba_tablespace_usage_metrics m
             WHERE NOT EXISTS (
                     SELECT 1
                       FROM dba_data_files df
                      WHERE df.tablespace_name = m.tablespace_name
                        AND df.autoextensible = 'YES'
                   )
             ORDER BY m.used_percent DESC
             FETCH FIRST 1 ROWS ONLY
            SQL);

        // Cero filas aquí es legítimo (catalogo-metricas-v0.md, M-ARC-05): la
        // instancia puede no tener ningún tablespace sin crecimiento
        // automático. NO_APLICA, no VACIA — nunca iba a dar datos, distinto
        // de "debía darlos y no pudo" (contrato-muestra.md §3.2).
        if ($filas === []) {
            return ['M-ARC-05' => ['estado' => 'NO_APLICA']];
        }

        return ['M-ARC-05' => [
            'estado' => 'OK',
            'valor'  => round((float) $filas[0]['used_percent'], 2),
            'partes' => ['tablespace' => (string) $filas[0]['tablespace_name']],
        ]];
    }

    /**
     * M-CON-01 + la evidencia completa para consulta_observada (§3.1 del
     * plan): una sola consulta alimenta las dos, tal como exige el catálogo.
     *
     * @return array{lectura: array<string, mixed>, observadas: list<array<string, mixed>>}
     */
    private function leerSentenciasCostosas(BaseDatos $bd, float $umbralMs): array
    {
        $filas = $bd->consultar(<<<'SQL'
            SELECT sql_id, plan_hash_value, executions,
                   ROUND(cpu_time / executions / 1000, 2) AS cpu_ms_exec,
                   ROUND(elapsed_time / executions / 1000, 2) AS elapsed_ms_exec,
                   ROUND(buffer_gets / executions, 0) AS lecturas_logicas_exec
              FROM v$sqlstats
             WHERE executions > 0
             ORDER BY elapsed_time DESC
             FETCH FIRST 20 ROWS ONLY
            SQL);

        if ($filas === []) {
            return [
                'lectura'    => ['M-CON-01' => ['estado' => 'VACIA', 'mensaje' => 'v$sqlstats no devolvió sentencias']],
                'observadas' => [],
            ];
        }

        $sobreUmbral = 0;
        $observadas = [];

        foreach ($filas as $fila) {
            $transcurrido = (float) $fila['elapsed_ms_exec'];

            if ($transcurrido > $umbralMs) {
                $sobreUmbral++;
            }

            $observadas[] = [
                'sql_id'                         => (string) $fila['sql_id'],
                'plan_hash_value'                => (int) $fila['plan_hash_value'],
                'ejecuciones'                     => (int) $fila['executions'],
                'cpu_ms_por_ejecucion'           => (float) $fila['cpu_ms_exec'],
                'transcurrido_ms_por_ejecucion'  => $transcurrido,
                'lecturas_logicas_por_ejecucion' => (int) $fila['lecturas_logicas_exec'],
            ];
        }

        return [
            'lectura'    => ['M-CON-01' => ['estado' => 'OK', 'valor' => $sobreUmbral]],
            'observadas' => $observadas,
        ];
    }

    // ── Interno ──────────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function probarContexto(BaseDatos $bd): array
    {
        $inicio = microtime(true);

        try {
            $bd->consultar('SELECT 1 AS uno FROM dual');

            return ['estado' => 'OK', 'duracion_ms' => (int) round((microtime(true) - $inicio) * 1000)];
        } catch (\Throwable $error) {
            return [
                'estado'      => 'ERROR',
                'duracion_ms' => (int) round((microtime(true) - $inicio) * 1000),
                'mensaje'     => $error->getMessage(),
            ];
        }
    }

    /**
     * Ejecuta una lectura y, si falla, marca sus propios códigos como ERROR
     * en vez de dejar que la excepción tumbe el resto de la muestra — cada
     * métrica se protege por separado (contrato-muestra.md, principio 4).
     *
     * @param list<string> $codigos
     * @return array<string, mixed>
     */
    private function protegido(array $codigos, \Closure $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable $error) {
            $resultado = [];

            foreach ($codigos as $codigo) {
                $resultado[$codigo] = ['estado' => 'ERROR', 'mensaje' => $error->getMessage()];
            }

            return $resultado;
        }
    }

    /**
     * @param array<string, mixed> $lecturas
     * @param list<string> $codigos
     */
    private function marcarAusentes(array &$lecturas, array $codigos, ?string $mensaje): void
    {
        foreach ($codigos as $codigo) {
            $lecturas[$codigo] = [
                'estado'  => 'ERROR',
                'mensaje' => $mensaje ?? 'Contexto de conexión no disponible',
            ];
        }
    }
}
