<?php

declare(strict_types=1);

/**
 * Los procesos y recursos que evalúa un índice.
 *
 * Es lo que se despliega al pulsar la ficha de IP, IM o IA. El componente trae
 * su propio encabezado y su propia ayuda —`monitor-ayuda`—: título, botón de
 * «?» y tabla son una pieza, y separarlos obligaba a que quien la coloca
 * supiera cómo se rotula.
 *
 * ── Cómo se reparte el ancho ─────────────────────────────────────────────────
 *
 * Izquierda, lo que se compara: el proceso, una columna por métrica y el
 * resultado. Derecha, lo que se lee: qué es ese proceso y qué hacer con ello.
 *
 * Las dos mitades quieren cosas opuestas —los números, estar juntos para poder
 * recorrerlos con la vista; la prosa, ancho— y por eso ninguna se estira sola:
 * las columnas de métrica se ajustan a su contenido y el sobrante se lo reparten
 * DESCRIPCIÓN y RECOMENDACIÓN, que son las únicas con anchura declarada.
 *
 * ── Descripción y recomendación son dos columnas, no una ─────────────────────
 *
 * Responden preguntas distintas: «qué es esto» y «qué hago con ello». Estuvieron
 * en un solo párrafo y obligaban a leerse la definición entera cada vez que uno
 * venía buscando solo la acción. Vienen ya separadas del catálogo, no partidas
 * al pintar.
 *
 * ── Celda vacía y celda con guion NO son lo mismo ────────────────────────────
 *
 * - **Vacía**: esa métrica no evalúa a ese proceso. `M-PRO-01` mide el cupo de
 *   sesiones y no dice nada sobre PMON; poner un cero o un guion ahí sugeriría
 *   que sí lo mide y salió mal.
 * - **Guion en gris**: sí lo evalúa, pero no se pudo recolectar. Lleva el motivo
 *   en el título emergente.
 *
 * Es el invariante 3 llevado a la cuadrícula: lo que no se midió no puede
 * parecer un cero. Esa distinción es la que explica el botón de ayuda.
 *
 * ── La casilla de resultado tiene TRES estados ───────────────────────────────
 *
 * Marcada, con aspa, y **sin dato**. Un proceso cuyas métricas no se pudieron
 * recolectar no ha fallado: pintarlo con aspa convertiría una falla del agente
 * en una falla de la base. Y cada casilla lleva su palabra al lado, porque
 * palomita y aspa se distinguen por forma pero también por color, y en gris se
 * parecen más de lo que conviene.
 *
 * @var \App\Core\Vista $vista
 * @var list<string> $columnas   Códigos de métrica, ya ordenados.
 * @var array<string, array<string, string>> $fichas  Nombre y qué mide, por código.
 * @var list<array<string, mixed>> $filas
 * @var string $indice   Clave del componente (PROCESOS | MEMORIA | ARCHIVOS).
 */
$idAyuda = 'ayuda-procesos-' . strtolower($indice);
?>
<div class="flex items-start justify-between gap-4">

    <h3 class="text-xs font-semibold uppercase tracking-[0.2em] text-texto-2">
        <?= e($vista->t('mon.procesos_de', $vista->t('mon.comp_' . strtolower($indice)))) ?>
    </h3>

    <?php
    /*
     * La nota que explica la tabla vivía al pie, siempre visible: cinco líneas
     * de letra pequeña que se leen una vez y estorban las mil siguientes. Aquí
     * se pide, y solo aparece cuando hace falta. La mecánica —hover, teclado,
     * árbol de accesibilidad— vive en el componente, no repetida aquí.
     */
    ?>
    <?= $vista->componente('monitor-ayuda', [
        'vista'     => $vista,
        'id'        => $idAyuda,
        'etiqueta'  => $vista->t('mon.ayuda_tabla'),
        'parrafos'  => [$vista->t('mon.procesos_nota')],
    ]) ?>
</div>

<?php if ($filas === []): ?>
    <p class="rv-hundido mt-4 rounded-rv border border-borde bg-fondo px-4 py-8 text-center text-sm text-texto-2">
        <?= e($vista->t('mon.sin_procesos')) ?>
    </p>
