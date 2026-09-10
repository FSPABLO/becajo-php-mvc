-- ============================================================================
-- Datos de demostración: la cartera de un auditor a lo largo de varios meses.
--
-- Levanta cuatro auditorías más para el auditor que ya tiene una, sobre LA
-- MISMA organización que ya auditó, repartidas en los meses anteriores, y
-- completa las respuestas de todas —las cuatro nuevas y la que ya existía—
-- para que el panel tenga una evolución real que dibujar en vez de un punto.
--
-- POR QUÉ ES UN SCRIPT Y NO UNOS INSERT A MANO
-- --------------------------------------------
-- Porque es reproducible y revisable: se ve de dónde sale cada cifra, se puede
-- volver a correr sin duplicar nada y se lee en el repositorio sin tener que
-- abrir la base para saber qué se metió.
--
-- ES RE-EJECUTABLE. Cada auditoría se crea solo si no existe ya una del mismo
-- auditor con la misma área y fecha, y cada respuesta se inserta solo para los
-- controles que esa auditoría todavía no tiene. Correrlo dos veces deja la
-- base igual que correrlo una.
--
-- SOLO INSERTA. Ninguna respuesta ya cargada se modifica ni se borra, así que
-- el trabajo real que hubiera en la auditoría existente sobrevive intacto: lo
-- único que se le añade son los controles que le faltaban por responder.
--
-- Ejecutar después de 01, 02 y 03:
--   docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/05_datos_demo_evolucion.sql
-- ============================================================================

SET SERVEROUTPUT ON

