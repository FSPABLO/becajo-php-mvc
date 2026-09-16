-- ============================================================================
-- EIF402 · Proyecto Rivendel — Monitor de salud (Parte 2)
-- Script de creación del esquema MONITOR (Oracle 21c+)
-- Preparado por Estudiante 2 — Datos y agente
--
-- Contrato de diseño: documentacion/Parte II/EIF402_Monitor_de_Salud_parte_2.md
-- (§7 Modelo de datos, Anexo A). Contratos de Fase 0:
--   documentacion/Parte II/contrato-muestra.md
--   documentacion/Parte II/contrato-repositorio-monitor.md
--
-- 12 tablas (las 11 de §7.1 más indice_causa, decisión B-9).
--
-- SOBRE LOS NOMBRES DE LAS LLAVES FORÁNEAS
-- -----------------------------------------
-- El plan describe las columnas de enlace como "instancia_id" y "metrica_id"
-- en prosa, pero INSTANCIA y METRICA ya tienen una llave natural estable
-- —igual que DOMINIO.clave y CONTROL.codigo en el esquema de la parte 1
-- (01_esquema.sql)— y es la que usa RepositorioMonitor en todos sus métodos
-- ($clave, $codigoMetrica: siempre string, nunca un id numérico). Este script
-- sigue ese precedente en vez de la prosa del plan: instancia.clave
-- y metrica.codigo son las llaves primarias, y las tablas que enlazan usan
-- clave_instancia / codigo_metrica — el mismo patrón que
-- proceso.clave_dominio y evaluacion_control.codigo_control. Evita un
-- surrogate id que nadie en la capa de PHP necesitaría pedir.
--
-- DECISIONES QUE VIENEN DE LOS CONTRATOS (Anexo A del plan)
-- -----------------------------------------------------------
--   B-4  umbral es la única fuente de u_opt/u_adv/u_deg/u_crit, versionada;
--        con clave_instancia nula es el juego general de la métrica.
--   B-7  M-PRO-05 compara identidad (spid), no magnitud: medicion.huella y
--        metrica.es_identidad, en vez de forzar el mecanismo de tasa.
--   B-8  metrica NO guarda los cuatro umbrales (solo u_max, que es el techo
--        de normalización, no un criterio de decisión versionable).
--   B-9  indice.causa se separa a la tabla puente indice_causa.
--
-- CONSULTAS Y entra_isbd
-- -----------------------
-- M-CON-01 se normaliza exactamente como una métrica "menor es mejor"
-- (catalogo-metricas-v0.md §1.1), así que no hace falta un cuarto valor de
-- `sentido`: alcanza con la bandera metrica.entra_isbd (0 para las métricas
-- de CONSULTAS, 1 para el resto), que es lo único que cambia entre ambas
-- (§3.1 del plan: se mide, se muestra y alerta, pero no se suma al ISBD).
--
-- CIRCULARIDAD ALERTA <-> EPISODIO
-- ---------------------------------
-- alerta.id_episodio apunta a EPISODIO y episodio.id_alerta_causa apunta a
-- ALERTA (§6.1 del plan). Ninguna de las dos puede declarar su FK en su
-- propio CREATE TABLE porque la otra todavía no existe: EPISODIO se crea
-- primero sin esa restricción y se cierra con ALTER TABLE después de crear
-- ALERTA.
--
-- Orden de creación (respeta las dependencias de llave foránea):
--   INSTANCIA, METRICA        (sin dependencias)
--   UMBRAL                    (depende de METRICA, INSTANCIA)
--   MUESTRA                   (depende de INSTANCIA)
--   MEDICION                  (depende de MUESTRA, METRICA, UMBRAL)
--   INDICE                    (depende de MUESTRA)
--   INDICE_CAUSA               (depende de INDICE, METRICA)
--   EPISODIO                  (depende de INSTANCIA; FK a ALERTA pendiente)
--   ALERTA                    (depende de INSTANCIA, METRICA, EPISODIO)
--   [ALTER episodio: FK a ALERTA]
--   PRECEDENCIA                (depende de METRICA x2)
--   CONSULTA_OBSERVADA          (depende de MUESTRA)
--   RESUMEN_HORA                (depende de INSTANCIA, METRICA)
--
-- Ejecutar después de 01-03 (esquema de la parte 1) y antes de
-- 07_datos_semilla_monitor.sql:
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/06_esquema_monitor.sql
-- ============================================================================

