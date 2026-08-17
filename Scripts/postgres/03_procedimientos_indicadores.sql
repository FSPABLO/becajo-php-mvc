-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Procedimientos almacenados de indicadores — PUERTO A POSTGRESQL de
-- Scripts/03_procedimientos_indicadores.sql
--
-- Oracle agrupa esto en un PACKAGE (pkg_indicadores) con procedimientos que
-- devuelven SYS_REFCURSOR. Postgres no tiene paquetes ni el concepto de
-- refcursor de salida a la manera de Oracle, así que la traducción no es
-- 1-a-1 en la forma, aunque el CONTENIDO de cada consulta es idéntico:
--
--   - Los procedimientos que Oracle abre como cursor (SYS_REFCURSOR) se
--     vuelven FUNCTIONS de Postgres que "RETURNS TABLE (...)". Se llaman con
--     un SELECT normal:  SELECT * FROM sp_resumen_auditoria(7);
--     Esto es MÁS simple del lado de PHP: no hace falta la danza de dos
--     ejecuciones (abrir el bloque, después leer el cursor) que exige oci8.
--
--   - Los procedimientos que solo escriben (calcular_riesgo_auditoria,
--     sp_crear_remediacion, sp_programar_reauditoria,
--     sp_actualizar_estado_remediacion) se vuelven PROCEDURES de Postgres
--     (CREATE PROCEDURE, soportado desde Postgres 11), que sí pueden hacer
--     COMMIT internamente igual que en PL/SQL. Se llaman con CALL:
--     CALL calcular_riesgo_auditoria(7);
--
--   - No existe un "paquete": cada rutina es un objeto de nivel superior en
--     el esquema. Se agrupan aquí por comentarios, no por sintaxis.
--
-- El resto es traducción mecánica de PL/SQL a plpgsql: IS -> AS $$ ... $$
-- LANGUAGE plpgsql, DECLARE va antes del BEGIN igual, %TYPE no se usa aquí,
-- SYSTIMESTAMP -> now(), SYSDATE -> CURRENT_DATE, TRUNC(SYSDATE) ->
-- CURRENT_DATE directo (ya no tiene componente hora), NVL -> COALESCE,
-- DBMS_OUTPUT.PUT_LINE -> RAISE NOTICE.
--
-- Los COMMIT que Oracle traía dentro de cada procedimiento (calcular_riesgo_
-- auditoria, sp_crear_remediacion, sp_programar_reauditoria,
-- sp_actualizar_estado_remediacion) SE QUITARON aquí a propósito: Postgres
-- prohíbe ejecutar COMMIT dentro de un procedimiento cuando el CALL viene
-- anidado desde un bloque DO, una función, u otro procedimiento con una
-- excepción activa (justo el caso de Scripts/postgres/05, que encadena
-- varios CALL dentro de un mismo DO). Sin ese COMMIT explícito no pasa
-- nada: BaseDatosPostgres::procedimiento() ejecuta cada CALL como una
-- sentencia normal de PDO en modo autocommit, así que el cambio queda
-- confirmado igual, sin que el procedimiento tenga que decirlo.
--
-- Ejecutar después de 01_esquema.sql y 02_datos_semilla.sql.
-- ============================================================================

-- ── fn_zona ──────────────────────────────────────────────────────────────
-- Mismos cortes que la función homónima de Oracle: <0.5 rojo, <0.8 amarillo,
-- si no verde. Vive fuera de los procedimientos porque varios la usan.
CREATE OR REPLACE FUNCTION fn_zona(p_promedio NUMERIC)
RETURNS VARCHAR AS $$
BEGIN
    IF p_promedio IS NULL THEN
        RETURN NULL;
    ELSIF p_promedio < 0.5 THEN
        RETURN 'ROJO';
    ELSIF p_promedio < 0.8 THEN
        RETURN 'AMARILLO';
    ELSE
        RETURN 'VERDE';
    END IF;
END;
$$ LANGUAGE plpgsql IMMUTABLE;


