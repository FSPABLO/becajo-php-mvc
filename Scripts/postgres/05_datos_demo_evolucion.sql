-- ============================================================================
-- Datos de demostración: la cartera de un auditor a lo largo de varios meses.
-- PUERTO A POSTGRESQL de Scripts/05_datos_demo_evolucion.sql
--
-- Mismo objetivo y misma lógica que la versión Oracle: cuatro auditorías
-- adicionales repartidas en meses anteriores, más completar la auditoría que
-- ya existía, para que el panel tenga una evolución real que dibujar.
--
-- QUÉ CAMBIÓ EN LA TRADUCCIÓN (la lógica de negocio es idéntica):
--   - El PROCEDURE anidado "llenar" de la versión Oracle no tiene equivalente
--     directo: plpgsql no permite procedimientos anidados dentro de un
--     bloque DECLARE. Se saca a un procedimiento de nivel superior,
--     _demo_llenar_evaluaciones, definido antes del bloque principal.
--   - TYPE t_plan / TYPE t_planes (arreglo de RECORD) se reemplaza por un FOR
--     sobre una tabla VALUES(...) en línea: es el equivalente idiomático en
--     Postgres y no necesita declarar un tipo aparte.
--   - NO_DATA_FOUND se obtiene con "SELECT ... INTO STRICT": sin STRICT,
--     plpgsql deja la variable en NULL en vez de lanzar la excepción.
--   - DBMS_OUTPUT.PUT_LINE -> RAISE NOTICE (con marcadores % en vez de ||).
--   - RAISE_APPLICATION_ERROR(-2000x, msg) -> RAISE EXCEPTION msg.
--   - SET SERVEROUTPUT ON / EXIT eran meta-comandos de sqlplus; no aplican
--     en psql (RAISE NOTICE ya se ve en la consola) y se omiten.
--
-- Sigue siendo RE-EJECUTABLE: cada auditoría se crea solo si no existe ya una
-- del mismo auditor con la misma área y fecha, y cada respuesta se inserta
-- solo para los controles que esa auditoría todavía no tiene.
--
-- Ejecutar después de 01, 02 y 03:
--   docker exec -i becajo-postgres psql -U becajo -d becajo -f Scripts/postgres/05_datos_demo_evolucion.sql
-- ============================================================================

-- ── Procedimiento auxiliar: llena una auditoría hasta el número de
-- controles del plan. Mismo rebote determinista que en Oracle
-- (MOD(rn * 37, 100)) para repartir los conformes entre todos los dominios.
CREATE OR REPLACE PROCEDURE _demo_llenar_evaluaciones(
    p_id_auditoria IN INTEGER,
    p_respondidos  IN INTEGER,
    p_banda_si     IN INTEGER,
    p_madurez_min  IN INTEGER,
    p_fecha        IN DATE,
    p_paso         IN INTEGER
) LANGUAGE plpgsql AS $$
DECLARE
    v_nuevas  INTEGER := 0;
    v_ya      INTEGER;
    c         RECORD;
    v_estado    VARCHAR(5);
    v_madurez   SMALLINT;
    v_impacto   SMALLINT;
    v_prob      SMALLINT;
    v_criterio  VARCHAR(20);
    v_evidencia VARCHAR(400);
    v_calidad   VARCHAR(20);
    v_hallazgo  VARCHAR(400);
    v_recom     VARCHAR(400);
    v_riesgo    NUMERIC(4,2);
