-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Migración: perfil del usuario (descripción y fotografía)
--
-- 01_esquema.sql ya trae este estado. Este script es para la base que YA existe
-- y tiene datos — la que no se puede volver a crear desde cero sin perder las
-- auditorías cargadas.
--
-- Qué hace:
--   1. Agrega usuario.descripcion, la nota que el propio usuario escribe sobre
--      sí mismo y edita desde /perfil.
--   2. Crea USUARIO_FOTO, la fotografía de perfil, con su llave foránea a
--      USUARIO (ON DELETE CASCADE) y el UNIQUE que impone una por cuenta.
--
-- Es RE-EJECUTABLE: cada paso comprueba antes si ya está hecho. No toca ninguna
-- fila existente: una cuenta sin descripción y sin foto es un estado válido —
-- las dos cosas son opcionales, y de hecho todas las cuentas nacen así.
--
-- POR QUÉ LA FOTO VA EN TABLA APARTE Y LA DESCRIPCIÓN NO. Es el mismo criterio
-- que separó EVIDENCIA_ARCHIVO de EVALUACION_CONTROL: `usuario` se consulta en
-- CADA petición —Autenticacion reconsulta al usuario para que desactivar una
-- cuenta surta efecto de inmediato— y el driver materializa los LOB que estén
-- en la lista de columnas. Un BLOB ahí sería la foto viajando en cada clic. La
-- descripción es un VARCHAR2 corto y no tiene ese problema.
--
-- No hace falta recargar 03: ningún procedimiento consulta estas columnas.
--
-- Uso (DESDE BASH, no desde PowerShell — la tubería de PowerShell recodifica y
-- las tildes entran dobles; ver CLAUDE.md):
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 \
--       < Scripts/13_perfil_usuario.sql
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE OFF

DECLARE
    n NUMBER;
BEGIN
    -- ── 1. La descripción del perfil ─────────────────────────────────────
    SELECT COUNT(*) INTO n
      FROM user_tab_columns
     WHERE table_name = 'USUARIO'
       AND column_name = 'DESCRIPCION';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE usuario ADD (descripcion VARCHAR2(500))';
        DBMS_OUTPUT.PUT_LINE('USUARIO: columna descripcion agregada.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('USUARIO: la columna descripcion ya existia.');
    END IF;

    -- ── 2. La fotografía de perfil ───────────────────────────────────────
    SELECT COUNT(*) INTO n
      FROM user_tables
     WHERE table_name = 'USUARIO_FOTO';

    IF n = 0 THEN
        EXECUTE IMMEDIATE 'CREATE TABLE usuario_foto (
            id_usuario_foto  NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            id_usuario       NUMBER         NOT NULL,
            nombre           VARCHAR2(255)  NOT NULL,
            tipo_mime        VARCHAR2(100)  NOT NULL,
            tamano_bytes     NUMBER         NOT NULL,
            contenido        BLOB           NOT NULL,
            fecha_carga      TIMESTAMP      DEFAULT SYSTIMESTAMP NOT NULL,
            CONSTRAINT fk_usufoto_usuario
                FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
            CONSTRAINT uq_usufoto_usuario
                UNIQUE (id_usuario),
            CONSTRAINT ck_usufoto_tipo
                CHECK (tipo_mime IN (''image/png'', ''image/jpeg'', ''image/webp'', ''image/gif'')),
            CONSTRAINT ck_usufoto_tamano
                CHECK (tamano_bytes BETWEEN 1 AND 2097152)
        )';
        DBMS_OUTPUT.PUT_LINE('USUARIO_FOTO: tabla creada.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('USUARIO_FOTO: la tabla ya existia.');
    END IF;
END;
/

COMMIT;

-- Comprobación: la columna y la tabla existen.
SELECT COUNT(*) AS columna_descripcion
  FROM user_tab_columns WHERE table_name = 'USUARIO' AND column_name = 'DESCRIPCION';

SELECT COUNT(*) AS fotos_de_perfil FROM usuario_foto;
