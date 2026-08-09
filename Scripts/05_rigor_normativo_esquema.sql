-- ============================================================================
-- EIF402 · becajo — Rama feature/rigor-normativo
-- Migración 05 — Fase B: cambios de esquema para los puntos 15 a 19
-- Preparado por Persona 2
--
-- A diferencia de 01_esquema.sql (creación desde cero), este script se corre
-- SOBRE una base de datos que YA tiene datos (tu instancia de prueba en
-- Docker). Por eso todo es ALTER TABLE, no DROP/CREATE, y por eso incluye un
-- backfill antes de activar la restricción del punto 15.
--
-- Ejecutar una sola vez, en orden, sobre tu esquema ya existente.
-- ============================================================================

-- ============================================================================
-- PUNTO 16 — Ponderación de controles
-- ============================================================================
-- Reutiliza la misma lógica de escala 0-5 que ya usa madurez, para no
-- introducir una escala nueva que alguien tenga que aprender: 1=Bajo,
-- 2=Medio, 3=Alto, 4=Crítico, 5=Crítico máximo (la UI mapea el número a una
-- etiqueta; la base solo guarda el número, que es lo que necesita el cálculo
-- ponderado).
ALTER TABLE control ADD (
    peso NUMBER(2) DEFAULT 1 NOT NULL
);
ALTER TABLE control ADD CONSTRAINT ck_control_peso CHECK (peso BETWEEN 1 AND 5);

COMMENT ON COLUMN control.peso IS
    'Importancia del control (1=Bajo .. 5=Crítico). Usado como peso en el promedio ponderado del cálculo de riesgo.';


-- ============================================================================
-- PUNTO 17 — Procesos primarios/secundarios y su relación declarada con C-I-D
-- ============================================================================
-- 'tipo' y los tres 'posible_*' son DECLARATIVOS, no restrictivos: no
-- reemplazan el afecta_confidencialidad/integridad/disponibilidad de
-- EVALUACION_CONTROL (que sigue siendo lo que el auditor marca en vivo, por
-- decisión ya tomada). Son la referencia de catálogo — "este proceso
-- típicamente puede tocar estas dimensiones" — para la matriz/mapa visual.
ALTER TABLE proceso ADD (
    tipo                     VARCHAR2(10) DEFAULT 'PRIMARIO' NOT NULL,
    posible_confidencialidad NUMBER(1)    DEFAULT 0 NOT NULL,
    posible_integridad       NUMBER(1)    DEFAULT 0 NOT NULL,
    posible_disponibilidad   NUMBER(1)    DEFAULT 0 NOT NULL
);
ALTER TABLE proceso ADD CONSTRAINT ck_proceso_tipo
    CHECK (tipo IN ('PRIMARIO', 'SECUNDARIO'));
ALTER TABLE proceso ADD CONSTRAINT ck_proceso_posible_c
    CHECK (posible_confidencialidad IN (0, 1));
ALTER TABLE proceso ADD CONSTRAINT ck_proceso_posible_i
    CHECK (posible_integridad IN (0, 1));
ALTER TABLE proceso ADD CONSTRAINT ck_proceso_posible_d
    CHECK (posible_disponibilidad IN (0, 1));

COMMENT ON COLUMN proceso.tipo IS
    'PRIMARIO = proceso central de la administración de BD; SECUNDARIO = proceso de apoyo.';
COMMENT ON COLUMN proceso.ancla IS
    'Referencia normativa del proceso (ya existía). Junto con control.referencia_iso, cubre "declarar la norma de referencia de cada ítem" del punto 17.';


-- ============================================================================
-- PUNTO 15 — Evidencia obligatoria, aunque la respuesta sea "cumple"
-- ============================================================================
ALTER TABLE evaluacion_control ADD (
    evidencia_verificada CLOB,
    calidad_evidencia    VARCHAR2(20)
);
ALTER TABLE evaluacion_control ADD CONSTRAINT ck_evalctrl_calidad_evidencia
    CHECK (calidad_evidencia IN ('BIEN_IMPLEMENTADO', 'REQUIERE_MEJORA', 'DECLARATIVO'));

