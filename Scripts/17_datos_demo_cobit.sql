-- ============================================================================
-- EIF402 · Proyecto Integrador — Multinorma
-- Datos de demostración: completar UNA auditoría COBIT 2019
--
-- El gemelo de 12_datos_demo_auditoria.sql para la otra norma. Hacía falta
-- aparte porque COBIT no se captura igual: el 12 rellena los 75 controles ISO
-- con estado y MADUREZ, y aquí la nota no vive en la práctica sino en el
-- OBJETIVO. Son dos tablas y dos escalas:
--
--   evaluacion_control    una fila por PRÁCTICA, con grado de logro N/P/L/F.
--                         El estado se deriva del grado (L y F cuentan como
--                         logrado), igual que hace EvaluacionControl::estadoDeGrado.
--   evaluacion_objetivo   una fila por OBJETIVO, con su capacidad (0 a 5) y la
--                         justificación de ese nivel. De aquí salen los
--                         indicadores: fn_promedio_objetivos en 03 pondera la
--                         capacidad por la relación P/S del objetivo con cada
--                         dimensión, y nunca mira la madurez.
--
-- LA CAPACIDAD NO ES UN DADO: se deriva de las prácticas que acaban de
-- responderse en ese mismo objetivo. Una pantalla donde el objetivo declara
-- capacidad 5 y sus dos prácticas están en N es una maqueta que enseña una
-- incoherencia, y quien la vea en la defensa lo va a preguntar. La
-- justificación dice el recuento del que sale el nivel.
--
-- Es RE-EJECUTABLE y NO PISA NADA: solo toca las prácticas sin responder y los
-- objetivos sin evaluar. Lo que ya esté capturado se queda como está.
--
-- La semilla del generador es el id de la auditoría, así que la misma auditoría
-- da siempre el mismo reparto.
--
-- Lo que NO hace: finalizar la auditoría. Eso es una decisión del auditor.
--
-- Uso (DESDE BASH, no desde PowerShell — la tubería de PowerShell recodifica y
-- las tildes entran dobles; ver CLAUDE.md):
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 \
--       < Scripts/17_datos_demo_cobit.sql
--
-- Para OTRA auditoría sin tocar este archivo, se reescribe el DEFINE al vuelo:
--   sed 's/^DEFINE id_auditoria = .*/DEFINE id_auditoria = 202/' \
--       Scripts/17_datos_demo_cobit.sql \
--     | docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1
-- ============================================================================

SET SERVEROUTPUT ON
SET DEFINE ON

DEFINE id_auditoria = 201

DECLARE
    c_auditoria CONSTANT NUMBER := &id_auditoria;

    TYPE t_frases IS TABLE OF VARCHAR2(400);

    v_hallazgo_no t_frases := t_frases(
        'La práctica se ejecuta, pero no hay definición escrita ni responsable nombrado: depende de quien esté de turno.',
        'Existe el procedimiento y no se aplica de forma consistente en los tres ambientes del alcance.',
        'No hay métrica ni meta asociada, así que nadie puede decir si la práctica está funcionando.',
        'Se ejecuta sin registro: no fue posible demostrar las últimas cuatro ejecuciones.',
        'El resultado depende de una herramienta que ya no tiene soporte del proveedor.',
        'Está definida para la plataforma principal; las bases departamentales quedaron fuera.'
    );

    v_recomendacion t_frases := t_frases(
        'Documentar la práctica, nombrar responsable y fijar la periodicidad de revisión.',
        'Extender la práctica a todos los ambientes del alcance y dejar constancia de cada aplicación.',
        'Definir al menos una métrica con meta y revisarla en el comité de TI.',
        'Habilitar el registro de la ejecución y conservarlo según la política de retención.',
        'Migrar a una herramienta con soporte vigente antes del próximo ciclo de auditoría.',
        'Incluir las bases departamentales en el alcance y reevaluar la práctica.'
    );

    v_hallazgo_si t_frases := t_frases(
        'Lograda. Se verificó contra la documentación aprobada y su registro de ejecución.',
        'Lograda. La práctica es repetible y tiene responsable nombrado.',
        'Lograda, con una observación menor: la métrica se revisa pero no se publica.',
        'Lograda. Se comprobó en los ambientes de producción y contingencia.'
    );

    v_hallazgo_na t_frases := t_frases(
        'No aplica: el objetivo no cubre servicios que la organización opere en el alcance auditado.',
        'No aplica: la actividad está tercerizada y el control recae en el proveedor.',
        'No aplica: no existe ese tipo de tratamiento en las bases del alcance.'
    );

    v_evidencia t_frases := t_frases(
        'Procedimiento aprobado en el gestor documental, con su bitácora de cambios.',
        'Acta del comité de TI donde se revisó la métrica del objetivo.',
        'Registro de ejecución de los últimos tres meses, exportado de la herramienta.',
        'Matriz de responsabilidades firmada por la jefatura de infraestructura.',
        'Demostración en pantalla con el administrador de base de datos y captura del resultado.'
    );

    -- Práctica
    v_dado          NUMBER;
    v_grado         VARCHAR2(1);
    v_estado        VARCHAR2(5);
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

    -- Objetivo
    v_practicas     NUMBER;
    v_logradas      NUMBER;
    v_brecha        NUMBER;
    v_capacidad     NUMBER;
    v_justificacion VARCHAR2(2000);

    v_norma         VARCHAR2(20);
    v_existe        NUMBER;
    v_tocadas       NUMBER := 0;
    v_ya_estaban    NUMBER := 0;
    v_objetivos     NUMBER := 0;

    FUNCTION entre(a NUMBER, b NUMBER) RETURN NUMBER IS
    BEGIN
        RETURN TRUNC(DBMS_RANDOM.VALUE(a, b + 1));
    END;

    FUNCTION alguna(l t_frases) RETURN VARCHAR2 IS
    BEGIN
        RETURN l(entre(1, l.COUNT));
    END;