-- ── calcular_riesgo_auditoria ────────────────────────────────────────────
-- Ponderado por CONTROL.peso (ALTA=3, MEDIA=2, BAJA=1), igual que en Oracle.
CREATE OR REPLACE PROCEDURE calcular_riesgo_auditoria(p_id_auditoria INTEGER)
LANGUAGE plpgsql AS $$
DECLARE
    v_promedio_c NUMERIC(4,3);
    v_promedio_i NUMERIC(4,3);
    v_promedio_d NUMERIC(4,3);
    v_indice     NUMERIC(5,2);
BEGIN
    DELETE FROM resultado_riesgo WHERE id_auditoria = p_id_auditoria;

    SELECT ROUND(
             SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END)
             / NULLIF(SUM(CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END), 0)
             / 5
           , 3)
      INTO v_promedio_c
      FROM evaluacion_control ec
      JOIN control c ON c.codigo = ec.codigo_control
     WHERE ec.id_auditoria = p_id_auditoria
       AND ec.afecta_confidencialidad = 1
       AND ec.madurez IS NOT NULL;

    IF v_promedio_c IS NOT NULL THEN
        INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
        VALUES (p_id_auditoria, 'CONFIDENCIALIDAD', v_promedio_c, fn_zona(v_promedio_c));
    END IF;

    SELECT ROUND(
             SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END)
             / NULLIF(SUM(CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END), 0)
             / 5
           , 3)
      INTO v_promedio_i
      FROM evaluacion_control ec
      JOIN control c ON c.codigo = ec.codigo_control
     WHERE ec.id_auditoria = p_id_auditoria
       AND ec.afecta_integridad = 1
       AND ec.madurez IS NOT NULL;

    IF v_promedio_i IS NOT NULL THEN
        INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
        VALUES (p_id_auditoria, 'INTEGRIDAD', v_promedio_i, fn_zona(v_promedio_i));
    END IF;

    SELECT ROUND(
             SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END)
             / NULLIF(SUM(CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END), 0)
             / 5
           , 3)
      INTO v_promedio_d
      FROM evaluacion_control ec
      JOIN control c ON c.codigo = ec.codigo_control
     WHERE ec.id_auditoria = p_id_auditoria
       AND ec.afecta_disponibilidad = 1
       AND ec.madurez IS NOT NULL;

    IF v_promedio_d IS NOT NULL THEN
        INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
        VALUES (p_id_auditoria, 'DISPONIBILIDAD', v_promedio_d, fn_zona(v_promedio_d));
    END IF;

    SELECT ROUND(AVG(promedio_madurez), 2)
      INTO v_indice
      FROM resultado_riesgo
     WHERE id_auditoria = p_id_auditoria;

    UPDATE auditoria
       SET indice_general_riesgo = v_indice
     WHERE id_auditoria = p_id_auditoria;
END;
$$;


-- ── sp_resumen_auditoria ─────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION sp_resumen_auditoria(p_id_auditoria INTEGER)
RETURNS TABLE (
    id_auditoria          INTEGER,
    estado                VARCHAR,
    indice_general_riesgo NUMERIC,
    controles_si          BIGINT,
    controles_no          BIGINT,
    controles_na          BIGINT,
    cumplimiento          NUMERIC,
    madurez_promedio      NUMERIC
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT
            a.id_auditoria,
            a.estado,
            a.indice_general_riesgo,
            COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END),
            COUNT(CASE WHEN ec.estado = 'NO' THEN 1 END),
            COUNT(CASE WHEN ec.estado = 'NA' THEN 1 END),
            ROUND(
                COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END)::NUMERIC
                / NULLIF(COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END), 0)
            , 4),
            ROUND(
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END)
                / NULLIF(SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
                    END
                ), 0)
            , 2)
        FROM auditoria a
        LEFT JOIN evaluacion_control ec ON ec.id_auditoria = a.id_auditoria
        LEFT JOIN control c ON c.codigo = ec.codigo_control
        WHERE a.id_auditoria = p_id_auditoria
        GROUP BY a.id_auditoria, a.estado, a.indice_general_riesgo;
END;
$$;