BEGIN
    FOR c IN (
        SELECT codigo, rn, MOD(rn * 37, 100) AS rebote
          FROM (SELECT codigo, ROW_NUMBER() OVER (ORDER BY codigo) AS rn FROM control) sub
         WHERE rn <= p_respondidos
    ) LOOP
        SELECT COUNT(*) INTO v_ya
          FROM evaluacion_control
         WHERE id_auditoria = p_id_auditoria
           AND codigo_control = c.codigo;

        CONTINUE WHEN v_ya > 0;

        IF c.rebote < 8 THEN
            v_estado := 'NA';
        ELSIF c.rebote < 8 + p_banda_si THEN
            v_estado := 'SI';
        ELSE
            v_estado := 'NO';
        END IF;

        IF v_estado = 'SI' THEN
            v_madurez   := LEAST(5, p_madurez_min + MOD(c.rn, 3));
            v_criterio  := CASE MOD(c.rn, 3)
                               WHEN 0 THEN 'DOCUMENTADO'
                               WHEN 1 THEN 'REPETIBLE'
                               ELSE 'EVIDENCIA'
                           END;
            v_evidencia := 'Procedimiento vigente y bitácora de aplicación revisados durante la entrevista ('
                        || TO_CHAR(p_fecha, 'YYYY-MM-DD') || ').';
            v_calidad   := CASE
                               WHEN v_madurez >= 4 THEN 'BIEN_IMPLEMENTADO'
                               WHEN v_madurez = 3  THEN 'REQUIERE_MEJORA'
                               ELSE 'DECLARATIVO'
                           END;
            v_hallazgo  := NULL;
            v_recom     := NULL;
        ELSIF v_estado = 'NO' THEN
            v_madurez   := GREATEST(0, p_madurez_min - 1 + MOD(c.rn, 2));
            v_criterio  := NULL;
            v_evidencia := NULL;
            v_calidad   := NULL;
            v_hallazgo  := 'El control no está implementado de forma verificable en el alcance evaluado.';
            v_recom     := 'Documentar el procedimiento, asignar responsable y dejar registro de su aplicación.';
        ELSE
            v_madurez   := NULL;
            v_criterio  := NULL;
            v_evidencia := NULL;
            v_calidad   := NULL;
            v_hallazgo  := NULL;
            v_recom     := NULL;
        END IF;

        IF v_estado = 'NA' THEN
            v_impacto := NULL;
            v_prob    := NULL;
            v_riesgo  := NULL;
        ELSE
            v_impacto := GREATEST(1, LEAST(5, 5 - MOD(c.rebote, 3) - FLOOR(p_paso / 3)));
            v_prob    := GREATEST(1, LEAST(5,
                             (CASE WHEN v_estado = 'SI' THEN 2 ELSE 4 END)
                             + MOD(c.rn, 2) - FLOOR(p_paso / 3)));
            v_riesgo  := (v_impacto + v_prob) / 2.0;
        END IF;

        INSERT INTO evaluacion_control (
            id_auditoria, codigo_control, estado, madurez, criterio,
            afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
            impacto, probabilidad, nivel_riesgo,
            hallazgo, recomendacion, evidencia_verificada, calidad_evidencia
        ) VALUES (
            p_id_auditoria, c.codigo, v_estado, v_madurez, v_criterio,
            CASE WHEN MOD(c.rn, 2) = 0 THEN 1 ELSE 0 END,
            CASE WHEN MOD(c.rn, 3) = 0 THEN 1 ELSE 0 END,
            CASE WHEN MOD(c.rn, 5) = 0 THEN 1 ELSE 0 END,
            v_impacto, v_prob, v_riesgo,
            v_hallazgo, v_recom, v_evidencia, v_calidad
        );

        v_nuevas := v_nuevas + 1;
    END LOOP;

    RAISE NOTICE '    respuestas nuevas: % · meta del mes: %', v_nuevas, p_respondidos;
END;
$$;


-- ── Bloque principal ─────────────────────────────────────────────────────
DO $$
DECLARE
    c_correo_auditor CONSTANT VARCHAR(150) := 'benjamin.solano@becajo.test';

    v_id_auditor   INTEGER;
    v_id_admin     INTEGER;
    v_id_previa    INTEGER;
    v_organizacion VARCHAR(150);
    v_id_auditoria INTEGER;
    v_existe       INTEGER;
    v_fecha_previa DATE;
    v_plan         RECORD;
    a              RECORD;
