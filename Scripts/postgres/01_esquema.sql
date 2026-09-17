-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Script de creación del esquema — PUERTO A POSTGRESQL de Scripts/01_esquema.sql
--
-- Consolidado desde el estado ACTUAL de Oracle (01_esquema.sql ya fusionado
-- con las migraciones 10_administrador_manual, 11_evidencia_archivo,
-- 13_perfil_usuario y 16_asistente_consulta, más 14_multinorma.sql y
-- 15_cobit_capacidad.sql aplicados desde el principio, no como ALTER
-- posteriores). Se creó así a propósito, igual que el propio 01_esquema.sql
-- de Oracle explica en su cabecera: para quien monta el proyecto de cero no
-- tiene sentido reproducir la secuencia histórica de migraciones.
--
-- Cambios de tipo, mecánicos y consistentes en todo el archivo:
--   NUMBER GENERATED ALWAYS AS IDENTITY -> INTEGER/BIGINT GENERATED ALWAYS AS IDENTITY
--   VARCHAR2(n)  -> VARCHAR(n)
--   NUMBER(p,s)  -> NUMERIC(p,s)
--   NUMBER(1)    -> SMALLINT
--   CLOB         -> TEXT
--   BLOB         -> BYTEA
--   TIMESTAMP DEFAULT SYSTIMESTAMP -> TIMESTAMP DEFAULT now()
--   DATE         -> DATE
--   NVL(...)     -> COALESCE(...) (en la vista, más abajo)
--
-- 13 tablas + 1 vista. Orden de creación (respeta FK):
--   USUARIO, DOMINIO, ESTANDAR         (sin dependencias)
--   USUARIO_FOTO                       (USUARIO)
--   NIVEL_MADUREZ                      (ESTANDAR)
--   PROCESO                            (DOMINIO)
--   CONTROL                            (PROCESO)
--   AUDITORIA                          (USUARIO, ESTANDAR)
--   EVALUACION_CONTROL                 (AUDITORIA, CONTROL)
--   EVALUACION_OBJETIVO                (AUDITORIA, PROCESO)
--   EVIDENCIA_ARCHIVO                  (EVALUACION_CONTROL)
--   RESULTADO_RIESGO                   (AUDITORIA)
--   REMEDIACION                        (EVALUACION_CONTROL, AUDITORIA)
--   ASISTENTE_CONSULTA                 (USUARIO)
--   V_AUDITORIA_ENTREVISTADO (vista)
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
    descripcion      VARCHAR(500),
    fecha_creacion   TIMESTAMP     DEFAULT now() NOT NULL,
    CONSTRAINT uq_usuario_correo UNIQUE (correo),
    CONSTRAINT ck_usuario_rol    CHECK (rol IN ('AUDITOR', 'ADMIN_BD')),
    CONSTRAINT ck_usuario_activo CHECK (activo IN (0, 1))
);

-- ── USUARIO_FOTO ─────────────────────────────────────────────────────────
CREATE TABLE usuario_foto (
    id_usuario_foto  INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario       INTEGER        NOT NULL,
    nombre           VARCHAR(255)   NOT NULL,
    tipo_mime        VARCHAR(100)   NOT NULL,
    tamano_bytes     INTEGER        NOT NULL,
    contenido        BYTEA          NOT NULL,
    fecha_carga      TIMESTAMP      DEFAULT now() NOT NULL,
    CONSTRAINT fk_usufoto_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT uq_usufoto_usuario UNIQUE (id_usuario),
    CONSTRAINT ck_usufoto_tipo
        CHECK (tipo_mime IN ('image/png', 'image/jpeg', 'image/webp', 'image/gif')),
    CONSTRAINT ck_usufoto_tamano
        CHECK (tamano_bytes BETWEEN 1 AND 2097152)
);

-- ── ESTANDAR ─────────────────────────────────────────────────────────────
-- modo_evaluacion nace ya en la tabla (en Oracle llegó por ALTER en el 15):
-- 'CONTROL' (ISO: Sí/No/No aplica + madurez por control) u 'OBJETIVO'
-- (COBIT: capacidad declarada por objetivo).
CREATE TABLE estandar (
    codigo           VARCHAR(20)   PRIMARY KEY,
    nombre           VARCHAR(200)  NOT NULL,
    version          VARCHAR(20)   NOT NULL,
    organismo        VARCHAR(150)  NOT NULL,
    escala_niveles   SMALLINT      NOT NULL,
    orden            SMALLINT      DEFAULT 0 NOT NULL,
    modo_evaluacion  VARCHAR(10)   DEFAULT 'CONTROL' NOT NULL,
    CONSTRAINT ck_estandar_escala CHECK (escala_niveles BETWEEN 2 AND 10),
    CONSTRAINT ck_estandar_modo   CHECK (modo_evaluacion IN ('CONTROL', 'OBJETIVO'))
);

