-- ============================================================================
-- Multinorma: ISO/IEC 27002 + COBIT 2019
-- ============================================================================
--
-- Re-ejecutable. Al elegir la norma al crear una auditoría, el instrumento
-- presenta solo los dominios, procesos y controles de esa norma. No son
-- equivalencias entre normas: son catálogos independientes.
--
-- Qué toca de lo existente:
--
--   dominio, auditoria  ADD codigo_estandar DEFAULT 'ISO27002' NOT NULL. El
--                       valor por omisión rellena las filas actuales y deja
--                       funcionando cualquier INSERT que no nombre la columna
--                       (02, 04, 05, 12 y los repositorios de PHP).
--   dominio, proceso,   Reciben las filas de COBIT. Las de ISO no se tocan.
--   control
--
-- Bases que ya corrieron la versión del 21 de agosto (con Essential Eight):
-- el paso 3 retira ese catálogo si ninguna auditoría lo usa, y el paso 4
-- devuelve ck_evalctrl_madurez a 0..5, porque las dos normas vigentes usan
-- esa escala.
--
-- Después de este script hay que recargar 03 (sp_evolucion_auditor mide la
-- cobertura contra el catálogo de la norma de cada auditoría).
--
-- Ejecución (desde PowerShell, copiar primero para no dañar las tildes):
--
--   docker cp Scripts\14_multinorma.sql becajo-oracle:/tmp/14.sql
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 "@/tmp/14.sql"
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE OFF


-- ----------------------------------------------------------------------------
-- 1. Estructura
-- ----------------------------------------------------------------------------
-- Cada sentencia ignora solo el error de "ya existe". Cualquier otro error
-- interrumpe: silenciarlos todos dejaría una migración a medias.

DECLARE
    ya_existe_objeto      EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_objeto,      -955);
    ya_existe_columna     EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_columna,     -1430);
    ya_existe_restriccion EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_restriccion, -2275);
    ya_existe_nombre      EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_nombre,      -2264);
    ya_indexada           EXCEPTION; PRAGMA EXCEPTION_INIT(ya_indexada,           -1408);

    PROCEDURE ejecutar(p_sql VARCHAR2, p_que VARCHAR2) IS
    BEGIN
        EXECUTE IMMEDIATE p_sql;
        DBMS_OUTPUT.PUT_LINE('  creado    : ' || p_que);
    EXCEPTION
        WHEN ya_existe_objeto OR ya_existe_columna OR ya_existe_restriccion
          OR ya_existe_nombre OR ya_indexada THEN
            DBMS_OUTPUT.PUT_LINE('  ya estaba : ' || p_que);
    END;
BEGIN
    DBMS_OUTPUT.PUT_LINE('Estructura');

    ejecutar(q'[
        CREATE TABLE estandar (
            codigo          VARCHAR2(20)   PRIMARY KEY,
            nombre          VARCHAR2(200)  NOT NULL,
            version         VARCHAR2(20)   NOT NULL,
            organismo       VARCHAR2(150)  NOT NULL,
            escala_niveles  NUMBER(2)      NOT NULL,
            orden           NUMBER(3)      DEFAULT 0 NOT NULL,
            CONSTRAINT ck_estandar_escala CHECK (escala_niveles BETWEEN 2 AND 10)
        )]', 'tabla estandar');

    ejecutar(q'[
        CREATE TABLE nivel_madurez (
            codigo_estandar  VARCHAR2(20)   NOT NULL,
            nivel            NUMBER(2)      NOT NULL,
            nombre           VARCHAR2(60)   NOT NULL,
            descripcion      VARCHAR2(500)  NOT NULL,
            CONSTRAINT pk_nivel_madurez PRIMARY KEY (codigo_estandar, nivel),
            CONSTRAINT fk_nivel_estandar
                FOREIGN KEY (codigo_estandar) REFERENCES estandar (codigo)
        )]', 'tabla nivel_madurez');

    ejecutar(q'[ALTER TABLE dominio
                ADD codigo_estandar VARCHAR2(20) DEFAULT 'ISO27002' NOT NULL]',
             'columna dominio.codigo_estandar');
    ejecutar(q'[ALTER TABLE auditoria
                ADD codigo_estandar VARCHAR2(20) DEFAULT 'ISO27002' NOT NULL]',
             'columna auditoria.codigo_estandar');

    -- Si la columna venía de la versión anterior (NOT NULL sin valor por
    -- omisión), el ADD de arriba no hizo nada: se le pone el DEFAULT aquí.
    EXECUTE IMMEDIATE q'[ALTER TABLE dominio   MODIFY codigo_estandar DEFAULT 'ISO27002']';
    EXECUTE IMMEDIATE q'[ALTER TABLE auditoria MODIFY codigo_estandar DEFAULT 'ISO27002']';
    DBMS_OUTPUT.PUT_LINE('  aplicado  : DEFAULT ISO27002 en ambas columnas');