-- ── INSTANCIA ────────────────────────────────────────────────────────────
-- Es la tabla que config/conexiones.php venía anunciando (§7.1 del plan).
-- clave: identificador natural corto ('FREEPDB1'), el mismo valor que viaja
-- como $clave en RepositorioMonitor y como 'instancia' en la muestra cruda
-- (contrato-muestra.md §3).
-- servicio_raiz / servicio_contenedor: el agente abre DOS conexiones por
-- muestra (§10.1 del plan) — la raíz (CDB$ROOT, donde vive V$RESOURCE_LIMIT)
-- y el contenedor (el PDB auditado). Comparten host y puerto; solo cambia el
-- nombre de servicio de la cadena de conexión.
-- demostrativa: decisión B-5 — separa los datos de Scripts/08 de una
-- recolección real, para que la interfaz pueda distinguirlas.
CREATE TABLE instancia (
    clave             VARCHAR2(50)   PRIMARY KEY,
    nombre            VARCHAR2(150)  NOT NULL,
    motor             VARCHAR2(30)   DEFAULT 'Oracle' NOT NULL,
    host              VARCHAR2(150)  NOT NULL,
    puerto            NUMBER(5),
    servicio_raiz     VARCHAR2(100)  NOT NULL,
    servicio_contenedor VARCHAR2(100) NOT NULL,
    entorno           VARCHAR2(50)   NOT NULL,
    criticidad        VARCHAR2(10)   DEFAULT 'MEDIA' NOT NULL,
    activa            NUMBER(1)      DEFAULT 1 NOT NULL,
    demostrativa      NUMBER(1)      DEFAULT 0 NOT NULL,
    fecha_creacion    TIMESTAMP      DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT ck_instancia_criticidad CHECK (criticidad IN ('ALTA', 'MEDIA', 'BAJA')),
    CONSTRAINT ck_instancia_activa     CHECK (activa IN (0, 1)),
    CONSTRAINT ck_instancia_demostrativa CHECK (demostrativa IN (0, 1))
);

-- ── METRICA ──────────────────────────────────────────────────────────────
-- codigo: identificador natural del catálogo ('M-PRO-01' ... 'M-CON-01'),
-- igual que control.codigo en la parte 1.
-- sentido: MENOR_MEJOR / MAYOR_MEJOR se normalizan por tramos (§5.2.1 del
-- plan); ESTADO es una compuerta (§1.3 del catálogo) y por eso no lleva peso
-- (ck_metrica_peso_estado).
-- entra_isbd: en 0 solo para las métricas de CONSULTAS (hoy M-CON-01): se
-- normalizan igual que una MENOR_MEJOR pero no se suman al índice (§3.1).
-- acumulada: la lectura cruda es un acumulado desde el arranque de la
-- instancia; el motor deriva la tasa contra RepositorioMonitor::acumuladosAnteriores().
-- es_identidad: la lectura cruda es una huella de texto (spid); el motor la
-- compara contra RepositorioMonitor::huellasAnteriores() (B-7). Hoy solo M-PRO-05.
-- u_max: el techo de la normalización — no siempre 100 (M-MEM-02 llega a 150).
-- metrica NO guarda u_opt/u_adv/u_deg/u_crit: viven solo en UMBRAL (B-8).
CREATE TABLE metrica (
    codigo         VARCHAR2(10)   PRIMARY KEY,
    componente     VARCHAR2(10)   NOT NULL,
    nombre         VARCHAR2(150)  NOT NULL,
    unidad         VARCHAR2(30),
    vista_origen   VARCHAR2(100)  NOT NULL,
    sentido        VARCHAR2(15)   NOT NULL,
    ambito         VARCHAR2(11)   NOT NULL,
    peso           NUMBER(3),
    u_max          NUMBER         DEFAULT 100 NOT NULL,
    acumulada      NUMBER(1)      DEFAULT 0 NOT NULL,
    es_identidad   NUMBER(1)      DEFAULT 0 NOT NULL,
    entra_isbd     NUMBER(1)      DEFAULT 1 NOT NULL,
    ancla_iso      VARCHAR2(100),
    CONSTRAINT ck_metrica_componente   CHECK (componente IN ('PROCESOS', 'MEMORIA', 'ARCHIVOS', 'CONSULTAS')),
    CONSTRAINT ck_metrica_sentido      CHECK (sentido IN ('MENOR_MEJOR', 'MAYOR_MEJOR', 'ESTADO')),
    CONSTRAINT ck_metrica_ambito       CHECK (ambito IN ('RAIZ', 'CONTENEDOR')),
    CONSTRAINT ck_metrica_acumulada    CHECK (acumulada IN (0, 1)),
    CONSTRAINT ck_metrica_es_identidad CHECK (es_identidad IN (0, 1)),
    CONSTRAINT ck_metrica_entra_isbd   CHECK (entra_isbd IN (0, 1)),
    CONSTRAINT ck_metrica_peso_estado  CHECK (sentido != 'ESTADO' OR peso IS NULL)
);