-- ── NIVEL_MADUREZ ────────────────────────────────────────────────────────
CREATE TABLE nivel_madurez (
    codigo_estandar  VARCHAR(20)   NOT NULL,
    nivel            SMALLINT      NOT NULL,
    nombre           VARCHAR(60)   NOT NULL,
    descripcion      VARCHAR(500)  NOT NULL,
    CONSTRAINT pk_nivel_madurez PRIMARY KEY (codigo_estandar, nivel),
    CONSTRAINT fk_nivel_estandar
        FOREIGN KEY (codigo_estandar) REFERENCES estandar (codigo)
);

-- ── DOMINIO ──────────────────────────────────────────────────────────────
CREATE TABLE dominio (
    clave            VARCHAR(20)   PRIMARY KEY,
    codigo_estandar  VARCHAR(20)   DEFAULT 'ISO27002' NOT NULL,
    nombre           VARCHAR(100)  NOT NULL,
    nombre_corto     VARCHAR(30)   NOT NULL,
    descripcion      VARCHAR(500),
    orden            SMALLINT      DEFAULT 0 NOT NULL,
    CONSTRAINT fk_dominio_estandar
        FOREIGN KEY (codigo_estandar) REFERENCES estandar (codigo)
);

CREATE INDEX ix_dominio_estandar ON dominio (codigo_estandar);

-- ── PROCESO ──────────────────────────────────────────────────────────────
CREATE TABLE proceso (
    numero                      SMALLINT      PRIMARY KEY,
    clave_dominio               VARCHAR(20)   NOT NULL,
    nombre                       VARCHAR(200)  NOT NULL,
    ancla                        VARCHAR(300),
    orden                        SMALLINT      DEFAULT 0 NOT NULL,
    relacion_confidencialidad    VARCHAR(1),
    relacion_integridad          VARCHAR(1),
    relacion_disponibilidad      VARCHAR(1),
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
    id_auditoria                INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditor                  INTEGER        NOT NULL,
    id_administrador_bd         INTEGER,
    administrador_nombre        VARCHAR(150),
    administrador_organizacion  VARCHAR(200),
    codigo_estandar             VARCHAR(20)    DEFAULT 'ISO27002' NOT NULL,
    area_evaluada               VARCHAR(200)   NOT NULL,
    fecha                       DATE           NOT NULL,
    estado                      VARCHAR(20)    NOT NULL,
    fecha_creacion              TIMESTAMP      DEFAULT now() NOT NULL,
    fecha_finalizacion          TIMESTAMP,
    indice_general_riesgo       NUMERIC(5,2),
    CONSTRAINT fk_auditoria_auditor
        FOREIGN KEY (id_auditor) REFERENCES usuario (id_usuario),
    CONSTRAINT fk_auditoria_administrador_bd
        FOREIGN KEY (id_administrador_bd) REFERENCES usuario (id_usuario),
    CONSTRAINT fk_auditoria_estandar
        FOREIGN KEY (codigo_estandar) REFERENCES estandar (codigo),
    CONSTRAINT ck_auditoria_estado
        CHECK (estado IN ('EN_PROGRESO', 'FINALIZADA')),
    CONSTRAINT ck_auditoria_administrador
        CHECK (
            (id_administrador_bd IS NOT NULL
             AND administrador_nombre IS NULL
             AND administrador_organizacion IS NULL)
         OR (id_administrador_bd IS NULL
             AND administrador_nombre IS NOT NULL
             AND administrador_organizacion IS NOT NULL)
        )
);

CREATE INDEX ix_auditoria_estandar ON auditoria (codigo_estandar);