END;
/


-- ----------------------------------------------------------------------------
-- 2. ISO/IEC 27002 y su escala
-- ----------------------------------------------------------------------------
-- Tiene que existir antes de las llaves foráneas del paso 4, porque las filas
-- actuales de dominio y auditoria ya apuntan a 'ISO27002'.

DECLARE
    v_n NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_n FROM estandar WHERE codigo = 'ISO27002';

    IF v_n = 0 THEN
        INSERT INTO estandar (codigo, nombre, version, organismo, escala_niveles, orden)
        VALUES ('ISO27002', 'ISO/IEC 27002', '2022', 'ISO/IEC', 6, 1);
        INSERT INTO nivel_madurez VALUES ('ISO27002', 0, 'Inexistente',
            'El control no existe ni se ha considerado. La organización no reconoce la necesidad.');
        INSERT INTO nivel_madurez VALUES ('ISO27002', 1, 'Inicial',
            'La actividad se ejecuta de forma reactiva y depende del criterio individual de quien la atiende.');
        INSERT INTO nivel_madurez VALUES ('ISO27002', 2, 'Repetible',
            'La práctica se repite con regularidad, pero no está documentada ni es uniforme entre personas.');
        INSERT INTO nivel_madurez VALUES ('ISO27002', 3, 'Documentado',
            'Existe un procedimiento escrito, comunicado y aplicado de manera consistente.');
        INSERT INTO nivel_madurez VALUES ('ISO27002', 4, 'Gestionado y medido',
            'El control se mide con indicadores y sus desviaciones generan acciones correctivas.');
        INSERT INTO nivel_madurez VALUES ('ISO27002', 5, 'Optimizado',
            'El control se mejora de forma continua con base en la medición y en el aprendizaje de incidentes.');
        DBMS_OUTPUT.PUT_LINE('Semilla   : ISO/IEC 27002 cargada');
    ELSE
        DBMS_OUTPUT.PUT_LINE('Semilla   : ISO/IEC 27002 ya estaba');
    END IF;
END;
/

COMMIT;


-- ----------------------------------------------------------------------------
-- 3. Retiro de Essential Eight (solo bases con la versión anterior)
-- ----------------------------------------------------------------------------

DECLARE
    v_norma NUMBER;
    v_uso   NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_norma FROM estandar WHERE codigo = 'E8';

    IF v_norma = 0 THEN
        DBMS_OUTPUT.PUT_LINE('E8        : no estaba cargado');
    ELSE
        SELECT COUNT(*) INTO v_uso FROM auditoria WHERE codigo_estandar = 'E8';

        IF v_uso > 0 THEN
            DBMS_OUTPUT.PUT_LINE('E8        : NO se retira, lo usan ' || v_uso || ' auditorías');
        ELSE
            DELETE FROM control
             WHERE numero_proceso IN (SELECT p.numero FROM proceso p
                                        JOIN dominio d ON d.clave = p.clave_dominio
                                       WHERE d.codigo_estandar = 'E8');
            DELETE FROM proceso
             WHERE clave_dominio IN (SELECT clave FROM dominio WHERE codigo_estandar = 'E8');
            DELETE FROM dominio       WHERE codigo_estandar = 'E8';
            DELETE FROM nivel_madurez WHERE codigo_estandar = 'E8';
            DELETE FROM estandar      WHERE codigo = 'E8';
            DBMS_OUTPUT.PUT_LINE('E8        : catálogo retirado');
        END IF;
    END IF;
END;
/

COMMIT;


-- ----------------------------------------------------------------------------
-- 4. Llaves foráneas, índices y rango de madurez
-- ----------------------------------------------------------------------------