-- ── sp_cumplimiento_dominio ──────────────────────────────────────────────
CREATE OR REPLACE FUNCTION sp_cumplimiento_dominio(p_id_auditoria INTEGER)
RETURNS TABLE (
    clave_dominio     VARCHAR,
    nombre_dominio    VARCHAR,
    controles_si      BIGINT,
    controles_no      BIGINT,
    controles_na      BIGINT,
    cumplimiento      NUMERIC,
    madurez_promedio  NUMERIC
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT
            d.clave,
            d.nombre,
            COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END),
            COUNT(CASE WHEN ec.estado = 'NO' THEN 1 END),
            COUNT(CASE WHEN ec.estado = 'NA' THEN 1 END),
            ROUND(
                COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END)::NUMERIC
                / NULLIF(COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END), 0)
            , 4),
            ROUND(
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END)
                / NULLIF(SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
                    END
                ), 0)
            , 2)
        FROM dominio d
        JOIN proceso p ON p.clave_dominio = d.clave
        JOIN control c ON c.numero_proceso = p.numero
        JOIN evaluacion_control ec
            ON ec.codigo_control = c.codigo AND ec.id_auditoria = p_id_auditoria
        GROUP BY d.clave, d.nombre
        ORDER BY d.clave;
END;
$$;


