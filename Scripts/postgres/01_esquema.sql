-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Script de creación del esquema — PUERTO A POSTGRESQL de Scripts/01_esquema.sql
--
-- Traducción directa, tabla por tabla, del esquema original de Oracle. Los
-- cambios de tipo son mecánicos:
--   NUMBER GENERATED ALWAYS AS IDENTITY -> INTEGER GENERATED ALWAYS AS IDENTITY
--     (Postgres 10+ soporta la misma cláusula IDENTITY; solo cambia el tipo
--     base, porque Postgres no tiene un NUMBER genérico).
--   VARCHAR2(n)  -> VARCHAR(n)
--   NUMBER(p,s)  -> NUMERIC(p,s)
--   NUMBER(1)    -> SMALLINT (para los indicadores 0/1 y NUMBER(1) de madurez)
--   CLOB         -> TEXT (Postgres no distingue CLOB de VARCHAR sin límite)
--   TIMESTAMP DEFAULT SYSTIMESTAMP -> TIMESTAMP DEFAULT now()
--   DATE         -> DATE (igual; Postgres también lo trata sin componente hora)
--
-- Las restricciones (PRIMARY KEY, FOREIGN KEY, UNIQUE, CHECK) son sintaxis
-- estándar y no cambiaron una sola línea.
-- ============================================================================

-- ── USUARIO ──────────────────────────────────────────────────────────────
CREATE TABLE usuario (
    id_usuario       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre           VARCHAR(150)  NOT NULL,
    correo           VARCHAR(150)  NOT NULL,
    contrasena_hash  VARCHAR(255)  NOT NULL,
    rol              VARCHAR(20)   NOT NULL,
    organizacion     VARCHAR(200)  NOT NULL,
    activo           SMALLINT      DEFAULT 1 NOT NULL,
    fecha_creacion   TIMESTAMP     DEFAULT now() NOT NULL,
    CONSTRAINT uq_usuario_correo UNIQUE (correo),
    CONSTRAINT ck_usuario_rol    CHECK (rol IN ('AUDITOR', 'ADMIN_BD')),
    CONSTRAINT ck_usuario_activo CHECK (activo IN (0, 1))
);

-- ── DOMINIO ──────────────────────────────────────────────────────────────
CREATE TABLE dominio (
    clave         VARCHAR(20)   PRIMARY KEY,
    nombre        VARCHAR(100)  NOT NULL,
    nombre_corto  VARCHAR(30)   NOT NULL,
    descripcion   VARCHAR(500),
    orden         SMALLINT      DEFAULT 0 NOT NULL
);

-- ── PROCESO ──────────────────────────────────────────────────────────────
CREATE TABLE proceso (
    numero                      SMALLINT      PRIMARY KEY,
    clave_dominio               VARCHAR(20)   NOT NULL,
    nombre                      VARCHAR(200)  NOT NULL,
    ancla                       VARCHAR(300),
    orden                       SMALLINT      DEFAULT 0 NOT NULL,
    relacion_confidencialidad   VARCHAR(1),
    relacion_integridad         VARCHAR(1),
    relacion_disponibilidad     VARCHAR(1),
    CONSTRAINT fk_proceso_dominio
        FOREIGN KEY (clave_dominio) REFERENCES dominio (clave),
    CONSTRAINT ck_proceso_rel_confidencialidad
        CHECK (relacion_confidencialidad IN ('P', 'S')),
    CONSTRAINT ck_proceso_rel_integridad
        CHECK (relacion_integridad IN ('P', 'S')),
    CONSTRAINT ck_proceso_rel_disponibilidad
        CHECK (relacion_disponibilidad IN ('P', 'S'))
);

-- ── CONTROL ──────────────────────────────────────────────────────────────
CREATE TABLE control (
    codigo              VARCHAR(6)    PRIMARY KEY,
    numero_proceso      SMALLINT      NOT NULL,
    referencia_iso      VARCHAR(100)  NOT NULL,
    enunciado           TEXT          NOT NULL,
    evidencia_esperada  TEXT,
    pregunta            TEXT          NOT NULL,
    peso                VARCHAR(10)   DEFAULT 'MEDIA' NOT NULL,
    CONSTRAINT fk_control_proceso
        FOREIGN KEY (numero_proceso) REFERENCES proceso (numero),
    CONSTRAINT ck_control_peso
        CHECK (peso IN ('ALTA', 'MEDIA', 'BAJA'))
);

-- ── AUDITORIA ────────────────────────────────────────────────────────────
CREATE TABLE auditoria (
    id_auditoria            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditor              INTEGER       NOT NULL,
    id_administrador_bd     INTEGER       NOT NULL,
    area_evaluada           VARCHAR(200)  NOT NULL,
    fecha                   DATE          NOT NULL,
    estado                  VARCHAR(20)   NOT NULL,
    fecha_creacion          TIMESTAMP     DEFAULT now() NOT NULL,
    fecha_finalizacion      TIMESTAMP,
    indice_general_riesgo   NUMERIC(5,2),
    CONSTRAINT fk_auditoria_auditor
        FOREIGN KEY (id_auditor) REFERENCES usuario (id_usuario),
    CONSTRAINT fk_auditoria_administrador_bd
        FOREIGN KEY (id_administrador_bd) REFERENCES usuario (id_usuario),
    CONSTRAINT ck_auditoria_estado
        CHECK (estado IN ('EN_PROGRESO', 'FINALIZADA'))
);