DECLARE
    ya_existe_objeto      EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_objeto,      -955);
    ya_existe_restriccion EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_restriccion, -2275);
    ya_existe_nombre      EXCEPTION; PRAGMA EXCEPTION_INIT(ya_existe_nombre,      -2264);
    ya_indexada           EXCEPTION; PRAGMA EXCEPTION_INIT(ya_indexada,           -1408);
    v_condicion           VARCHAR2(4000);
    v_fuera_rango         NUMBER;

    PROCEDURE ejecutar(p_sql VARCHAR2, p_que VARCHAR2) IS
    BEGIN
        EXECUTE IMMEDIATE p_sql;
        DBMS_OUTPUT.PUT_LINE('  aplicado  : ' || p_que);
    EXCEPTION
        WHEN ya_existe_objeto OR ya_existe_restriccion
          OR ya_existe_nombre OR ya_indexada THEN
            DBMS_OUTPUT.PUT_LINE('  ya estaba : ' || p_que);
    END;
BEGIN
    DBMS_OUTPUT.PUT_LINE('Restricciones');

    ejecutar('ALTER TABLE dominio ADD CONSTRAINT fk_dominio_estandar
                  FOREIGN KEY (codigo_estandar) REFERENCES estandar (codigo)',
             'fk_dominio_estandar');
    ejecutar('ALTER TABLE auditoria ADD CONSTRAINT fk_auditoria_estandar
                  FOREIGN KEY (codigo_estandar) REFERENCES estandar (codigo)',
             'fk_auditoria_estandar');

    -- Sin índice, borrar o cambiar una fila de estandar bloquea la tabla hija.
    ejecutar('CREATE INDEX ix_dominio_estandar ON dominio (codigo_estandar)',
             'ix_dominio_estandar');
    ejecutar('CREATE INDEX ix_auditoria_estandar ON auditoria (codigo_estandar)',
             'ix_auditoria_estandar');

    SELECT search_condition_vc INTO v_condicion
      FROM user_constraints
     WHERE constraint_name = 'CK_EVALCTRL_MADUREZ';

    IF INSTR(v_condicion, '5') > 0 THEN
        DBMS_OUTPUT.PUT_LINE('  ya estaba : ck_evalctrl_madurez en 0..5');
    ELSE
        SELECT COUNT(*) INTO v_fuera_rango FROM evaluacion_control WHERE madurez > 5;

        IF v_fuera_rango > 0 THEN
            DBMS_OUTPUT.PUT_LINE('  PENDIENTE : ' || v_fuera_rango
                || ' evaluaciones con madurez > 5, no se restringe a 0..5');
        ELSE
            EXECUTE IMMEDIATE 'ALTER TABLE evaluacion_control DROP CONSTRAINT ck_evalctrl_madurez';
            EXECUTE IMMEDIATE 'ALTER TABLE evaluacion_control ADD CONSTRAINT ck_evalctrl_madurez
                                   CHECK (madurez BETWEEN 0 AND 5)';
            DBMS_OUTPUT.PUT_LINE('  aplicado  : ck_evalctrl_madurez devuelta a 0..5');
        END IF;
    END IF;
END;
/


-- ----------------------------------------------------------------------------
-- 5. Catálogo COBIT 2019
-- ----------------------------------------------------------------------------
--
-- Doce objetivos de cuatro dominios, los que inciden en la administración de
-- bases de datos. Se dejan fuera EDM completo y los objetivos que tratan
-- cartera, arquitectura empresarial y gestión de personas, con el mismo
-- criterio con que se seleccionaron 75 de los 93 controles de ISO.
--
-- Los identificadores de COBIT son referencias citables. Los enunciados, la
-- evidencia esperada y las preguntas son redacción propia del equipo: el
-- material de ISACA tiene derechos de autor y no se reproduce.

