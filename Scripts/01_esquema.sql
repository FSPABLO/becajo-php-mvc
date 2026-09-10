-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Script de creación del esquema (Oracle 21c+)
-- Preparado por Persona 2 — versión consolidada final
--
-- Reemplaza a los antiguos 01_esquema.sql + 05_rigor_normativo_esquema.sql +
-- 06_correccion_p17_p16.sql: aquellos tres eran pasos de una MIGRACIÓN sobre
-- una base de datos que ya existía (por eso usaban ALTER TABLE, con
-- backfill incluido). Este script crea el esquema completo desde cero, en
-- su estado final, para quien monta el proyecto por primera vez.
--
-- 9 tablas. Decisiones que aplican en esta versión:
--   - No existe tabla ORGANIZACION: la afiliación institucional es el campo
--     de texto usuario.organizacion.
--   - resultado_riesgo.zona es VARCHAR2 con CHECK ('ROJO','AMARILLO','VERDE')
--     — no se modela como tabla aparte (se evaluó y se descartó).
--   - evaluacion_control incluye impacto, probabilidad, nivel_riesgo y
--     pregunta_personalizada (el auditor puede editar el texto de la
--     pregunta para una evaluación puntual).
--   - Un control puede tener varias dimensiones de riesgo (C/I/D) marcadas a
--     la vez, y pueden variar de una evaluación a otra del mismo control.
--   - control.peso (ALTA/MEDIA/BAJA) y proceso.relacion_confidencialidad /
--     relacion_integridad / relacion_disponibilidad (P/S) usan la misma
--     notación que COBIT 4.1 (Apéndice II): importancia relativa y relación
--     primaria/secundaria con cada criterio de información.
--   - evaluacion_control.evidencia_verificada y calidad_evidencia son
--     obligatorias cuando estado='SI': la conformidad se prueba con
--     evidencia, no con la afirmación del auditado (ISO/IEC 27007).
--   - remediacion registra el plazo de corrección de un hallazgo puntual,
--     con re-auditoría programable al vencimiento (ciclo PHVA — ISO 9001
--     §8.5.2 / ISO-IEC 27001, cláusula 10).
--
-- Orden de creación (respeta las dependencias de llave foránea):
--   USUARIO, DOMINIO           (sin dependencias)
--   PROCESO                    (depende de DOMINIO)
--   CONTROL                    (depende de PROCESO)
--   AUDITORIA                  (depende de USUARIO)
--   EVALUACION_CONTROL         (depende de AUDITORIA, CONTROL)
--   EVIDENCIA_ARCHIVO          (depende de EVALUACION_CONTROL)
--   RESULTADO_RIESGO           (depende de AUDITORIA)
--   REMEDIACION                (depende de EVALUACION_CONTROL, AUDITORIA)
-- ============================================================================

-- ── USUARIO ──────────────────────────────────────────────────────────────
-- rol ADMIN_BD = super admin del sistema (además de figurar en las auditorías
-- como administrador de BD entrevistado). organizacion identifica la empresa
-- a la que pertenece el usuario (reemplaza la antigua tabla ORGANIZACION).
CREATE TABLE usuario (
    id_usuario       NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre           VARCHAR2(150)  NOT NULL,
    correo           VARCHAR2(150)  NOT NULL,
    contrasena_hash  VARCHAR2(255)  NOT NULL,
    rol              VARCHAR2(20)   NOT NULL,
    organizacion     VARCHAR2(200)  NOT NULL,
    activo           NUMBER(1)      DEFAULT 1 NOT NULL,
    fecha_creacion   TIMESTAMP      DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT uq_usuario_correo UNIQUE (correo),
    CONSTRAINT ck_usuario_rol    CHECK (rol IN ('AUDITOR', 'ADMIN_BD')),
    CONSTRAINT ck_usuario_activo CHECK (activo IN (0, 1))
);