BEGIN
    BEGIN
        SELECT id_usuario INTO STRICT v_id_auditor
          FROM usuario
         WHERE LOWER(correo) = LOWER(c_correo_auditor);
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            RAISE EXCEPTION 'No existe el auditor %', c_correo_auditor;
    END;

    -- La auditoría de partida es la de menor id (la que el auditor ya tenía
    -- antes de que este script existiera): sirve para deducir la
    -- organización y, al final, terminar de responderla.
    BEGIN
        SELECT MIN(id_auditoria) INTO STRICT v_id_previa
          FROM auditoria
         WHERE id_auditor = v_id_auditor;

        SELECT a.id_administrador_bd, u.organizacion
          INTO STRICT v_id_admin, v_organizacion
          FROM auditoria a
          JOIN usuario u ON u.id_usuario = a.id_administrador_bd
         WHERE a.id_auditoria = v_id_previa;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            RAISE EXCEPTION 'El auditor no tiene ninguna auditoría previa de la cual deducir la organización.';
    END;

    RAISE NOTICE 'Auditor % · organización auditada: %', v_id_auditor, v_organizacion;
    RAISE NOTICE 'Auditoría de partida: %', v_id_previa;

    -- El plan de cada mes: más controles respondidos y mejor cumplimiento
    -- cada mes, igual que en la versión Oracle.
    FOR v_plan IN
        SELECT * FROM (VALUES
            ('Gestión de accesos y privilegios',              DATE '2026-04-14', 18, 37, 1, 1),
            ('Respaldos y recuperación',                      DATE '2026-05-19', 31, 48, 2, 2),
            ('Servidores de base de datos de producción',     DATE '2026-06-16', 45, 56, 2, 3),
            ('Cifrado y protección del dato',                 DATE '2026-07-21', 58, 63, 3, 4)
        ) AS t(area, fecha, respondidos, banda_si, madurez_min, paso)
    LOOP
        SELECT COUNT(*) INTO v_existe
          FROM auditoria
         WHERE id_auditor = v_id_auditor
           AND area_evaluada = v_plan.area
           AND fecha = v_plan.fecha;

        IF v_existe > 0 THEN
            SELECT MIN(id_auditoria) INTO v_id_auditoria
              FROM auditoria
             WHERE id_auditor = v_id_auditor
               AND area_evaluada = v_plan.area
               AND fecha = v_plan.fecha;

            RAISE NOTICE '  ya existía la auditoría % (%)', v_id_auditoria, v_plan.area;
        ELSE
            INSERT INTO auditoria (id_auditor, id_administrador_bd, area_evaluada, fecha, estado)
            VALUES (v_id_auditor, v_id_admin, v_plan.area, v_plan.fecha, 'EN_PROGRESO')
            RETURNING id_auditoria INTO v_id_auditoria;

            RAISE NOTICE '  creada la auditoría % (% · %)', v_id_auditoria, v_plan.area, v_plan.fecha;
        END IF;

        CALL _demo_llenar_evaluaciones(
            v_id_auditoria, v_plan.respondidos, v_plan.banda_si,
            v_plan.madurez_min, v_plan.fecha, v_plan.paso
        );
    END LOOP;

    -- Se termina de responder la auditoría de partida, para que el mes más
    -- reciente no se quede con un puñado de controles.
    SELECT fecha INTO v_fecha_previa FROM auditoria WHERE id_auditoria = v_id_previa;

    RAISE NOTICE '  completando la auditoría de partida %', v_id_previa;
    CALL _demo_llenar_evaluaciones(v_id_previa, 66, 70, 3, v_fecha_previa, 5);

    -- Los indicadores se recalculan con el mismo procedimiento que usa la
    -- aplicación al guardar, no se escriben a mano.
    FOR a IN SELECT id_auditoria FROM auditoria WHERE id_auditor = v_id_auditor LOOP
        CALL calcular_riesgo_auditoria(a.id_auditoria);
    END LOOP;

    RAISE NOTICE 'Indicadores recalculados.';
END;
$$;
