-- ============================================================================
-- EIF402 · Proyecto Integrador — Evaluación de Riesgo ISO/IEC 27002
-- Datos de demostración: completar UNA auditoría con respuestas verosímiles
--
-- Para qué: dejar una auditoría con sus 75 controles respondidos, de modo que
-- la pantalla de resultados, la matriz C/I/D, el reporte ejecutivo y los
-- indicadores tengan algo que enseñar sin teclear setenta y cinco fichas a
-- mano. NO es un dato de producción: es la maqueta de una auditoría terminada.
--
-- Qué auditoría: la del DEFINE de abajo. Cámbielo para rellenar otra.
--
-- Es RE-EJECUTABLE y NO PISA NADA. Solo toca los controles que siguen sin
-- responder (sin fila, o con fila y estado NULL); los que el auditor ya
-- contestó se quedan exactamente como están. Correrlo dos veces la segunda vez
-- no hace nada, porque ya no queda ninguno sin responder.
--
-- La semilla del generador es el id de la auditoría, así que la misma auditoría
-- da siempre las mismas respuestas: dos personas que corran el script ven la
-- misma pantalla, y una captura de pantalla de la memoria sigue siendo válida
-- mañana.
--
-- Lo que NO hace: finalizar la auditoría. Eso la bloquea contra cambios y es
-- una decisión del auditor, a un clic en «Finalizar» desde /evaluacion/{id}.
--
-- Uso (DESDE BASH, no desde PowerShell — la tubería de PowerShell recodifica y
-- las tildes entran dobles; ver CLAUDE.md):
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 \
--       < Scripts/12_datos_demo_auditoria.sql
--
-- Para OTRA auditoria sin tocar este archivo, se reescribe el DEFINE al vuelo.
-- El script se lee por la entrada estandar, asi que los parametros posicionales
-- de sqlplus (@script 161) no llegan; sed si:
--   sed 's/^DEFINE id_auditoria = .*/DEFINE id_auditoria = 161/' \
--       Scripts/12_datos_demo_auditoria.sql \
--     | docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE ON

DEFINE id_auditoria = 152

DECLARE
    c_auditoria CONSTANT NUMBER := &id_auditoria;

    TYPE t_frases IS TABLE OF VARCHAR2(400);

    /*
     * Los textos. Se eligen por índice aleatorio, no se concatenan a partir de
     * plantillas: un hallazgo generado con «El control X presenta la deficiencia
     * Y» se lee como lo que es, y la maqueta existe justamente para que la
     * pantalla se parezca a una auditoría de verdad.
     */
    v_hallazgo_no t_frases := t_frases(
        'No existe evidencia documental del procedimiento; se ejecuta por costumbre y depende de una sola persona.',
        'El procedimiento está definido pero no se ha revisado desde hace más de dos años y no refleja la plataforma actual.',
        'La configuración observada en el motor no coincide con la que declara la política vigente.',
        'Se realiza de forma reactiva: no hay calendario ni responsable asignado por escrito.',
        'Los registros existen pero nadie los revisa, así que una desviación podría pasar meses sin detectarse.',
        'La medida está implementada solo en el ambiente productivo principal; las réplicas quedaron fuera del alcance.',
        'No fue posible demostrar la última ejecución: no se conserva bitácora ni acuse.',
        'Hay controles compensatorios manuales, pero no están documentados ni se prueban periódicamente.'
    );

    v_recomendacion t_frases := t_frases(
        'Documentar el procedimiento, asignar responsable y fijar una periodicidad de revisión.',
        'Actualizar la política a la versión actual del motor y aprobarla formalmente.',
        'Alinear la configuración del motor con lo declarado y dejar constancia del cambio.',
        'Establecer un calendario con recordatorio automático y registro de cada ejecución.',
        'Definir una revisión periódica de los registros con responsable y evidencia de la revisión.',
        'Extender la medida a todas las instancias del alcance, incluidas réplicas y ambientes de contingencia.',
        'Habilitar la bitácora del control y conservarla según el periodo de retención acordado.',
        'Formalizar los controles compensatorios y programar pruebas al menos semestrales.'
    );

    v_hallazgo_si t_frases := t_frases(
        'Cumple. Se verificó contra la configuración vigente y la documentación aprobada.',
        'Cumple. La ejecución periódica está registrada y es trazable.',
        'Cumple, con una observación menor: la documentación va una versión por detrás de la práctica.',
        'Cumple. Se comprobó en las tres instancias del alcance.'
    );

    v_hallazgo_na t_frases := t_frases(
        'No aplica: la organización no opera ese componente en el alcance auditado.',
        'No aplica: la funcionalidad no está licenciada en la edición del motor en uso.',
        'No aplica: el servicio está tercerizado y el control recae en el proveedor.',
        'No aplica: no existe tratamiento de ese tipo de dato en las bases del alcance.'
    );

    v_evidencia t_frases := t_frases(
        'Política vigente en el gestor documental, con firma de aprobación de la dirección.',
        'Captura de la configuración del motor y salida de la consulta de parámetros.',
        'Bitácora de ejecución de los últimos tres meses, exportada desde la consola.',
        'Acta de la reunión de revisión y matriz de responsabilidades firmada.',
        'Registro de la última prueba de restauración, con hora de inicio y de fin.',
        'Reporte de la herramienta de monitoreo correspondiente al periodo auditado.',
        'Entrevista con el administrador de base de datos y demostración en pantalla.'
    );

    v_criterio t_frases := t_frases('DOCUMENTADO', 'REPETIBLE', 'EVIDENCIA');

    -- Estado de cada control
    v_dado          NUMBER;
    v_estado        VARCHAR2(5);
    v_madurez       NUMBER;
    v_criterio_val  VARCHAR2(20);
    v_conf          NUMBER;
    v_int           NUMBER;
    v_disp          NUMBER;
    v_impacto       NUMBER;
    v_probabilidad  NUMBER;
    v_riesgo        NUMBER;
    v_hallazgo      CLOB;
    v_recom         CLOB;
    v_evid          CLOB;
    v_calidad       VARCHAR2(20);

    v_existe        NUMBER;
    v_tocados       NUMBER := 0;
    v_ya_estaban    NUMBER := 0;

    /* Un entero de a a b, ambos incluidos. */
    FUNCTION entre(a NUMBER, b NUMBER) RETURN NUMBER IS
    BEGIN
        RETURN TRUNC(DBMS_RANDOM.VALUE(a, b + 1));
    END;

    /* Un elemento cualquiera de la lista. */
    FUNCTION alguna(l t_frases) RETURN VARCHAR2 IS
    BEGIN
        RETURN l(entre(1, l.COUNT));
    END;
