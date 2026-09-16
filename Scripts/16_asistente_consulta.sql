-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Migración: registro de consultas a Lembas, el asistente del módulo
--
-- 01_esquema.sql ya trae este estado. Este script es para la base que YA existe
-- y tiene datos.
--
-- Qué hace: crea ASISTENTE_CONSULTA, una fila por pregunta que llegó a la API
-- de Anthropic. Sirve para DOS cosas:
--   1. Los límites diarios de la aplicación (LEMBAS_LIMITE_DIARIO_USUARIO y
--      _TOTAL en .env). Se cuentan aquí y no en la sesión: el total es de
--      todos los usuarios, y un límite por usuario guardado en su sesión se
--      reinicia con cerrar la sesión y volver a entrar.
--   2. Trazabilidad del uso (ISO/IEC 27002 A.8.15): quién preguntó, cuándo,
--      qué herramientas usó el modelo y cuántos tokens costó.
--
-- LO QUE NO GUARDA, A PROPÓSITO: la pregunta ni la respuesta. Quien escribe en
-- el chat puede escribir un hallazgo o el nombre de una empresa aunque el panel
-- le pida que no; guardar el texto convertiría esta tabla en una copia de datos
-- de auditoría fuera de su sitio, sin sus controles de acceso. Para los límites
-- y la trazabilidad basta con los metadatos.
--
-- Es RE-EJECUTABLE y no toca ninguna fila existente. No hace falta recargar 03:
-- ningún procedimiento consulta esta tabla.
--
-- Uso (DESDE BASH, no desde PowerShell — ver CLAUDE.md):
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 \
--       < Scripts/16_asistente_consulta.sql
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE OFF

DECLARE
    n NUMBER;
BEGIN
    SELECT COUNT(*) INTO n
      FROM user_tables
     WHERE table_name = 'ASISTENTE_CONSULTA';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'CREATE TABLE asistente_consulta (
            id_asistente_consulta  NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            id_usuario             NUMBER         NOT NULL,
            fecha                  TIMESTAMP      DEFAULT SYSTIMESTAMP NOT NULL,
            pantalla               VARCHAR2(300),
            herramientas           VARCHAR2(500),
            tokens_entrada         NUMBER         DEFAULT 0 NOT NULL,
            tokens_salida          NUMBER         DEFAULT 0 NOT NULL,
            resultado              VARCHAR2(20)   NOT NULL,
            CONSTRAINT fk_asiscons_usuario
                FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
            CONSTRAINT ck_asiscons_resultado
                CHECK (resultado IN (''RESPONDIDA'', ''RECHAZADA'', ''ERROR'')),
            CONSTRAINT ck_asiscons_tokens
                CHECK (tokens_entrada >= 0 AND tokens_salida >= 0)
        )';
        DBMS_OUTPUT.PUT_LINE('ASISTENTE_CONSULTA: tabla creada.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('ASISTENTE_CONSULTA: la tabla ya existia.');
    END IF;

    -- Los dos recuentos de los límites filtran por fecha, y el de usuario
    -- además por id_usuario: un índice compuesto sirve a los dos.
    SELECT COUNT(*) INTO n
      FROM user_indexes
     WHERE index_name = 'IX_ASISCONS_USUARIO_FECHA';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'CREATE INDEX ix_asiscons_usuario_fecha
                               ON asistente_consulta (id_usuario, fecha)';
        DBMS_OUTPUT.PUT_LINE('ASISTENTE_CONSULTA: indice (id_usuario, fecha) creado.');
    END IF;

    SELECT COUNT(*) INTO n
      FROM user_indexes
     WHERE index_name = 'IX_ASISCONS_FECHA';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'CREATE INDEX ix_asiscons_fecha ON asistente_consulta (fecha)';
        DBMS_OUTPUT.PUT_LINE('ASISTENTE_CONSULTA: indice (fecha) creado.');
    END IF;
END;
/

COMMIT;

-- Comprobación: la tabla existe y está vacía (o con las consultas de hoy).
SELECT COUNT(*) AS consultas_registradas FROM asistente_consulta;
