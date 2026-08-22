-- ============================================================================
-- EIF402 · Proyecto Rivendel — Monitor de salud (Parte 2)
-- Paquete pkg_monitor: consolidación horaria y purga (Oracle 21c+)
-- Preparado por Estudiante 2 — Datos y agente
--
-- Contrato de diseño: documentacion/Parte II/EIF402_Monitor_de_Salud_parte_2.md
-- §7.4 (Retención) y contrato-repositorio-monitor.md §3
-- (RepositorioMonitorEscritura::consolidarHora / ::purgar).
--
-- Dos procedimientos, sin cursor porque ninguno de los dos devuelve datos a
-- PHP: mutan el esquema y no más — misma idea que
-- pkg_indicadores.calcular_riesgo_auditoria en Scripts/03.
--
-- ORDEN DE INVOCACIÓN — NO ES OPCIONAL
-- ---------------------------------------
-- "La consolidación corre ANTES de la purga y su éxito se verifica; si
-- falla, no se purga nada" (§7.4 del plan). Ese orden lo hace cumplir quien
-- llama —bin/monitor.php o el programador de docker-compose—, no este
-- paquete: si consolidar_hora lanza una excepción, no hace COMMIT, y quien
-- llama no debe invocar purgar a continuación.
--
-- RETENCIONES DISTINTAS, UN SOLO PARÁMETRO
-- -------------------------------------------
-- RepositorioMonitorEscritura::purgar(string $hastaUtc) recibe un solo
-- instante, pero la tabla del §7.4 fija cuatro retenciones distintas
-- (medicion 30 días; resumen_hora, indice y alerta 13 meses). p_hasta_utc
-- es el "ahora" de referencia — típicamente SYSTIMESTAMP en el momento en
-- que corre el trabajo programado— y cada tabla resta su propia ventana
-- desde ahí. Las constantes viven en el cuerpo del paquete, no en PHP: son
-- parte de la política de retención, no del agente.
--
-- ORDEN DE BORRADO DENTRO DE purgar — por las llaves foráneas
-- ---------------------------------------------------------------
-- muestra sobrevive 13 meses, no 30 días, aunque medicion (su hija) se
-- purgue a los 30: indice (la otra hija de muestra) todavía la necesita
-- hasta los 13 meses, y borrar el padre mientras el hijo exista viola la FK.
-- Por eso el orden es: medicion y consulta_observada primero (30 días,
-- sin nada que dependa de ellas); indice_causa e indice después (13 meses);
-- muestra al final, cuando ya no le queda ningún hijo más allá de los 30
-- días que la bloquee. alerta y resumen_hora no cuelgan de muestra y se
-- purgan aparte.
--
-- Ejecutar después de 06_esquema_monitor.sql:
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/09_procedimientos_monitor.sql
-- ============================================================================

CREATE OR REPLACE PACKAGE pkg_monitor AS

    -- Consolida en resumen_hora todas las horas completas anteriores a
    -- p_hasta_utc que todavía tengan medicion cruda. Reemplaza (no
    -- acumula) la fila de una hora si ya existía, para poder re-ejecutarse
    -- sin duplicar. No toca un resumen_hora cuyo origen ya fue purgado.
    PROCEDURE consolidar_hora(
        p_hasta_utc IN TIMESTAMP
    );

    -- Purga medicion/consulta_observada (30 días), indice/indice_causa,
    -- alerta cerrada y resumen_hora (13 meses), todo medido hacia atrás
    -- desde p_hasta_utc. Debe llamarse solo si consolidar_hora tuvo éxito.
    PROCEDURE purgar(
        p_hasta_utc IN TIMESTAMP
    );

END pkg_monitor;
/