-- ── EVALUACION_CONTROL ───────────────────────────────────────────────────
CREATE TABLE evaluacion_control (
    id_evaluacion_control    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditoria             INTEGER       NOT NULL,
    codigo_control           VARCHAR(6)    NOT NULL,
    pregunta_personalizada   TEXT,
    estado                   VARCHAR(5),
    madurez                  SMALLINT,
    criterio                 VARCHAR(20),
    afecta_integridad        SMALLINT      DEFAULT 0,
    afecta_confidencialidad  SMALLINT      DEFAULT 0,
    afecta_disponibilidad    SMALLINT      DEFAULT 0,
    impacto                  SMALLINT,
    probabilidad             SMALLINT,
    nivel_riesgo             NUMERIC(4,2),
    hallazgo                 TEXT,
    recomendacion            TEXT,
    evidencia_verificada     TEXT,
    calidad_evidencia        VARCHAR(20),
    CONSTRAINT fk_evalctrl_auditoria
        FOREIGN KEY (id_auditoria) REFERENCES auditoria (id_auditoria),
    CONSTRAINT fk_evalctrl_control
        FOREIGN KEY (codigo_control) REFERENCES control (codigo),
    CONSTRAINT uq_evalctrl_auditoria_control
        UNIQUE (id_auditoria, codigo_control),
    CONSTRAINT ck_evalctrl_estado
        CHECK (estado IN ('SI', 'NO', 'NA')),
    CONSTRAINT ck_evalctrl_madurez
        CHECK (madurez BETWEEN 0 AND 5),
    CONSTRAINT ck_evalctrl_criterio
        CHECK (criterio IN ('DOCUMENTADO', 'REPETIBLE', 'EVIDENCIA')),
    CONSTRAINT ck_evalctrl_afecta_integridad
        CHECK (afecta_integridad IN (0, 1)),
    CONSTRAINT ck_evalctrl_afecta_confidencialidad
        CHECK (afecta_confidencialidad IN (0, 1)),
    CONSTRAINT ck_evalctrl_afecta_disponibilidad
        CHECK (afecta_disponibilidad IN (0, 1)),
    CONSTRAINT ck_evalctrl_impacto
        CHECK (impacto BETWEEN 1 AND 5),
    CONSTRAINT ck_evalctrl_probabilidad
        CHECK (probabilidad BETWEEN 1 AND 5),
    CONSTRAINT ck_evalctrl_calidad_evidencia
        CHECK (calidad_evidencia IN ('BIEN_IMPLEMENTADO', 'REQUIERE_MEJORA', 'DECLARATIVO')),
    CONSTRAINT ck_evalctrl_evidencia_si
        CHECK (estado != 'SI' OR (evidencia_verificada IS NOT NULL AND calidad_evidencia IS NOT NULL))
);

-- ── RESULTADO_RIESGO ─────────────────────────────────────────────────────
CREATE TABLE resultado_riesgo (
    id_resultado_riesgo  INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditoria         INTEGER       NOT NULL,
    tipo_riesgo          VARCHAR(20)   NOT NULL,
    promedio_madurez     NUMERIC(4,3),
    zona                 VARCHAR(10),
    fecha_calculo        TIMESTAMP     DEFAULT now(),
    CONSTRAINT fk_resriesgo_auditoria
        FOREIGN KEY (id_auditoria) REFERENCES auditoria (id_auditoria),
    CONSTRAINT uq_resriesgo_auditoria_tipo
        UNIQUE (id_auditoria, tipo_riesgo),
    CONSTRAINT ck_resriesgo_tipo
        CHECK (tipo_riesgo IN ('CONFIDENCIALIDAD', 'INTEGRIDAD', 'DISPONIBILIDAD')),
    CONSTRAINT ck_resriesgo_madurez
        CHECK (promedio_madurez BETWEEN 0 AND 1),
    CONSTRAINT ck_resriesgo_zona
        CHECK (zona IN ('ROJO', 'AMARILLO', 'VERDE'))
);

-- ── REMEDIACION ──────────────────────────────────────────────────────────
CREATE TABLE remediacion (
    id_remediacion            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_evaluacion_control     INTEGER       NOT NULL,
    fecha_limite              DATE          NOT NULL,
    estado                    VARCHAR(20)   DEFAULT 'PENDIENTE' NOT NULL,
    responsable               VARCHAR(150),
    id_auditoria_reauditoria  INTEGER,
    fecha_creacion            TIMESTAMP     DEFAULT now() NOT NULL,
    CONSTRAINT fk_remediacion_evalctrl
        FOREIGN KEY (id_evaluacion_control) REFERENCES evaluacion_control (id_evaluacion_control),
    CONSTRAINT fk_remediacion_reauditoria
        FOREIGN KEY (id_auditoria_reauditoria) REFERENCES auditoria (id_auditoria),
    CONSTRAINT ck_remediacion_estado
        CHECK (estado IN ('PENDIENTE', 'EN_PROCESO', 'CUMPLIDO', 'VENCIDO'))
);