COMMENT ON COLUMN evaluacion_control.evidencia_verificada IS
    'Descripción concreta de la evidencia revisada (documento, log, captura, config). Distinto de "hallazgo", que es la nota general de la verificación.';
COMMENT ON COLUMN evaluacion_control.calidad_evidencia IS
    'BIEN_IMPLEMENTADO / REQUIERE_MEJORA / DECLARATIVO — exigido por ISO/IEC 27007: la conformidad se determina contra evidencia, no contra la afirmación del auditado.';

-- Backfill: las evaluaciones que YA existen con estado SI pero sin evidencia
-- (como tu C-001 de prueba) necesitan un valor antes de poder activar la
-- restricción de abajo, o Oracle la rechaza con ORA-02293.
UPDATE evaluacion_control
   SET evidencia_verificada = '[Migración] Evidencia pendiente de registrar — evaluación anterior a la regla de evidencia obligatoria.',
       calidad_evidencia    = 'DECLARATIVO'
 WHERE estado = 'SI'
   AND evidencia_verificada IS NULL;

-- A partir de aquí, toda evaluación nueva con estado 'SI' está obligada a
-- traer evidencia y su clasificación de calidad.
ALTER TABLE evaluacion_control ADD CONSTRAINT ck_evalctrl_evidencia_si
    CHECK (estado != 'SI' OR (evidencia_verificada IS NOT NULL AND calidad_evidencia IS NOT NULL));

COMMIT;


-- ============================================================================
-- PUNTO 19 — Plazos de remediación y re-auditoría (ciclo PHVA, ISO/IEC 27001 cl. 10)
-- ============================================================================
-- Una remediación cuelga de UNA evaluación de control puntual (el hallazgo
-- concreto que hay que corregir), no de la auditoría completa. Cuando se
-- programa la re-auditoría, id_auditoria_reauditoria enlaza hacia la nueva
-- fila de AUDITORIA que se crea para verificar si se corrigió.
CREATE TABLE remediacion (
    id_remediacion            NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_evaluacion_control     NUMBER        NOT NULL,
    fecha_limite              DATE          NOT NULL,
    estado                    VARCHAR2(20)  DEFAULT 'PENDIENTE' NOT NULL,
    responsable                VARCHAR2(150),
    id_auditoria_reauditoria  NUMBER,
    fecha_creacion            TIMESTAMP     DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_remediacion_evalctrl
        FOREIGN KEY (id_evaluacion_control) REFERENCES evaluacion_control (id_evaluacion_control),
    CONSTRAINT fk_remediacion_reauditoria
        FOREIGN KEY (id_auditoria_reauditoria) REFERENCES auditoria (id_auditoria),
    CONSTRAINT ck_remediacion_estado
        CHECK (estado IN ('PENDIENTE', 'EN_PROCESO', 'CUMPLIDO', 'VENCIDO'))
);

COMMENT ON TABLE remediacion IS
    'Plazo de corrección para un hallazgo puntual, con re-auditoría programable al vencimiento (punto 19, ciclo PHVA).';

COMMIT;


-- ============================================================================
-- Verificación rápida (no falla el script si algo no cuadra, solo informa)
-- ============================================================================
PROMPT
PROMPT ===== Verificación de la migración =====
SELECT column_name, data_type, nullable
  FROM user_tab_columns
 WHERE table_name = 'CONTROL' AND column_name = 'PESO';

SELECT column_name, data_type, nullable
  FROM user_tab_columns
 WHERE table_name = 'PROCESO' AND column_name IN ('TIPO','POSIBLE_CONFIDENCIALIDAD','POSIBLE_INTEGRIDAD','POSIBLE_DISPONIBILIDAD')
 ORDER BY column_name;

SELECT column_name, data_type, nullable
  FROM user_tab_columns
 WHERE table_name = 'EVALUACION_CONTROL' AND column_name IN ('EVIDENCIA_VERIFICADA','CALIDAD_EVIDENCIA')
 ORDER BY column_name;

SELECT table_name FROM user_tables WHERE table_name = 'REMEDIACION';

SELECT codigo_control, estado, evidencia_verificada, calidad_evidencia
  FROM evaluacion_control
 WHERE estado = 'SI';