-- ── UMBRAL ───────────────────────────────────────────────────────────────
-- Fuente única de u_opt/u_adv/u_deg/u_crit (B-8). clave_instancia nula es el
-- juego general de la métrica; con valor, la anulación para esa instancia
-- (absorbe a la vieja umbral_instancia — B-4). Nunca se hace UPDATE sobre los
-- cuatro números: se cierra valido_hasta y se abre una fila nueva (§7.2).
CREATE TABLE umbral (
    id_umbral       NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_metrica  VARCHAR2(10)  NOT NULL,
    clave_instancia VARCHAR2(50),
    u_opt           NUMBER        NOT NULL,
    u_adv           NUMBER        NOT NULL,
    u_deg           NUMBER        NOT NULL,
    u_crit          NUMBER        NOT NULL,
    valido_desde    TIMESTAMP     DEFAULT SYSTIMESTAMP NOT NULL,
    valido_hasta    TIMESTAMP,
    CONSTRAINT fk_umbral_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo),
    CONSTRAINT fk_umbral_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave)
);

-- A lo sumo un juego "vigente" (valido_hasta nulo) por métrica e instancia
-- (o por métrica en general, cuando clave_instancia es nula) — es la
-- invariante que sostiene el versionado del §7.2.
CREATE UNIQUE INDEX ux_umbral_vigente
    ON umbral (codigo_metrica, NVL(clave_instancia, '*'), (CASE WHEN valido_hasta IS NULL THEN 1 END));

-- Resuelve "cuál es el umbral vigente ahora" sin recorrer toda la tabla.
CREATE INDEX ix_umbral_metrica_instancia
    ON umbral (codigo_metrica, clave_instancia, valido_hasta);

-- ── MUESTRA ──────────────────────────────────────────────────────────────
-- Cabecera de una recolección. tomada_en es el reloj del monitor, en UTC
-- (A.8.17) — nunca el de la instancia vigilada. Se persiste siempre, incluso
-- cuando resultado = FALLIDA (contrato-muestra.md, principio 3).
CREATE TABLE muestra (
    id_muestra      NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia VARCHAR2(50)  NOT NULL,
    tomada_en       TIMESTAMP     NOT NULL,
    duracion_ms     NUMBER,
    resultado       VARCHAR2(10)  NOT NULL,
    cobertura_pct   NUMBER(5,2),
    mensaje         VARCHAR2(500),
    CONSTRAINT fk_muestra_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave),
    CONSTRAINT ck_muestra_resultado
        CHECK (resultado IN ('OK', 'PARCIAL', 'FALLIDA'))
);

CREATE INDEX ix_muestra_instancia_fecha
    ON muestra (clave_instancia, tomada_en DESC);

