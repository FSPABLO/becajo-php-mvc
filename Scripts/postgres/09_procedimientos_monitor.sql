-- ============================================================================
-- EIF402 · Proyecto Rivendel — Monitor de salud (Parte 2)
-- pkg_monitor: consolidación horaria y purga — PUERTO A POSTGRESQL
--
-- Traducción de Scripts/09_procedimientos_monitor.sql. Postgres no tiene
-- PACKAGE: los dos procedimientos de pkg_monitor pasan a ser dos FUNCTIONs
-- independientes, siguiendo el mismo criterio que 03_procedimientos_indicadores.sql
-- usó para pkg_indicadores. Ninguna de las dos devuelve tabla (RETURNS void):
-- mutan el esquema y nada más, igual que en Oracle.
--
-- Se llaman igual que antes desde RepositorioMonitorPostgres:
--   SELECT consolidar_hora(:hasta);
--   SELECT purgar(:hasta);
--
-- ORDEN DE INVOCACIÓN — sigue sin ser opcional (ver cabecera del original):
-- consolidar_hora() antes de purgar(), y solo si la primera no lanzó
-- excepción. Eso lo decide quien llama (bin/monitor.php), no este script.
--
-- DIFERENCIAS DE DIALECTO
-- ------------------------
--   TRUNC(ts, 'HH24')            -> date_trunc('hour', ts)
--   ADD_MONTHS(ts, -n)           -> ts - (n || ' months')::interval
--   ts - n  (días, Oracle NUMBER)-> ts - (n || ' days')::interval
--   PERCENTILE_CONT(...) WITHIN GROUP (...)  -> misma sintaxis en Postgres,
--       PERCENTILE_CONT es una "ordered-set aggregate function" estándar que
--       los dos motores soportan igual.
--   COMMIT dentro del procedimiento -> se omite: una FUNCTION de PL/pgSQL
--       corre dentro de la transacción de quien la llama (aquí,
--       BaseDatosPostgres::procedimiento(), que ejecuta en autocommit salvo
--       que el propio código PHP haya abierto una transacción). No hace
--       falta un COMMIT explícito ni es válido dentro de una función normal
--       de PL/pgSQL (sí lo sería en un PROCEDURE con manejo propio de
--       transacción, pero eso complicaría la llamada sin ninguna ganancia
--       aquí).
--
-- Ejecutar después de 06_esquema_monitor.sql:
--   psql "$BD_CADENA" -U becajo -d rivendel -f Scripts/postgres/09_procedimientos_monitor.sql
-- ============================================================================

-- ------------------------------------------------------------------
-- consolidar_hora
-- ------------------------------------------------------------------
-- Se agrega sobre valor_crudo, no sobre valor_normalizado — mismo motivo
-- que en Oracle: serieMetrica() sigue mostrando la misma unidad al cruzar la
-- frontera de 30 días entre medicion y resumen_hora.
CREATE OR REPLACE FUNCTION consolidar_hora(p_hasta_utc TIMESTAMP)
RETURNS void AS $$
DECLARE
    v_limite TIMESTAMP := date_trunc('hour', p_hasta_utc);
BEGIN
    DELETE FROM resumen_hora rh
     WHERE rh.hora < v_limite
       AND EXISTS (
             SELECT 1
               FROM muestra mu
              WHERE date_trunc('hour', mu.tomada_en) = rh.hora
           );

    INSERT INTO resumen_hora (
        clave_instancia, codigo_metrica, hora,
        minimo, maximo, promedio, p95, conteo_muestras
    )
    SELECT
        mu.clave_instancia,
        me.codigo_metrica,
        date_trunc('hour', mu.tomada_en) AS hora,
        MIN(me.valor_crudo),
        MAX(me.valor_crudo),
        ROUND(AVG(me.valor_crudo)::NUMERIC, 2),
        ROUND((PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY me.valor_crudo))::NUMERIC, 2),
        COUNT(me.valor_crudo)
      FROM medicion me
      JOIN muestra mu ON mu.id_muestra = me.id_muestra
     WHERE mu.tomada_en < v_limite
       AND me.valor_crudo IS NOT NULL
     GROUP BY mu.clave_instancia, me.codigo_metrica, date_trunc('hour', mu.tomada_en);
END;
$$ LANGUAGE plpgsql;

-- ------------------------------------------------------------------
-- purgar
-- ------------------------------------------------------------------
-- Mismo orden que el original y por la misma razón (las llaves foráneas):
-- medicion/consulta_observada (30 días) -> indice_causa/indice (13 meses) ->
-- muestra (13 meses) -> alerta cerrada (13 meses, desenganchando primero
-- episodio.id_alerta_causa) -> resumen_hora (13 meses, independiente).
CREATE OR REPLACE FUNCTION purgar(p_hasta_utc TIMESTAMP)
RETURNS void AS $$
DECLARE
    c_dias_medicion   CONSTANT NUMERIC := 30;
    c_meses_retencion CONSTANT NUMERIC := 13;
    v_limite_crudo    TIMESTAMP := p_hasta_utc - (c_dias_medicion || ' days')::interval;
    v_limite_largo    TIMESTAMP := p_hasta_utc - (c_meses_retencion || ' months')::interval;
BEGIN
    -- 1) medicion (30 días) — sin hijos, se borra directo.
    DELETE FROM medicion
     WHERE id_muestra IN (
         SELECT id_muestra FROM muestra WHERE tomada_en < v_limite_crudo
     );

    -- 2) consulta_observada (30 días) — evidencia de C-066, mismo ciclo de
    -- vida que la muestra que la produjo, no una retención propia.
    DELETE FROM consulta_observada
     WHERE id_muestra IN (
         SELECT id_muestra FROM muestra WHERE tomada_en < v_limite_crudo
     );

    -- 3) indice_causa e indice (13 meses).
    DELETE FROM indice_causa
     WHERE id_indice IN (
         SELECT i.id_indice
           FROM indice i
           JOIN muestra mu ON mu.id_muestra = i.id_muestra
          WHERE mu.tomada_en < v_limite_largo
     );

    DELETE FROM indice
     WHERE id_muestra IN (
         SELECT id_muestra FROM muestra WHERE tomada_en < v_limite_largo
     );

    -- 4) muestra (13 meses, no 30 días): sobrevive lo que dure su hijo de
    -- vida más larga (indice). medicion y consulta_observada de estas
    -- muestras ya se borraron en 1) y 2).
    DELETE FROM muestra
     WHERE tomada_en < v_limite_largo;

    -- 5) alerta cerrada (13 meses). Se desengancha primero cualquier
    -- episodio que la señale como causa probable, para no violar esa FK.
    -- Una alerta ABIERTA nunca se purga, sin importar la edad.
    UPDATE episodio
       SET id_alerta_causa = NULL
     WHERE id_alerta_causa IN (
         SELECT id_alerta
           FROM alerta
          WHERE estado_atencion = 'CERRADA'
            AND vista_por_ultima_vez < v_limite_largo
     );

    DELETE FROM alerta
     WHERE estado_atencion = 'CERRADA'
       AND vista_por_ultima_vez < v_limite_largo;

    -- 6) resumen_hora (13 meses) — independiente de muestra.
    DELETE FROM resumen_hora
     WHERE hora < v_limite_largo;
END;
$$ LANGUAGE plpgsql;
