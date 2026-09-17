-- ============================================================================
-- EIF402 · Proyecto Rivendel — Monitor de salud (Parte 2)
-- Datos semilla del catálogo de métricas — PUERTO A POSTGRESQL
--
-- Traducción de Scripts/07_datos_semilla_monitor.sql. El cuerpo de INSERTs es
-- SQL ANSI puro (sin funciones ni tipos específicos de Oracle), así que se
-- reutiliza sin cambios — ver ese archivo para toda la justificación de cada
-- valor (por qué 11 umbrales y no 15, la transformación de las métricas
-- MAYOR_MEJOR, etc.).
--
-- Ejecutar después de 06_esquema_monitor.sql:
--   psql "$BD_CADENA" -U becajo -d rivendel -f Scripts/postgres/07_datos_semilla_monitor.sql
-- ============================================================================

-- ── INSTANCIA (1) ────────────────────────────────────────────────────────

INSERT INTO instancia (clave, nombre, motor, host, puerto, servicio_raiz, servicio_contenedor, entorno, criticidad, activa, demostrativa)
VALUES ('FREEPDB1', 'FREEPDB1 · becajo', 'Oracle', 'oracle', 1521, 'FREE', 'FREEPDB1', 'Aplicación', 'MEDIA', 1, 0);

-- ── METRICA (15) ─────────────────────────────────────────────────────────
-- componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max,
-- acumulada, es_identidad, entra_isbd, ancla_iso

-- PROCESOS
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-PRO-01', 'PROCESOS', 'Utilización de sesiones', '%', 'v$resource_limit', 'MENOR_MEJOR', 'RAIZ', 3, 100, 0, 0, 1, 'A.8.6');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-PRO-02', 'PROCESOS', 'Utilización de procesos', '%', 'v$resource_limit', 'MENOR_MEJOR', 'RAIZ', 2, 100, 0, 0, 1, 'A.8.6');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-PRO-03', 'PROCESOS', 'Procesos de fondo obligatorios presentes', NULL, 'v$bgprocess', 'ESTADO', 'RAIZ', NULL, 100, 0, 0, 1, 'A.8.16; A.5.30');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-PRO-04', 'PROCESOS', 'Espera media de escritura de redo', 'ms', 'v$system_event', 'MENOR_MEJOR', 'RAIZ', 2, 20, 1, 0, 1, 'A.8.16');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-PRO-05', 'PROCESOS', 'Reinicio de proceso de fondo detectado', NULL, 'v$bgprocess + v$process', 'ESTADO', 'RAIZ', NULL, 100, 0, 1, 1, 'A.8.16; A.5.30');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-PRO-06', 'PROCESOS', 'Antigüedad del punto de control', '%', 'v$instance_recovery', 'MENOR_MEJOR', 'RAIZ', 2, 100, 0, 0, 1, 'A.8.6; A.5.30');

-- MEMORIA
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-MEM-01', 'MEMORIA', 'Aciertos de caché de PGA', '%', 'v$pgastat', 'MAYOR_MEJOR', 'RAIZ', 2, 100, 0, 0, 1, 'A.8.6');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-MEM-02', 'MEMORIA', 'PGA asignada sobre el objetivo', '%', 'v$pgastat', 'MENOR_MEJOR', 'RAIZ', 3, 150, 0, 0, 1, 'A.8.6');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-MEM-03', 'MEMORIA', 'Memoria libre de la shared pool', '%', 'v$sgastat', 'MAYOR_MEJOR', 'RAIZ', 2, 100, 0, 0, 1, 'A.8.6');

-- ARCHIVOS
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-ARC-01', 'ARCHIVOS', 'Utilización del peor tablespace', '%', 'dba_tablespace_usage_metrics', 'MENOR_MEJOR', 'CONTENEDOR', 3, 100, 0, 0, 1, 'A.8.6');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-ARC-02', 'ARCHIVOS', 'Datafiles en estado válido', NULL, 'v$datafile', 'ESTADO', 'CONTENEDOR', NULL, 100, 0, 0, 1, 'A.5.30; A.8.6');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-ARC-03', 'ARCHIVOS', 'Grupos de redo sin miembros inválidos', NULL, 'v$log + v$logfile', 'ESTADO', 'RAIZ', NULL, 100, 0, 0, 1, 'A.5.30; A.8.15');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-ARC-04', 'ARCHIVOS', 'Utilización del peor tablespace temporal', '%', 'dba_temp_free_space', 'MENOR_MEJOR', 'CONTENEDOR', 2, 100, 0, 0, 1, 'A.8.6');
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-ARC-05', 'ARCHIVOS', 'Utilización del peor tablespace sin crecimiento automático', '%', 'dba_tablespace_usage_metrics + dba_data_files', 'MENOR_MEJOR', 'CONTENEDOR', 2, 100, 0, 0, 1, 'A.8.6');

