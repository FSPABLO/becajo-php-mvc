-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Fase 4 — Procedimientos almacenados de indicadores (Oracle 21c+)
-- Preparado por Persona 2
--
-- Requisito del profesor: el acceso a los indicadores se hace por medio de
-- procedimientos almacenados, no de SELECT sueltos desde la aplicación.
-- Persona 3 llama estos procedimientos desde PHP (oci8 / PDO_OCI); nunca
-- envía un SELECT directo contra las tablas para generar reportes.
--
-- Ejecutar después de 01_esquema.sql y 02_datos_semilla.sql.
-- ============================================================================

CREATE OR REPLACE PACKAGE pkg_indicadores AS

    -- Calcula y persiste la exposición al riesgo (C/I/D) y el índice general
    -- de una auditoría. Se llama cada vez que el auditor guarda avances o
    -- finaliza la auditoría. No devuelve cursor: actualiza resultado_riesgo
    -- y auditoria.indice_general_riesgo directamente.
    PROCEDURE calcular_riesgo_auditoria(
        p_id_auditoria IN NUMBER
    );

    -- Cumplimiento general y madurez promedio de toda la auditoría.
    PROCEDURE sp_resumen_auditoria(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    );

    -- Cumplimiento y madurez promedio, agrupado por dominio.
    PROCEDURE sp_cumplimiento_dominio(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    );

    -- Top N controles con menor madurez (los más críticos de resolver).
    PROCEDURE sp_menor_madurez(
        p_id_auditoria IN  NUMBER,
        p_top_n        IN  NUMBER DEFAULT 5,
        p_cursor       OUT SYS_REFCURSOR
    );

    -- Top N controles con mayor nivel de riesgo (impacto x probabilidad).
    PROCEDURE sp_mayor_riesgo(
        p_id_auditoria IN  NUMBER,
        p_top_n        IN  NUMBER DEFAULT 5,
        p_cursor       OUT SYS_REFCURSOR
    );

    -- Lee los resultados de exposición al riesgo ya calculados y guardados
    -- por calcular_riesgo_auditoria (hasta 3 filas: C, I, D).
    PROCEDURE sp_exposicion_riesgo(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    );

END pkg_indicadores;
/


