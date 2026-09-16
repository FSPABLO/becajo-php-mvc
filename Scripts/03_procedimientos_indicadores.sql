-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Procedimientos almacenados de indicadores (Oracle 21c+) — versión final
-- Preparado por Persona 2
--
-- Reemplaza al antiguo 03_procedimientos_indicadores.sql (que no ponderaba
-- por peso ni tenía histórico/remediación) y al archivo intermedio
-- 07_pkg_indicadores_v2.sql de la rama feature/rigor-normativo — este es el
-- único paquete que hace falta ejecutar.
--
-- Requisito del profesor: el acceso a los indicadores se hace por medio de
-- procedimientos almacenados, no de SELECT sueltos desde la aplicación.
-- Persona 3 llama estos procedimientos desde PHP (oci8 / PDO_OCI); nunca
-- envía un SELECT directo contra las tablas para generar reportes.
--
-- Ejecutar después de 01_esquema.sql y 02_datos_semilla.sql.
--
-- Resumen de lo que hace cada procedimiento:
--
--   MODIFICADOS (punto 16 — ponderación):
--     - calcular_riesgo_auditoria: la madurez ya no se promedia simple,
--       se pondera por CONTROL.peso (ALTA=3, MEDIA=2, BAJA=1).
--     - sp_resumen_auditoria y sp_cumplimiento_dominio: su madurez_promedio
--       usa la misma ponderación, para que el reporte no contradiga al
--       cálculo de riesgo.
--
--   SIN CAMBIOS: sp_menor_madurez, sp_mayor_riesgo, sp_exposicion_riesgo.
--
--   NUEVOS:
--     - sp_historico_dominio (punto 18): madurez ponderada por dominio, a
--       través de todas las auditorías de una organización, para graficar
--       la evolución (no solo el índice general que ya mostraba comparar()).
--     - sp_crear_remediacion, sp_remediaciones_auditoria,
--       sp_remediaciones_vencidas, sp_programar_reauditoria,
--       sp_actualizar_estado_remediacion (punto 19).
--
-- El peso NUNCA se convierte a número con una función PL/SQL llamada desde
-- SQL (eso fue el error PLS-00231 de la migración pasada) — se resuelve con
-- un CASE normal, que sí es válido dentro de una sentencia SQL.
-- ============================================================================

CREATE OR REPLACE PACKAGE pkg_indicadores AS

    PROCEDURE calcular_riesgo_auditoria(
        p_id_auditoria IN NUMBER
    );

    PROCEDURE sp_resumen_auditoria(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    );

    PROCEDURE sp_cumplimiento_dominio(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    );

    PROCEDURE sp_menor_madurez(
        p_id_auditoria IN  NUMBER,
        p_top_n        IN  NUMBER DEFAULT 5,
        p_cursor       OUT SYS_REFCURSOR
    );

    PROCEDURE sp_mayor_riesgo(
        p_id_auditoria IN  NUMBER,
        p_top_n        IN  NUMBER DEFAULT 5,
        p_cursor       OUT SYS_REFCURSOR
    );

    PROCEDURE sp_exposicion_riesgo(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    );

    -- Punto 18 — histórico de madurez por dominio, para una organización.
    PROCEDURE sp_historico_dominio(
        p_organizacion IN  VARCHAR2,
        p_cursor       OUT SYS_REFCURSOR
    );

    -- Evolución mensual del trabajo de un auditor, para el panel de entrada.
    -- p_organizacion en NULL trae la cartera completa.
    PROCEDURE sp_evolucion_auditor(
        p_id_auditor   IN  NUMBER,
        p_organizacion IN  VARCHAR2,
        p_cursor       OUT SYS_REFCURSOR
    );

    -- Punto 19 — remediación y re-auditoría.
    PROCEDURE sp_crear_remediacion(
        p_id_evaluacion_control IN NUMBER,
        p_fecha_limite          IN DATE,
        p_responsable           IN VARCHAR2 DEFAULT NULL
    );

    PROCEDURE sp_remediaciones_auditoria(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    );

    PROCEDURE sp_remediaciones_vencidas(
        p_cursor OUT SYS_REFCURSOR
    );

    PROCEDURE sp_programar_reauditoria(
        p_id_remediacion           IN NUMBER,
        p_id_auditoria_reauditoria IN NUMBER
    );

    PROCEDURE sp_actualizar_estado_remediacion(
        p_id_remediacion IN NUMBER,
        p_estado         IN VARCHAR2
    );