-- ── MEDICION ─────────────────────────────────────────────────────────────
-- Una fila por métrica efectivamente recolectada dentro de una muestra.
-- Tabla angosta y de alto volumen (§7.3 del plan): ~2,6 millones de filas al
-- año con 25 métricas, 1 instancia y muestreo cada 5 minutos.
-- Una métrica con estado VACIA/ERROR/NO_APLICA en la lectura cruda (§3.2 de
-- contrato-muestra.md) NO genera fila aquí: sale del denominador de
-- cobertura por su ausencia, no por un valor guardado (invariante 3). Motor
-- de cálculo (F3), no este esquema, decide cuándo insertar.
-- valor_acumulado: solo métricas con metrica.acumulada = 1 (tasas).
-- huella: solo métricas con metrica.es_identidad = 1 (B-7, hoy M-PRO-05).
CREATE TABLE medicion (
    id_medicion        NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_muestra         NUMBER        NOT NULL,
    codigo_metrica     VARCHAR2(10)  NOT NULL,
    valor_crudo        NUMBER,
    valor_normalizado  NUMBER(5,2),
    estado             VARCHAR2(12),
    id_umbral          NUMBER,
    valor_acumulado    NUMBER,
    huella             VARCHAR2(200),
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
-- Una fila por muestra, y solo cuando hay algo que publicar: una muestra
-- PARCIAL bajo el piso de cobertura (invariante 4) o FALLIDA no genera fila
-- aquí. "No existe una muestra sin índice ni un índice sin muestra" (§4 de
-- contrato-repositorio-monitor.md) se cumple en la entidad Muestra que arma
-- el repositorio, no obligando a esta tabla a tener una fila artificial.
CREATE TABLE indice (
    id_indice   NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_muestra  NUMBER        NOT NULL,
    ip          NUMBER(5,2),
    im          NUMBER(5,2),
    ia          NUMBER(5,2),
    isbd_bruto  NUMBER(5,2),
    isbd        NUMBER(5,2),
    estado      VARCHAR2(12),
    CONSTRAINT fk_indice_muestra
        FOREIGN KEY (id_muestra) REFERENCES muestra (id_muestra),
    CONSTRAINT uq_indice_muestra
        UNIQUE (id_muestra),
    CONSTRAINT ck_indice_estado
        CHECK (estado IN ('OPTIMO', 'SALUDABLE', 'ADVERTENCIA', 'DEGRADADO', 'CRITICO'))
);

-- ── INDICE_CAUSA ─────────────────────────────────────────────────────────
-- Tabla puente (B-9): qué métricas explican el estado publicado (invariante
-- 5 — "el ISBD nunca se muestra sin causa"). Sustituye a un campo de texto
-- con códigos separados por coma.
CREATE TABLE indice_causa (
    id_indice      NUMBER        NOT NULL,
    codigo_metrica VARCHAR2(10)  NOT NULL,
    CONSTRAINT pk_indice_causa PRIMARY KEY (id_indice, codigo_metrica),
    CONSTRAINT fk_indicecausa_indice
        FOREIGN KEY (id_indice) REFERENCES indice (id_indice),
    CONSTRAINT fk_indicecausa_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo)
);

-- ── EPISODIO ─────────────────────────────────────────────────────────────
-- Agrupación de alertas concurrentes de una misma instancia (§6.1 del plan).
-- id_alerta_causa se declara aquí pero su FK se agrega después de crear
-- ALERTA (ver nota de circularidad en la cabecera del script).
CREATE TABLE episodio (
    id_episodio     NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia VARCHAR2(50)  NOT NULL,
    id_alerta_causa NUMBER,
    abierto_en      TIMESTAMP     DEFAULT SYSTIMESTAMP NOT NULL,
    CONSTRAINT fk_episodio_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave)
);

