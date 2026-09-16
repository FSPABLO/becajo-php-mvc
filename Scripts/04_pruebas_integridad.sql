-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Pruebas de integridad (Oracle 21c+) — versión consolidada final
-- Preparado por Persona 2
--
-- Fusiona las pruebas del esquema original (tests 1-12) con las de la rama
-- feature/rigor-normativo (tests 13-21, antes en un archivo aparte
-- 08_pruebas_integridad_rigor_normativo.sql) — ya no hace falta tenerlas
-- separadas: todas prueban restricciones que hoy son parte del mismo
-- 01_esquema.sql, así que se corren juntas, en una sola pasada.
--
-- CÓMO LEER LOS RESULTADOS:
-- Cada bloque está etiquetado con PROMPT y dice qué error se espera.
-- La prueba PASA cuando Oracle devuelve exactamente ese error (la base de
-- datos está protegiendo la integridad de los datos). Si una sentencia que
-- debería fallar se ejecuta sin error ("1 row created" o "row updated"),
-- ESO es un problema: significa que falta una restricción en el esquema.
--
-- Ejecutar después de 01_esquema.sql, 02_datos_semilla.sql y
-- 03_procedimientos_indicadores.sql (usa los datos ya cargados por el seed:
-- C-001 en estado SI, C-046 en estado NO, ambos en la auditoría 1).
-- ============================================================================

SET SERVEROUTPUT ON

PROMPT
PROMPT ############################################################
PROMPT #  BLOQUE 1 — restricciones del esquema original (tests 1-12)
PROMPT ############################################################

PROMPT
PROMPT ============================================================
PROMPT TEST 1 — rol inválido en USUARIO (se espera ORA-02290, ck_usuario_rol)
PROMPT ============================================================
INSERT INTO usuario (nombre, correo, contrasena_hash, rol, organizacion)
VALUES ('Usuario Prueba', 'prueba1@test.example', 'hash', 'SUPERADMIN', 'Org de prueba');

PROMPT
PROMPT ============================================================
PROMPT TEST 2 — correo duplicado en USUARIO (se espera ORA-00001, uq_usuario_correo)
PROMPT ============================================================
INSERT INTO usuario (nombre, correo, contrasena_hash, rol, organizacion)
VALUES ('Otra Persona', 'ana.alfaro@consultora.example', 'hash', 'AUDITOR', 'Org de prueba');

PROMPT
PROMPT ============================================================
PROMPT TEST 3 — nombre NULL en USUARIO (se espera ORA-01400, NOT NULL)
PROMPT ============================================================
INSERT INTO usuario (nombre, correo, contrasena_hash, rol, organizacion)
VALUES (NULL, 'prueba3@test.example', 'hash', 'AUDITOR', 'Org de prueba');

PROMPT
PROMPT ============================================================
PROMPT TEST 4 — estado inválido en AUDITORIA (se espera ORA-02290, ck_auditoria_estado)
PROMPT ============================================================
INSERT INTO auditoria (id_auditor, id_administrador_bd, area_evaluada, fecha, estado)
VALUES (1, 2, 'Prueba de integridad', SYSDATE, 'CANCELADA');

PROMPT
PROMPT ============================================================
PROMPT TEST 5 — auditor inexistente en AUDITORIA (se espera ORA-02291, FK a USUARIO)
PROMPT ============================================================
INSERT INTO auditoria (id_auditor, id_administrador_bd, area_evaluada, fecha, estado)
VALUES (9999, 2, 'Prueba de integridad', SYSDATE, 'EN_PROGRESO');

PROMPT
PROMPT ============================================================
PROMPT TEST 6 — control inexistente en EVALUACION_CONTROL (se espera ORA-02291, FK a CONTROL)
PROMPT ============================================================
INSERT INTO evaluacion_control (id_auditoria, codigo_control, estado, madurez)
VALUES (1, 'C-999', 'SI', 3);

PROMPT
PROMPT ============================================================
PROMPT TEST 7 — mismo control evaluado dos veces en la misma auditoría
PROMPT (se espera ORA-00001, uq_evalctrl_auditoria_control)
PROMPT ============================================================
INSERT INTO evaluacion_control (id_auditoria, codigo_control, estado, madurez)
VALUES (1, 'C-001', 'SI', 3);

PROMPT
PROMPT ============================================================
PROMPT TEST 8 — madurez fuera de rango 0-5 (se espera ORA-02290, ck_evalctrl_madurez)
PROMPT ============================================================
UPDATE evaluacion_control SET madurez = 9 WHERE codigo_control = 'C-001' AND id_auditoria = 1;