-- ── EVALUACION_CONTROL ───────────────────────────────────────────────────
-- grado_logro: N/P/L/F (ISO/IEC 33020, usado por COBIT). Nulo en ISO.
CREATE TABLE evaluacion_control (
    id_evaluacion_control    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditoria             INTEGER       NOT NULL,
    codigo_control           VARCHAR(6)    NOT NULL,
    pregunta_personalizada   TEXT,
    estado                   VARCHAR(5),
    madurez                  SMALLINT,
    criterio                 VARCHAR(20),
    grado_logro              VARCHAR(1),
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
    CONSTRAINT ck_evalctrl_grado_logro
        CHECK (grado_logro IN ('N', 'P', 'L', 'F')),
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

-- ── EVALUACION_OBJETIVO ──────────────────────────────────────────────────
-- Captura COBIT: capacidad 0-5 declarada por objetivo (proceso), con
-- justificación. Paralelo a evaluacion_control.madurez para ISO.
CREATE TABLE evaluacion_objetivo (
    id_auditoria        INTEGER       NOT NULL,
    numero_proceso      SMALLINT      NOT NULL,
    capacidad           SMALLINT      NOT NULL,
    justificacion       VARCHAR(2000) NOT NULL,
    fecha_actualizacion TIMESTAMP     DEFAULT now() NOT NULL,
    CONSTRAINT pk_evaluacion_objetivo PRIMARY KEY (id_auditoria, numero_proceso),
    CONSTRAINT fk_evalobj_auditoria
        FOREIGN KEY (id_auditoria) REFERENCES auditoria (id_auditoria),
    CONSTRAINT fk_evalobj_proceso
        FOREIGN KEY (numero_proceso) REFERENCES proceso (numero),
    CONSTRAINT ck_evalobj_capacidad CHECK (capacidad BETWEEN 0 AND 5)
);

CREATE INDEX ix_evalobj_proceso ON evaluacion_objetivo (numero_proceso);

-- ── EVIDENCIA_ARCHIVO ────────────────────────────────────────────────────
CREATE TABLE evidencia_archivo (
    id_evidencia_archivo   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_evaluacion_control  INTEGER        NOT NULL,
    nombre                 VARCHAR(255)   NOT NULL,
    tipo_mime              VARCHAR(100)   NOT NULL,
    tamano_bytes           INTEGER        NOT NULL,
    contenido              BYTEA          NOT NULL,
    fecha_carga            TIMESTAMP      DEFAULT now() NOT NULL,
    CONSTRAINT fk_evidarch_evalctrl
        FOREIGN KEY (id_evaluacion_control)
        REFERENCES evaluacion_control (id_evaluacion_control) ON DELETE CASCADE,
    CONSTRAINT uq_evidarch_evalctrl UNIQUE (id_evaluacion_control),
    CONSTRAINT ck_evidarch_tipo
        CHECK (tipo_mime IN ('image/png', 'image/jpeg', 'image/webp',
                             'image/gif', 'application/pdf')),
    CONSTRAINT ck_evidarch_tamano
        CHECK (tamano_bytes BETWEEN 1 AND 5242880)
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

-- ── ASISTENTE_CONSULTA ───────────────────────────────────────────────────
CREATE TABLE asistente_consulta (
    id_asistente_consulta  INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario             INTEGER        NOT NULL,
    fecha                  TIMESTAMP      DEFAULT now() NOT NULL,
    pantalla               VARCHAR(300),
    herramientas           VARCHAR(500),
    tokens_entrada         INTEGER        DEFAULT 0 NOT NULL,
    tokens_salida          INTEGER        DEFAULT 0 NOT NULL,
    resultado              VARCHAR(20)    NOT NULL,
    CONSTRAINT fk_asiscons_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT ck_asiscons_resultado
        CHECK (resultado IN ('RESPONDIDA', 'RECHAZADA', 'ERROR')),
    CONSTRAINT ck_asiscons_tokens
        CHECK (tokens_entrada >= 0 AND tokens_salida >= 0)
);

CREATE INDEX ix_asiscons_usuario_fecha ON asistente_consulta (id_usuario, fecha);
CREATE INDEX ix_asiscons_fecha ON asistente_consulta (fecha);

-- ── V_AUDITORIA_ENTREVISTADO ─────────────────────────────────────────────
-- NVL de Oracle -> COALESCE, sintaxis estándar que Postgres también soporta.
CREATE OR REPLACE VIEW v_auditoria_entrevistado AS
SELECT a.id_auditoria,
       COALESCE(dba.nombre,       a.administrador_nombre)       AS nombre_administrador_bd,
       COALESCE(dba.organizacion, a.administrador_organizacion) AS organizacion,
       CASE WHEN a.id_administrador_bd IS NULL THEN 1 ELSE 0 END AS es_manual
  FROM auditoria a
  LEFT JOIN usuario dba ON dba.id_usuario = a.id_administrador_bd;
