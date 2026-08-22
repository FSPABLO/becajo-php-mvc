<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatos;
use App\Models\Contratos\RepositorioMonitor;
use App\Models\Contratos\RepositorioMonitorEscritura;
use App\Models\Entidades\Alerta;
use App\Models\Entidades\Episodio;
use App\Models\Entidades\Instancia;
use App\Models\Entidades\Medicion;
use App\Models\Entidades\Metrica;
use App\Models\Entidades\Muestra;

/**
 * Monitor de salud sobre Oracle: las dos caras, lectura y escritura.
 *
 * Implementa RepositorioMonitor (lo usan el sitio y el motor de cálculo) y
 * RepositorioMonitorEscritura (solo bin/monitor.php) — misma clase, dos
 * contratos, igual que RepositorioInstrumentoOracle con RepositorioCatalogo.
 *
 * Convenciones que se repiten en todo el archivo:
 *
 * - Los instantes viajan como texto ISO 8601 en UTC ('2026-08-19T14:35:02',
 *   con o sin el sufijo '+00:00' — sinZonaHoraria() lo recorta si viene). La
 *   columna de Oracle es TIMESTAMP sin zona: todo el sistema opera en UTC
 *   (A.8.17 del plan), así que no hace falta guardar el offset.
 *
 * - Las series y la retención salen de pkg_monitor, nunca de un DELETE o una
 *   agregación sueltos en PHP — misma regla que pkg_indicadores en la parte 1.
 *
 * - guardarMuestra(), agruparEnEpisodio() y versionarUmbral() son las únicas
 *   escrituras de más de una sentencia, y las tres corren dentro de una
 *   transacción (BaseDatos::iniciarTransaccion()): el contrato exige que
 *   guardarMuestra() sea todo o nada, y las otras dos tocan más de una tabla
 *   por la misma razón.
 */
final class RepositorioMonitorOracle implements RepositorioMonitor, RepositorioMonitorEscritura
{
    private const FORMATO = 'YYYY-MM-DD"T"HH24:MI:SS';

    /**
     * Cabecera de muestra con su índice ya resuelto (LEFT JOIN: una muestra
     * FALLIDA o PARCIAL bajo el piso de cobertura no tiene fila en `indice`).
     * La causa sale de una subconsulta con LISTAGG porque es una lista de
     * códigos, no un escalar — indice_causa es una tabla puente (B-9).
     */
    private const SELECCION_MUESTRA = <<<'SQL'
        SELECT mu.id_muestra, mu.clave_instancia,
               TO_CHAR(mu.tomada_en, 'YYYY-MM-DD"T"HH24:MI:SS') AS tomada_en,
               mu.duracion_ms, mu.resultado, mu.cobertura_pct, mu.mensaje,
               i.ip, i.im, i.ia, i.isbd_bruto, i.isbd, i.estado,
               (SELECT LISTAGG(ic.codigo_metrica, ',') WITHIN GROUP (ORDER BY ic.codigo_metrica)
                  FROM indice_causa ic WHERE ic.id_indice = i.id_indice) AS causa
          FROM muestra mu
          LEFT JOIN indice i ON i.id_muestra = mu.id_muestra
        SQL;

    public function __construct(private readonly BaseDatos $bd)
    {
    }

    // ── RepositorioMonitor — Instancias vigiladas ────────────────────────────

    /** @return list<Instancia> */
    public function instancias(): array
    {
        $filas = $this->bd->consultar(
            'SELECT clave, nombre, motor, host, puerto, servicio_raiz, servicio_contenedor,
                    entorno, criticidad, activa, demostrativa
               FROM instancia
              ORDER BY nombre',
        );

        return array_map(Instancia::desdeFila(...), $filas);
    }

    public function instancia(string $clave): ?Instancia
    {
        $fila = $this->bd->consultarUna(
            'SELECT clave, nombre, motor, host, puerto, servicio_raiz, servicio_contenedor,
                    entorno, criticidad, activa, demostrativa
               FROM instancia
              WHERE clave = :clave',
            ['clave' => $clave],
        );

        return $fila === null ? null : Instancia::desdeFila($fila);
    }

    // ── RepositorioMonitor — Estado actual ───────────────────────────────────