-- ── ALERTA ───────────────────────────────────────────────────────────────
-- Ciclo de vida completo (§6 del plan): a lo sumo una alerta abierta por
-- (instancia, métrica) — no por nivel (B-3, Anexo A) —, así que el nivel
-- escala dentro de la misma fila (nivel_actual / nivel_maximo).
-- responsable: NUMBER porque RepositorioMonitorEscritura::cerrarAlerta()
-- recibe ?int $idResponsable — referencia informal a usuario.id_usuario del
-- esquema de la parte 1 (BECAJO), sin FK formal: MONITOR es un esquema
-- propio y separado (§7 del plan), y remediacion.responsable en la parte 1
-- ya usa el mismo criterio de no acoplar esquemas con una FK.
CREATE TABLE alerta (
    id_alerta            NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia      VARCHAR2(50)  NOT NULL,
    codigo_metrica       VARCHAR2(10)  NOT NULL,
    nivel_actual         VARCHAR2(12)  NOT NULL,
    nivel_maximo         VARCHAR2(12)  NOT NULL,
    estado_atencion      VARCHAR2(12)  DEFAULT 'ABIERTA' NOT NULL,
    valor                NUMBER,
    umbral               NUMBER,
    descripcion          VARCHAR2(500),
    ocurrencias          NUMBER        DEFAULT 1 NOT NULL,
    vista_primera_vez    TIMESTAMP     DEFAULT SYSTIMESTAMP NOT NULL,
    vista_por_ultima_vez TIMESTAMP     DEFAULT SYSTIMESTAMP NOT NULL,
    responsable          NUMBER,
    fecha_reconocimiento TIMESTAMP,
    fecha_cierre         TIMESTAMP,
    motivo_cierre        VARCHAR2(500),
    accion               VARCHAR2(1000),
    id_episodio          NUMBER,
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

-- Cierra la circularidad con EPISODIO (ver cabecera del script).
ALTER TABLE episodio ADD CONSTRAINT fk_episodio_alerta_causa
    FOREIGN KEY (id_alerta_causa) REFERENCES alerta (id_alerta);

-- ── PRECEDENCIA ──────────────────────────────────────────────────────────
-- Qué métrica suele arrastrar a cuál (§6.1 del plan). Conocimiento
-- declarado y revisable, no correlación calculada.
CREATE TABLE precedencia (
    id_precedencia              NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_metrica_origen       VARCHAR2(10)  NOT NULL,
    codigo_metrica_consecuencia VARCHAR2(10)  NOT NULL,
    nota                        VARCHAR2(500),
    CONSTRAINT fk_precedencia_origen
        FOREIGN KEY (codigo_metrica_origen) REFERENCES metrica (codigo),
    CONSTRAINT fk_precedencia_consecuencia
        FOREIGN KEY (codigo_metrica_consecuencia) REFERENCES metrica (codigo),
    CONSTRAINT uq_precedencia
        UNIQUE (codigo_metrica_origen, codigo_metrica_consecuencia)
);

-- ── CONSULTA_OBSERVADA ───────────────────────────────────────────────────
-- Top-N de sentencias por muestra (§3.1 del plan). Alimenta la evidencia de
-- C-066; no entra al ISBD.
CREATE TABLE consulta_observada (
    id_consulta_observada          NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_muestra                     NUMBER        NOT NULL,
    sql_id                         VARCHAR2(13)  NOT NULL,
    plan_hash_value                NUMBER,
    ejecuciones                    NUMBER,
    cpu_ms_por_ejecucion           NUMBER,
    transcurrido_ms_por_ejecucion  NUMBER,
    lecturas_logicas_por_ejecucion NUMBER,
    CONSTRAINT fk_consultaobs_muestra
        FOREIGN KEY (id_muestra) REFERENCES muestra (id_muestra)
);

CREATE INDEX ix_consultaobs_muestra
    ON consulta_observada (id_muestra);

-- ── RESUMEN_HORA ─────────────────────────────────────────────────────────
-- Consolidación horaria (§7.4 del plan): retención de 13 meses, contra 30
-- días de medicion en crudo. La llena pkg_monitor.consolidar_hora, no PHP.
CREATE TABLE resumen_hora (
    id_resumen_hora  NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave_instancia  VARCHAR2(50)  NOT NULL,
    codigo_metrica   VARCHAR2(10)  NOT NULL,
    hora             TIMESTAMP     NOT NULL,
    minimo           NUMBER,
    maximo           NUMBER,
    promedio         NUMBER,
    p95              NUMBER,
    conteo_muestras  NUMBER        NOT NULL,
    CONSTRAINT fk_resumenhora_instancia
        FOREIGN KEY (clave_instancia) REFERENCES instancia (clave),
    CONSTRAINT fk_resumenhora_metrica
        FOREIGN KEY (codigo_metrica) REFERENCES metrica (codigo),
    CONSTRAINT uq_resumenhora
        UNIQUE (clave_instancia, codigo_metrica, hora)
);

-- ============================================================================
-- Diccionario de datos: comentarios sobre las columnas que no se explican
-- solas. Quedan en el diccionario real de Oracle (ALL_TAB_COMMENTS /
-- ALL_COL_COMMENTS), consultable desde sqlplus con `desc` o `COMMENT`, en vez
-- de un documento aparte que se puede desincronizar del DDL.
-- ============================================================================

COMMENT ON TABLE instancia IS 'Una base Oracle vigilada. Es la tabla que config/conexiones.php anunciaba.';
COMMENT ON COLUMN instancia.clave IS 'Identificador natural (ej. FREEPDB1); el mismo valor que RepositorioMonitor recibe como $clave.';
COMMENT ON COLUMN instancia.servicio_raiz IS 'Nombre de servicio de CDB$ROOT, para la conexion RAIZ del agente (V$RESOURCE_LIMIT no se ve desde el PDB).';
COMMENT ON COLUMN instancia.servicio_contenedor IS 'Nombre de servicio del PDB auditado, para la conexion CONTENEDOR del agente.';
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
COMMENT ON COLUMN alerta.responsable IS 'Referencia informal a BECAJO.USUARIO.ID_USUARIO (parte 1); sin FK formal, MONITOR es un esquema separado.';

COMMENT ON TABLE precedencia IS 'Que metrica suele arrastrar a cual (S6.1 del plan). Declarado y revisable, no correlacion calculada.';

COMMENT ON TABLE consulta_observada IS 'Top-N de sentencias por muestra (S3.1 del plan). Evidencia de C-066; no entra al ISBD.';

COMMENT ON TABLE resumen_hora IS 'Consolidacion horaria (S7.4 del plan). La llena pkg_monitor.consolidar_hora, no PHP.';

COMMIT;