-- CONSULTAS — peso = 1 según la ficha individual (catalogo-metricas-v0.md
-- §3, M-CON-01); la tabla resumen del §2 lo anota como "—" pero el peso no
-- se usa en ningún cálculo porque entra_isbd = 0 (§3.1 del plan).
INSERT INTO metrica (codigo, componente, nombre, unidad, vista_origen, sentido, ambito, peso, u_max, acumulada, es_identidad, entra_isbd, ancla_iso) VALUES ('M-CON-01', 'CONSULTAS', 'Sentencias del top-N que superan el umbral de tiempo por ejecución', 'sentencias', 'v$sqlstats', 'MENOR_MEJOR', 'CONTENEDOR', 1, 20, 0, 0, 0, 'A.8.6; C-066');

-- ── UMBRAL (11) ──────────────────────────────────────────────────────────
-- Juego general por métrica: clave_instancia NULL, valido_hasta NULL
-- (vigente desde la carga). codigo_metrica, u_opt, u_adv, u_deg, u_crit

INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-PRO-01', 50, 70, 85, 95);
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-PRO-02', 50, 70, 85, 95);
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-PRO-04', 10, 14, 17, 19);
-- Provisional (ver nota de cabecera): reutiliza 50/70/85/95 a falta de
-- lectura de calibración contra becajo-oracle.
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-PRO-06', 50, 70, 85, 95);
-- MAYOR_MEJOR, valores transformados (100 − v): ver nota de cabecera.
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-MEM-01', 5, 10, 20, 30);
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-MEM-02', 70, 90, 100, 120);
-- MAYOR_MEJOR, valores transformados (100 − v): ver nota de cabecera.
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-MEM-03', 90, 95, 98, 99);
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-ARC-01', 50, 70, 85, 95);
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-ARC-04', 50, 70, 85, 95);
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-ARC-05', 50, 70, 85, 95);
INSERT INTO umbral (codigo_metrica, u_opt, u_adv, u_deg, u_crit) VALUES ('M-CON-01', 0, 1, 3, 5);

-- ── PRECEDENCIA (6) ──────────────────────────────────────────────────────
-- Qué métrica suele arrastrar a cuál (§6.1 del plan; catalogo-metricas-v0.md
-- §5). Conocimiento declarado, no correlación calculada.

INSERT INTO precedencia (codigo_metrica_origen, codigo_metrica_consecuencia, nota) VALUES ('M-PRO-02', 'M-PRO-01', 'Agotados los procesos, no hay dónde alojar sesiones nuevas.');
INSERT INTO precedencia (codigo_metrica_origen, codigo_metrica_consecuencia, nota) VALUES ('M-ARC-01', 'M-ARC-02', 'Un tablespace sin espacio deja archivos que no pueden extenderse.');
INSERT INTO precedencia (codigo_metrica_origen, codigo_metrica_consecuencia, nota) VALUES ('M-MEM-03', 'M-MEM-01', 'Sin espacio en la shared pool se descartan planes y crece el trabajo en PGA.');
INSERT INTO precedencia (codigo_metrica_origen, codigo_metrica_consecuencia, nota) VALUES ('M-MEM-01', 'M-ARC-04', 'Bajan los aciertos de caché de PGA; el ordenamiento que no cabe en memoria se traslada al tablespace temporal.');
INSERT INTO precedencia (codigo_metrica_origen, codigo_metrica_consecuencia, nota) VALUES ('M-ARC-05', 'M-ARC-02', 'Un tablespace sin crecimiento automático que se llena no tiene margen: la siguiente escritura falla y el archivo puede quedar fuera de línea.');
INSERT INTO precedencia (codigo_metrica_origen, codigo_metrica_consecuencia, nota) VALUES ('M-CON-01', 'M-MEM-02', 'Sentencias con ordenamientos o joins grandes consumen PGA por encima de lo habitual, empujando la asignación sobre el objetivo.');

COMMIT;
