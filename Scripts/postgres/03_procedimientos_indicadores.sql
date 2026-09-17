-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Procedimientos de indicadores — PUERTO A POSTGRESQL de
-- Scripts/03_procedimientos_indicadores.sql (pkg_indicadores)
--
-- Postgres no tiene paquetes ni SYS_REFCURSOR. Cada PROCEDURE ... OUT
-- SYS_REFCURSOR de Oracle se traduce a una FUNCTION ... RETURNS TABLE(...)
-- de PL/pgSQL, que se consulta con un SELECT normal (SELECT * FROM
-- sp_resumen_auditoria(7)) — es la misma idea que BaseDatosPostgres.php ya
-- explica en su docblock: "un procedimiento que en Oracle abre un cursor
-- aquí es una FUNCTION que declara RETURNS TABLE(...)". Cada PROCEDURE que
-- solo hace INSERT/UPDATE se traduce a una FUNCTION RETURNS void.
--
-- Requisito del curso sigue cumplido: la aplicación llama estas funciones
-- (BaseDatosPostgres::consultar / ::ejecutar), nunca hace SELECT suelto
-- contra las tablas para un indicador.
--
-- CUIDADO CON LA DIVISIÓN ENTERA: en Oracle, NUMBER / NUMBER siempre da
-- resultado exacto. En Postgres, integer / integer TRUNCA. Cada CASE que en
-- Oracle devolvía 3/2/1 aquí devuelve 3.0/2.0/1.0 (numeric), para que la
-- división que sigue no trunque silenciosamente.
--
-- Ejecutar después de 01_esquema.sql y 02_datos_semilla.sql.
-- ============================================================================

-- fn_zona era privada del cuerpo del paquete en Oracle (solo la llamaban las
-- otras funciones del paquete, nunca SQL). Aquí es una función normal:
-- Postgres no tiene el problema PLS-00231 de Oracle (una función SQL sí
-- puede llamar a otra función definida por el usuario dentro de un INSERT).
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


-- ------------------------------------------------------------------
-- Promedio de una dimensión en una auditoría COBIT (capacidad por objetivo,
-- ponderada P=2/S=1 por la relación del proceso con la dimensión).
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_promedio_objetivos(p_id_auditoria INTEGER, p_dimension VARCHAR)
RETURNS NUMERIC AS $$
DECLARE
    v_promedio NUMERIC(4,3);
BEGIN
    SELECT ROUND(SUM(eo.capacidad * pp.peso) / NULLIF(SUM(pp.peso), 0) / 5, 3)
      INTO v_promedio
      FROM evaluacion_objetivo eo
      JOIN (SELECT numero,
                   CASE (CASE p_dimension
                            WHEN 'C' THEN relacion_confidencialidad
                            WHEN 'I' THEN relacion_integridad
                            ELSE relacion_disponibilidad
                        END)
                       WHEN 'P' THEN 2.0
                       WHEN 'S' THEN 1.0
                   END AS peso
              FROM proceso) pp ON pp.numero = eo.numero_proceso
     WHERE eo.id_auditoria = p_id_auditoria
       AND pp.peso IS NOT NULL;

    RETURN v_promedio;
END;
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- Promedio de una dimensión en una auditoría ISO: madurez por control,
-- ponderada por control.peso, de los controles marcados con esa dimensión.
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_promedio_controles(p_id_auditoria INTEGER, p_dimension VARCHAR)
RETURNS NUMERIC AS $$
DECLARE
    v_promedio NUMERIC(4,3);
BEGIN
    SELECT ROUND(
             SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 ELSE 1.0 END)
             / NULLIF(SUM(CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 ELSE 1.0 END), 0)
             / 5
           , 3)
      INTO v_promedio
      FROM evaluacion_control ec
      JOIN control c ON c.codigo = ec.codigo_control
     WHERE ec.id_auditoria = p_id_auditoria
       AND ec.madurez IS NOT NULL
       AND 1 = (CASE p_dimension
                   WHEN 'C' THEN ec.afecta_confidencialidad
                   WHEN 'I' THEN ec.afecta_integridad
                   ELSE ec.afecta_disponibilidad
               END);

    RETURN v_promedio;