DECLARE
    v_n NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_n FROM estandar WHERE codigo = 'COBIT2019';

    IF v_n = 0 THEN
        INSERT INTO estandar (codigo, nombre, version, organismo, escala_niveles, orden)
        VALUES ('COBIT2019', 'COBIT', '2019', 'ISACA', 6, 2);
        INSERT INTO nivel_madurez (codigo_estandar, nivel, nombre, descripcion) VALUES
            ('COBIT2019', 0, 'Incompleto',
             'El proceso no se ejecuta o no logra su propósito. No hay evidencia de actividad sistemática.');
        INSERT INTO nivel_madurez (codigo_estandar, nivel, nombre, descripcion) VALUES
            ('COBIT2019', 1, 'Realizado',
             'El proceso logra su propósito, pero de forma no organizada y sin planificación previa.');
        INSERT INTO nivel_madurez (codigo_estandar, nivel, nombre, descripcion) VALUES
            ('COBIT2019', 2, 'Gestionado',
             'El proceso se planifica, se supervisa y se ajusta, y sus productos se establecen y controlan.');
        INSERT INTO nivel_madurez (codigo_estandar, nivel, nombre, descripcion) VALUES
            ('COBIT2019', 3, 'Establecido',
             'El proceso se ejecuta a partir de un estándar definido de la organización, capaz de alcanzar sus resultados.');
        INSERT INTO nivel_madurez (codigo_estandar, nivel, nombre, descripcion) VALUES
            ('COBIT2019', 4, 'Predecible',
             'El proceso opera dentro de límites definidos y sus resultados se predicen con base en datos cuantitativos.');
        INSERT INTO nivel_madurez (codigo_estandar, nivel, nombre, descripcion) VALUES
            ('COBIT2019', 5, 'Optimizado',
             'El proceso se mejora de forma continua para responder a objetivos de negocio presentes y proyectados.');
        INSERT INTO dominio (clave, codigo_estandar, nombre, nombre_corto, descripcion, orden) VALUES
            ('cobit-apo', 'COBIT2019', 'Alinear, planificar y organizar', 'APO',
             'Riesgo, seguridad y gestión del dato como activo: las decisiones que anteceden a la operación de la base.', 1);
        INSERT INTO dominio (clave, codigo_estandar, nombre, nombre_corto, descripcion, orden) VALUES
            ('cobit-bai', 'COBIT2019', 'Construir, adquirir e implementar', 'BAI',
             'Cambios, configuración y construcción de soluciones sobre el motor de base de datos.', 2);
        INSERT INTO dominio (clave, codigo_estandar, nombre, nombre_corto, descripcion, orden) VALUES
            ('cobit-dss', 'COBIT2019', 'Entregar, dar servicio y soporte', 'DSS',
             'Operación diaria, continuidad, servicios de seguridad y controles sobre el procesamiento de datos.', 3);
        INSERT INTO dominio (clave, codigo_estandar, nombre, nombre_corto, descripcion, orden) VALUES
            ('cobit-mea', 'COBIT2019', 'Supervisar, evaluar y valorar', 'MEA',
             'Monitoreo del desempeño, conformidad interna y cumplimiento de requisitos externos.', 4);
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (100, 'cobit-apo', 'Gestión del riesgo', 'COBIT 2019 APO12', 1, 'P', 'P', 'P');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (101, 'cobit-apo', 'Gestión de la seguridad', 'COBIT 2019 APO13', 2, 'P', 'P', 'S');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (102, 'cobit-apo', 'Gestión de los datos', 'COBIT 2019 APO14', 3, 'P', 'P', 'S');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (103, 'cobit-bai', 'Identificación y construcción de soluciones', 'COBIT 2019 BAI03', 1, 'S', 'P', 'S');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (104, 'cobit-bai', 'Gestión de los cambios de TI', 'COBIT 2019 BAI06', 2, 'S', 'P', 'P');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (105, 'cobit-bai', 'Gestión de la configuración', 'COBIT 2019 BAI10', 3, 'S', 'P', 'S');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (106, 'cobit-dss', 'Gestión de las operaciones', 'COBIT 2019 DSS01', 1, 'S', 'S', 'P');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (107, 'cobit-dss', 'Gestión de la continuidad', 'COBIT 2019 DSS04', 2, 'S', 'P', 'P');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (108, 'cobit-dss', 'Gestión de los servicios de seguridad', 'COBIT 2019 DSS05', 3, 'P', 'P', 'S');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (109, 'cobit-dss', 'Gestión de los controles de procesos de negocio', 'COBIT 2019 DSS06', 4, 'P', 'P', 'S');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (110, 'cobit-mea', 'Monitoreo del desempeño y la conformidad', 'COBIT 2019 MEA01', 1, 'S', 'S', 'P');
        INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden, relacion_confidencialidad, relacion_integridad, relacion_disponibilidad) VALUES
            (111, 'cobit-mea', 'Cumplimiento de requisitos externos', 'COBIT 2019 MEA03', 2, 'P', 'S', 'S');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-001', 100, 'COBIT 2019 APO12',
             'Existe un perfil de riesgo de los servicios de base de datos, mantenido al día, que expresa cada escenario en términos de impacto para el negocio y no solo de falla técnica.',
             'Perfil de riesgo vigente con fecha de última revisión y traducción de cada escenario a impacto de negocio.',
             '¿Cómo se traduce hoy una falla de base de datos a un impacto para el negocio, quién hace esa traducción y cuándo se revisó por última vez ese perfil?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-002', 100, 'COBIT 2019 APO12',
             'Los escenarios de riesgo asociados a la base de datos cuentan con respuesta definida, responsable nombrado y umbral de tolerancia aprobado por la dirección.',
             'Registro de riesgos con la respuesta elegida, el responsable y el umbral de apetito aprobado.',
             '¿Qué nivel de riesgo de base de datos está dispuesta a aceptar la organización, quién lo aprobó y qué pasa cuando se supera ese umbral?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-003', 101, 'COBIT 2019 APO13',
             'Existe un sistema de gestión de seguridad de la información vigente cuyo alcance incluye de manera explícita las bases de datos en operación.',
             'Declaración de alcance del sistema de gestión, con las bases de datos identificadas nominalmente.',
             '¿Qué bases de datos quedan dentro del alcance del sistema de gestión de seguridad, cuáles quedaron fuera y con qué justificación?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-004', 101, 'COBIT 2019 APO13',
             'El plan de tratamiento de riesgos de seguridad se revisa con periodicidad definida y sus desviaciones se reportan a la dirección.',
             'Plan de tratamiento con estado por acción y actas de reporte a la dirección del último periodo.',
             '¿Cada cuánto se informa a la dirección sobre la seguridad de las bases de datos, qué se le reporta y cuál fue la última decisión que tomó a partir de ese informe?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-005', 102, 'COBIT 2019 APO14',
             'Existe una estrategia de gestión de datos que define propiedad, clasificación y calidad esperada por conjunto de datos.',
             'Documento de estrategia de datos con la matriz de propietarios y los criterios de clasificación.',
             '¿Quién es el propietario de cada conjunto de datos, con qué criterio se clasifican y dónde consta esa asignación?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-006', 102, 'COBIT 2019 APO14',
             'La calidad de los datos se mide con indicadores definidos y sus desviaciones generan acciones correctivas con seguimiento.',
             'Indicadores de calidad de datos del último periodo y registro de las acciones derivadas.',
             '¿Qué indicadores de calidad de datos se miden hoy, quién los revisa y qué se hizo con la última desviación detectada?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-007', 103, 'COBIT 2019 BAI03',
             'El diseño de cada solución que persiste datos se somete a revisión antes de su construcción, con criterios de seguridad y desempeño declarados.',
             'Documento de diseño con la revisión firmada y los criterios aplicados.',
             '¿Quién revisa el diseño de base de datos de una solución nueva antes de que se construya y qué criterios aplica en esa revisión?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-008', 103, 'COBIT 2019 BAI03',
             'Las soluciones se prueban contra criterios de aceptación acordados antes de su paso a producción, incluidas las pruebas sobre el modelo de datos.',
             'Criterios de aceptación acordados y resultados de las pruebas de la última solución liberada.',
             '¿Qué se probó de la base de datos antes de la última liberación a producción, quién aceptó el resultado y qué defectos quedaron abiertos?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-009', 104, 'COBIT 2019 BAI06',
             'Todo cambio sobre la base de datos se evalúa por impacto y se autoriza según su categoría antes de aplicarse.',
             'Registro de cambios del último trimestre con categoría, evaluación de impacto y autorizador.',
             '¿Cómo se decide qué nivel de autorización necesita un cambio de base de datos y quién lo decidió en el último cambio mayor?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-010', 104, 'COBIT 2019 BAI06',
             'Los cambios de emergencia siguen un procedimiento definido y se documentan y autorizan de forma retroactiva dentro de un plazo establecido.',
             'Procedimiento de cambio de emergencia y registro de los cambios de emergencia del último año con su autorización posterior.',
             '¿Cuál fue el último cambio de emergencia en la base de datos, quién lo aplicó y en cuánto tiempo quedó documentado y autorizado?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-011', 105, 'COBIT 2019 BAI10',
             'Existe un repositorio de configuración que registra cada instancia de base de datos, su versión, sus parámetros y sus relaciones con otros elementos.',
             'Extracto del repositorio de configuración con las instancias en operación y su fecha de última actualización.',
             '¿Dónde está registrada la configuración de cada instancia, cómo se actualiza ese registro y cuándo se verificó por última vez contra la realidad?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-012', 105, 'COBIT 2019 BAI10',
             'Se verifica periódicamente que la configuración registrada corresponda a la configuración real, y las diferencias se investigan y corrigen.',
             'Informe de la última verificación con las diferencias encontradas y su tratamiento.',
             '¿Cuántas diferencias aparecieron en la última verificación entre lo registrado y lo que había en el servidor, y qué se hizo con cada una?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-013', 106, 'COBIT 2019 DSS01',
             'Los procedimientos operativos de la base de datos están definidos, programados y su ejecución queda registrada.',
             'Calendario de tareas operativas y bitácora de ejecución del último mes.',
             '¿Qué tareas operativas se ejecutan sobre la base de datos cada semana, quién las ejecuta y dónde queda constancia de que se hicieron?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-014', 106, 'COBIT 2019 DSS01',
             'Los eventos de la infraestructura de base de datos se monitorean y las excepciones se escalan según una ruta definida.',
             'Configuración de alertas, ruta de escalamiento y registro de las excepciones del último mes.',
             '¿Qué ocurre cuando una alerta de base de datos se dispara fuera del horario laboral, a quién le llega y en cuánto tiempo se atendió la última?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-015', 107, 'COBIT 2019 DSS04',
             'Existe un plan de continuidad que cubre las bases de datos críticas, con objetivos de tiempo y punto de recuperación acordados con el negocio.',
             'Plan de continuidad vigente con los objetivos acordados y la firma de aceptación del negocio.',
             '¿Qué tiempo de recuperación acordó el negocio para cada base de datos crítica y quién firmó ese acuerdo?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-016', 107, 'COBIT 2019 DSS04',
             'El plan de continuidad se ejercita con periodicidad definida y los resultados de cada ejercicio se usan para actualizarlo.',
             'Informe del último ejercicio con los tiempos obtenidos y las modificaciones que produjo en el plan.',
             '¿Cuándo fue el último ejercicio de continuidad, qué falló durante el ejercicio y qué cambió en el plan como consecuencia?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-017', 108, 'COBIT 2019 DSS05',
             'Los accesos lógicos a la base de datos se otorgan según el principio de privilegio mínimo y se revisan con periodicidad definida.',
             'Matriz de accesos vigente y resultado de la última revisión periódica con las cuentas retiradas.',
             '¿Cuándo se revisaron por última vez los privilegios de la base de datos, cuántos se retiraron y quién aprobó los que se mantuvieron?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-018', 108, 'COBIT 2019 DSS05',
             'Los eventos de seguridad de la base de datos se registran, se conservan por un periodo definido y se revisan buscando actividad anómala.',
             'Configuración de la auditoría del motor, política de retención de registros y constancia de la última revisión.',
             '¿Qué eventos de seguridad quedan registrados hoy en la base de datos, cuánto tiempo se conservan y quién los revisa?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-019', 109, 'COBIT 2019 DSS06',
             'Las transacciones que modifican datos sensibles dejan una traza que permite reconstruir quién las ejecutó y cuándo.',
             'Diseño de la traza de auditoría de la aplicación y ejemplo de reconstrucción de una transacción.',
             '¿Si hay que reconstruir quién modificó un dato sensible hace tres meses, con qué información se cuenta y quién puede obtenerla?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-020', 109, 'COBIT 2019 DSS06',
             'Los roles con capacidad de alterar datos de negocio están segregados de los roles que los revisan o autorizan.',
             'Matriz de segregación de funciones y análisis de conflictos sobre las cuentas activas.',
             '¿Qué cuentas pueden modificar datos de negocio y a la vez revisar esa modificación, y qué control compensatorio aplica en esos casos?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-021', 110, 'COBIT 2019 MEA01',
             'Se han acordado metas e indicadores de desempeño para los servicios de base de datos y se reportan con periodicidad definida.',
             'Cuadro de indicadores con metas acordadas y los reportes del último periodo.',
             '¿Qué indicadores de base de datos se reportan, con qué meta, cada cuánto y a quién?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-022', 110, 'COBIT 2019 MEA01',
             'Las desviaciones respecto de las metas de desempeño generan acciones correctivas con responsable y fecha de cierre.',
             'Registro de desviaciones del último periodo con las acciones abiertas y cerradas.',
             '¿Cuál fue la última meta de desempeño que no se cumplió, qué acción se abrió y en qué estado está hoy?',
             'MEDIA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-023', 111, 'COBIT 2019 MEA03',
             'Están identificados los requisitos legales y regulatorios aplicables a los datos que la organización almacena, con responsable de su seguimiento.',
             'Inventario de requisitos aplicables con la fecha de última actualización y el responsable asignado.',
             '¿Qué normativa aplica a los datos que custodian, quién vigila que siga vigente y cuándo se actualizó por última vez esa lista?',
             'ALTA');
        INSERT INTO control (codigo, numero_proceso, referencia_iso, enunciado, evidencia_esperada, pregunta, peso) VALUES
            ('CB-024', 111, 'COBIT 2019 MEA03',
             'El cumplimiento de esos requisitos se evalúa periódicamente y los incumplimientos se tratan con un plan formal.',
             'Última evaluación de cumplimiento y plan de tratamiento de los incumplimientos detectados.',
             '¿Cuándo se evaluó por última vez el cumplimiento normativo sobre las bases de datos, qué incumplimientos aparecieron y quién responde por cerrarlos?',
             'ALTA');

        DBMS_OUTPUT.PUT_LINE('Semilla   : COBIT 2019 cargado');
    ELSE
        DBMS_OUTPUT.PUT_LINE('Semilla   : COBIT 2019 ya estaba');
    END IF;