CREATE OR REPLACE PACKAGE BODY pkg_monitor AS

    -- ------------------------------------------------------------------
    -- consolidar_hora
    -- ------------------------------------------------------------------
    -- Se agrega sobre valor_crudo, no sobre valor_normalizado: es lo que
    -- permite que serieMetrica() (contrato-repositorio-monitor.md §2) siga
    -- mostrando la misma unidad cuando la ventana pedida cruza la frontera
    -- de 30 días entre medicion y resumen_hora — el mismo gráfico, sin una
    -- costura donde cambia de escala.
    --
    -- Las compuertas (valor_crudo NULL) quedan fuera: no tienen una serie
    -- continua que consolidar, tienen historial de alertas.
    -- ------------------------------------------------------------------
    PROCEDURE consolidar_hora(
        p_hasta_utc IN TIMESTAMP
    ) IS
        v_limite TIMESTAMP := TRUNC(p_hasta_utc, 'HH24');
    BEGIN
        DELETE FROM resumen_hora rh
         WHERE rh.hora < v_limite
           AND EXISTS (
                 SELECT 1
                   FROM muestra mu
                  WHERE TRUNC(mu.tomada_en, 'HH24') = rh.hora
               );

        INSERT INTO resumen_hora (
            clave_instancia, codigo_metrica, hora,
            minimo, maximo, promedio, p95, conteo_muestras
        )
        SELECT
            mu.clave_instancia,
            me.codigo_metrica,
            TRUNC(mu.tomada_en, 'HH24') AS hora,
            MIN(me.valor_crudo),
            MAX(me.valor_crudo),
            ROUND(AVG(me.valor_crudo), 2),
            ROUND(PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY me.valor_crudo), 2),
            COUNT(me.valor_crudo)
          FROM medicion me
          JOIN muestra mu ON mu.id_muestra = me.id_muestra
         WHERE mu.tomada_en < v_limite
           AND me.valor_crudo IS NOT NULL
         GROUP BY mu.clave_instancia, me.codigo_metrica, TRUNC(mu.tomada_en, 'HH24');

        COMMIT;
    END consolidar_hora;


    -- ------------------------------------------------------------------
    -- purgar
    -- ------------------------------------------------------------------
    PROCEDURE purgar(
        p_hasta_utc IN TIMESTAMP
    ) IS
        c_dias_medicion   CONSTANT NUMBER := 30;
        c_meses_retencion CONSTANT NUMBER := 13;
        v_limite_crudo    TIMESTAMP := p_hasta_utc - c_dias_medicion;
        v_limite_largo    TIMESTAMP := ADD_MONTHS(p_hasta_utc, -c_meses_retencion);
    BEGIN
        -- 1) medicion (30 días) — sin hijos, se borra directo.
        DELETE FROM medicion
         WHERE id_muestra IN (
             SELECT id_muestra FROM muestra WHERE tomada_en < v_limite_crudo
         );

        -- 2) consulta_observada (30 días) — evidencia de C-066, mismo ciclo
        -- de vida que la muestra que la produjo, no una retención propia.
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

        -- 4) muestra (13 meses, no 30 días): sobrevive lo que dure su hijo
        -- de vida más larga (indice). Para cuando se llega aquí, medicion y
        -- consulta_observada de estas muestras ya se borraron en 1) y 2)
        -- porque 13 meses es siempre más viejo que 30 días.
        DELETE FROM muestra
         WHERE tomada_en < v_limite_largo;

        -- 5) alerta cerrada (13 meses). Antes de borrar, se desengancha
        -- cualquier episodio que la señale como causa probable
        -- (episodio.id_alerta_causa) — si no, el DELETE fallaría por esa
        -- FK para cualquier alerta vieja que todavía sea la causa de un
        -- episodio. Una alerta ABIERTA nunca se purga, sin importar la
        -- edad: un problema sin cerrar no debe desaparecer solo.
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

        -- 6) resumen_hora (13 meses) — independiente de muestra, se filtra
        -- por su propia columna hora.
        DELETE FROM resumen_hora
         WHERE hora < v_limite_largo;

        COMMIT;
    END purgar;

END pkg_monitor;
/