    /** @return list<Muestra> */
    public function ultimasMuestras(): array
    {
        $filas = $this->bd->consultar(
            self::SELECCION_MUESTRA . '
             WHERE mu.id_muestra IN (
                 SELECT id_muestra FROM (
                     SELECT id_muestra,
                            ROW_NUMBER() OVER (PARTITION BY clave_instancia ORDER BY tomada_en DESC) AS rn
                       FROM muestra
                 )
                 WHERE rn = 1
             )
             ORDER BY mu.clave_instancia',
        );

        return array_map(Muestra::desdeFila(...), $filas);
    }

    public function ultimaMuestra(string $clave): ?Muestra
    {
        $fila = $this->bd->consultarUna(
            self::SELECCION_MUESTRA . '
             WHERE mu.clave_instancia = :clave
             ORDER BY mu.tomada_en DESC
             FETCH FIRST 1 ROW ONLY',
            ['clave' => $clave],
        );

        return $fila === null ? null : Muestra::desdeFila($fila);
    }

    /** @return list<Medicion> */
    public function mediciones(int $idMuestra): array
    {
        $filas = $this->bd->consultar(
            'SELECT id_medicion, id_muestra, codigo_metrica, valor_crudo, valor_normalizado,
                    estado, id_umbral, valor_acumulado, huella
               FROM medicion
              WHERE id_muestra = :id_muestra
              ORDER BY codigo_metrica',
            ['id_muestra' => $idMuestra],
        );

        return array_map(Medicion::desdeFila(...), $filas);
    }

    // ── RepositorioMonitor — Series históricas ───────────────────────────────

    /** @return list<array<string, mixed>> */
    public function serieIndice(string $clave, string $desdeUtc, string $hastaUtc): array
    {
        return $this->bd->consultar(
            "SELECT TO_CHAR(mu.tomada_en, '" . self::FORMATO . "') AS tomada_en, i.isbd, i.estado
               FROM muestra mu
               JOIN indice i ON i.id_muestra = mu.id_muestra
              WHERE mu.clave_instancia = :clave
                AND mu.tomada_en BETWEEN TO_TIMESTAMP(:desde, '" . self::FORMATO . "')
                                     AND TO_TIMESTAMP(:hasta, '" . self::FORMATO . "')
              ORDER BY mu.tomada_en",
            [
                'clave' => $clave,
                'desde' => self::sinZonaHoraria($desdeUtc),
                'hasta' => self::sinZonaHoraria($hastaUtc),
            ],
        );
    }

    /**
     * Por encima de los 30 días de retención en crudo (§7.4 del plan), la
     * ventana pedida se sirve desde resumen_hora en vez de medicion. Se agrega
     * sobre valor_crudo, no el normalizado (misma razón que pkg_monitor: la
     * serie no cambia de unidad al cruzar esa frontera).
     *
     * @return list<array<string, mixed>>
     */
    public function serieMetrica(
        string $clave,
        string $codigoMetrica,
        string $desdeUtc,
        string $hastaUtc,
    ): array {
        return $this->bd->consultar(
            "SELECT TO_CHAR(instante, '" . self::FORMATO . "') AS instante, valor
               FROM (
                   SELECT mu.tomada_en AS instante, me.valor_crudo AS valor
                     FROM medicion me
                     JOIN muestra mu ON mu.id_muestra = me.id_muestra
                    WHERE mu.clave_instancia = :clave
                      AND me.codigo_metrica = :codigo
                      AND mu.tomada_en >= SYSTIMESTAMP - 30
                    UNION ALL
                    SELECT rh.hora AS instante, rh.promedio AS valor
                      FROM resumen_hora rh
                     WHERE rh.clave_instancia = :clave
                       AND rh.codigo_metrica = :codigo
                       AND rh.hora < SYSTIMESTAMP - 30
               )
              WHERE instante BETWEEN TO_TIMESTAMP(:desde, '" . self::FORMATO . "')
                                  AND TO_TIMESTAMP(:hasta, '" . self::FORMATO . "')
              ORDER BY instante",
            [
                'clave'  => $clave,
                'codigo' => $codigoMetrica,
                'desde'  => self::sinZonaHoraria($desdeUtc),
                'hasta'  => self::sinZonaHoraria($hastaUtc),
            ],
        );
    }

    // ── RepositorioMonitor — Alertas y episodios ─────────────────────────────

