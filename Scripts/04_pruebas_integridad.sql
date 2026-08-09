-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Fase 5 — Pruebas de integridad (Oracle 21c+)
-- Preparado por Persona 2
--
-- CÓMO LEER LOS RESULTADOS:
-- Cada bloque está etiquetado con PROMPT y dice qué error se espera.
-- La prueba PASA cuando Oracle devuelve exactamente ese error (la base de
-- datos está protegiendo la integridad de los datos). Si una sentencia que
-- debería fallar se ejecuta sin error ("1 row created" o "row updated"),
-- ESO es un problema: significa que falta una restricción en el esquema.
--
-- Ejecutar después de 01_esquema.sql, 02_datos_semilla.sql y
-- 03_procedimientos_indicadores.sql (usa datos ya cargados por el seed).
-- ============================================================================

SET SERVEROUTPUT ON

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
UPDATE evaluacion_control SET estado = 'TALVEZ' WHERE codigo_control = 'C-001' AND id_auditoria = 1;

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
PROMPT ============================================================
PROMPT CONTROL DE SANIDAD — nada de lo anterior debió modificar datos reales.
PROMPT Estos conteos deben seguir dando los mismos números de siempre:
PROMPT ============================================================
SELECT COUNT(*) AS usuarios FROM usuario;              -- debe seguir en 2
SELECT COUNT(*) AS auditorias FROM auditoria;           -- debe seguir en 1
SELECT COUNT(*) AS evaluaciones FROM evaluacion_control; -- debe seguir en 2
SELECT madurez, estado, impacto FROM evaluacion_control
 WHERE id_auditoria = 1 AND codigo_control = 'C-001';    -- madurez debe seguir en 3, estado 'SI'

ROLLBACK;