END pkg_indicadores;
/


CREATE OR REPLACE PACKAGE BODY pkg_indicadores AS

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


    -- ------------------------------------------------------------------
    -- Promedio de una dimensión en una auditoría COBIT.
    --
    -- Capacidad declarada por objetivo, ponderada por la relación del
    -- objetivo con la dimensión (P = primaria, S = secundaria; criterio
    -- propio del equipo por proceso, no una tabla oficial de COBIT 2019 —
    -- ver Scripts/14_multinorma.sql y el punto 7 de entrega-multinorma-cobit.md):
    -- P pesa 2, S pesa 1 y sin relación no entra. Normalizado a 0..1.
    -- ------------------------------------------------------------------
    FUNCTION fn_promedio_objetivos(
        p_id_auditoria IN NUMBER,
        p_dimension    IN VARCHAR2
    ) RETURN NUMBER IS
        v_promedio NUMBER(4,3);
    BEGIN
        SELECT ROUND(SUM(eo.capacidad * pp.peso) / NULLIF(SUM(pp.peso), 0) / 5, 3)
          INTO v_promedio
          FROM evaluacion_objetivo eo
          JOIN (SELECT numero,
                       CASE CASE p_dimension
                                WHEN 'C' THEN relacion_confidencialidad
                                WHEN 'I' THEN relacion_integridad
                                ELSE relacion_disponibilidad
                            END
                           WHEN 'P' THEN 2
                           WHEN 'S' THEN 1
                       END AS peso
                  FROM proceso) pp ON pp.numero = eo.numero_proceso
         WHERE eo.id_auditoria = p_id_auditoria
           AND pp.peso IS NOT NULL;

        RETURN v_promedio;
    END fn_promedio_objetivos;


    -- ------------------------------------------------------------------
    -- Promedio de una dimensión en una auditoría ISO: madurez por control,
    -- ponderada por CONTROL.peso, de los controles marcados con esa
    -- dimensión.
    -- ------------------------------------------------------------------
    FUNCTION fn_promedio_controles(
        p_id_auditoria IN NUMBER,
        p_dimension    IN VARCHAR2
    ) RETURN NUMBER IS
        v_promedio NUMBER(4,3);
    BEGIN
        SELECT ROUND(
                 SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END)
                 / NULLIF(SUM(CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 ELSE 1 END), 0)
                 / 5
               , 3)
          INTO v_promedio
          FROM evaluacion_control ec
          JOIN control c ON c.codigo = ec.codigo_control
         WHERE ec.id_auditoria = p_id_auditoria
           AND ec.madurez IS NOT NULL
           AND 1 = CASE p_dimension
                       WHEN 'C' THEN ec.afecta_confidencialidad
                       WHEN 'I' THEN ec.afecta_integridad
                       ELSE ec.afecta_disponibilidad
                   END;

        RETURN v_promedio;
    END fn_promedio_controles;


    -- ------------------------------------------------------------------
    -- calcular_riesgo_auditoria — exposición por dimensión e índice.
    --
    -- La norma decide de dónde sale cada promedio (estandar.modo_evaluacion);
    -- la zona, el registro en resultado_riesgo y el índice son iguales para
    -- las dos. En COBIT, resultado_riesgo.promedio_madurez guarda capacidad
    -- normalizada: misma escala de 0 a 1, otro origen.
    -- ------------------------------------------------------------------
    PROCEDURE calcular_riesgo_auditoria(
        p_id_auditoria IN NUMBER
    ) IS
        TYPE t_texto IS VARRAY(3) OF VARCHAR2(20);
        v_letras   t_texto := t_texto('C', 'I', 'D');
        v_nombres  t_texto := t_texto('CONFIDENCIALIDAD', 'INTEGRIDAD', 'DISPONIBILIDAD');
        v_modo     estandar.modo_evaluacion%TYPE;
        v_promedio NUMBER(4,3);
        v_zona     VARCHAR2(10);
        v_indice   NUMBER(5,2);
    BEGIN
        BEGIN
            SELECT e.modo_evaluacion
              INTO v_modo
              FROM auditoria a
              JOIN estandar e ON e.codigo = a.codigo_estandar
             WHERE a.id_auditoria = p_id_auditoria;
        EXCEPTION
            WHEN NO_DATA_FOUND THEN
                RETURN;
        END;

        DELETE FROM resultado_riesgo WHERE id_auditoria = p_id_auditoria;

        FOR i IN 1 .. v_letras.COUNT LOOP
            IF v_modo = 'OBJETIVO' THEN
                v_promedio := fn_promedio_objetivos(p_id_auditoria, v_letras(i));
            ELSE
                v_promedio := fn_promedio_controles(p_id_auditoria, v_letras(i));
            END IF;

            IF v_promedio IS NOT NULL THEN
                -- fn_zona es privada del cuerpo del paquete: Oracle no permite
                -- llamarla directamente dentro de un INSERT ... VALUES (PLS-00231,
                -- "function may not be used in SQL"). Se resuelve en PL/SQL puro
                -- antes de la sentencia SQL y se pasa ya calculada.
                v_zona := fn_zona(v_promedio);
                INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
                VALUES (p_id_auditoria, v_nombres(i), v_promedio, v_zona);
            END IF;
        END LOOP;

        SELECT ROUND(AVG(promedio_madurez), 2)
          INTO v_indice
          FROM resultado_riesgo
         WHERE id_auditoria = p_id_auditoria;

        UPDATE auditoria
           SET indice_general_riesgo = v_indice
         WHERE id_auditoria = p_id_auditoria;

        COMMIT;
    END calcular_riesgo_auditoria;


    -- ------------------------------------------------------------------
    -- sp_resumen_auditoria — madurez_promedio ahora ponderada.
    -- ------------------------------------------------------------------
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
                ROUND(
                    SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END)
                    / NULLIF(SUM(
                        CASE WHEN ec.madurez IS NOT NULL
                             THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
                        END
                    ), 0)
                , 2) AS madurez_promedio
            FROM auditoria a
            LEFT JOIN evaluacion_control ec ON ec.id_auditoria = a.id_auditoria
            LEFT JOIN control c ON c.codigo = ec.codigo_control
            WHERE a.id_auditoria = p_id_auditoria
            GROUP BY a.id_auditoria, a.estado, a.indice_general_riesgo;
    END sp_resumen_auditoria;


    -- ------------------------------------------------------------------
    -- sp_cumplimiento_dominio — madurez_promedio ahora ponderada.
    -- ------------------------------------------------------------------
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
                ROUND(
                    SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END)
                    / NULLIF(SUM(
                        CASE WHEN ec.madurez IS NOT NULL
                             THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
                        END
                    ), 0)
                , 2) AS madurez_promedio
            FROM dominio d
            JOIN proceso p ON p.clave_dominio = d.clave
            JOIN control c ON c.numero_proceso = p.numero
            JOIN evaluacion_control ec
                ON ec.codigo_control = c.codigo AND ec.id_auditoria = p_id_auditoria
            GROUP BY d.clave, d.nombre
            ORDER BY d.clave;
    END sp_cumplimiento_dominio;


    -- ------------------------------------------------------------------
    -- Sin cambios respecto a la versión anterior.
    -- ------------------------------------------------------------------
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


    -- ------------------------------------------------------------------
    -- Punto 18 — histórico de madurez por dominio, para una organización.
    -- Complementa a AuditoriaController::comparar(), que ya muestra el
    -- índice general por auditoría; esto desglosa por dominio.
    --
    -- La fecha sale con TO_CHAR y en ISO, como en el resto de los
    -- repositorios: devuelta como DATE, la sirve el cliente con el
    -- NLS_DATE_FORMAT de la sesión —«01-AUG-26»— y la misma consulta da
    -- una cadena distinta según dónde corra. En la pantalla de comparación
    -- esa cadena es un encabezado de columna, y además una fecha así no
    -- ordena como fecha.
    -- ------------------------------------------------------------------
    PROCEDURE sp_historico_dominio(
        p_organizacion IN  VARCHAR2,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
            SELECT
                aud.id_auditoria,
                TO_CHAR(aud.fecha, 'YYYY-MM-DD') AS fecha,
                d.clave AS clave_dominio,
                d.nombre_corto AS dominio,
                -- El orden de presentación del instrumento, no el alfabético:
                -- por clave, 'dato' (Protección) cae entre Continuidad y
                -- Gobierno. Viaja en la fila porque quien dibuja el radar
                -- necesita los ejes siempre en la misma sucesión.
                d.orden AS orden_dominio,
                ROUND(
                    SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END)
                    / NULLIF(SUM(
                        CASE WHEN ec.madurez IS NOT NULL
                             THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
                        END
                    ), 0)
                , 2) AS madurez_promedio
            FROM auditoria aud
            -- La vista, no USUARIO: una auditoría con el entrevistado
            -- escrito a mano no tiene cuenta que unir, y con el JOIN
            -- antiguo desaparecía del histórico sin decirlo.
            JOIN v_auditoria_entrevistado u ON u.id_auditoria = aud.id_auditoria
            JOIN evaluacion_control ec ON ec.id_auditoria = aud.id_auditoria
            JOIN control c ON c.codigo = ec.codigo_control
            JOIN proceso p ON p.numero = c.numero_proceso
            JOIN dominio d ON d.clave = p.clave_dominio
            WHERE u.organizacion = p_organizacion
              AND ec.madurez IS NOT NULL
            GROUP BY aud.id_auditoria, aud.fecha, d.clave, d.nombre_corto, d.orden
            ORDER BY aud.fecha, d.orden, d.clave;
    END sp_historico_dominio;


    -- ------------------------------------------------------------------
    -- Evolución mensual del trabajo de un auditor.
    --
    -- Una fila por mes CON auditorías. Los meses vacíos no se rellenan
    -- aquí: qué hacer con un hueco es una decisión de presentación, y la
    -- vista es la única que sabe si dibuja un corte o une los extremos.
    --
    -- Dos medidas, ambas proporciones de 0 a 1 y por tanto comparables en
    -- un mismo eje:
    --   cumplimiento — de lo respondido, cuánto resultó conforme.
    --   cobertura    — cuánto del instrumento se llegó a responder.
    --
    -- El cumplimiento del mes NO es el promedio de los cumplimientos de
    -- sus auditorías: es la razón agregada, SUM(si) / SUM(si+no).
    -- Promediar razones le daría el mismo peso a una auditoría con tres
    -- controles respondidos que a una con setenta y cinco.
    --
    -- La cobertura se mide contra el catálogo VIVO de la norma de cada
    -- auditoría y no contra un 75 escrito a mano: el catálogo es editable,
    -- y con varias normas cargadas un conteo global mezclaría catálogos.
    -- ------------------------------------------------------------------
    --
    -- p_organizacion filtra por la EMPRESA AUDITADA, que es la del
    -- administrador de BD entrevistado (el esquema no tiene tabla de
    -- organizaciones; ver el comentario de 01_esquema.sql). En NULL trae la
    -- cartera completa del auditor.
    PROCEDURE sp_evolucion_auditor(
        p_id_auditor   IN  NUMBER,
        p_organizacion IN  VARCHAR2,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
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
                    TO_CHAR(aud.fecha, 'YYYY-MM') AS mes,
                    MAX(cat.controles) AS controles,
                    COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END)          AS si,
                    COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END) AS si_no,
                    -- COUNT sobre la columna, no sobre la fila: una
                    -- evaluación empezada y sin estado todavía no cuenta
                    -- como control respondido.
                    COUNT(ec.estado)                                      AS respondidos,
                    SUM(ec.madurez * CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END) AS madurez_pond,
                    SUM(
                        CASE WHEN ec.madurez IS NOT NULL
                             THEN CASE c.peso WHEN 'ALTA' THEN 3 WHEN 'MEDIA' THEN 2 WHEN 'BAJA' THEN 1 END
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
                mes,
                COUNT(*) AS auditorias,
                ROUND(SUM(si) / NULLIF(SUM(si_no), 0), 4)                       AS cumplimiento,
                ROUND(SUM(respondidos) / NULLIF(SUM(controles), 0), 4)          AS cobertura,
                ROUND(SUM(madurez_pond) / NULLIF(SUM(peso_total), 0), 2)        AS madurez_promedio
            FROM por_auditoria
            GROUP BY mes
            ORDER BY mes;
    END sp_evolucion_auditor;


    -- ------------------------------------------------------------------
    -- Punto 19 — remediación y re-auditoría (ciclo PHVA).
    -- ------------------------------------------------------------------
    PROCEDURE sp_crear_remediacion(
        p_id_evaluacion_control IN NUMBER,
        p_fecha_limite          IN DATE,
        p_responsable           IN VARCHAR2 DEFAULT NULL
    ) IS
    BEGIN
        INSERT INTO remediacion (id_evaluacion_control, fecha_limite, responsable, estado)
        VALUES (p_id_evaluacion_control, p_fecha_limite, p_responsable, 'PENDIENTE');

        COMMIT;
    END sp_crear_remediacion;


    PROCEDURE sp_remediaciones_auditoria(
        p_id_auditoria IN  NUMBER,
        p_cursor       OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
            SELECT
                r.id_remediacion,
                ec.codigo_control,
                c.enunciado,
                r.fecha_limite,
                CASE
                    WHEN r.estado IN ('PENDIENTE', 'EN_PROCESO') AND r.fecha_limite < TRUNC(SYSDATE)
                        THEN 'VENCIDO'
                    ELSE r.estado
                END AS estado,
                r.responsable,
                r.id_auditoria_reauditoria
            FROM remediacion r
            JOIN evaluacion_control ec ON ec.id_evaluacion_control = r.id_evaluacion_control
            JOIN control c ON c.codigo = ec.codigo_control
            WHERE ec.id_auditoria = p_id_auditoria
            ORDER BY r.fecha_limite;
    END sp_remediaciones_auditoria;


    PROCEDURE sp_remediaciones_vencidas(
        p_cursor OUT SYS_REFCURSOR
    ) IS
    BEGIN
        OPEN p_cursor FOR
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
              AND r.fecha_limite < TRUNC(SYSDATE)
            ORDER BY r.fecha_limite;
    END sp_remediaciones_vencidas;


    PROCEDURE sp_programar_reauditoria(
        p_id_remediacion           IN NUMBER,
        p_id_auditoria_reauditoria IN NUMBER
    ) IS
    BEGIN
        UPDATE remediacion
           SET id_auditoria_reauditoria = p_id_auditoria_reauditoria,
               estado = 'EN_PROCESO'
         WHERE id_remediacion = p_id_remediacion;

        COMMIT;
    END sp_programar_reauditoria;


    PROCEDURE sp_actualizar_estado_remediacion(
        p_id_remediacion IN NUMBER,
        p_estado         IN VARCHAR2
    ) IS
    BEGIN
        UPDATE remediacion
           SET estado = p_estado
         WHERE id_remediacion = p_id_remediacion;

        COMMIT;
    END sp_actualizar_estado_remediacion;

END pkg_indicadores;
/