DECLARE
    -- A quién se le llena la cartera. Se busca por correo y no por id: los id
    -- son IDENTITY y cambian según en qué orden se sembró cada base.
    c_correo_auditor CONSTANT VARCHAR2(150) := 'benjamin.solano@becajo.test';

    v_id_auditor   NUMBER;
    v_id_admin     NUMBER;
    -- El entrevistado de la auditoría de partida puede estar escrito a
    -- mano en vez de ser una cuenta: entonces v_id_admin viene NULO y lo
    -- que se copia son estas dos columnas de texto.
    v_nombre_admin VARCHAR2(150);
    v_id_previa    NUMBER;
    v_organizacion VARCHAR2(200);   -- el ancho de la columna, no uno menor
    v_id_auditoria NUMBER;
    v_controles    NUMBER;
    v_existe       NUMBER;

    /*
     * El plan de cada mes. La progresión es lo que se quiere ver en el panel:
     * más controles respondidos y mejor cumplimiento cada mes.
     *
     *   respondidos — cuántos de los 75 controles se alcanzaron a evaluar.
     *   banda_si    — de cada 100 respondidos, cuántos salen conformes. Va
     *                 sobre el total respondido, así que el cumplimiento que
     *                 acaba mostrando el panel —que descuenta los "no aplica"—
     *                 queda unos puntos por encima de esta cifra.
     *   madurez_min — piso de madurez de los conformes; sube con los meses.
     */
    TYPE t_plan IS RECORD (
        area        VARCHAR2(200),
        fecha       DATE,
        respondidos NUMBER,
        banda_si    NUMBER,
        madurez_min NUMBER,
        paso        NUMBER   -- 1..5; baja el riesgo conforme avanza
    );

    TYPE t_planes IS TABLE OF t_plan;

    v_planes t_planes := t_planes(NULL, NULL, NULL, NULL);

    /*
     * Rellena una auditoría hasta el número de controles del plan.
     *
     * El reparto sí / no / no-aplica se decide con un rebote determinista
     * sobre el número de fila (MOD(rn * 37, 100)): sale siempre igual, pero
     * desperdiga los conformes por todos los dominios en vez de amontonarlos
     * en los primeros — que es lo que pasaría respondiendo «los primeros N
     * conformes y el resto no», y dejaría el cumplimiento por dominio en
     * 100 % arriba y 0 % abajo.
     */
    PROCEDURE llenar(
        p_id_auditoria IN NUMBER,
        p_respondidos  IN NUMBER,
        p_banda_si     IN NUMBER,
        p_madurez_min  IN NUMBER,
        p_fecha        IN DATE,
        p_paso         IN NUMBER
    ) IS
        v_nuevas NUMBER := 0;
        v_ya     NUMBER;
    BEGIN
        FOR c IN (
            SELECT codigo, rn, MOD(rn * 37, 100) AS rebote
              FROM (SELECT codigo, ROW_NUMBER() OVER (ORDER BY codigo) AS rn FROM control)
             WHERE rn <= p_respondidos
        ) LOOP
            -- Solo los controles que esta auditoría todavía no tiene: nunca se
            -- pisa una respuesta ya cargada.
            SELECT COUNT(*) INTO v_ya
              FROM evaluacion_control
             WHERE id_auditoria = p_id_auditoria
               AND codigo_control = c.codigo;

            CONTINUE WHEN v_ya > 0;

            DECLARE
                v_estado    VARCHAR2(5);
                v_madurez   NUMBER;
                v_impacto   NUMBER;
                v_prob      NUMBER;
                v_criterio  VARCHAR2(20);
                v_evidencia VARCHAR2(400);
                v_calidad   VARCHAR2(20);
                v_hallazgo  VARCHAR2(400);
                v_recom     VARCHAR2(400);
                v_riesgo    NUMBER;
            BEGIN
                IF c.rebote < 8 THEN
                    v_estado := 'NA';
                ELSIF c.rebote < 8 + p_banda_si THEN
                    v_estado := 'SI';
                ELSE
                    v_estado := 'NO';
                END IF;

                IF v_estado = 'SI' THEN
                    v_madurez   := LEAST(5, p_madurez_min + MOD(c.rn, 3));
                    v_criterio  := CASE MOD(c.rn, 3)
                                       WHEN 0 THEN 'DOCUMENTADO'
                                       WHEN 1 THEN 'REPETIBLE'
                                       ELSE 'EVIDENCIA'
                                   END;
                    -- Obligatorio cuando la respuesta es «sí»: lo exige
                    -- ck_evalctrl_evidencia_si, y con razón (ISO/IEC 27007 —
                    -- la conformidad se prueba con evidencia).
                    v_evidencia := 'Procedimiento vigente y bitácora de aplicación revisados durante la entrevista ('
                                || TO_CHAR(p_fecha, 'YYYY-MM-DD') || ').';
                    v_calidad   := CASE
                                       WHEN v_madurez >= 4 THEN 'BIEN_IMPLEMENTADO'
                                       WHEN v_madurez = 3  THEN 'REQUIERE_MEJORA'
                                       ELSE 'DECLARATIVO'
                                   END;
                    v_hallazgo  := NULL;
                    v_recom     := NULL;
                ELSIF v_estado = 'NO' THEN
                    v_madurez   := GREATEST(0, p_madurez_min - 1 + MOD(c.rn, 2));
                    v_criterio  := NULL;
                    v_evidencia := NULL;
                    v_calidad   := NULL;
                    v_hallazgo  := 'El control no está implementado de forma verificable en el alcance evaluado.';
                    v_recom     := 'Documentar el procedimiento, asignar responsable y dejar registro de su aplicación.';
                ELSE
                    v_madurez   := NULL;
                    v_criterio  := NULL;
                    v_evidencia := NULL;
                    v_calidad   := NULL;
                    v_hallazgo  := NULL;
                    v_recom     := NULL;
                END IF;

                /*
                 * Impacto y probabilidad solo donde el control se calificó, y
                 * bajando conforme avanzan los meses: es lo que hace que la
                 * matriz del panel se vaya despoblando de la esquina roja. Un
                 * «no aplica» no lleva coordenadas — no está en ninguna casilla
                 * del tablero, y ponerlo en una sería inventarlo.
                 */
                IF v_estado = 'NA' THEN
                    v_impacto := NULL;
                    v_prob    := NULL;
                    v_riesgo  := NULL;
                ELSE
                    v_impacto := GREATEST(1, LEAST(5, 5 - MOD(c.rebote, 3) - FLOOR(p_paso / 3)));
                    v_prob    := GREATEST(1, LEAST(5,
                                     CASE WHEN v_estado = 'SI' THEN 2 ELSE 4 END
                                     + MOD(c.rn, 2) - FLOOR(p_paso / 3)));
                    v_riesgo  := (v_impacto + v_prob) / 2;
                END IF;

                INSERT INTO evaluacion_control (
                    id_auditoria, codigo_control, estado, madurez, criterio,
                    afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
                    impacto, probabilidad, nivel_riesgo,
                    hallazgo, recomendacion, evidencia_verificada, calidad_evidencia
                ) VALUES (
                    p_id_auditoria, c.codigo, v_estado, v_madurez, v_criterio,
                    CASE WHEN MOD(c.rn, 2) = 0 THEN 1 ELSE 0 END,
                    CASE WHEN MOD(c.rn, 3) = 0 THEN 1 ELSE 0 END,
                    CASE WHEN MOD(c.rn, 5) = 0 THEN 1 ELSE 0 END,
                    v_impacto, v_prob, v_riesgo,
                    v_hallazgo, v_recom, v_evidencia, v_calidad
                );

                v_nuevas := v_nuevas + 1;
            END;
        END LOOP;

        DBMS_OUTPUT.PUT_LINE('    respuestas nuevas: ' || v_nuevas
            || ' · meta del mes: ' || p_respondidos);
    END llenar;
