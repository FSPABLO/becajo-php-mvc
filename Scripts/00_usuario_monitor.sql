-- ============================================================================
-- EIF402 · Proyecto Rivendel — Monitor de salud (Parte 2)
-- Cuenta de solo lectura del agente (Oracle 21c+, multitenant)
-- Preparado por Estudiante 2 — Datos y agente
--
-- Contrato de diseño: documentacion/Parte II/EIF402_Monitor_de_Salud_parte_2.md
-- §8.1 (La cuenta del agente) y §10.1 (el riesgo verificado de V$RESOURCE_LIMIT).
--
-- POR QUÉ ESTE SCRIPT NO ES "Scripts/08" NI CORRE COMO becajo
-- ----------------------------------------------------------------
-- Todo lo demás en Scripts/ corre como becajo@FREEPDB1: es el dueño de sus
-- propias tablas. Este script hace algo que becajo no puede hacer —crear
-- OTRO usuario— y tiene que crearlo donde vive V$RESOURCE_LIMIT de verdad:
-- CDB$ROOT, no el PDB (§10.1 del plan: la misma consulta da 0 filas sin
-- error dentro del PDB y 27 filas en la raíz). Por eso corre como SYS,
-- contra el servicio FREE, no FREEPDB1:
--
--   docker exec -i becajo-oracle sqlplus -s sys/oracle@FREE as sysdba < Scripts/00_usuario_monitor.sql
--
-- El "00" es a propósito: es un prerrequisito de arranque, no un paso más
-- en la cadena 01→09 que carga datos en el esquema de la aplicación.
--
-- POR QUÉ UN USUARIO COMÚN (C##) Y NO UNO LOCAL AL PDB
-- ------------------------------------------------------
-- El agente abre dos conexiones por muestra: RAIZ (CDB$ROOT, para
-- V$RESOURCE_LIMIT, V$BGPROCESS...) y CONTENEDOR (FREEPDB1, para los
-- tablespaces y V$SQLSTATS de la base auditada). Un usuario local al PDB no
-- puede conectarse a la raíz. Un usuario común, creado aquí con el prefijo
-- obligatorio C##, sí puede — con la MISMA cuenta y la MISMA contraseña en
-- las dos conexiones, mientras los GRANT se den con CONTAINER = ALL.
--
-- CLAVE DE DESARROLLO
-- ---------------------
-- Sigue la misma convención que becajo/becajo y SYS/oracle en
-- docker-compose.yml: una clave fija y versionada para el entorno local.
-- config/monitor.ejemplo.php la cita como valor de ejemplo.
-- ============================================================================

ALTER SESSION SET CONTAINER = CDB$ROOT;

-- Perfil dedicado (§8.1 del plan): expiración de contraseña y bloqueo por
-- intentos fallidos. Con prefijo C## para que nazca común, igual que el
-- usuario que lo usa.
CREATE PROFILE C##RIVENDEL_MONITOR_PERFIL LIMIT
    FAILED_LOGIN_ATTEMPTS 5
    PASSWORD_LOCK_TIME    1
    PASSWORD_LIFE_TIME    180;

CREATE USER C##RIVENDEL_MONITOR IDENTIFIED BY "RivendelMonitor2026"
    DEFAULT TABLESPACE users
    TEMPORARY TABLESPACE temp
    PROFILE C##RIVENDEL_MONITOR_PERFIL
    ACCOUNT UNLOCK;

-- Cuota 0: nunca se concede ningún privilegio de creación, pero cuota 0 es
-- una segunda barrera — ni un GRANT futuro mal dado le alcanzaría para
-- crear un objeto sin que además alguien le diera espacio.
ALTER USER C##RIVENDEL_MONITOR QUOTA 0 ON users;

GRANT CREATE SESSION TO C##RIVENDEL_MONITOR CONTAINER = ALL;

-- ── Ámbito RAIZ (CDB$ROOT) ───────────────────────────────────────────────
-- Las vistas V$ son sinónimos públicos de las V_$ correspondientes; el
-- GRANT va sobre el objeto real (V_$...), nunca sobre el sinónimo — así se
-- consulta igual como V$RESOURCE_LIMIT después de concedido.
GRANT SELECT ON V_$RESOURCE_LIMIT    TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$BGPROCESS         TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$PROCESS           TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$SYSTEM_EVENT      TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$INSTANCE_RECOVERY TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$PGASTAT           TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$SGASTAT           TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$LOG               TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$LOGFILE           TO C##RIVENDEL_MONITOR CONTAINER = ALL;

-- ── Ámbito CONTENEDOR (el PDB auditado) ──────────────────────────────────
GRANT SELECT ON V_$DATAFILE                  TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON V_$SQLSTATS                  TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON DBA_TABLESPACE_USAGE_METRICS TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON DBA_TEMP_FREE_SPACE          TO C##RIVENDEL_MONITOR CONTAINER = ALL;
GRANT SELECT ON DBA_DATA_FILES               TO C##RIVENDEL_MONITOR CONTAINER = ALL;

-- Nada de INSERT/UPDATE/DELETE, nada de DBA, nada de SYSDBA, nada de
-- SELECT_CATALOG_ROLE. Es la lista completa de privilegios: 1 de sesión +
-- 14 de lectura, ni uno más (§8.1 del plan).