    /** @return list<Alerta> */
    public function alertasAbiertas(?string $clave = null): array
    {
        $filas = $this->bd->consultar(
            "SELECT id_alerta, clave_instancia, codigo_metrica, nivel_actual, nivel_maximo,
                    estado_atencion, valor, umbral, descripcion, ocurrencias,
                    TO_CHAR(vista_primera_vez, '" . self::FORMATO . "') AS vista_primera_vez,
                    TO_CHAR(vista_por_ultima_vez, '" . self::FORMATO . "') AS vista_por_ultima_vez,
                    responsable,
                    TO_CHAR(fecha_reconocimiento, '" . self::FORMATO . "') AS fecha_reconocimiento,
                    TO_CHAR(fecha_cierre, '" . self::FORMATO . "') AS fecha_cierre,
                    motivo_cierre, accion, id_episodio
               FROM alerta
              WHERE estado_atencion = 'ABIERTA'
                AND (:clave IS NULL OR clave_instancia = :clave)
              ORDER BY vista_por_ultima_vez DESC",
            ['clave' => $clave],
        );

        return array_map(Alerta::desdeFila(...), $filas);
    }

    /** @return list<Episodio> */
    public function episodios(string $clave, string $desdeUtc, string $hastaUtc): array
    {
        $filas = $this->bd->consultar(
            "SELECT id_episodio, clave_instancia, id_alerta_causa,
                    TO_CHAR(abierto_en, '" . self::FORMATO . "') AS abierto_en
               FROM episodio
              WHERE clave_instancia = :clave
                AND abierto_en BETWEEN TO_TIMESTAMP(:desde, '" . self::FORMATO . "')
                                   AND TO_TIMESTAMP(:hasta, '" . self::FORMATO . "')
              ORDER BY abierto_en DESC",
            [
                'clave' => $clave,
                'desde' => self::sinZonaHoraria($desdeUtc),
                'hasta' => self::sinZonaHoraria($hastaUtc),
            ],
        );

        return array_map(Episodio::desdeFila(...), $filas);
    }

    // ── RepositorioMonitor — Catálogo ─────────────────────────────────────────

    /** @return list<Metrica> */
    public function metricas(): array
    {
        $filas = $this->bd->consultar(
            "SELECT m.codigo, m.componente, m.nombre, m.unidad, m.vista_origen, m.sentido, m.ambito,
                    m.peso, m.u_max, m.acumulada, m.es_identidad, m.entra_isbd, m.ancla_iso,
                    u.id_umbral, u.codigo_metrica, u.clave_instancia,
                    u.u_opt, u.u_adv, u.u_deg, u.u_crit,
                    TO_CHAR(u.valido_desde, '" . self::FORMATO . "') AS valido_desde,
                    TO_CHAR(u.valido_hasta, '" . self::FORMATO . "') AS valido_hasta
               FROM metrica m
               LEFT JOIN umbral u
                      ON u.codigo_metrica = m.codigo
                     AND u.clave_instancia IS NULL
                     AND u.valido_hasta IS NULL
              ORDER BY m.componente, m.codigo",
        );

        return array_map(Metrica::desdeFila(...), $filas);
    }

    // ── RepositorioMonitor — Lo que necesita el motor de cálculo ─────────────

    /** @return array<string, float> */
    public function acumuladosAnteriores(string $clave): array
    {
        return $this->ultimosValoresDeContinuidad($clave, 'valor_acumulado');
    }

    /** @return array<string, string> */
    public function huellasAnteriores(string $clave): array
    {
        return array_map(
            static fn (mixed $v): string => (string) $v,
            $this->ultimosValoresDeContinuidad($clave, 'huella'),
        );
    }

    /** @return list<string> */
    public function estadosRecientes(string $clave, string $codigoMetrica, int $cuantas): array
    {
        $filas = $this->bd->consultar(
            'SELECT me.estado
               FROM medicion me
               JOIN muestra mu ON mu.id_muestra = me.id_muestra
              WHERE mu.clave_instancia = :clave
                AND me.codigo_metrica = :codigo
                AND me.estado IS NOT NULL
              ORDER BY mu.tomada_en DESC
              FETCH FIRST :cuantas ROWS ONLY',
            ['clave' => $clave, 'codigo' => $codigoMetrica, 'cuantas' => $cuantas],
        );

        return array_map(static fn (array $f): string => (string) $f['estado'], $filas);
    }

