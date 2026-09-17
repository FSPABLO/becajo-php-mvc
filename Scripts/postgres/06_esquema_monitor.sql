-- ============================================================================
-- EIF402 · Proyecto Rivendel — Monitor de salud (Parte 2)
-- Script de creación del esquema MONITOR — PUERTO A POSTGRESQL
--
-- Traducción directa de Scripts/06_esquema_monitor.sql (Oracle 21c+). Ver ese
-- archivo para la justificación de diseño de cada tabla (nombres de llave,
-- B-4/B-7/B-8/B-9, la circularidad alerta<->episodio); aquí solo se anota lo
-- que cambia de un motor a otro.
--
-- ADVERTENCIA IMPORTANTE (que ya hace el CLAUDE.md del proyecto): a día de
-- hoy /monitoreo es una MAQUETA que no consulta este esquema en NINGÚN motor
-- —lee config/monitor-mockup.php—, así que cargar este script no cambia nada
-- de lo que ve un usuario. Se migra igual para que RepositorioMonitorPostgres
-- tenga sobre qué correr el día que el frente 4 conecte la fuente real.
--
-- DIFERENCIAS DE DIALECTO FRENTE AL ORIGINAL ORACLE
-- ----------------------------------------------------
--   NUMBER GENERATED ALWAYS AS IDENTITY -> INTEGER/BIGINT GENERATED ALWAYS AS IDENTITY
--   VARCHAR2(n)      -> VARCHAR(n)
--   NUMBER(p,s)      -> NUMERIC(p,s)
--   NUMBER(1)        -> SMALLINT (con el mismo CHECK IN (0,1))
--   NUMBER sin escala -> NUMERIC (magnitud variable: ip/im/ia, valor_crudo, etc.)
--   SYSTIMESTAMP     -> now()
--   NVL(x, y)        -> COALESCE(x, y), en el índice único de umbral vigente
--   COMMENT ON ...   -> misma sintaxis, Postgres la soporta igual
--
-- El índice funcional de "a lo sumo un vigente" (ux_umbral_vigente /
-- ux_alerta_abierta_unica) usa la misma técnica que Oracle: una expresión que
-- vale 1 en la fila "activa" y NULL en las demás, y un índice único ignora
-- los NULL — funciona igual en los dos motores.
--
-- Orden de creación: igual que el original (ver esa cabecera para el porqué).
-- Ejecutar después de 01-03 (Scripts/postgres/*.sql) y antes de
-- 07_datos_semilla_monitor.sql:
--   psql "$BD_CADENA" -U becajo -d rivendel -f Scripts/postgres/06_esquema_monitor.sql
-- ============================================================================

-- ── INSTANCIA ────────────────────────────────────────────────────────────
CREATE TABLE instancia (
    clave                VARCHAR(50)   PRIMARY KEY,
    nombre               VARCHAR(150)  NOT NULL,
    motor                VARCHAR(30)   DEFAULT 'Oracle' NOT NULL,
    host                 VARCHAR(150)  NOT NULL,
    puerto               NUMERIC(5),
    servicio_raiz        VARCHAR(100)  NOT NULL,
    servicio_contenedor  VARCHAR(100)  NOT NULL,
    entorno              VARCHAR(50)   NOT NULL,
    criticidad           VARCHAR(10)   DEFAULT 'MEDIA' NOT NULL,
    activa               SMALLINT      DEFAULT 1 NOT NULL,
    demostrativa         SMALLINT      DEFAULT 0 NOT NULL,
    fecha_creacion       TIMESTAMP     DEFAULT now() NOT NULL,
    CONSTRAINT ck_instancia_criticidad   CHECK (criticidad IN ('ALTA', 'MEDIA', 'BAJA')),
    CONSTRAINT ck_instancia_activa       CHECK (activa = ANY (ARRAY[0, 1])),
    CONSTRAINT ck_instancia_demostrativa CHECK (demostrativa = ANY (ARRAY[0, 1]))
);

-- ── METRICA ──────────────────────────────────────────────────────────────
CREATE TABLE metrica (
    codigo         VARCHAR(10)   PRIMARY KEY,
    componente     VARCHAR(10)   NOT NULL,
    nombre         VARCHAR(150)  NOT NULL,
    unidad         VARCHAR(30),
    vista_origen   VARCHAR(100)  NOT NULL,
    sentido        VARCHAR(15)   NOT NULL,
    ambito         VARCHAR(11)   NOT NULL,
    peso           NUMERIC(3),
    u_max          NUMERIC       DEFAULT 100 NOT NULL,
    acumulada      SMALLINT      DEFAULT 0 NOT NULL,
    es_identidad   SMALLINT      DEFAULT 0 NOT NULL,
    entra_isbd     SMALLINT      DEFAULT 1 NOT NULL,
    ancla_iso      VARCHAR(100),
    CONSTRAINT ck_metrica_componente   CHECK (componente IN ('PROCESOS', 'MEMORIA', 'ARCHIVOS', 'CONSULTAS')),
    CONSTRAINT ck_metrica_sentido      CHECK (sentido IN ('MENOR_MEJOR', 'MAYOR_MEJOR', 'ESTADO')),
    CONSTRAINT ck_metrica_ambito       CHECK (ambito IN ('RAIZ', 'CONTENEDOR')),
    CONSTRAINT ck_metrica_acumulada    CHECK (acumulada = ANY (ARRAY[0, 1])),
    CONSTRAINT ck_metrica_es_identidad CHECK (es_identidad = ANY (ARRAY[0, 1])),
    CONSTRAINT ck_metrica_entra_isbd   CHECK (entra_isbd = ANY (ARRAY[0, 1])),
    CONSTRAINT ck_metrica_peso_estado  CHECK (sentido != 'ESTADO' OR peso IS NULL)
);

-- ── UMBRAL ───────────────────────────────────────────────────────────────
CREATE TABLE umbral (
    id_umbral       INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_metrica  VARCHAR(10)  NOT NULL,
    clave_instancia VARCHAR(50),
    u_opt           NUMERIC      NOT NULL,
    u_adv           NUMERIC      NOT NULL,
    u_deg           NUMERIC      NOT NULL,
    u_crit          NUMERIC      NOT NULL,
    valido_desde    TIMESTAMP    DEFAULT now() NOT NULL,
    valido_hasta    TIMESTAMP,
    CONSTRAINT fk_umbral_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo),
    CONSTRAINT fk_umbral_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave)
);

-- A lo sumo un juego "vigente" (valido_hasta nulo) por métrica e instancia.
CREATE UNIQUE INDEX ux_umbral_vigente
    ON umbral (codigo_metrica, COALESCE(clave_instancia, '*'), (CASE WHEN valido_hasta IS NULL THEN 1 END));

CREATE INDEX ix_umbral_metrica_instancia
    ON umbral (codigo_metrica, clave_instancia, valido_hasta);

-- ── MUESTRA ──────────────────────────────────────────────────────────────
CREATE TABLE muestra (
    id_muestra      INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia VARCHAR(50)  NOT NULL,
    tomada_en       TIMESTAMP    NOT NULL,
    duracion_ms     NUMERIC,
    resultado       VARCHAR(10)  NOT NULL,
    cobertura_pct   NUMERIC(5,2),
    mensaje         VARCHAR(500),
    CONSTRAINT fk_muestra_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave),
    CONSTRAINT ck_muestra_resultado
        CHECK (resultado IN ('OK', 'PARCIAL', 'FALLIDA'))
);

CREATE INDEX ix_muestra_instancia_fecha
    ON muestra (clave_instancia, tomada_en DESC);

-- ── MEDICION ─────────────────────────────────────────────────────────────
CREATE TABLE medicion (
    id_medicion        INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_muestra         INTEGER      NOT NULL,
    codigo_metrica     VARCHAR(10)  NOT NULL,
    valor_crudo        NUMERIC,
    valor_normalizado  NUMERIC(5,2),
    estado             VARCHAR(12),
    id_umbral          INTEGER,
    valor_acumulado    NUMERIC,
    huella             VARCHAR(200),
    CONSTRAINT fk_medicion_muestra
        FOREIGN KEY (id_muestra) REFERENCES muestra (id_muestra),
    CONSTRAINT fk_medicion_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo),
    CONSTRAINT fk_medicion_umbral
        FOREIGN KEY (id_umbral) REFERENCES umbral (id_umbral),
    CONSTRAINT uq_medicion_muestra_metrica
        UNIQUE (id_muestra, codigo_metrica),
    CONSTRAINT ck_medicion_estado
        CHECK (estado IN ('OPTIMO', 'SALUDABLE', 'ADVERTENCIA', 'DEGRADADO', 'CRITICO'))
);

CREATE INDEX ix_medicion_muestra
    ON medicion (id_muestra);

-- ── INDICE ───────────────────────────────────────────────────────────────
CREATE TABLE indice (
    id_indice   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_muestra  INTEGER      NOT NULL,
    ip          NUMERIC(5,2),
    im          NUMERIC(5,2),
    ia          NUMERIC(5,2),
    isbd_bruto  NUMERIC(5,2),
    isbd        NUMERIC(5,2),
    estado      VARCHAR(12),
    CONSTRAINT fk_indice_muestra
        FOREIGN KEY (id_muestra) REFERENCES muestra (id_muestra),
    CONSTRAINT uq_indice_muestra
        UNIQUE (id_muestra),
    CONSTRAINT ck_indice_estado
        CHECK (estado IN ('OPTIMO', 'SALUDABLE', 'ADVERTENCIA', 'DEGRADADO', 'CRITICO'))
);

-- ── INDICE_CAUSA ─────────────────────────────────────────────────────────
CREATE TABLE indice_causa (
    id_indice      INTEGER      NOT NULL,
    codigo_metrica VARCHAR(10)  NOT NULL,
    CONSTRAINT pk_indice_causa PRIMARY KEY (id_indice, codigo_metrica),
    CONSTRAINT fk_indicecausa_indice
        FOREIGN KEY (id_indice) REFERENCES indice (id_indice),
    CONSTRAINT fk_indicecausa_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo)
);

-- ── EPISODIO ─────────────────────────────────────────────────────────────
CREATE TABLE episodio (
    id_episodio     INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia VARCHAR(50)  NOT NULL,
    id_alerta_causa INTEGER,
    abierto_en      TIMESTAMP    DEFAULT now() NOT NULL,
    CONSTRAINT fk_episodio_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave)
);

-- ── ALERTA ───────────────────────────────────────────────────────────────
CREATE TABLE alerta (
    id_alerta            INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia      VARCHAR(50)  NOT NULL,
    codigo_metrica       VARCHAR(10)  NOT NULL,
    nivel_actual         VARCHAR(12)  NOT NULL,
    nivel_maximo         VARCHAR(12)  NOT NULL,
    estado_atencion      VARCHAR(12)  DEFAULT 'ABIERTA' NOT NULL,
    valor                NUMERIC,
    umbral               NUMERIC,
    descripcion          VARCHAR(500),
    ocurrencias          NUMERIC      DEFAULT 1 NOT NULL,
    vista_primera_vez    TIMESTAMP    DEFAULT now() NOT NULL,
    vista_por_ultima_vez TIMESTAMP    DEFAULT now() NOT NULL,
    responsable          INTEGER,
    fecha_reconocimiento TIMESTAMP,
    fecha_cierre         TIMESTAMP,
    motivo_cierre        VARCHAR(500),
    accion               VARCHAR(1000),
    id_episodio          INTEGER,
    CONSTRAINT fk_alerta_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave),
    CONSTRAINT fk_alerta_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo),
    CONSTRAINT fk_alerta_episodio
        FOREIGN KEY (id_episodio) REFERENCES episodio (id_episodio),
    CONSTRAINT ck_alerta_nivel_actual
        CHECK (nivel_actual IN ('ADVERTENCIA', 'DEGRADADO', 'CRITICO')),
    CONSTRAINT ck_alerta_nivel_maximo
        CHECK (nivel_maximo IN ('ADVERTENCIA', 'DEGRADADO', 'CRITICO')),
    CONSTRAINT ck_alerta_estado_atencion
        CHECK (estado_atencion IN ('ABIERTA', 'RECONOCIDA', 'CERRADA'))
);

-- A lo sumo una alerta ABIERTA por (instancia, métrica) — B-3 del Anexo A.
CREATE UNIQUE INDEX ux_alerta_abierta_unica
    ON alerta (clave_instancia, codigo_metrica, (CASE WHEN estado_atencion = 'ABIERTA' THEN 1 END));

CREATE INDEX ix_alerta_instancia_estado
    ON alerta (clave_instancia, estado_atencion);

-- Cierra la circularidad con EPISODIO (ver cabecera del script original).
ALTER TABLE episodio ADD CONSTRAINT fk_episodio_alerta_causa
    FOREIGN KEY (id_alerta_causa) REFERENCES alerta (id_alerta);

-- ── PRECEDENCIA ──────────────────────────────────────────────────────────
CREATE TABLE precedencia (
    id_precedencia              INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_metrica_origen       VARCHAR(10)  NOT NULL,
    codigo_metrica_consecuencia VARCHAR(10)  NOT NULL,
    nota                        VARCHAR(500),
    CONSTRAINT fk_precedencia_origen
        FOREIGN KEY (codigo_metrica_origen) REFERENCES metrica (codigo),
    CONSTRAINT fk_precedencia_consecuencia
        FOREIGN KEY (codigo_metrica_consecuencia) REFERENCES metrica (codigo),
    CONSTRAINT uq_precedencia
        UNIQUE (codigo_metrica_origen, codigo_metrica_consecuencia)
);

-- ── CONSULTA_OBSERVADA ───────────────────────────────────────────────────
CREATE TABLE consulta_observada (
    id_consulta_observada          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_muestra                     INTEGER      NOT NULL,
    sql_id                         VARCHAR(13)  NOT NULL,
    plan_hash_value                NUMERIC,
    ejecuciones                    NUMERIC,
    cpu_ms_por_ejecucion           NUMERIC,
    transcurrido_ms_por_ejecucion  NUMERIC,
    lecturas_logicas_por_ejecucion NUMERIC,
    CONSTRAINT fk_consultaobs_muestra
        FOREIGN KEY (id_muestra) REFERENCES muestra (id_muestra)
);

CREATE INDEX ix_consultaobs_muestra
    ON consulta_observada (id_muestra);

-- ── RESUMEN_HORA ─────────────────────────────────────────────────────────
CREATE TABLE resumen_hora (
    id_resumen_hora  INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia  VARCHAR(50)  NOT NULL,
    codigo_metrica   VARCHAR(10)  NOT NULL,
    hora             TIMESTAMP    NOT NULL,
    minimo           NUMERIC,
    maximo           NUMERIC,
    promedio         NUMERIC,
    p95              NUMERIC,
    conteo_muestras  NUMERIC      NOT NULL,
    CONSTRAINT fk_resumenhora_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave),
    CONSTRAINT fk_resumenhora_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo),
    CONSTRAINT uq_resumenhora
        UNIQUE (clave_instancia, codigo_metrica, hora)
);

-- ============================================================================
-- Diccionario de datos (mismos comentarios que el original Oracle).
-- ============================================================================

COMMENT ON TABLE instancia IS 'Una base vigilada. Es la tabla que config/conexiones.php anunciaba.';
COMMENT ON COLUMN instancia.clave IS 'Identificador natural (ej. FREEPDB1); el mismo valor que RepositorioMonitor recibe como $clave.';
COMMENT ON COLUMN instancia.servicio_raiz IS 'Nombre de servicio de CDB$ROOT, para la conexion RAIZ del agente (solo aplica bajo Oracle).';
COMMENT ON COLUMN instancia.servicio_contenedor IS 'Nombre de servicio del PDB auditado, para la conexion CONTENEDOR del agente (solo aplica bajo Oracle).';
COMMENT ON COLUMN instancia.demostrativa IS 'B-5: distingue los datos de Scripts/08_datos_demo_monitor.sql de una recoleccion real.';

COMMENT ON TABLE metrica IS 'Catalogo de variables medibles del instrumento de salud (catalogo-metricas-v0.md).';
COMMENT ON COLUMN metrica.sentido IS 'MENOR_MEJOR / MAYOR_MEJOR se normalizan por tramos; ESTADO es una compuerta y no lleva peso.';
COMMENT ON COLUMN metrica.entra_isbd IS 'En 0 solo para metricas de CONSULTAS: se normalizan pero no se suman al indice (S3.1 del plan).';
COMMENT ON COLUMN metrica.acumulada IS 'La lectura cruda es un acumulado desde el arranque; el motor deriva la tasa (contrato-muestra.md).';
COMMENT ON COLUMN metrica.es_identidad IS 'B-7: la lectura cruda es una huella de texto (spid), no una magnitud. Hoy solo M-PRO-05.';
COMMENT ON COLUMN metrica.u_max IS 'Techo de la normalizacion; no siempre 100 (M-MEM-02 llega a 150).';

COMMENT ON TABLE umbral IS 'Juegos de umbrales con vigencia (B-8): fuente unica de u_opt/u_adv/u_deg/u_crit.';
COMMENT ON COLUMN umbral.clave_instancia IS 'NULL = umbral general de la metrica; con valor, la anulacion para esa instancia.';
COMMENT ON COLUMN umbral.valido_hasta IS 'NULL = juego vigente. Nunca se hace UPDATE sobre los cuatro numeros (S7.2 del plan).';

COMMENT ON TABLE muestra IS 'Cabecera de una recoleccion. Se persiste siempre, incluso cuando resultado = FALLIDA.';
COMMENT ON COLUMN muestra.tomada_en IS 'UTC, reloj del monitor (A.8.17) — nunca el de la instancia vigilada.';
COMMENT ON COLUMN muestra.cobertura_pct IS 'Metricas recolectadas / metricas planificadas. Bajo el piso, no se publica ISBD (invariante 4).';

COMMENT ON TABLE medicion IS 'Una fila por metrica efectivamente recolectada dentro de una muestra. Tabla angosta y de alto volumen.';
COMMENT ON COLUMN medicion.valor_acumulado IS 'Solo metricas con metrica.acumulada = 1: el total leido, para que la muestra siguiente reste.';
COMMENT ON COLUMN medicion.huella IS 'B-7: solo metricas con metrica.es_identidad = 1. Equivalente de texto de valor_acumulado.';
COMMENT ON COLUMN medicion.id_umbral IS 'El juego de umbral vigente con el que se califico esta medicion (S7.2 del plan).';

COMMENT ON TABLE indice IS 'Una fila por muestra con indice publicable. IP/IM/IA ya topados (S5.4 del plan), no los brutos del componente.';
COMMENT ON TABLE indice_causa IS 'B-9: tabla puente indice-metrica. Que metricas explican el estado publicado (invariante 5).';

COMMENT ON TABLE episodio IS 'Agrupacion de alertas concurrentes de una misma instancia (S6.1 del plan).';
COMMENT ON COLUMN episodio.id_alerta_causa IS 'La alerta senalada como causa probable por la tabla precedencia, si la hay.';

COMMENT ON TABLE alerta IS 'Ciclo de vida completo de una alerta (S6 del plan). A lo sumo una ABIERTA por (instancia, metrica) — B-3.';
COMMENT ON COLUMN alerta.nivel_actual IS 'Escala dentro de la misma fila cuando el problema empeora; no abre una alerta nueva (B-3).';
COMMENT ON COLUMN alerta.responsable IS 'Referencia informal a usuario.id_usuario (parte 1); sin FK formal, MONITOR es un esquema separado.';

COMMENT ON TABLE precedencia IS 'Que metrica suele arrastrar a cual (S6.1 del plan). Declarado y revisable, no correlacion calculada.';

COMMENT ON TABLE consulta_observada IS 'Top-N de sentencias por muestra (S3.1 del plan). Evidencia de C-066; no entra al ISBD.';

COMMENT ON TABLE resumen_hora IS 'Consolidacion horaria (S7.4 del plan). La llena la funcion consolidar_hora(), no PHP.';