END;
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- calcular_riesgo_auditoria — exposición por dimensión e índice.
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION calcular_riesgo_auditoria(p_id_auditoria INTEGER)
RETURNS void AS $$
DECLARE
    v_letras   VARCHAR[] := ARRAY['C', 'I', 'D'];
    v_nombres  VARCHAR[] := ARRAY['CONFIDENCIALIDAD', 'INTEGRIDAD', 'DISPONIBILIDAD'];
    v_modo     VARCHAR;
    v_promedio NUMERIC(4,3);
    v_zona     VARCHAR;
    v_indice   NUMERIC(5,2);
    i          INTEGER;
BEGIN
    SELECT e.modo_evaluacion
      INTO v_modo
      FROM auditoria a
      JOIN estandar e ON e.codigo = a.codigo_estandar
     WHERE a.id_auditoria = p_id_auditoria;

    IF NOT FOUND THEN
        RETURN;
    END IF;

    DELETE FROM resultado_riesgo WHERE id_auditoria = p_id_auditoria;

    FOR i IN 1 .. array_length(v_letras, 1) LOOP
        IF v_modo = 'OBJETIVO' THEN
            v_promedio := fn_promedio_objetivos(p_id_auditoria, v_letras[i]);
        ELSE
            v_promedio := fn_promedio_controles(p_id_auditoria, v_letras[i]);
        END IF;

        IF v_promedio IS NOT NULL THEN
            v_zona := fn_zona(v_promedio);
            INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
            VALUES (p_id_auditoria, v_nombres[i], v_promedio, v_zona);
        END IF;
    END LOOP;

    SELECT ROUND(AVG(promedio_madurez), 2)
      INTO v_indice
      FROM resultado_riesgo
     WHERE id_auditoria = p_id_auditoria;

    UPDATE auditoria
       SET indice_general_riesgo = v_indice
     WHERE id_auditoria = p_id_auditoria;
END;
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- sp_resumen_auditoria
-- ------------------------------------------------------------------
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
) AS $$
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
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END)
                / NULLIF(SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END
                    END
                ), 0)
            , 2)
        FROM auditoria a
        LEFT JOIN evaluacion_control ec ON ec.id_auditoria = a.id_auditoria
        LEFT JOIN control c ON c.codigo = ec.codigo_control
        WHERE a.id_auditoria = p_id_auditoria
        GROUP BY a.id_auditoria, a.estado, a.indice_general_riesgo;
