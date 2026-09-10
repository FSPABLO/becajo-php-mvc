-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Migración: entrevistado escrito a mano en el alta de auditoría
--
-- 01_esquema.sql ya trae este estado: crea AUDITORIA con las dos columnas
-- nuevas y la vista V_AUDITORIA_ENTREVISTADO. Este script es para la base que
-- YA existe y tiene datos — la que no se puede volver a crear desde cero sin
-- perder las auditorías cargadas.
--
-- Qué hace:
--   1. Agrega auditoria.administrador_nombre y .administrador_organizacion.
--   2. Levanta el NOT NULL de auditoria.id_administrador_bd.
--   3. Añade ck_auditoria_administrador: o la cuenta, o el texto; nunca las
--      dos ni ninguna.
--   4. Crea (o reemplaza) la vista V_AUDITORIA_ENTREVISTADO.
--
-- Es RE-EJECUTABLE: cada paso comprueba antes si ya está hecho, así que
-- correrlo dos veces no falla ni pisa nada. No toca ninguna fila existente —
-- las auditorías que ya hay siguen apuntando a su cuenta registrada y quedan
-- del lado bueno de la restricción sin backfill.
--
-- Después de este script hay que recargar los procedimientos, que ahora
-- consultan la vista en vez de USUARIO:
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 \
--       < Scripts/03_procedimientos_indicadores.sql
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE OFF

DECLARE
    n NUMBER;
BEGIN
    -- ── 1. Las dos columnas nuevas ───────────────────────────────────────
    SELECT COUNT(*) INTO n
      FROM user_tab_columns
     WHERE table_name = 'AUDITORIA'
       AND column_name = 'ADMINISTRADOR_NOMBRE';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE auditoria ADD (
            administrador_nombre       VARCHAR2(150),
            administrador_organizacion VARCHAR2(200)
        )';
        DBMS_OUTPUT.PUT_LINE('AUDITORIA: columnas del entrevistado manual agregadas.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('AUDITORIA: las columnas ya existian.');
    END IF;

    -- ── 2. id_administrador_bd deja de ser obligatorio ───────────────────
    -- El dato no se vuelve opcional: pasa a ser una de dos alternativas, y
    -- quien obliga a que haya exactamente una es el CHECK del paso 3.
    SELECT COUNT(*) INTO n
      FROM user_tab_columns
     WHERE table_name = 'AUDITORIA'
       AND column_name = 'ID_ADMINISTRADOR_BD'
       AND nullable = 'N';

    IF n = 1 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE auditoria MODIFY (id_administrador_bd NULL)';
        DBMS_OUTPUT.PUT_LINE('AUDITORIA: id_administrador_bd ya admite NULL.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('AUDITORIA: id_administrador_bd ya admitia NULL.');
    END IF;

    -- ── 3. La restricción que sustituye al NOT NULL ──────────────────────
    SELECT COUNT(*) INTO n
      FROM user_constraints
     WHERE constraint_name = 'CK_AUDITORIA_ADMINISTRADOR';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE auditoria ADD CONSTRAINT ck_auditoria_administrador
            CHECK (
                (id_administrador_bd IS NOT NULL
                 AND administrador_nombre IS NULL
                 AND administrador_organizacion IS NULL)
             OR (id_administrador_bd IS NULL
                 AND administrador_nombre IS NOT NULL
                 AND administrador_organizacion IS NOT NULL)
            )';
        DBMS_OUTPUT.PUT_LINE('AUDITORIA: ck_auditoria_administrador creada.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('AUDITORIA: ck_auditoria_administrador ya existia.');
    END IF;
END;
/

-- ── 4. La vista ──────────────────────────────────────────────────────────
-- Misma definición que la de 01_esquema.sql. Si cambia allá, cambia aquí:
-- son el mismo objeto visto por quien monta de cero y por quien migra.
CREATE OR REPLACE VIEW v_auditoria_entrevistado AS
SELECT a.id_auditoria,
       NVL(dba.nombre,       a.administrador_nombre)       AS nombre_administrador_bd,
       NVL(dba.organizacion, a.administrador_organizacion) AS organizacion,
       CASE WHEN a.id_administrador_bd IS NULL THEN 1 ELSE 0 END AS es_manual
  FROM auditoria a
  LEFT JOIN usuario dba ON dba.id_usuario = a.id_administrador_bd;

COMMIT;

-- Comprobación: ninguna auditoría puede quedarse sin entrevistado resuelto.
SELECT COUNT(*) AS auditorias_sin_entrevistado
  FROM v_auditoria_entrevistado
 WHERE organizacion IS NULL;