    /** @return list<float> */
    public function ventanaLineaBase(
        string $clave,
        string $codigoMetrica,
        int $dias,
        int $tramoHora,
    ): array {
        $filas = $this->bd->consultar(
            'SELECT me.valor_normalizado
               FROM medicion me
               JOIN muestra mu ON mu.id_muestra = me.id_muestra
              WHERE mu.clave_instancia = :clave
                AND me.codigo_metrica = :codigo
                AND me.valor_normalizado IS NOT NULL
                AND mu.tomada_en >= SYSTIMESTAMP - :dias
                AND EXTRACT(HOUR FROM mu.tomada_en) = :tramo_hora
              ORDER BY mu.tomada_en',
            ['clave' => $clave, 'codigo' => $codigoMetrica, 'dias' => $dias, 'tramo_hora' => $tramoHora],
        );

        return array_map(static fn (array $f): float => (float) $f['valor_normalizado'], $filas);
    }

    /**
     * acumuladosAnteriores() y huellasAnteriores() son la misma consulta con
     * otra columna: el valor de continuidad (valor_acumulado o huella) que
     * dejó la muestra más reciente de la instancia, por código de métrica.
     *
     * @return array<string, mixed>
     */
    private function ultimosValoresDeContinuidad(string $clave, string $columna): array
    {
        $filas = $this->bd->consultar(
            "SELECT me.codigo_metrica, me.{$columna} AS valor
               FROM medicion me
               JOIN muestra mu ON mu.id_muestra = me.id_muestra
              WHERE mu.clave_instancia = :clave
                AND me.{$columna} IS NOT NULL
                AND mu.tomada_en = (SELECT MAX(tomada_en) FROM muestra WHERE clave_instancia = :clave)",
            ['clave' => $clave],
        );

        $resultado = [];

        foreach ($filas as $fila) {
            $resultado[(string) $fila['codigo_metrica']] = $columna === 'huella'
                ? (string) $fila['valor']
                : (float) $fila['valor'];
        }

        return $resultado;
    }

    // ── RepositorioMonitorEscritura — Persistencia de la muestra ─────────────

    public function guardarMuestra(array $muestraEvaluada): int
    {
        $this->bd->iniciarTransaccion();

        try {
            $idMuestra = $this->bd->insertar(
                "INSERT INTO muestra (clave_instancia, tomada_en, duracion_ms, resultado, cobertura_pct, mensaje)
                 VALUES (:clave, TO_TIMESTAMP(:tomada_en, '" . self::FORMATO . "'), :duracion_ms, :resultado, :cobertura_pct, :mensaje)
                 RETURNING id_muestra INTO :id",
                [
                    'clave'         => $muestraEvaluada['instancia'],
                    'tomada_en'     => self::sinZonaHoraria((string) $muestraEvaluada['tomada_en']),
                    'duracion_ms'   => $muestraEvaluada['duracion_ms'] ?? null,
                    'resultado'     => $muestraEvaluada['resultado'],
                    'cobertura_pct' => $muestraEvaluada['cobertura_pct'] ?? null,
                    'mensaje'       => $muestraEvaluada['mensaje'] ?? null,
                ],
                'id',
                confirmar: false,
            );

            /** @var array<string, array<string, mixed>> $mediciones */
            $mediciones = $muestraEvaluada['mediciones'] ?? [];

            foreach ($mediciones as $codigo => $medicion) {
                $this->bd->ejecutar(
                    'INSERT INTO medicion
                            (id_muestra, codigo_metrica, valor_crudo, valor_normalizado, estado,
                             id_umbral, valor_acumulado, huella)
                     VALUES (:id_muestra, :codigo, :valor_crudo, :valor_normalizado, :estado,
                             :id_umbral, :valor_acumulado, :huella)',
                    [
                        'id_muestra'        => $idMuestra,
                        'codigo'            => $codigo,
                        'valor_crudo'       => $medicion['valor_crudo'] ?? null,
                        'valor_normalizado' => $medicion['valor_normalizado'] ?? null,
                        'estado'            => $medicion['estado'] ?? null,
                        'id_umbral'         => $medicion['umbral_id'] ?? null,
                        'valor_acumulado'   => $medicion['valor_acumulado'] ?? null,
                        'huella'            => $medicion['huella'] ?? null,
                    ],
                    confirmar: false,
                );
            }

            /** @var array<string, mixed>|null $indice */
            $indice = $muestraEvaluada['indice'] ?? null;

            if ($indice !== null) {
                /** @var array<string, array<string, mixed>> $componentes */
                $componentes = $muestraEvaluada['componentes'] ?? [];

                $idIndice = $this->bd->insertar(
                    'INSERT INTO indice (id_muestra, ip, im, ia, isbd_bruto, isbd, estado)
                     VALUES (:id_muestra, :ip, :im, :ia, :isbd_bruto, :isbd, :estado)
                     RETURNING id_indice INTO :id',
                    [
                        'id_muestra' => $idMuestra,
                        'ip'         => $componentes['PROCESOS']['publicado'] ?? null,
                        'im'         => $componentes['MEMORIA']['publicado'] ?? null,
                        'ia'         => $componentes['ARCHIVOS']['publicado'] ?? null,
                        'isbd_bruto' => $indice['isbd_bruto'] ?? null,
                        'isbd'       => $indice['isbd'] ?? null,
                        'estado'     => $indice['estado'] ?? null,
                    ],
                    'id',
                    confirmar: false,
                );

                foreach ($indice['causa'] ?? [] as $codigoCausa) {
                    $this->bd->ejecutar(
                        'INSERT INTO indice_causa (id_indice, codigo_metrica) VALUES (:id_indice, :codigo)',
                        ['id_indice' => $idIndice, 'codigo' => $codigoCausa],
                        confirmar: false,
                    );
                }
            }

            $this->bd->confirmarTransaccion();

            return $idMuestra;
        } catch (\Throwable $error) {
            $this->bd->revertirTransaccion();

            throw $error;
        }
    }

