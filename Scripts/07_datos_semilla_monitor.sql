-- ============================================================================
-- EIF402 · Proyecto Rivendel — Monitor de salud (Parte 2)
-- Datos semilla del catálogo de métricas (Oracle 21c+)
-- Preparado por Estudiante 2 — Datos y agente
--
-- Fuente: documentacion/Parte II/catalogo-metricas-v0.md (versión 1, quince
-- métricas). Los valores se copian directo de la tabla "Criterios de
-- decisión" de cada ficha — no hay que traducir nada, salvo la excepción de
-- las métricas MAYOR_MEJOR que se explica abajo.
--
-- Tres bloques: METRICA (15 filas), UMBRAL (11 filas) y PRECEDENCIA (6 filas).
--
-- POR QUÉ 11 UMBRALES Y NO 15
-- ----------------------------
-- Las cuatro métricas de familia ESTADO (M-PRO-03, M-PRO-05, M-ARC-02,
-- M-ARC-03) son compuertas: no tienen u_opt/u_adv/u_deg/u_crit porque no hay
-- "qué tan ausente" está un proceso de fondo (catalogo-metricas-v0.md,
-- "Justificación de umbrales" de cada una). Las otras once sí llevan fila.
--
-- LA EXCEPCIÓN MAYOR_MEJOR: LOS UMBRALES SE GUARDAN TRANSFORMADOS
-- ------------------------------------------------------------------
-- M-MEM-01 y M-MEM-03 son "mayor es mejor". El plan (§5.2.1) dice que para
-- estas métricas se aplica la MISMA tabla de normalización pero sobre
-- (100 − u) — no hay una segunda fórmula que mantener. Eso solo funciona si
-- lo que vive en la columna `u_opt`/`u_adv`/`u_deg`/`u_crit` de UMBRAL es ya
-- el valor transformado, en el mismo sentido ascendente ("más alto es peor")
-- que usan las métricas MENOR_MEJOR. El catálogo redacta el criterio de
-- decisión de estas dos fichas sobre el valor crudo (el porcentaje de
-- aciertos, el porcentaje libre), así que aquí se transforma antes de
-- sembrar:
--
--   M-MEM-01 (aciertos de caché): ÓPTIMO ≥95 · SALUDABLE ≥90 · ADVERTENCIA
--   ≥80 · DEGRADADO ≥70 → u = 100 − v → u_opt=5, u_adv=10, u_deg=20, u_crit=30
--
--   M-MEM-03 (memoria libre shared pool): ÓPTIMO ≥10 · SALUDABLE ≥5 ·
--   ADVERTENCIA ≥2 · DEGRADADO ≥1 → u = 100 − v →
--   u_opt=90, u_adv=95, u_deg=98, u_crit=99
--
-- El motor de cálculo (F3) hace la misma transformación sobre valor_crudo
-- antes de comparar contra estos umbrales — no una segunda vez sobre ellos.
--
-- M-PRO-06 ES PROVISIONAL
-- -------------------------
-- La ficha marca sus cuatro umbrales como provisionales: reutiliza la escala
-- 50/70/85/95 porque todavía no hay una lectura de calibración contra
-- becajo-oracle que confirme si target_mttr está configurado en esta
-- instancia (catalogo-metricas-v0.md, nota de M-PRO-06). Se siembra igual
-- para no bloquear al resto de los frentes, marcado como tal en este
-- comentario — no hay columna para "provisional" en UMBRAL porque es una
-- propiedad de la calibración, no del modelo de datos.
--
-- Ejecutar después de 06_esquema_monitor.sql:
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/07_datos_semilla_monitor.sql
-- ============================================================================

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