CREATE OR REPLACE PACKAGE BODY pkg_indicadores AS

    -- Clasifica un promedio de madurez (0-1) en su zona del mapa de calor.
    -- Función privada: solo la usa este paquete.
    FUNCTION fn_zona(p_promedio IN NUMBER) RETURN VARCHAR2 IS
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
    END fn_zona;


    PROCEDURE calcular_riesgo_auditoria(
        p_id_auditoria IN NUMBER
    ) IS
        v_promedio_c  NUMBER(4,3);
        v_promedio_i  NUMBER(4,3);
        v_promedio_d  NUMBER(4,3);
        v_zona_c      VARCHAR2(10);
        v_zona_i      VARCHAR2(10);
        v_zona_d      VARCHAR2(10);
        v_indice      NUMBER(5,2);
    BEGIN
        -- Recálculo idempotente: se puede llamar varias veces sin duplicar filas.
        DELETE FROM resultado_riesgo WHERE id_auditoria = p_id_auditoria;

        SELECT ROUND(AVG(madurez) / 5, 3)
          INTO v_promedio_c
          FROM evaluacion_control
         WHERE id_auditoria = p_id_auditoria
           AND afecta_confidencialidad = 1
           AND madurez IS NOT NULL;

        -- fn_zona() se llama aquí, como asignación PL/SQL pura (permitido),
        -- no dentro del INSERT (una función solo declarada en el BODY del
        -- paquete no puede invocarse desde una sentencia SQL: PLS-00231).
        v_zona_c := fn_zona(v_promedio_c);

        IF v_promedio_c IS NOT NULL THEN
            INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
            VALUES (p_id_auditoria, 'CONFIDENCIALIDAD', v_promedio_c, v_zona_c);
        END IF;

        SELECT ROUND(AVG(madurez) / 5, 3)
          INTO v_promedio_i
          FROM evaluacion_control
         WHERE id_auditoria = p_id_auditoria
           AND afecta_integridad = 1
           AND madurez IS NOT NULL;

        v_zona_i := fn_zona(v_promedio_i);

        IF v_promedio_i IS NOT NULL THEN
            INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
            VALUES (p_id_auditoria, 'INTEGRIDAD', v_promedio_i, v_zona_i);
        END IF;

        SELECT ROUND(AVG(madurez) / 5, 3)
          INTO v_promedio_d
          FROM evaluacion_control
         WHERE id_auditoria = p_id_auditoria
           AND afecta_disponibilidad = 1
           AND madurez IS NOT NULL;

        v_zona_d := fn_zona(v_promedio_d);

        IF v_promedio_d IS NOT NULL THEN
            INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
            VALUES (p_id_auditoria, 'DISPONIBILIDAD', v_promedio_d, v_zona_d);
        END IF;

        -- Índice general = promedio de las dimensiones que sí tuvieron datos.
        SELECT ROUND(AVG(promedio_madurez), 2)
          INTO v_indice
          FROM resultado_riesgo
         WHERE id_auditoria = p_id_auditoria;

        UPDATE auditoria
           SET indice_general_riesgo = v_indice
         WHERE id_auditoria = p_id_auditoria;

        COMMIT;
    END calcular_riesgo_auditoria;


    PROCEDURE sp_resumen_auditoria(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
            SELECT
                a.id_auditoria,
                a.estado,
                a.indice_general_riesgo,
                COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END) AS controles_si,
                COUNT(CASE WHEN ec.estado = 'NO' THEN 1 END) AS controles_no,
                COUNT(CASE WHEN ec.estado = 'NA' THEN 1 END) AS controles_na,
                ROUND(
                    COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END)
                    / NULLIF(COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END), 0)
                , 4) AS cumplimiento,
                ROUND(AVG(ec.madurez), 2) AS madurez_promedio
            FROM auditoria a
            LEFT JOIN evaluacion_control ec ON ec.id_auditoria = a.id_auditoria
            WHERE a.id_auditoria = p_id_auditoria
            GROUP BY a.id_auditoria, a.estado, a.indice_general_riesgo;
    END sp_resumen_auditoria;


    PROCEDURE sp_cumplimiento_dominio(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
            SELECT
                d.clave AS clave_dominio,
                d.nombre AS nombre_dominio,
                COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END) AS controles_si,
                COUNT(CASE WHEN ec.estado = 'NO' THEN 1 END) AS controles_no,
                COUNT(CASE WHEN ec.estado = 'NA' THEN 1 END) AS controles_na,
                ROUND(
                    COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END)
                    / NULLIF(COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END), 0)
                , 4) AS cumplimiento,
                ROUND(AVG(ec.madurez), 2) AS madurez_promedio
            FROM dominio d
            JOIN proceso p ON p.clave_dominio = d.clave
            JOIN control c ON c.numero_proceso = p.numero
            JOIN evaluacion_control ec
                ON ec.codigo_control = c.codigo AND ec.id_auditoria = p_id_auditoria
            GROUP BY d.clave, d.nombre
            ORDER BY d.clave;
    END sp_cumplimiento_dominio;


    PROCEDURE sp_menor_madurez(
        p_id_auditoria IN  NUMBER,
        p_top_n        IN  NUMBER DEFAULT 5,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
            SELECT codigo_control, enunciado, dominio, estado, madurez, hallazgo, posicion
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
            )
            WHERE posicion <= p_top_n
            ORDER BY posicion;
    END sp_menor_madurez;


    PROCEDURE sp_mayor_riesgo(
        p_id_auditoria IN  NUMBER,
        p_top_n        IN  NUMBER DEFAULT 5,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
            SELECT codigo_control, enunciado, dominio, impacto, probabilidad,
                   nivel_riesgo, dimensiones, posicion
            FROM (
                SELECT
                    ec.codigo_control,
                    c.enunciado,
                    d.nombre AS dominio,
                    ec.impacto,
                    ec.probabilidad,
                    ec.nivel_riesgo,
                    CASE WHEN ec.afecta_confidencialidad = 1 THEN 'C' END ||
                    CASE WHEN ec.afecta_integridad       = 1 THEN 'I' END ||
                    CASE WHEN ec.afecta_disponibilidad   = 1 THEN 'D' END AS dimensiones,
                    RANK() OVER (ORDER BY ec.nivel_riesgo DESC) AS posicion
                FROM evaluacion_control ec
                JOIN control c ON c.codigo = ec.codigo_control
                JOIN proceso p ON p.numero = c.numero_proceso
                JOIN dominio d ON d.clave = p.clave_dominio
                WHERE ec.id_auditoria = p_id_auditoria
                  AND ec.nivel_riesgo IS NOT NULL
            )
            WHERE posicion <= p_top_n
            ORDER BY posicion;
    END sp_mayor_riesgo;


    PROCEDURE sp_exposicion_riesgo(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
            SELECT tipo_riesgo, promedio_madurez, zona, fecha_calculo
              FROM resultado_riesgo
             WHERE id_auditoria = p_id_auditoria
             ORDER BY tipo_riesgo;
    END sp_exposicion_riesgo;

END pkg_indicadores;
/