BEGIN
    /*
     * La semilla es el id: la misma auditoría sale siempre igual. Sin esto,
     * volver a correr el script sobre otra base daría otro reparto y las cifras
     * del manual dejarían de coincidir con la pantalla.
     */
    DBMS_RANDOM.SEED(c_auditoria);

    SELECT COUNT(*) INTO v_existe FROM auditoria WHERE id_auditoria = c_auditoria;

    IF v_existe = 0 THEN
        DBMS_OUTPUT.PUT_LINE('La auditoria ' || c_auditoria || ' no existe. Nada que hacer.');
        RETURN;
    END IF;

    FOR c IN (SELECT codigo FROM control ORDER BY codigo) LOOP

        SELECT COUNT(*) INTO v_existe
          FROM evaluacion_control
         WHERE id_auditoria = c_auditoria
           AND codigo_control = c.codigo
           AND estado IS NOT NULL;

        IF v_existe > 0 THEN
            v_ya_estaban := v_ya_estaban + 1;
            CONTINUE;
        END IF;

        /*
         * El reparto: mayoría de cumplimientos, un tercio largo de hallazgos y
         * unos pocos «no aplica». Una auditoría en la que todo cumple no
         * ejercita ni la matriz de riesgo ni las remediaciones; una en la que
         * nada cumple no se parece a ninguna auditoría real.
         */
        v_dado := DBMS_RANDOM.VALUE(0, 1);

        IF v_dado < 0.58 THEN
            v_estado := 'SI';
        ELSIF v_dado < 0.90 THEN
            v_estado := 'NO';
        ELSE
            v_estado := 'NA';
        END IF;

        v_criterio_val := alguna(v_criterio);
        v_conf := 0; v_int := 0; v_disp := 0;
        v_impacto := NULL; v_probabilidad := NULL; v_riesgo := NULL;
        v_hallazgo := NULL; v_recom := NULL; v_evid := NULL; v_calidad := NULL;

        IF v_estado = 'SI' THEN
            -- Cumple: madurez alta y, cuando toca, riesgo residual bajo.
            v_madurez := entre(3, 5);
            v_conf := entre(0, 1);
            v_int  := entre(0, 1);
            v_disp := entre(0, 1);

            IF DBMS_RANDOM.VALUE(0, 1) < 0.6 THEN
                v_impacto      := entre(1, 3);
                v_probabilidad := entre(1, 2);
            END IF;

            -- Obligatorias con estado 'SI' (ck_evalctrl_evidencia_si y la
            -- validacion de AuditoriaController): la conformidad se prueba con
            -- evidencia, no con la afirmacion del auditado.
            v_evid := alguna(v_evidencia);
            v_calidad := CASE WHEN DBMS_RANDOM.VALUE(0, 1) < 0.7
                              THEN 'BIEN_IMPLEMENTADO' ELSE 'REQUIERE_MEJORA' END;

            IF DBMS_RANDOM.VALUE(0, 1) < 0.45 THEN
                v_hallazgo := alguna(v_hallazgo_si);
            END IF;

        ELSIF v_estado = 'NO' THEN
            -- Hallazgo: madurez baja, riesgo siempre calculable y remediacion.
            v_madurez := entre(0, 2);
            v_conf := entre(0, 1);
            v_int  := entre(0, 1);
            v_disp := entre(0, 1);

            -- Un hallazgo que no compromete nada no tendria por que ser un
            -- hallazgo. Si el dado dejo las tres en cero, se marca integridad.
            IF v_conf + v_int + v_disp = 0 THEN
                v_int := 1;
            END IF;

            v_impacto      := entre(2, 5);
            v_probabilidad := entre(2, 5);

            v_hallazgo := alguna(v_hallazgo_no);
            v_recom    := alguna(v_recomendacion);
            v_evid     := alguna(v_evidencia);
            v_calidad  := CASE WHEN DBMS_RANDOM.VALUE(0, 1) < 0.5
                               THEN 'DECLARATIVO' ELSE 'REQUIERE_MEJORA' END;

        ELSE
            /*
             * No aplica: sin madurez —no se evalua nada que graduar— y sin
             * riesgo. Pero SIEMPRE con justificacion escrita: un 'NA' sin
             * justificar sale del denominador del cumplimiento, y la pantalla
             * lo marca con un aviso justamente para que no sea la salida facil.
             */
            v_madurez := NULL;
            v_criterio_val := NULL;
            v_hallazgo := alguna(v_hallazgo_na);
        END IF;

        IF v_impacto IS NOT NULL THEN
            -- La misma formula que EvaluacionControl::nivelRiesgoCalculado().
            v_riesgo := ROUND((v_impacto + v_probabilidad) / 2, 2);
        END IF;

        MERGE INTO evaluacion_control destino
        USING (SELECT c_auditoria AS id_auditoria, c.codigo AS codigo_control FROM dual) origen
           ON (destino.id_auditoria = origen.id_auditoria
          AND destino.codigo_control = origen.codigo_control)
        WHEN MATCHED THEN
            UPDATE SET destino.estado = v_estado,
                       destino.madurez = v_madurez,
                       destino.criterio = v_criterio_val,
                       destino.afecta_confidencialidad = v_conf,
                       destino.afecta_integridad = v_int,
                       destino.afecta_disponibilidad = v_disp,
                       destino.impacto = v_impacto,
                       destino.probabilidad = v_probabilidad,
                       destino.nivel_riesgo = v_riesgo,
                       destino.hallazgo = v_hallazgo,
                       destino.recomendacion = v_recom,
                       destino.evidencia_verificada = v_evid,
                       destino.calidad_evidencia = v_calidad
        WHEN NOT MATCHED THEN
            INSERT (id_auditoria, codigo_control, estado, madurez, criterio,
                    afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
                    impacto, probabilidad, nivel_riesgo,
                    hallazgo, recomendacion, evidencia_verificada, calidad_evidencia)
            VALUES (c_auditoria, c.codigo, v_estado, v_madurez, v_criterio_val,
                    v_conf, v_int, v_disp,
                    v_impacto, v_probabilidad, v_riesgo,
                    v_hallazgo, v_recom, v_evid, v_calidad);

        v_tocados := v_tocados + 1;
    END LOOP;

    COMMIT;

    DBMS_OUTPUT.PUT_LINE('Auditoria ' || c_auditoria || ': '
        || v_tocados || ' controles rellenados, '
        || v_ya_estaban || ' respetados por venir ya respondidos.');

    /*
     * Los indicadores se recalculan aqui por la misma razon que
     * AuditoriaController lo hace en cada guardado: una auditoria con
     * respuestas y con la exposicion al riesgo sin actualizar enseña cifras
     * viejas, que es peor que no enseñar ninguna.
     */
    pkg_indicadores.calcular_riesgo_auditoria(c_auditoria);
    COMMIT;

    DBMS_OUTPUT.PUT_LINE('Indicadores recalculados.');
END;
/

-- Comprobacion: como quedo el reparto.
SELECT estado,
       COUNT(*) AS controles,
       ROUND(AVG(madurez), 2) AS madurez_media,
       ROUND(AVG(nivel_riesgo), 2) AS riesgo_medio
  FROM evaluacion_control
 WHERE id_auditoria = &id_auditoria
 GROUP BY estado
 ORDER BY estado;

SELECT COUNT(*) AS total_respondidos
  FROM evaluacion_control
 WHERE id_auditoria = &id_auditoria
   AND estado IS NOT NULL;