-- ── sp_menor_madurez ─────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION sp_menor_madurez(p_id_auditoria INTEGER, p_top_n INTEGER DEFAULT 5)
RETURNS TABLE (
    codigo_control VARCHAR,
    enunciado      TEXT,
    dominio        VARCHAR,
    estado         VARCHAR,
    madurez        SMALLINT,
    hallazgo       TEXT,
    posicion       BIGINT
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT sub.codigo_control, sub.enunciado, sub.dominio, sub.estado, sub.madurez, sub.hallazgo, sub.posicion
        FROM (
            SELECT
                ec.codigo_control,
                c.enunciado,
                d.nombre AS dominio,
                ec.estado,
                ec.madurez,
                ec.hallazgo,
                RANK() OVER (ORDER BY ec.madurez ASC) AS posicion
            FROM evaluacion_control ec
            JOIN control c ON c.codigo = ec.codigo_control
            JOIN proceso p ON p.numero = c.numero_proceso
            JOIN dominio d ON d.clave = p.clave_dominio
            WHERE ec.id_auditoria = p_id_auditoria
              AND ec.madurez IS NOT NULL
        ) sub
        WHERE sub.posicion <= p_top_n
        ORDER BY sub.posicion;
END;
$$;


-- ── sp_mayor_riesgo ──────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION sp_mayor_riesgo(p_id_auditoria INTEGER, p_top_n INTEGER DEFAULT 5)
RETURNS TABLE (
    codigo_control VARCHAR,
    enunciado      TEXT,
    dominio        VARCHAR,
    impacto        SMALLINT,
    probabilidad   SMALLINT,
    nivel_riesgo   NUMERIC,
    dimensiones    TEXT,
    posicion       BIGINT
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT sub.codigo_control, sub.enunciado, sub.dominio, sub.impacto, sub.probabilidad,
               sub.nivel_riesgo, sub.dimensiones, sub.posicion
        FROM (
            SELECT
                ec.codigo_control,
                c.enunciado,
                d.nombre AS dominio,
                ec.impacto,
                ec.probabilidad,
                ec.nivel_riesgo,
                CONCAT(
                    CASE WHEN ec.afecta_confidencialidad = 1 THEN 'C' END,
                    CASE WHEN ec.afecta_integridad       = 1 THEN 'I' END,
                    CASE WHEN ec.afecta_disponibilidad   = 1 THEN 'D' END
                ) AS dimensiones,
                RANK() OVER (ORDER BY ec.nivel_riesgo DESC) AS posicion
            FROM evaluacion_control ec
            JOIN control c ON c.codigo = ec.codigo_control
            JOIN proceso p ON p.numero = c.numero_proceso
            JOIN dominio d ON d.clave = p.clave_dominio
            WHERE ec.id_auditoria = p_id_auditoria
              AND ec.nivel_riesgo IS NOT NULL
        ) sub
        WHERE sub.posicion <= p_top_n
        ORDER BY sub.posicion;
END;
$$;

-- Nota sobre "dimensiones": Oracle concatena con || y NULL || 'x' = NULL,
-- que en la práctica se comporta como si esa pieza desapareciera porque las
-- otras piezas alrededor también son NULL o texto. En Postgres, || con NULL
-- SÍ produce NULL igual que en Oracle, pero CONCAT() ignora los NULL en vez
-- de propagarlos — que es el comportamiento que la vista espera (por
-- ejemplo, "CD" cuando no afecta integridad). Por eso aquí se usa CONCAT()
-- y no ||.


-- ── sp_exposicion_riesgo ─────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION sp_exposicion_riesgo(p_id_auditoria INTEGER)
RETURNS TABLE (
    tipo_riesgo       VARCHAR,
    promedio_madurez  NUMERIC,
    zona              VARCHAR,
    fecha_calculo     TIMESTAMP
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT r.tipo_riesgo, r.promedio_madurez, r.zona, r.fecha_calculo
          FROM resultado_riesgo r
         WHERE r.id_auditoria = p_id_auditoria
         ORDER BY r.tipo_riesgo;
END;
$$;


-- ── sp_historico_dominio (punto 18) ──────────────────────────────────────
CREATE OR REPLACE FUNCTION sp_historico_dominio(p_organizacion VARCHAR)
RETURNS TABLE (
    id_auditoria      INTEGER,
    fecha             VARCHAR,
    clave_dominio     VARCHAR,
    dominio           VARCHAR,
    orden_dominio     SMALLINT,
    madurez_promedio  NUMERIC
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT
            aud.id_auditoria,
            TO_CHAR(aud.fecha, 'YYYY-MM-DD')::VARCHAR,
            d.clave,
            d.nombre_corto,
            d.orden,
            ROUND(
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END)
                / NULLIF(SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
                    END
                ), 0)
            , 2)
        FROM auditoria aud
        JOIN usuario u ON u.id_usuario = aud.id_administrador_bd
        JOIN evaluacion_control ec ON ec.id_auditoria = aud.id_auditoria
        JOIN control c ON c.codigo = ec.codigo_control
        JOIN proceso p ON p.numero = c.numero_proceso
        JOIN dominio d ON d.clave = p.clave_dominio
        WHERE u.organizacion = p_organizacion
          AND ec.madurez IS NOT NULL
        GROUP BY aud.id_auditoria, aud.fecha, d.clave, d.nombre_corto, d.orden
        ORDER BY aud.fecha, d.orden, d.clave;
END;
$$;


-- ── sp_evolucion_auditor ─────────────────────────────────────────────────
-- p_organizacion en NULL trae la cartera completa del auditor.
CREATE OR REPLACE FUNCTION sp_evolucion_auditor(p_id_auditor INTEGER, p_organizacion VARCHAR)
RETURNS TABLE (
    mes               VARCHAR,
    auditorias        BIGINT,
    cumplimiento      NUMERIC,
    cobertura         NUMERIC,
    madurez_promedio  NUMERIC
) LANGUAGE plpgsql AS $$
DECLARE
    v_controles NUMERIC;
BEGIN
    SELECT COUNT(*) INTO v_controles FROM control;

    RETURN QUERY
        WITH por_auditoria AS (
            SELECT
                aud.id_auditoria,
                TO_CHAR(aud.fecha, 'YYYY-MM')::VARCHAR AS mes,
                COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END)          AS si,
                COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END) AS si_no,
                COUNT(ec.estado)                                      AS respondidos,
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END) AS madurez_pond,
                SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
                    END
                ) AS peso_total
            FROM auditoria aud
            JOIN usuario adm ON adm.id_usuario = aud.id_administrador_bd
            LEFT JOIN evaluacion_control ec ON ec.id_auditoria = aud.id_auditoria
            LEFT JOIN control c ON c.codigo = ec.codigo_control
            WHERE aud.id_auditor = p_id_auditor
              AND (p_organizacion IS NULL OR adm.organizacion = p_organizacion)
            GROUP BY aud.id_auditoria, TO_CHAR(aud.fecha, 'YYYY-MM')
        )
        SELECT
            pa.mes,
            COUNT(*),
            ROUND(SUM(pa.si)::NUMERIC / NULLIF(SUM(pa.si_no), 0), 4),
            ROUND(SUM(pa.respondidos)::NUMERIC / NULLIF(COUNT(*) * v_controles, 0), 4),
            ROUND(SUM(pa.madurez_pond) / NULLIF(SUM(pa.peso_total), 0), 2)
        FROM por_auditoria pa
        GROUP BY pa.mes
        ORDER BY pa.mes;