    // ── RepositorioMonitorEscritura — Ciclo de vida de las alertas ───────────

    public function registrarAlerta(
        string $clave,
        string $codigoMetrica,
        string $nivel,
        float $valor,
        float $umbral,
        string $vistaUtc,
    ): int {
        $vista = self::sinZonaHoraria($vistaUtc);

        $abierta = $this->bd->consultarUna(
            "SELECT id_alerta
               FROM alerta
              WHERE clave_instancia = :clave
                AND codigo_metrica = :codigo
                AND estado_atencion = 'ABIERTA'",
            ['clave' => $clave, 'codigo' => $codigoMetrica],
        );

        if ($abierta !== null) {
            $id = (int) $abierta['id_alerta'];

            // nivel_maximo solo sube: el rango numérico es interno a esta
            // sentencia y no viaja a ninguna parte, solo decide cuál de los
            // dos niveles es más severo.
            $this->bd->ejecutar(
                "UPDATE alerta
                    SET nivel_actual = :nivel,
                        nivel_maximo = CASE
                            WHEN (CASE nivel_maximo WHEN 'CRITICO' THEN 3 WHEN 'DEGRADADO' THEN 2 ELSE 1 END)
                               >= (CASE :nivel WHEN 'CRITICO' THEN 3 WHEN 'DEGRADADO' THEN 2 ELSE 1 END)
                            THEN nivel_maximo
                            ELSE :nivel
                        END,
                        valor = :valor,
                        umbral = :umbral,
                        ocurrencias = ocurrencias + 1,
                        vista_por_ultima_vez = TO_TIMESTAMP(:vista, '" . self::FORMATO . "')
                  WHERE id_alerta = :id",
                [
                    'nivel' => $nivel, 'valor' => $valor, 'umbral' => $umbral,
                    'vista' => $vista, 'id' => $id,
                ],
            );

            return $id;
        }

        return $this->bd->insertar(
            "INSERT INTO alerta
                    (clave_instancia, codigo_metrica, nivel_actual, nivel_maximo, valor, umbral,
                     vista_primera_vez, vista_por_ultima_vez)
             VALUES (:clave, :codigo, :nivel, :nivel, :valor, :umbral,
                     TO_TIMESTAMP(:vista, '" . self::FORMATO . "'), TO_TIMESTAMP(:vista, '" . self::FORMATO . "'))
             RETURNING id_alerta INTO :id",
            [
                'clave' => $clave, 'codigo' => $codigoMetrica, 'nivel' => $nivel,
                'valor' => $valor, 'umbral' => $umbral, 'vista' => $vista,
            ],
        );
    }

    public function cerrarAlerta(
        int $idAlerta,
        string $motivo,
        ?string $accion,
        ?int $idResponsable,
    ): void {
        $this->bd->ejecutar(
            "UPDATE alerta
                SET estado_atencion = 'CERRADA',
                    fecha_cierre = SYSTIMESTAMP,
                    motivo_cierre = :motivo,
                    accion = :accion,
                    responsable = :responsable
              WHERE id_alerta = :id",
            ['motivo' => $motivo, 'accion' => $accion, 'responsable' => $idResponsable, 'id' => $idAlerta],
        );
    }