END;
/

-- La versión anterior cargó las preguntas sin el signo de apertura.
UPDATE control
   SET pregunta = '¿' || pregunta
 WHERE codigo LIKE 'CB-%'
   AND DBMS_LOB.SUBSTR(pregunta, 1, 1) <> '¿';

COMMIT;


-- ----------------------------------------------------------------------------
-- 6. Verificación
-- ----------------------------------------------------------------------------

SET LINESIZE 120
COLUMN nombre FORMAT A20
COLUMN organismo FORMAT A12
COLUMN restriccion_madurez FORMAT A40

PROMPT
PROMPT --- Normas cargadas (deben ser 2: ISO27002 y COBIT2019) ---
SELECT codigo, nombre, version, organismo, escala_niveles FROM estandar ORDER BY orden;

PROMPT --- Niveles de madurez por norma (6 y 6, de 0 a 5) ---
SELECT codigo_estandar, COUNT(*) AS niveles, MIN(nivel) AS desde, MAX(nivel) AS hasta
  FROM nivel_madurez GROUP BY codigo_estandar ORDER BY codigo_estandar;

PROMPT --- Catálogo por norma (COBIT2019 4/12/24, ISO27002 7/25/75) ---
SELECT d.codigo_estandar,
       COUNT(DISTINCT d.clave)  AS dominios,
       COUNT(DISTINCT p.numero) AS procesos,
       COUNT(c.codigo)          AS controles
  FROM dominio d
  LEFT JOIN proceso p ON p.clave_dominio = d.clave
  LEFT JOIN control c ON c.numero_proceso = p.numero
 GROUP BY d.codigo_estandar
 ORDER BY d.codigo_estandar;

PROMPT --- Auditorías por norma ---
SELECT codigo_estandar, COUNT(*) AS auditorias FROM auditoria GROUP BY codigo_estandar;

PROMPT --- Restricción de madurez (debe decir BETWEEN 0 AND 5) ---
SELECT search_condition_vc AS restriccion_madurez
  FROM user_constraints WHERE constraint_name = 'CK_EVALCTRL_MADUREZ';