END;
$$;


-- ── Punto 19 — remediación y re-auditoría ────────────────────────────────

CREATE OR REPLACE PROCEDURE sp_crear_remediacion(
    p_id_evaluacion_control INTEGER,
    p_fecha_limite          DATE,
    p_responsable           VARCHAR DEFAULT NULL
) LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO remediacion (id_evaluacion_control, fecha_limite, responsable, estado)
    VALUES (p_id_evaluacion_control, p_fecha_limite, p_responsable, 'PENDIENTE');
END;
$$;


CREATE OR REPLACE FUNCTION sp_remediaciones_auditoria(p_id_auditoria INTEGER)
RETURNS TABLE (
    id_remediacion            INTEGER,
    codigo_control            VARCHAR,
    enunciado                 TEXT,
    fecha_limite              DATE,
    estado                    VARCHAR,
    responsable               VARCHAR,
    id_auditoria_reauditoria  INTEGER
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT
            r.id_remediacion,
            ec.codigo_control,
            c.enunciado,
            r.fecha_limite,
            CASE
                WHEN r.estado IN ('PENDIENTE', 'EN_PROCESO') AND r.fecha_limite < CURRENT_DATE
                    THEN 'VENCIDO'
                ELSE r.estado
            END::VARCHAR,
            r.responsable,
            r.id_auditoria_reauditoria
        FROM remediacion r
        JOIN evaluacion_control ec ON ec.id_evaluacion_control = r.id_evaluacion_control
        JOIN control c ON c.codigo = ec.codigo_control
        WHERE ec.id_auditoria = p_id_auditoria
        ORDER BY r.fecha_limite;
END;
$$;


CREATE OR REPLACE FUNCTION sp_remediaciones_vencidas()
RETURNS TABLE (
    id_remediacion  INTEGER,
    id_auditoria    INTEGER,
    organizacion    VARCHAR,
    codigo_control  VARCHAR,
    enunciado       TEXT,
    fecha_limite    DATE,
    responsable     VARCHAR
) LANGUAGE plpgsql AS $$
BEGIN
    RETURN QUERY
        SELECT
            r.id_remediacion,
            aud.id_auditoria,
            u.organizacion,
            ec.codigo_control,
            c.enunciado,
            r.fecha_limite,
            r.responsable
        FROM remediacion r
        JOIN evaluacion_control ec ON ec.id_evaluacion_control = r.id_evaluacion_control
        JOIN control c ON c.codigo = ec.codigo_control
        JOIN auditoria aud ON aud.id_auditoria = ec.id_auditoria
        JOIN usuario u ON u.id_usuario = aud.id_administrador_bd
        WHERE r.estado IN ('PENDIENTE', 'EN_PROCESO')
          AND r.fecha_limite < CURRENT_DATE
        ORDER BY r.fecha_limite;
END;
$$;


CREATE OR REPLACE PROCEDURE sp_programar_reauditoria(
    p_id_remediacion           INTEGER,
    p_id_auditoria_reauditoria INTEGER
) LANGUAGE plpgsql AS $$
BEGIN
    UPDATE remediacion
       SET id_auditoria_reauditoria = p_id_auditoria_reauditoria,
           estado = 'EN_PROCESO'
     WHERE id_remediacion = p_id_remediacion;
END;
$$;


CREATE OR REPLACE PROCEDURE sp_actualizar_estado_remediacion(
    p_id_remediacion INTEGER,
    p_estado         VARCHAR
) LANGUAGE plpgsql AS $$
BEGIN
    UPDATE remediacion
       SET estado = p_estado
     WHERE id_remediacion = p_id_remediacion;
END;
$$;