-- ── DOMINIO ──────────────────────────────────────────────────────────────
-- clave: identificador natural corto, el mismo que usa el frontend
-- (config/instrumento-bd.php) para las pestañas: 'gobierno', 'accesos', etc.
-- orden: posición de presentación (no se deduce de 'clave', que daría un
-- listado alfabético distinto del orden real del instrumento).
CREATE TABLE dominio (
    clave         VARCHAR2(20)   PRIMARY KEY,
    nombre        VARCHAR2(100)  NOT NULL,
    nombre_corto  VARCHAR2(30)   NOT NULL,
    descripcion   VARCHAR2(500),
    orden         NUMBER(3)      DEFAULT 0 NOT NULL
);

-- ── PROCESO ──────────────────────────────────────────────────────────────
-- numero: se conserva el número original del catálogo (1-25), no el orden
-- de presentación, para trazabilidad con el marco de referencia de Persona 1.
-- relacion_confidencialidad/integridad/disponibilidad: 'P' (relación
-- primaria) / 'S' (relación secundaria) / NULL (sin relación relevante) —
-- notación de COBIT 4.1, Apéndice II. Es la relación DECLARADA del proceso
-- en el catálogo; no reemplaza lo que el auditor marca en cada evaluación
-- puntual (evaluacion_control.afecta_*), que sigue siendo lo que se usa
-- para calcular el riesgo.
CREATE TABLE proceso (
    numero                      NUMBER(3)      PRIMARY KEY,
    clave_dominio                VARCHAR2(20)   NOT NULL,
    nombre                       VARCHAR2(200)  NOT NULL,
    ancla                        VARCHAR2(300),
    orden                        NUMBER(3)      DEFAULT 0 NOT NULL,
    relacion_confidencialidad    VARCHAR2(1),
    relacion_integridad          VARCHAR2(1),
    relacion_disponibilidad      VARCHAR2(1),
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
-- codigo: identificador natural del catálogo (C-001 ... C-075). Una sola
-- pregunta por control por defecto; el auditor puede sobrescribirla por
-- evaluación (ver EVALUACION_CONTROL.pregunta_personalizada).
-- peso: importancia relativa (COBIT 4.1) usada en el promedio ponderado del
-- cálculo de riesgo — un control ALTA pesa más que uno BAJA.
CREATE TABLE control (
    codigo              VARCHAR2(6)    PRIMARY KEY,
    numero_proceso      NUMBER(3)      NOT NULL,
    referencia_iso      VARCHAR2(100)  NOT NULL,
    enunciado           CLOB           NOT NULL,
    evidencia_esperada  CLOB,
    pregunta            CLOB           NOT NULL,
    peso                VARCHAR2(10)   DEFAULT 'MEDIA' NOT NULL,
    CONSTRAINT fk_control_proceso
        FOREIGN KEY (numero_proceso) REFERENCES proceso (numero),
    CONSTRAINT ck_control_peso
        CHECK (peso IN ('ALTA', 'MEDIA', 'BAJA'))
);

-- ── AUDITORIA ────────────────────────────────────────────────────────────
-- Sin referencia a ninguna tabla de organizaciones: la organización auditada
-- se identifica a través del entrevistado, y hay DOS formas de identificarlo:
--
--   a) id_administrador_bd -> USUARIO (cuenta registrada con rol ADMIN_BD).
--      Es el caso normal y el único que existía antes.
--   b) administrador_nombre + administrador_organizacion, escritos a mano.
--      Un auditor entrevista a gente que no tiene —ni va a tener— cuenta en
--      el sistema; obligarlo a registrarla antes de abrir la auditoría era
--      pedirle que creara un usuario falso para poder trabajar.
--
-- ck_auditoria_administrador obliga a UNA de las dos, nunca las dos ni
-- ninguna: con la cuenta y el texto rellenos a la vez, «¿de quién es esta
-- auditoría?» tendría dos respuestas y quien lea la fila elegiría una.
-- Por eso el id ya no es NOT NULL — la restricción lo cubre mejor que la
-- columna, porque sabe de la alternativa.
CREATE TABLE auditoria (
    id_auditoria            NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditor              NUMBER         NOT NULL,
    id_administrador_bd     NUMBER,
    administrador_nombre       VARCHAR2(150),
    administrador_organizacion VARCHAR2(200),
    area_evaluada           VARCHAR2(200)  NOT NULL,
    fecha                   DATE           NOT NULL,
    estado                  VARCHAR2(20)   NOT NULL,
    fecha_creacion          TIMESTAMP      DEFAULT SYSTIMESTAMP NOT NULL,
    fecha_finalizacion      TIMESTAMP,
    indice_general_riesgo   NUMBER(5,2),
    CONSTRAINT fk_auditoria_auditor
        FOREIGN KEY (id_auditor) REFERENCES usuario (id_usuario),
    CONSTRAINT fk_auditoria_administrador_bd
        FOREIGN KEY (id_administrador_bd) REFERENCES usuario (id_usuario),
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

-- ── EVALUACION_CONTROL ───────────────────────────────────────────────────
-- estado NULL = control sin evaluar todavía (distinto de 'NA').
-- afecta_* vive aquí y no en CONTROL: pueden marcarse varias dimensiones a
-- la vez y variar según lo que encuentre el auditor en cada evaluación.
-- impacto/probabilidad/nivel_riesgo: nivel_riesgo = promedio de impacto y
-- probabilidad (metodología de la clase del 27/7).
-- evidencia_verificada/calidad_evidencia: obligatorias cuando estado='SI'
-- (ck_evalctrl_evidencia_si) — ISO/IEC 27007: la conformidad se determina
-- contra evidencia verificable, no contra la afirmación del auditado.
CREATE TABLE evaluacion_control (
    id_evaluacion_control    NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditoria             NUMBER        NOT NULL,
    codigo_control           VARCHAR2(6)   NOT NULL,
    pregunta_personalizada   CLOB,
    estado                   VARCHAR2(5),
    madurez                  NUMBER(1),
    criterio                 VARCHAR2(20),
    afecta_integridad        NUMBER(1)     DEFAULT 0,
    afecta_confidencialidad  NUMBER(1)     DEFAULT 0,
    afecta_disponibilidad    NUMBER(1)     DEFAULT 0,
    impacto                  NUMBER(2),
    probabilidad             NUMBER(2),
    nivel_riesgo             NUMBER(4,2),
    hallazgo                 CLOB,
    recomendacion            CLOB,
    evidencia_verificada     CLOB,
    calidad_evidencia        VARCHAR2(20),
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

-- ── EVIDENCIA_ARCHIVO ────────────────────────────────────────────────────
-- El adjunto de la evidencia: la captura, el PDF de la política, el log
-- exportado. evaluacion_control.evidencia_verificada sigue siendo la
-- DESCRIPCIÓN escrita por el auditor y no se sustituye — ISO/IEC 27007 pide
-- que quede constancia de qué se revisó, y un archivo sin una línea que diga
-- qué se miró en él obliga a abrirlo para saberlo.
--
-- Tabla APARTE y no una columna BLOB en evaluacion_control, por una razón
-- práctica: el repositorio lee esa tabla con SELECT * (las 75 evaluaciones de
-- una auditoría de una vez) y el driver trae los LOB ya materializados, así
-- que abrir el panel se llevaría por delante los 75 adjuntos. Aquí el binario
-- solo se toca cuando alguien pide el archivo.
--
-- UNIQUE sobre id_evaluacion_control: un adjunto por control evaluado. Si
-- mañana hacen falta varios, se cae esa restricción y no cambia nada más.
--
-- ON DELETE CASCADE: borrar la evaluación de un control se lleva su adjunto.
-- Sin eso, eliminarEvaluacion() dejaría binarios huérfanos que nadie puede
-- alcanzar ya desde ninguna pantalla.
CREATE TABLE evidencia_archivo (
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
    -- La lista blanca vive TAMBIÉN aquí, no solo en PHP: es el mismo criterio
    -- que el resto del esquema — la validación en PHP da el mensaje, la
    -- restricción es la última línea de defensa.
    CONSTRAINT ck_evidarch_tipo
        CHECK (tipo_mime IN ('image/png', 'image/jpeg', 'image/webp',
                             'image/gif', 'application/pdf')),
    CONSTRAINT ck_evidarch_tamano
        CHECK (tamano_bytes BETWEEN 1 AND 5242880)
);

-- ── RESULTADO_RIESGO ─────────────────────────────────────────────────────
-- promedio_madurez: promedio PONDERADO (por control.peso) de la madurez de
-- los controles que afectan cada dimensión, normalizado sobre 1 — no un
-- promedio simple. zona es texto con restricción CHECK (se evaluó modelarla
-- como tabla aparte y se descartó).
CREATE TABLE resultado_riesgo (
    id_resultado_riesgo  NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_auditoria         NUMBER         NOT NULL,
    tipo_riesgo          VARCHAR2(20)   NOT NULL,
    promedio_madurez     NUMBER(4,3),
    zona                 VARCHAR2(10),
    fecha_calculo        TIMESTAMP      DEFAULT SYSTIMESTAMP,
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
-- Cuelga de UNA evaluación de control puntual (el hallazgo concreto que hay
-- que corregir), no de la auditoría completa. id_auditoria_reauditoria
-- enlaza hacia la auditoría creada para verificar si se corrigió, cuando ya
-- se programó. El estado 'VENCIDO' se calcula al vuelo (comparando
-- fecha_limite contra la fecha actual) y no se guarda como tal — por eso no
-- hace falta un job que lo actualice.
CREATE TABLE remediacion (
    id_remediacion            NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_evaluacion_control     NUMBER        NOT NULL,
    fecha_limite              DATE          NOT NULL,
    estado                    VARCHAR2(20)  DEFAULT 'PENDIENTE' NOT NULL,
    responsable               VARCHAR2(150),
    id_auditoria_reauditoria  NUMBER,
    fecha_creacion            TIMESTAMP     DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_remediacion_evalctrl
        FOREIGN KEY (id_evaluacion_control) REFERENCES evaluacion_control (id_evaluacion_control),
    CONSTRAINT fk_remediacion_reauditoria
        FOREIGN KEY (id_auditoria_reauditoria) REFERENCES auditoria (id_auditoria),
    CONSTRAINT ck_remediacion_estado
        CHECK (estado IN ('PENDIENTE', 'EN_PROCESO', 'CUMPLIDO', 'VENCIDO'))
);

-- ── V_AUDITORIA_ENTREVISTADO ─────────────────────────────────────────────
-- La ÚNICA definición de «quién fue entrevistado y de qué empresa».
--
-- Con dos orígenes posibles (cuenta registrada o texto a mano), resolver la
-- pareja es un NVL sobre un LEFT JOIN. Ese NVL se necesita en cinco sitios
-- —la consulta de auditorías en PHP y tres procedimientos de 03— y repetirlo
-- es garantizar que un día uno se quede con el JOIN antiguo y las auditorías
-- escritas a mano desaparezcan de ese informe sin avisar. La vista lo escribe
-- una vez; los demás la consultan por id_auditoria.
--
-- No es un indicador y por eso es una vista y no un procedimiento: no calcula
-- nada, solo dice de dónde sale un dato que la tabla guarda en dos columnas.
CREATE OR REPLACE VIEW v_auditoria_entrevistado AS
SELECT a.id_auditoria,
       NVL(dba.nombre,       a.administrador_nombre)       AS nombre_administrador_bd,
       NVL(dba.organizacion, a.administrador_organizacion) AS organizacion,
       CASE WHEN a.id_administrador_bd IS NULL THEN 1 ELSE 0 END AS es_manual
  FROM auditoria a
  LEFT JOIN usuario dba ON dba.id_usuario = a.id_administrador_bd;

COMMIT;