END;
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- sp_cumplimiento_dominio
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION sp_cumplimiento_dominio(p_id_auditoria INTEGER)
RETURNS TABLE (
    clave_dominio     VARCHAR,
    nombre_dominio    VARCHAR,
    controles_si      BIGINT,
    controles_no      BIGINT,
    controles_na      BIGINT,
    cumplimiento      NUMERIC,
    madurez_promedio  NUMERIC
) AS $$
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
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END)
                / NULLIF(SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END
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
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- sp_menor_madurez
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION sp_menor_madurez(p_id_auditoria INTEGER, p_top_n INTEGER DEFAULT 5)
RETURNS TABLE (
    codigo_control VARCHAR,
    enunciado      TEXT,
    dominio        VARCHAR,
    estado         VARCHAR,
    madurez        SMALLINT,
    hallazgo       TEXT,
    posicion       BIGINT
) AS $$
BEGIN
    RETURN QUERY
        SELECT * FROM (
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
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- sp_mayor_riesgo
-- ------------------------------------------------------------------
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
) AS $$
BEGIN
    RETURN QUERY
        SELECT * FROM (
            SELECT
                ec.codigo_control,
                c.enunciado,
                d.nombre AS dominio,
                ec.impacto,
                ec.probabilidad,
                ec.nivel_riesgo,
                CONCAT(
                    CASE WHEN ec.afecta_confidencialidad = 1 THEN 'C' ELSE '' END,
                    CASE WHEN ec.afecta_integridad       = 1 THEN 'I' ELSE '' END,
                    CASE WHEN ec.afecta_disponibilidad   = 1 THEN 'D' ELSE '' END
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
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- sp_exposicion_riesgo
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION sp_exposicion_riesgo(p_id_auditoria INTEGER)
RETURNS TABLE (
    tipo_riesgo      VARCHAR,
    promedio_madurez NUMERIC,
    zona             VARCHAR,
    fecha_calculo    TIMESTAMP
) AS $$
BEGIN
    RETURN QUERY
        SELECT rr.tipo_riesgo, rr.promedio_madurez, rr.zona, rr.fecha_calculo
          FROM resultado_riesgo rr
         WHERE rr.id_auditoria = p_id_auditoria
         ORDER BY rr.tipo_riesgo;
END;
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- sp_historico_dominio — histórico de madurez por dominio, por organización.
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION sp_historico_dominio(p_organizacion VARCHAR)
RETURNS TABLE (
    id_auditoria     INTEGER,
    fecha            VARCHAR,
    clave_dominio    VARCHAR,
    dominio          VARCHAR,
    orden_dominio    SMALLINT,
    madurez_promedio NUMERIC
) AS $$
BEGIN
    RETURN QUERY
        SELECT
            aud.id_auditoria,
            TO_CHAR(aud.fecha, 'YYYY-MM-DD')::VARCHAR,
            d.clave,
            d.nombre_corto,
            d.orden,
            ROUND(
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END)
                / NULLIF(SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END
                    END
                ), 0)
            , 2)
        FROM auditoria aud
        JOIN v_auditoria_entrevistado u ON u.id_auditoria = aud.id_auditoria
        JOIN evaluacion_control ec ON ec.id_auditoria = aud.id_auditoria
        JOIN control c ON c.codigo = ec.codigo_control
        JOIN proceso p ON p.numero = c.numero_proceso
        JOIN dominio d ON d.clave = p.clave_dominio
        WHERE u.organizacion = p_organizacion
          AND ec.madurez IS NOT NULL
        GROUP BY aud.id_auditoria, aud.fecha, d.clave, d.nombre_corto, d.orden
        ORDER BY aud.fecha, d.orden, d.clave;
END;
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- sp_evolucion_auditor — evolución mensual del trabajo de un auditor.
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION sp_evolucion_auditor(p_id_auditor INTEGER, p_organizacion VARCHAR)
RETURNS TABLE (
    mes              VARCHAR,
    auditorias       BIGINT,
    cumplimiento     NUMERIC,
    cobertura        NUMERIC,
    madurez_promedio NUMERIC
) AS $$
BEGIN
    RETURN QUERY
        WITH catalogo AS (
            SELECT d.codigo_estandar, COUNT(*) AS controles
              FROM control c
              JOIN proceso p ON p.numero = c.numero_proceso
              JOIN dominio d ON d.clave = p.clave_dominio
             GROUP BY d.codigo_estandar
        ),
        por_auditoria AS (
            SELECT
                aud.id_auditoria,
                TO_CHAR(aud.fecha, 'YYYY-MM')::VARCHAR AS mes,
                MAX(cat.controles) AS controles,
                COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END)          AS si,
                COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END) AS si_no,
                COUNT(ec.estado)                                      AS respondidos,
                SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END) AS madurez_pond,
                SUM(
                    CASE WHEN ec.madurez IS NOT NULL
                         THEN CASE c.peso WHEN 'ALTA' THEN 3.0 WHEN 'MEDIA' THEN 2.0 WHEN 'BAJA' THEN 1.0 END
                    END
                ) AS peso_total
            FROM auditoria aud
            JOIN v_auditoria_entrevistado adm ON adm.id_auditoria = aud.id_auditoria
            LEFT JOIN evaluacion_control ec ON ec.id_auditoria = aud.id_auditoria
            LEFT JOIN control c ON c.codigo = ec.codigo_control
            LEFT JOIN catalogo cat ON cat.codigo_estandar = aud.codigo_estandar
            WHERE aud.id_auditor = p_id_auditor
              AND (p_organizacion IS NULL OR adm.organizacion = p_organizacion)
            GROUP BY aud.id_auditoria, TO_CHAR(aud.fecha, 'YYYY-MM')
        )
        SELECT
            pa.mes::VARCHAR,
            COUNT(*),
            ROUND(SUM(pa.si)::NUMERIC / NULLIF(SUM(pa.si_no), 0), 4),
            ROUND(SUM(pa.respondidos)::NUMERIC / NULLIF(SUM(pa.controles), 0), 4),
            ROUND(SUM(pa.madurez_pond) / NULLIF(SUM(pa.peso_total), 0), 2)
        FROM por_auditoria pa
        GROUP BY pa.mes
        ORDER BY pa.mes;
