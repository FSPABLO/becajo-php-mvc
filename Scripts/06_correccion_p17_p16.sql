-- ============================================================================
-- EIF402 · becajo — Rama feature/rigor-normativo
-- Migración 06 — Corrección de la Fase B tras revisar COBIT 4.1 e ISO 9001
-- Preparado por Persona 2
--
-- Motivo del cambio (ver conversación): la migración 05 modeló el punto 17
-- como una etiqueta única por proceso (tipo PRIMARIO/SECUNDARIO) más tres
-- banderas sueltas de "posible" C/I/D. El Apéndice II de COBIT 4.1 clasifica
-- la relación de CADA proceso con CADA criterio de información por separado,
-- usando 'P' (relación primaria) o 'S' (relación secundaria) — no una sola
-- etiqueta para todo el proceso. Esta migración corrige eso.
--
-- De paso, simplifica CONTROL.peso de una escala 1-5 a ALTA/MEDIA/BAJA,
-- que es literalmente el "indicador de importancia relativa" que usa COBIT
-- en esa misma tabla del Apéndice II (punto 16).
--
-- Como nadie ha usado 'tipo' ni 'posible_*' todavía desde ninguna pantalla
-- (siguen en su valor por defecto para las 25 filas), no hace falta backfill
-- esta vez: se eliminan y se reemplazan directamente.
-- ============================================================================

-- ============================================================================
-- PUNTO 17 — corrección: relación P/S por dimensión, no una etiqueta única
-- ============================================================================
-- Al eliminar una columna, Oracle elimina automáticamente cualquier
-- restricción CHECK de una sola columna asociada a ella — no hace falta
-- borrar ck_proceso_tipo ni los ck_proceso_posible_* a mano.
ALTER TABLE proceso DROP (tipo, posible_confidencialidad, posible_integridad, posible_disponibilidad);

ALTER TABLE proceso ADD (
    relacion_confidencialidad VARCHAR2(1),
    relacion_integridad       VARCHAR2(1),
    relacion_disponibilidad   VARCHAR2(1)
);
ALTER TABLE proceso ADD CONSTRAINT ck_proceso_rel_confidencialidad
    CHECK (relacion_confidencialidad IN ('P', 'S'));
ALTER TABLE proceso ADD CONSTRAINT ck_proceso_rel_integridad
    CHECK (relacion_integridad IN ('P', 'S'));
ALTER TABLE proceso ADD CONSTRAINT ck_proceso_rel_disponibilidad
    CHECK (relacion_disponibilidad IN ('P', 'S'));

COMMENT ON COLUMN proceso.relacion_confidencialidad IS
    'P = relación primaria, S = relación secundaria, NULL = sin relación relevante (notación de COBIT 4.1, Apéndice II).';
COMMENT ON COLUMN proceso.relacion_integridad IS
    'P = relación primaria, S = relación secundaria, NULL = sin relación relevante (notación de COBIT 4.1, Apéndice II).';
COMMENT ON COLUMN proceso.relacion_disponibilidad IS
    'P = relación primaria, S = relación secundaria, NULL = sin relación relevante (notación de COBIT 4.1, Apéndice II).';


-- ============================================================================
-- PUNTO 16 — corrección: peso de escala 1-5 a ALTA/MEDIA/BAJA
-- ============================================================================
-- Oracle no permite cambiar el tipo de dato de NUMBER a VARCHAR2 con un
-- MODIFY directo sobre una columna con datos; se elimina y se vuelve a
-- crear. Como todos los controles siguen con el valor por defecto (nadie ha
-- ajustado el peso todavía desde el catálogo), no se pierde información real.
ALTER TABLE control DROP COLUMN peso;

ALTER TABLE control ADD (
    peso VARCHAR2(10) DEFAULT 'MEDIA' NOT NULL
);
ALTER TABLE control ADD CONSTRAINT ck_control_peso
    CHECK (peso IN ('ALTA', 'MEDIA', 'BAJA'));

COMMENT ON COLUMN control.peso IS
    'Importancia relativa del control: ALTA/MEDIA/BAJA — mismo indicador de importancia relativa que usa COBIT 4.1 en el Apéndice II. Se usa como peso en el promedio ponderado del cálculo de riesgo.';

COMMIT;


-- ============================================================================
-- Verificación
-- ============================================================================
PROMPT
PROMPT ===== Verificación de la migración correctiva =====
SELECT column_name, data_type, data_length, nullable
  FROM user_tab_columns
 WHERE table_name = 'PROCESO'
   AND column_name IN ('RELACION_CONFIDENCIALIDAD','RELACION_INTEGRIDAD','RELACION_DISPONIBILIDAD')
 ORDER BY column_name;

SELECT column_name, data_type, data_default, nullable
  FROM user_tab_columns
 WHERE table_name = 'CONTROL' AND column_name = 'PESO';

-- Confirma que ya no quedan las columnas viejas
SELECT column_name
  FROM user_tab_columns
 WHERE table_name = 'PROCESO'
   AND column_name IN ('TIPO','POSIBLE_CONFIDENCIALIDAD','POSIBLE_INTEGRIDAD','POSIBLE_DISPONIBILIDAD');
-- Esta última consulta debe devolver 0 filas (\"no rows selected\").

SELECT codigo, peso FROM control WHERE ROWNUM <= 5;