BEGIN
    v_planes(1).area := 'Gestión de accesos y privilegios';
    v_planes(1).fecha := DATE '2026-04-14';
    v_planes(1).respondidos := 18;
    v_planes(1).banda_si := 37;
    v_planes(1).madurez_min := 1;
    v_planes(1).paso := 1;

    v_planes(2).area := 'Respaldos y recuperación';
    v_planes(2).fecha := DATE '2026-05-19';
    v_planes(2).respondidos := 31;
    v_planes(2).banda_si := 48;
    v_planes(2).madurez_min := 2;
    v_planes(2).paso := 2;

    v_planes(3).area := 'Servidores de base de datos de producción';
    v_planes(3).fecha := DATE '2026-06-16';
    v_planes(3).respondidos := 45;
    v_planes(3).banda_si := 56;
    v_planes(3).madurez_min := 2;
    v_planes(3).paso := 3;

    v_planes(4).area := 'Cifrado y protección del dato';
    v_planes(4).fecha := DATE '2026-07-21';
    v_planes(4).respondidos := 58;
    v_planes(4).banda_si := 63;
    v_planes(4).madurez_min := 3;
    v_planes(4).paso := 4;

    SELECT COUNT(*) INTO v_controles FROM control;

    BEGIN
        SELECT id_usuario INTO v_id_auditor
          FROM usuario
         WHERE LOWER(correo) = LOWER(c_correo_auditor);
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            RAISE_APPLICATION_ERROR(-20001, 'No existe el auditor ' || c_correo_auditor);
    END;

    /*
     * La auditoría de partida es la de menor id: la que el auditor ya tenía
     * antes de que este script existiera. Se usa para dos cosas — deducir la
     * organización auditada y, al final, terminar de responderla.
     *
     * Por id y no por fecha: las cuatro que crea el script son anteriores en
     * el calendario, así que «la más antigua por fecha» dejaría de ser la
     * misma en cuanto se corriera una vez.
     */
    BEGIN
        SELECT MIN(id_auditoria) INTO v_id_previa
          FROM auditoria
         WHERE id_auditor = v_id_auditor;

        SELECT a.id_administrador_bd, a.administrador_nombre, e.organizacion
          INTO v_id_admin, v_nombre_admin, v_organizacion
          FROM auditoria a
          JOIN v_auditoria_entrevistado e ON e.id_auditoria = a.id_auditoria
         WHERE a.id_auditoria = v_id_previa;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            RAISE_APPLICATION_ERROR(-20002,
                'El auditor no tiene ninguna auditoría previa de la cual deducir la organización.');
    END;

    /*
     * La organización auditada NO se escribe aquí: se deduce del administrador
     * de BD que el auditor entrevistó. Así «la misma empresa» lo dice la base y
     * no un literal que se queda viejo el día que alguien la renombre.
     */
    DBMS_OUTPUT.PUT_LINE('Auditor ' || v_id_auditor || ' · organización auditada: ' || v_organizacion);
    DBMS_OUTPUT.PUT_LINE('Auditoría de partida: ' || v_id_previa);

    FOR i IN 1 .. v_planes.COUNT LOOP

        SELECT COUNT(*) INTO v_existe
          FROM auditoria
         WHERE id_auditor = v_id_auditor
           AND area_evaluada = v_planes(i).area
           AND fecha = v_planes(i).fecha;

        IF v_existe > 0 THEN
            SELECT MIN(id_auditoria) INTO v_id_auditoria
              FROM auditoria
             WHERE id_auditor = v_id_auditor
               AND area_evaluada = v_planes(i).area
               AND fecha = v_planes(i).fecha;

            DBMS_OUTPUT.PUT_LINE('  ya existía la auditoría ' || v_id_auditoria
                || ' (' || v_planes(i).area || ')');
        ELSE
            -- Quedan EN_PROGRESO: son las auditorías pendientes del auditor.
            -- El entrevistado se copia del lado del que viniera: con la cuenta,
            -- las dos columnas de texto van a NULL, y al revés. Rellenar los
            -- tres campos choca contra ck_auditoria_administrador.
            INSERT INTO auditoria (id_auditor, id_administrador_bd,
                                   administrador_nombre, administrador_organizacion,
                                   area_evaluada, fecha, estado)
            VALUES (v_id_auditor, v_id_admin,
                    CASE WHEN v_id_admin IS NULL THEN v_nombre_admin END,
                    CASE WHEN v_id_admin IS NULL THEN v_organizacion END,
                    v_planes(i).area, v_planes(i).fecha, 'EN_PROGRESO')
            RETURNING id_auditoria INTO v_id_auditoria;

            DBMS_OUTPUT.PUT_LINE('  creada la auditoría ' || v_id_auditoria
                || ' (' || v_planes(i).area || ' · ' || TO_CHAR(v_planes(i).fecha, 'YYYY-MM-DD') || ')');
        END IF;

        llenar(v_id_auditoria, v_planes(i).respondidos, v_planes(i).banda_si,
               v_planes(i).madurez_min, v_planes(i).fecha, v_planes(i).paso);
    END LOOP;

    /*
     * Y se termina de responder la que ya existía. Sin esto el último mes de
     * la serie —el más reciente— se queda con un puñado de controles y el
     * gráfico enseña una caída al final que no es una caída: es una auditoría
     * a medio empezar.
     */
    DECLARE
        v_fecha_previa DATE;
    BEGIN
        SELECT fecha INTO v_fecha_previa FROM auditoria WHERE id_auditoria = v_id_previa;

        DBMS_OUTPUT.PUT_LINE('  completando la auditoría de partida ' || v_id_previa);
        llenar(v_id_previa, 66, 70, 3, v_fecha_previa, 5);
    END;

    COMMIT;

    /*
     * Los indicadores no se escriben a mano: se recalculan con el mismo
     * procedimiento que usa la aplicación al guardar. Si el cálculo cambiara
     * mañana, estos datos cambian con él en vez de quedarse contando una
     * versión vieja de la verdad.
     */
    FOR a IN (SELECT id_auditoria FROM auditoria WHERE id_auditor = v_id_auditor) LOOP
        pkg_indicadores.calcular_riesgo_auditoria(a.id_auditoria);
    END LOOP;

    DBMS_OUTPUT.PUT_LINE('Indicadores recalculados.');
END;
/

EXIT
