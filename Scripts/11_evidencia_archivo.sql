-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Migración: archivo adjunto de la evidencia (imagen o PDF)
--
-- 01_esquema.sql ya trae este estado: crea EVIDENCIA_ARCHIVO junto al resto.
-- Este script es para la base que YA existe y tiene datos — la que no se
-- puede volver a crear desde cero sin perder las auditorías cargadas.
--
-- Qué hace:
--   1. Crea la tabla EVIDENCIA_ARCHIVO, con su llave foránea a
--      EVALUACION_CONTROL (ON DELETE CASCADE), el UNIQUE que impone un
--      adjunto por control evaluado y los dos CHECK de tipo y tamaño.
--
-- Es RE-EJECUTABLE: comprueba antes si la tabla ya está, así que correrlo dos
-- veces no falla ni pisa nada. No toca ninguna fila existente: las
-- evaluaciones que ya hay simplemente no tienen adjunto, que es un estado
-- válido — el archivo es OPCIONAL incluso cuando la respuesta es "Sí".
--
-- Por qué una tabla aparte y no una columna BLOB en EVALUACION_CONTROL: el
-- repositorio lee esa tabla con SELECT * (las 75 evaluaciones de una auditoría
-- de una vez) y el driver trae los LOB ya materializados, así que abrir el
-- panel se llevaría por delante los 75 adjuntos. Aquí el binario solo se toca
-- cuando alguien pide el archivo.
--
-- No hace falta recargar 03: ningún procedimiento consulta esta tabla.
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE OFF

DECLARE
    n NUMBER;
BEGIN
    SELECT COUNT(*) INTO n
      FROM user_tables
     WHERE table_name = 'EVIDENCIA_ARCHIVO';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'CREATE TABLE evidencia_archivo (
            id_evidencia_archivo   NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            id_evaluacion_control  NUMBER         NOT NULL,
            nombre                 VARCHAR2(255)  NOT NULL,
            tipo_mime              VARCHAR2(100)  NOT NULL,
            tamano_bytes           NUMBER         NOT NULL,
            contenido              BLOB           NOT NULL,
            fecha_carga            TIMESTAMP      DEFAULT SYSTIMESTAMP NOT NULL,
            CONSTRAINT fk_evidarch_evalctrl
                FOREIGN KEY (id_evaluacion_control)
                REFERENCES evaluacion_control (id_evaluacion_control) ON DELETE CASCADE,
            CONSTRAINT uq_evidarch_evalctrl
                UNIQUE (id_evaluacion_control),
            CONSTRAINT ck_evidarch_tipo
                CHECK (tipo_mime IN (''image/png'', ''image/jpeg'', ''image/webp'',
                                     ''image/gif'', ''application/pdf'')),
            CONSTRAINT ck_evidarch_tamano
                CHECK (tamano_bytes BETWEEN 1 AND 5242880)
        )';
        DBMS_OUTPUT.PUT_LINE('EVIDENCIA_ARCHIVO: tabla creada.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('EVIDENCIA_ARCHIVO: la tabla ya existia.');
    END IF;
END;
/

COMMIT;

-- Comprobación: la tabla existe y está vacía o con los adjuntos que ya hubiera.
SELECT COUNT(*) AS adjuntos_de_evidencia FROM evidencia_archivo;