END;
$$ LANGUAGE plpgsql;


-- ------------------------------------------------------------------
-- Remediación y re-auditoría (ciclo PHVA).
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION sp_crear_remediacion(
    p_id_evaluacion_control INTEGER,
    p_fecha_limite          DATE,
    p_responsable           VARCHAR DEFAULT NULL
) RETURNS void AS $$
BEGIN
    INSERT INTO remediacion (id_evaluacion_control, fecha_limite, responsable, estado)
    VALUES (p_id_evaluacion_control, p_fecha_limite, p_responsable, 'PENDIENTE');
END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION sp_remediaciones_auditoria(p_id_auditoria INTEGER)
RETURNS TABLE (
    id_remediacion           INTEGER,
    codigo_control           VARCHAR,
    enunciado                TEXT,
    fecha_limite             DATE,
    estado                   VARCHAR,
    responsable              VARCHAR,
    id_auditoria_reauditoria INTEGER
) AS $$
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
            END,
            r.responsable,
            r.id_auditoria_reauditoria
        FROM remediacion r
        JOIN evaluacion_control ec ON ec.id_evaluacion_control = r.id_evaluacion_control
        JOIN control c ON c.codigo = ec.codigo_control
        WHERE ec.id_auditoria = p_id_auditoria
        ORDER BY r.fecha_limite;
END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION sp_remediaciones_vencidas()
RETURNS TABLE (
    id_remediacion INTEGER,
    id_auditoria   INTEGER,
    organizacion   VARCHAR,
    codigo_control VARCHAR,
    enunciado      TEXT,
    fecha_limite   DATE,
    responsable    VARCHAR
) AS $$
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
        JOIN v_auditoria_entrevistado u ON u.id_auditoria = aud.id_auditoria
        WHERE r.estado IN ('PENDIENTE', 'EN_PROCESO')
          AND r.fecha_limite < CURRENT_DATE
        ORDER BY r.fecha_limite;
END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION sp_programar_reauditoria(
    p_id_remediacion INTEGER,
    p_id_auditoria_reauditoria INTEGER
) RETURNS void AS $$
BEGIN
    UPDATE remediacion
       SET id_auditoria_reauditoria = p_id_auditoria_reauditoria,
           estado = 'EN_PROCESO'
     WHERE id_remediacion = p_id_remediacion;
END;
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION sp_actualizar_estado_remediacion(
    p_id_remediacion INTEGER,
    p_estado VARCHAR
) RETURNS void AS $$
BEGIN
    UPDATE remediacion
       SET estado = p_estado
     WHERE id_remediacion = p_id_remediacion;
END;
$$ LANGUAGE plpgsql;
