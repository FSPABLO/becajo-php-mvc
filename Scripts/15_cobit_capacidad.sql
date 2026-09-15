-- ============================================================================
-- COBIT 2019 con captura propia: capacidad por objetivo y logro por práctica
-- ============================================================================
--
-- Re-ejecutable. Requiere 14_multinorma.sql. Después hay que recargar 03.
--
--   estandar.modo_evaluacion   'CONTROL' (ISO: Sí/No/No aplica + madurez por
--                              control) u 'OBJETIVO' (COBIT: capacidad
--                              declarada por objetivo). Es dato y no una
--                              condición en PHP, para que una norma nueva no
--                              obligue a tocar código.
--
--   evaluacion_control         + grado_logro: N, P, L, F (no, parcial, amplio,
--   .grado_logro               totalmente logrado; escala de ISO/IEC 33020
--                              que usa COBIT 2019). Nulo en ISO. El estado se
--                              DERIVA de él en la aplicación: L y F cuentan
--                              como logrado (SI), N y P como no (NO), para que
--                              cumplimiento y remediaciones sigan funcionando.
--
--   evaluacion_objetivo        Capacidad 0..5 que el auditor declara por
--                              objetivo, con su justificación. Juicio
--                              profesional, igual que la madurez en ISO.
--
-- Ejecución (PowerShell):
--   docker cp Scripts\15_cobit_capacidad.sql becajo-oracle:/tmp/15.sql
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/15.sql"
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE OFF

DECLARE
    ya_existe_objeto      EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_objeto,      -955);
    ya_existe_columna     EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_columna,     -1430);
    ya_existe_nombre      EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_nombre,      -2264);

    PROCEDURE ejecutar(p_sql VARCHAR2, p_que VARCHAR2) IS
    BEGIN
        EXECUTE IMMEDIATE p_sql;
        DBMS_OUTPUT.PUT_LINE('  creado    : ' || p_que);
    EXCEPTION
        WHEN ya_existe_objeto OR ya_existe_columna OR ya_existe_nombre THEN
            DBMS_OUTPUT.PUT_LINE('  ya estaba : ' || p_que);
    END;
BEGIN
    ejecutar(q'[ALTER TABLE estandar
                ADD modo_evaluacion VARCHAR2(10) DEFAULT 'CONTROL' NOT NULL]',
             'columna estandar.modo_evaluacion');
    ejecutar(q'[ALTER TABLE estandar ADD CONSTRAINT ck_estandar_modo
                CHECK (modo_evaluacion IN ('CONTROL', 'OBJETIVO'))]',
             'ck_estandar_modo');

    ejecutar('ALTER TABLE evaluacion_control ADD grado_logro VARCHAR2(1)',
             'columna evaluacion_control.grado_logro');
    ejecutar(q'[ALTER TABLE evaluacion_control ADD CONSTRAINT ck_evalctrl_grado_logro
                CHECK (grado_logro IN ('N', 'P', 'L', 'F'))]',
             'ck_evalctrl_grado_logro');

    ejecutar(q'[
        CREATE TABLE evaluacion_objetivo (
            id_auditoria        NUMBER          NOT NULL,
            numero_proceso      NUMBER(3)       NOT NULL,
            capacidad           NUMBER(1)       NOT NULL,
            justificacion       VARCHAR2(2000 CHAR) NOT NULL,
            fecha_actualizacion TIMESTAMP       DEFAULT SYSTIMESTAMP NOT NULL,
            CONSTRAINT pk_evaluacion_objetivo PRIMARY KEY (id_auditoria, numero_proceso),
            CONSTRAINT fk_evalobj_auditoria
                FOREIGN KEY (id_auditoria) REFERENCES auditoria (id_auditoria),
            CONSTRAINT fk_evalobj_proceso
                FOREIGN KEY (numero_proceso) REFERENCES proceso (numero),
            CONSTRAINT ck_evalobj_capacidad CHECK (capacidad BETWEEN 0 AND 5)
        )]', 'tabla evaluacion_objetivo');

    -- La PK cubre id_auditoria; numero_proceso necesita su índice propio
    -- para no bloquear proceso al borrar uno desde el catálogo.
    ejecutar('CREATE INDEX ix_evalobj_proceso ON evaluacion_objetivo (numero_proceso)',
             'ix_evalobj_proceso');
END;
/

UPDATE estandar SET modo_evaluacion = 'OBJETIVO' WHERE codigo = 'COBIT2019';
COMMIT;

PROMPT
PROMPT --- Modo por norma (ISO27002 CONTROL, COBIT2019 OBJETIVO) ---
SELECT codigo, modo_evaluacion FROM estandar ORDER BY orden;

PROMPT --- Tabla nueva (debe existir) ---
SELECT table_name FROM user_tables WHERE table_name = 'EVALUACION_OBJETIVO';