PROMPT
PROMPT ============================================================
PROMPT TEST 9 — estado inválido en EVALUACION_CONTROL (se espera ORA-02290, ck_evalctrl_estado)
PROMPT ============================================================
UPDATE evaluacion_control SET estado = 'QUIZA' WHERE codigo_control = 'C-001' AND id_auditoria = 1;

PROMPT
PROMPT ============================================================
PROMPT TEST 10 — impacto fuera de rango 1-5 (se espera ORA-02290, ck_evalctrl_impacto)
PROMPT ============================================================
UPDATE evaluacion_control SET impacto = 10 WHERE codigo_control = 'C-046' AND id_auditoria = 1;

PROMPT
PROMPT ============================================================
PROMPT TEST 11 — zona inválida en RESULTADO_RIESGO (se espera ORA-02290, ck_resriesgo_zona)
PROMPT ============================================================
INSERT INTO resultado_riesgo (id_auditoria, tipo_riesgo, promedio_madurez, zona)
VALUES (1, 'INTEGRIDAD', 0.400, 'NARANJA');

PROMPT
PROMPT ============================================================
PROMPT TEST 12 — borrar un control que ya tiene evaluaciones
PROMPT (se espera ORA-02292, integridad referencial protege el historial)
PROMPT ============================================================
DELETE FROM control WHERE codigo = 'C-001';


PROMPT
PROMPT ############################################################
PROMPT #  BLOQUE 2 — restricciones de la rama rigor-normativo (tests 13-21)
PROMPT ############################################################

PROMPT
PROMPT ============================================================
PROMPT TEST 13 — peso inválido en CONTROL (se espera ORA-02290, ck_control_peso)
PROMPT ============================================================
UPDATE control SET peso = 'EXTREMA' WHERE codigo = 'C-001';

PROMPT
PROMPT ============================================================
PROMPT TEST 14 — relación C-I-D inválida en PROCESO
PROMPT (se espera ORA-02290, ck_proceso_rel_confidencialidad)
PROMPT ============================================================
UPDATE proceso SET relacion_confidencialidad = 'X' WHERE numero = 1;

PROMPT
PROMPT ============================================================
PROMPT TEST 15 — respuesta "Sí" sin evidencia (se espera ORA-02290, ck_evalctrl_evidencia_si)
PROMPT ============================================================
UPDATE evaluacion_control
   SET evidencia_verificada = NULL
 WHERE codigo_control = 'C-001' AND id_auditoria = 1;

PROMPT
PROMPT ============================================================
PROMPT TEST 16 — respuesta "Sí" con evidencia pero sin calidad
PROMPT (se espera ORA-02290, ck_evalctrl_evidencia_si)
PROMPT ============================================================
UPDATE evaluacion_control
   SET calidad_evidencia = NULL
 WHERE codigo_control = 'C-001' AND id_auditoria = 1;

PROMPT
PROMPT ============================================================
PROMPT TEST 17 — calidad de evidencia con valor inválido
PROMPT (se espera ORA-02290, ck_evalctrl_calidad_evidencia)
PROMPT ============================================================
UPDATE evaluacion_control
   SET calidad_evidencia = 'EXCELENTE'
 WHERE codigo_control = 'C-001' AND id_auditoria = 1;

PROMPT
PROMPT ============================================================
PROMPT TEST 18 — estado inválido en REMEDIACION (se espera ORA-02290, ck_remediacion_estado)
PROMPT ============================================================
INSERT INTO remediacion (id_evaluacion_control, fecha_limite, estado)
VALUES (
    (SELECT id_evaluacion_control FROM evaluacion_control
      WHERE codigo_control = 'C-046' AND id_auditoria = 1),
    DATE '2026-12-01',
    'CANCELADA'
);

PROMPT
PROMPT ============================================================
PROMPT TEST 19 — remediación de una evaluación inexistente
PROMPT (se espera ORA-02291, fk_remediacion_evalctrl)
PROMPT ============================================================
INSERT INTO remediacion (id_evaluacion_control, fecha_limite)
VALUES (999999, DATE '2026-12-01');

PROMPT
PROMPT ============================================================
PROMPT TEST 20 — remediación enlazada a una auditoría de seguimiento inexistente
PROMPT (se espera ORA-02291, fk_remediacion_reauditoria)
PROMPT ============================================================
INSERT INTO remediacion (id_evaluacion_control, fecha_limite, id_auditoria_reauditoria)
VALUES (
    (SELECT id_evaluacion_control FROM evaluacion_control
      WHERE codigo_control = 'C-046' AND id_auditoria = 1),
    DATE '2026-12-01',
    999999
);