    public function agruparEnEpisodio(array $idsAlerta, ?int $idAlertaCausa): int
    {
        $this->bd->iniciarTransaccion();

        try {
            $idEpisodio = $this->bd->insertar(
                'INSERT INTO episodio (clave_instancia, id_alerta_causa)
                 VALUES ((SELECT clave_instancia FROM alerta WHERE id_alerta = :primera), :causa)
                 RETURNING id_episodio INTO :id',
                ['primera' => $idsAlerta[0] ?? 0, 'causa' => $idAlertaCausa],
                'id',
                confirmar: false,
            );

            foreach ($idsAlerta as $idAlerta) {
                $this->bd->ejecutar(
                    'UPDATE alerta SET id_episodio = :episodio WHERE id_alerta = :id',
                    ['episodio' => $idEpisodio, 'id' => $idAlerta],
                    confirmar: false,
                );
            }

            $this->bd->confirmarTransaccion();

            return $idEpisodio;
        } catch (\Throwable $error) {
            $this->bd->revertirTransaccion();

            throw $error;
        }
    }

    // ── RepositorioMonitorEscritura — Umbrales versionados ───────────────────

    public function versionarUmbral(
        string $codigoMetrica,
        ?string $clave,
        float $uOpt,
        float $uAdv,
        float $uDeg,
        float $uCrit,
        string $desdeUtc,
    ): int {
        $desde = self::sinZonaHoraria($desdeUtc);

        $this->bd->iniciarTransaccion();

        try {
            // Cierra el juego vigente (si lo hay) — nunca UPDATE sobre los
            // cuatro números, solo sobre valido_hasta (§7.2 del plan).
            $this->bd->ejecutar(
                "UPDATE umbral
                    SET valido_hasta = TO_TIMESTAMP(:desde, '" . self::FORMATO . "')
                  WHERE codigo_metrica = :codigo
                    AND ((:clave IS NULL AND clave_instancia IS NULL) OR clave_instancia = :clave)
                    AND valido_hasta IS NULL",
                ['desde' => $desde, 'codigo' => $codigoMetrica, 'clave' => $clave],
                confirmar: false,
            );

            $idUmbral = $this->bd->insertar(
                "INSERT INTO umbral (codigo_metrica, clave_instancia, u_opt, u_adv, u_deg, u_crit, valido_desde)
                 VALUES (:codigo, :clave, :u_opt, :u_adv, :u_deg, :u_crit, TO_TIMESTAMP(:desde, '" . self::FORMATO . "'))
                 RETURNING id_umbral INTO :id",
                [
                    'codigo' => $codigoMetrica, 'clave' => $clave,
                    'u_opt' => $uOpt, 'u_adv' => $uAdv, 'u_deg' => $uDeg, 'u_crit' => $uCrit,
                    'desde' => $desde,
                ],
                'id',
                confirmar: false,
            );

            $this->bd->confirmarTransaccion();

            return $idUmbral;
        } catch (\Throwable $error) {
            $this->bd->revertirTransaccion();

            throw $error;
        }
    }

    // ── RepositorioMonitorEscritura — Mantenimiento (pkg_monitor) ────────────

    public function consolidarHora(string $hastaUtc): void
    {
        $this->bd->procedimiento(
            "BEGIN pkg_monitor.consolidar_hora(TO_TIMESTAMP(:hasta, '" . self::FORMATO . "')); END;",
            ['hasta' => self::sinZonaHoraria($hastaUtc)],
        );
    }

    public function purgar(string $hastaUtc): void
    {
        $this->bd->procedimiento(
            "BEGIN pkg_monitor.purgar(TO_TIMESTAMP(:hasta, '" . self::FORMATO . "')); END;",
            ['hasta' => self::sinZonaHoraria($hastaUtc)],
        );
    }

    // ── Interno ──────────────────────────────────────────────────────────────

    /**
     * Recorta el sufijo de huso horario ('+00:00' o 'Z') de un instante ISO.
     * La columna de Oracle es TIMESTAMP sin zona porque todo el sistema
     * trabaja en UTC (A.8.17 del plan): guardar el offset sería repetir un
     * dato que ya se sabe fijo.
     */
    private static function sinZonaHoraria(string $iso): string
    {
        return preg_replace('/(Z|[+-]\d{2}:?\d{2})$/', '', $iso) ?? $iso;
    }
}