<?php else: ?>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <?php
            /*
             * Cabecera de DOS filas. Los códigos de métrica, solos, dirían qué
             * mide cada columna pero no que todas esas columnas son métricas: la
             * fila de arriba las agrupa bajo un único rótulo y las demás la
             * atraviesan con rowspan.
             */
            ?>
            <thead>
                <tr class="text-xs uppercase tracking-wider text-texto-2">
                    <th scope="col" rowspan="2" class="py-2.5 pr-4 align-bottom font-medium">
                        <?= e($vista->t('mon.col_proceso')) ?>
                    </th>

                    <th scope="colgroup" colspan="<?= count($columnas) ?>"
                        class="border-b border-borde px-2.5 pb-1.5 text-center font-medium">
                        <?= e($vista->t('mon.col_metricas')) ?>
                    </th>

                    <th scope="col" rowspan="2" class="whitespace-nowrap px-4 py-2.5 align-bottom font-medium">
                        <?= e($vista->t('mon.col_resultado')) ?>
                    </th>

                    <?php
                    /*
                     * Las dos columnas de prosa se reparten el ancho sobrante a
                     * partes iguales. Sin ese reparto el navegador se lo daba a
                     * las de métrica, que quedaban tan separadas que leer una
                     * hacia abajo —para lo que existe la matriz— obligaba a
                     * recorrer medio panel con la vista.
                     */
                    ?>
                    <th scope="col" rowspan="2" class="w-[30%] px-4 py-2.5 align-bottom font-medium">
                        <?= e($vista->t('mon.col_descripcion')) ?>
                    </th>
                    <th scope="col" rowspan="2" class="w-[30%] py-2.5 pl-4 align-bottom font-medium">
                        <?= e($vista->t('mon.col_recomendacion')) ?>
                    </th>
                </tr>

                <tr class="border-b border-borde">
                    <?php foreach ($columnas as $codigo): ?>
                        <?php
                        /*
                         * El código en la cabecera, en oro y monoespaciada: es
                         * una referencia normativa —la ficha del catálogo de
                         * métricas—, que es lo único que el oro significa aquí.
                         * No se envuelve: partido en dos líneas deja de leerse
                         * como un identificador.
                         *
                         * ── Y qué mide, al pasar por encima ─────────────────
                         *
                         * El código identifica pero no explica: `M-PRO-01` dice
                         * «métrica de procesos, la primera» y nada más. En la
                         * cabecera no cabe el nombre —seis columnas de texto
                         * largo dejarían la matriz ilegible—, así que el nombre
                         * y el resumen se piden.
                         *
                         * El disparador es un `<span tabindex="0">` y no un
                         * botón: no ejecuta nada, solo se consulta, y un botón
                         * anunciaría una acción que no existe. Con `tabindex`
                         * entra en el recorrido del teclado, que es lo que hace
                         * falta para que `group-focus-within` lo abra sin ratón.
                         *
                         * El panel se abre hacia ABAJO y centrado: cae dentro
                         * del contenedor con `overflow-x-auto`, que recorta lo
                         * que se salga. Hacia arriba quedaría cortado por el
                         * borde de la tabla.
                         *
                         * El subrayado punteado es la señal de «esto se puede
                         * consultar»; sin él nadie descubre que hay ayuda.
                         */
                        $ficha = $fichas[$codigo] ?? null;
                        /*
                         * Prefijo `metrica-` y no `ficha-`: las tarjetas IP/IM/IA
                         * ya usan `ficha-procesos`, `ficha-memoria`… y tener dos
                         * cosas distintas llamadas «ficha» en la misma página
                         * confunde a quien lea el DOM después.
                         */
                        $idFicha = 'metrica-' . strtolower($indice) . '-' . strtolower(str_replace('-', '', $codigo));
                        ?>
                        <th scope="col" class="px-2.5 pb-2.5 text-right text-[11px] font-normal">
                            <?php if ($ficha === null): ?>
                                <span class="rv-id whitespace-nowrap text-oro-texto"><?= e($codigo) ?></span>
                            <?php else: ?>
                                <span class="group relative inline-block">
                                    <span tabindex="0"
                                          aria-describedby="<?= e($idFicha) ?>"
                                          class="rv-id cursor-help whitespace-nowrap border-b border-dashed
                                                 border-oro/60 text-oro-texto">
                                        <?= e($codigo) ?>
                                    </span>

                                    <span id="<?= e($idFicha) ?>" role="tooltip"
                                          class="rv-extruido-lg pointer-events-none absolute left-1/2 top-full z-30 mt-2
                                                 block w-72 -translate-x-1/2 rounded-rv border border-borde
                                                 bg-superficie p-3 text-left text-xs font-normal normal-case
                                                 leading-relaxed tracking-normal text-texto-2 opacity-0
                                                 transition-opacity
                                                 group-hover:pointer-events-auto group-hover:opacity-100
                                                 group-focus-within:pointer-events-auto group-focus-within:opacity-100">
                                        <span class="mb-1 block font-semibold text-texto">
                                            <?= e((string) $ficha['nombre']) ?>
                                        </span>
                                        <?= e((string) $ficha['descripcion']) ?>
                                    </span>
                                </span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($filas as $fila): ?>
                    <?php
                    $exitoso = $fila['exitoso'];

                    [$tono, $simbolo, $etiqueta] = $exitoso === null
                        ? ['na',  'minus',        $vista->t('mon.res_sin_dato')]
                        : ($exitoso
                            ? ['ok',  'circle-check', $vista->t('mon.res_correcto')]
                            : ['bad', 'aspa',         $vista->t('mon.res_hallazgo')]);
                    ?>
                    <tr class="border-b border-borde/60 align-top last:border-0">

                        <th scope="row" class="whitespace-nowrap py-3 pr-4 font-semibold text-texto">
                            <?= e((string) $fila['nombre']) ?>
                        </th>

                        <?php foreach ($columnas as $codigo): ?>
                            <?php $metrica = $fila['metricas'][$codigo] ?? null; ?>
                            <td class="tabular px-2.5 py-3 text-right">
                                <?php if ($metrica === null): ?>
                                    <?php /* No evalúa a este proceso: la celda se queda en blanco. */ ?>
                                <?php elseif ($metrica['valor'] === null): ?>
                                    <span class="text-na"
                                          <?= $metrica['motivo'] !== null
                                              ? 'title="' . e((string) $metrica['motivo']) . '"'
                                              : '' ?>>—</span>
                                <?php else: ?>
                                    <span class="font-semibold text-texto">
                                        <?= e(number_format((float) $metrica['valor'], 2, ',', '')) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>

                        <td class="px-4 py-3">
                            <?php
                            /*
                             * La casilla NO es un <input type="checkbox">: no se
                             * marca ni se desmarca, es el resultado de un
                             * cálculo. Un control de formulario deshabilitado
                             * invitaría a pulsarlo y anunciaría «casilla» a un
                             * lector de pantalla, cuando lo que hay es un
                             * estado. Se dibuja como casilla porque así se lee
                             * de un vistazo, y se anuncia como lo que es.
                             */
                            ?>
                            <span class="flex items-center gap-2">
                                <span class="rv-hundido grid h-5 w-5 shrink-0 place-items-center rounded-[5px]
                                             border border-borde bg-fondo text-<?= e($tono) ?>"
                                      aria-hidden="true">
                                    <?= icono($simbolo, 'h-3.5 w-3.5') ?>
                                </span>
                                <span class="whitespace-nowrap text-xs font-medium text-texto-2"><?= e($etiqueta) ?></span>
                            </span>
                        </td>

                        <td class="px-4 py-3 text-xs leading-relaxed text-texto-2">
                            <?= e((string) $fila['descripcion']) ?>
                        </td>

                        <?php
                        /*
                         * La recomendación va en tinta de cuerpo y no en la
                         * secundaria: es la única celda que pide una acción, y
                         * apagarla igual que la descripción la dejaría leerse
                         * como más de lo mismo.
                         */
                        ?>
                        <td class="py-3 pl-4 text-xs leading-relaxed text-texto">
                            <?= e((string) $fila['recomendacion']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