BEGIN
    DBMS_RANDOM.SEED(c_auditoria);

    SELECT MAX(codigo_estandar) INTO v_norma
      FROM auditoria WHERE id_auditoria = c_auditoria;

    IF v_norma IS NULL THEN
        DBMS_OUTPUT.PUT_LINE('La auditoria ' || c_auditoria || ' no existe. Nada que hacer.');
        RETURN;
    END IF;

    /*
     * La guarda importa: el 12 y este script escriben en tablas distintas, y
     * correr el equivocado deja una auditoria COBIT con madurez ISO o una ISO
     * sin grado de logro. Mejor no hacer nada y decirlo.
     */
    IF v_norma <> 'COBIT2019' THEN
        DBMS_OUTPUT.PUT_LINE('La auditoria ' || c_auditoria || ' es de ' || v_norma
            || ', no de COBIT2019. Para esa use Scripts/12_datos_demo_auditoria.sql.');
        RETURN;
    END IF;

    -- ── 1. Las prácticas ────────────────────────────────────────────────
    FOR c IN (SELECT ctl.codigo, ctl.numero_proceso
                FROM control ctl
                JOIN proceso pro ON pro.numero = ctl.numero_proceso
                JOIN dominio dom ON dom.clave = pro.clave_dominio
               WHERE dom.codigo_estandar = 'COBIT2019'
               ORDER BY ctl.codigo) LOOP

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
         * El reparto de grados. Mayoría de logradas pero con brechas de sobra:
         * una auditoria donde todo esta en F no ejercita ni las remediaciones
         * ni la capacidad por objetivo, que es lo que esta pantalla existe para
         * enseñar.
         */
        v_dado := DBMS_RANDOM.VALUE(0, 1);

        IF v_dado < 0.28 THEN
            v_grado := 'F';
        ELSIF v_dado < 0.60 THEN
            v_grado := 'L';
        ELSIF v_dado < 0.85 THEN
            v_grado := 'P';
        ELSIF v_dado < 0.95 THEN
            v_grado := 'N';
        ELSE
            v_grado := NULL;   -- No aplica
        END IF;

        -- La misma regla que EvaluacionControl::estadoDeGrado().
        v_estado := CASE
                        WHEN v_grado IN ('L', 'F') THEN 'SI'
                        WHEN v_grado IN ('N', 'P') THEN 'NO'
                        ELSE 'NA'
                    END;

        v_conf := 0; v_int := 0; v_disp := 0;
        v_impacto := NULL; v_probabilidad := NULL; v_riesgo := NULL;
        v_hallazgo := NULL; v_recom := NULL; v_evid := NULL; v_calidad := NULL;

        IF v_estado = 'SI' THEN
            v_conf := entre(0, 1);
            v_int  := entre(0, 1);
            v_disp := entre(0, 1);

            IF DBMS_RANDOM.VALUE(0, 1) < 0.6 THEN
                v_impacto      := entre(1, 3);
                v_probabilidad := entre(1, 2);
            END IF;

            -- Obligatorias con estado 'SI' (ck_evalctrl_evidencia_si): una
            -- practica lograda se prueba con evidencia.
            v_evid := alguna(v_evidencia);
            v_calidad := CASE WHEN DBMS_RANDOM.VALUE(0, 1) < 0.7
                              THEN 'BIEN_IMPLEMENTADO' ELSE 'REQUIERE_MEJORA' END;

            IF DBMS_RANDOM.VALUE(0, 1) < 0.45 THEN
                v_hallazgo := alguna(v_hallazgo_si);
            END IF;

        ELSIF v_estado = 'NO' THEN
            v_conf := entre(0, 1);
            v_int  := entre(0, 1);
            v_disp := entre(0, 1);

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
            v_hallazgo := alguna(v_hallazgo_na);
        END IF;

        IF v_impacto IS NOT NULL THEN
            v_riesgo := ROUND((v_impacto + v_probabilidad) / 2, 2);
        END IF;

        MERGE INTO evaluacion_control destino
        USING (SELECT c_auditoria AS id_auditoria, c.codigo AS codigo_control FROM dual) origen
           ON (destino.id_auditoria = origen.id_auditoria
          AND destino.codigo_control = origen.codigo_control)
        WHEN MATCHED THEN
            UPDATE SET destino.estado = v_estado,
                       destino.grado_logro = v_grado,
                       -- Sin madurez a proposito: la nota vive en el objetivo.
                       destino.madurez = NULL,
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
            INSERT (id_auditoria, codigo_control, estado, grado_logro,
                    afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
                    impacto, probabilidad, nivel_riesgo,
                    hallazgo, recomendacion, evidencia_verificada, calidad_evidencia)
            VALUES (c_auditoria, c.codigo, v_estado, v_grado,
                    v_conf, v_int, v_disp,
                    v_impacto, v_probabilidad, v_riesgo,
                    v_hallazgo, v_recom, v_evid, v_calidad);

        v_tocadas := v_tocadas + 1;
    END LOOP;

    COMMIT;

    -- ── 2. Los objetivos ────────────────────────────────────────────────
    FOR o IN (SELECT pro.numero
                FROM proceso pro
                JOIN dominio dom ON dom.clave = pro.clave_dominio
               WHERE dom.codigo_estandar = 'COBIT2019'
               ORDER BY pro.numero) LOOP

        SELECT COUNT(*) INTO v_existe
          FROM evaluacion_objetivo
         WHERE id_auditoria = c_auditoria
           AND numero_proceso = o.numero;

        IF v_existe > 0 THEN
            CONTINUE;
        END IF;

        SELECT COUNT(CASE WHEN ec.estado IN ('SI', 'NO') THEN 1 END),
               COUNT(CASE WHEN ec.estado = 'SI' THEN 1 END),
               COUNT(CASE WHEN ec.estado = 'NO' THEN 1 END)
          INTO v_practicas, v_logradas, v_brecha
          FROM evaluacion_control ec
          JOIN control ctl ON ctl.codigo = ec.codigo_control
         WHERE ec.id_auditoria = c_auditoria
           AND ctl.numero_proceso = o.numero;

        /*
         * De la proporcion de practicas logradas al nivel de capacidad. Los
         * cortes son los de la escala de COBIT que carga 14_multinorma
         * (0 Incompleto .. 5 Optimizado): sin practicas logradas el objetivo
         * esta Incompleto, y solo cuando estan todas se puede hablar de los dos
         * niveles altos.
         */
        IF v_practicas = 0 THEN
            v_capacidad := 0;
        ELSIF v_logradas = 0 THEN
            v_capacidad := 0;
        ELSIF v_logradas = v_practicas THEN
            v_capacidad := entre(4, 5);
        ELSIF v_logradas * 2 >= v_practicas THEN
            v_capacidad := entre(2, 3);
        ELSE
            v_capacidad := 1;
        END IF;

        v_justificacion :=
            'De ' || v_practicas || ' practicas evaluadas, ' || v_logradas
            || ' resultaron logradas (L o F) y ' || v_brecha
            || ' con brecha. El nivel refleja esa proporcion y la evidencia revisada en cada una. '
            || 'Dato de demostracion: generado por Scripts/17_datos_demo_cobit.sql.';

        INSERT INTO evaluacion_objetivo (id_auditoria, numero_proceso, capacidad, justificacion)
        VALUES (c_auditoria, o.numero, v_capacidad, v_justificacion);

        v_objetivos := v_objetivos + 1;
    END LOOP;

    COMMIT;

    DBMS_OUTPUT.PUT_LINE('Auditoria ' || c_auditoria || ': '
        || v_tocadas || ' practicas rellenadas, '
        || v_ya_estaban || ' respetadas por venir ya respondidas, '
        || v_objetivos || ' objetivos evaluados.');

    -- Por lo mismo que en el 12: indicadores viejos son peores que ninguno.
    pkg_indicadores.calcular_riesgo_auditoria(c_auditoria);
    COMMIT;

    DBMS_OUTPUT.PUT_LINE('Indicadores recalculados.');
END;
/

-- Comprobacion: el reparto de grados y la capacidad por objetivo.
SELECT NVL(grado_logro, 'NA') AS grado, COUNT(*) AS practicas
  FROM evaluacion_control
 WHERE id_auditoria = &id_auditoria
 GROUP BY NVL(grado_logro, 'NA')
 ORDER BY 1;

SELECT eo.numero_proceso, eo.capacidad, nm.nombre AS nivel
  FROM evaluacion_objetivo eo
  LEFT JOIN nivel_madurez nm
         ON nm.codigo_estandar = 'COBIT2019' AND nm.nivel = eo.capacidad
 WHERE eo.id_auditoria = &id_auditoria
 ORDER BY eo.numero_proceso;