PROMPT
PROMPT ============================================================
PROMPT Preparación para TEST 21 — se crea una remediación real de prueba
PROMPT (esto SÍ debe insertar sin error; es el montaje, no la prueba)
PROMPT ============================================================
INSERT INTO remediacion (id_evaluacion_control, fecha_limite, responsable)
VALUES (
    (SELECT id_evaluacion_control FROM evaluacion_control
      WHERE codigo_control = 'C-046' AND id_auditoria = 1),
    DATE '2026-12-01',
    'Prueba de integridad'
);

PROMPT
PROMPT ============================================================
PROMPT TEST 21 — borrar una evaluación que ya tiene una remediación
PROMPT (se espera ORA-02292, fk_remediacion_evalctrl protege el historial)
PROMPT ============================================================
DELETE FROM evaluacion_control WHERE codigo_control = 'C-046' AND id_auditoria = 1;


PROMPT
PROMPT ############################################################
PROMPT #  BLOQUE 3 — entrevistado escrito a mano (tests 22-24)
PROMPT ############################################################
PROMPT (ck_auditoria_administrador: o la cuenta registrada, o el nombre y la
PROMPT  empresa a mano. Exactamente uno de los dos lados, nunca los dos ni
PROMPT  ninguno — si no, «¿a quién se entrevistó?» tiene dos respuestas.)

PROMPT
PROMPT ============================================================
PROMPT TEST 22 — auditoría sin entrevistado de ninguna de las dos formas
PROMPT (se espera ORA-02290, ck_auditoria_administrador)
PROMPT ============================================================
INSERT INTO auditoria (id_auditor, id_administrador_bd,
                       administrador_nombre, administrador_organizacion,
                       area_evaluada, fecha, estado)
VALUES (1, NULL, NULL, NULL, 'Prueba de integridad', SYSDATE, 'EN_PROGRESO');

PROMPT
PROMPT ============================================================
PROMPT TEST 23 — cuenta registrada Y nombre a mano a la vez
PROMPT (se espera ORA-02290, ck_auditoria_administrador)
PROMPT ============================================================
INSERT INTO auditoria (id_auditor, id_administrador_bd,
                       administrador_nombre, administrador_organizacion,
                       area_evaluada, fecha, estado)
VALUES (1, 2, 'Marta Jiménez', 'Panadería del Bosque S.A.',
        'Prueba de integridad', SYSDATE, 'EN_PROGRESO');

PROMPT
PROMPT ============================================================
PROMPT TEST 24 — a mano con nombre pero sin empresa
PROMPT (se espera ORA-02290: sin empresa no hay organización auditada, que es
PROMPT  por donde se filtran el tablero y el histórico)
PROMPT ============================================================
INSERT INTO auditoria (id_auditor, id_administrador_bd,
                       administrador_nombre, administrador_organizacion,
                       area_evaluada, fecha, estado)
VALUES (1, NULL, 'Marta Jiménez', NULL,
        'Prueba de integridad', SYSDATE, 'EN_PROGRESO');

PROMPT
PROMPT ============================================================
PROMPT CONTROL DE SANIDAD — nada de lo anterior debió modificar datos reales.
PROMPT Corre cada SELECT por separado si tu cliente pega varias líneas a la vez.
PROMPT ============================================================
SELECT COUNT(*) AS usuarios FROM usuario;                          -- 2
SELECT COUNT(*) AS auditorias FROM auditoria;                      -- 1
SELECT COUNT(*) AS evaluaciones FROM evaluacion_control;           -- 2
SELECT madurez, estado, impacto FROM evaluacion_control
 WHERE id_auditoria = 1 AND codigo_control = 'C-001';               -- 3, SI, 2
SELECT codigo, peso FROM control WHERE codigo = 'C-001';           -- MEDIA
SELECT relacion_confidencialidad FROM proceso WHERE numero = 1;    -- NULL
SELECT evidencia_verificada, calidad_evidencia FROM evaluacion_control
 WHERE codigo_control = 'C-001' AND id_auditoria = 1;               -- con texto, BIEN_IMPLEMENTADO
SELECT COUNT(*) AS remediaciones FROM remediacion;                  -- vuelve al número de antes de este script

ROLLBACK;
