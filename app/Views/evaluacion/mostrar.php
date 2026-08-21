<?php

declare(strict_types=1);

/**
 * Detalle de una auditoría: encabezado, avance y los 75 controles.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var list<\App\Models\Entidades\Control> $controles
 * @var array<int, \App\Models\Entidades\Proceso> $procesos
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var array<string, int> $totalPorDominio
 * @var array<string, int> $respondidoPorDominio
 * @var array<string, \App\Models\Entidades\EvaluacionControl> $evaluaciones
 * @var int $evaluados
 * @var int $total
 * @var list<\App\Models\Entidades\Usuario> $administradores
 * @var array<string, string> $errores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$abierta = !$auditoria->estaFinalizada();
$porcentaje = $total > 0 ? round($evaluados / $total * 100) : 0;

$valores = [
    'administrador' => (string) $auditoria->idAdministradorBd,
    'area'          => $auditoria->areaEvaluada,
    'fecha'         => $auditoria->fecha,
];

/*
 * Estado de cada control -> tono de la escala semántica (§4).
 *
 * 'NA' es gris neutro a propósito, nunca verde: teñir de verde un control
 * excluido inflaría visualmente el cumplimiento y contradiría la lógica de la
 * Declaración de Aplicabilidad, donde esos controles salen del denominador.
 */
$tonoEstado = [
    'SI' => ['ok',   $vista->t('eval.estado_si')],
    'NO' => ['bad',  $vista->t('eval.estado_no')],
    'NA' => ['na',   $vista->t('eval.estado_na')],
];
?>
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion')) ?>" class="text-primario hover:underline">
            <?= e($vista->t('eval.volver_auditorias')) ?>
        </a>
    </nav>

    <header class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="rv-titulo text-3xl font-semibold text-texto">
                    <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
                </h1>
                <?= $abierta
                    ? pill('warn', $vista->t('eval.en_progreso'))
                    : pill('ok', $vista->t('eval.finalizada')) ?>
            </div>
            <p class="mt-1 text-texto-2">
                <?= e($auditoria->organizacion) ?> · <?= e($auditoria->areaEvaluada) ?>
            </p>
            <p class="mt-0.5 text-sm text-texto-2">
                <?= e($auditoria->fecha) ?> · <?= e($vista->t('eval.entrevistado')) ?>
                <?= e($auditoria->nombreAdministradorBd) ?>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/resultados')) ?>"
               class="rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                <?= e($vista->t('eval.ver_resultados')) ?>
            </a>

            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/remediaciones')) ?>"
               class="rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                Remediaciones
            </a>

            <?php if ($abierta): ?>
                <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/finalizar')) ?>">
                    <?= $vista->campoToken() ?>
                    <button type="submit"
                            class="rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto">
                        <?= e($vista->t('eval.finalizar')) ?>
                    </button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id . '/reabrir')) ?>">
                    <?= $vista->campoToken() ?>
                    <button type="submit"
                            class="rounded-rv border border-borde px-4 py-2.5 text-sm font-semibold text-texto transition hover:bg-elevado">
                        <?= e($vista->t('eval.reabrir')) ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if (!$abierta): ?>
        <p class="mb-6 rounded-rv-lg border border-borde bg-elevado px-4 py-3 text-sm text-texto-2">
            <?= e($vista->t('eval.aviso_finalizada')) ?>
        </p>
    <?php endif; ?>

    <!-- Avance -->
    <div class="mb-10 rounded-rv-lg border border-borde p-5">
        <div class="flex items-baseline justify-between">
            <p class="text-sm font-semibold text-texto"><?= e($vista->t('eval.avance')) ?></p>
            <p class="text-sm tabular text-texto-2">
                <?= e((string) $evaluados) ?> <?= e($vista->t('eval.de_controles')) ?> <?= e((string) $total) ?> <?= e($vista->t('eval.controles_palabra')) ?>
                (<?= e((string) $porcentaje) ?>%)
            </p>
        </div>
        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-elevado">
            <div class="h-full rounded-full bg-primario" style="width: <?= e((string) $porcentaje) ?>%"></div>
        </div>
    </div>

    <!-- Encabezado editable -->
    <?php if ($abierta): ?>
    <details class="mb-10 rounded-rv-lg border border-borde p-5" <?= $errores !== [] ? 'open' : '' ?>>
        <summary class="cursor-pointer text-sm font-semibold text-texto">
            <?= e($vista->t('eval.editar_encabezado')) ?>
        </summary>
        <form method="post" action="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="mt-5 space-y-5">
            <?= $vista->campoToken() ?>
            <?= $vista->renderizar('evaluacion/_encabezado-form', compact('errores', 'valores', 'administradores')) ?>
            <button type="submit"
                    class="rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto">
                <?= e($vista->t('eval.guardar_encabezado')) ?>
            </button>
        </form>
    </details>
    <?php endif; ?>

    <!-- Controles -->
    <h2 class="mb-4 text-xl font-bold text-texto"><?= e($vista->t('eval.controles_instrumento')) ?></h2>

    <?php
    /*
     * Antes: los 75 controles en una sola tira, sin más corte que el scroll
     * del navegador. Se agrupan por dominio con el mismo componente de
     * pestañas del instrumento público (tabs-dominios.php), así que de un
     * vistazo hay ~10 filas por dominio en vez de 75 seguidas, y el contador
     * ya trae el avance real de ESTA auditoría en vez de nacer en 0/N.
     *
     * $dominioActivo es el primero de la lista, igual que en tabs-dominios.php
     * (el primer tab nace seleccionado); las filas de los demás dominios se
     * marcan hidden en el propio HTML para que no haya parpadeo al cargar, y
     * el script de más abajo se limita a alternar esa marca al hacer clic.
     */
    $dominioActivo = $dominios[0]->clave ?? null;
    ?>

    <div class="rv-extruido overflow-hidden rounded-rv-lg border border-borde bg-superficie">
        <?= $vista->renderizar('herramientas/parciales/tabs-dominios', [
            'ambito'                => 'auditoria',
            'etiquetaLista'         => $vista->t('eval.dominios_lista'),
            'dominios'              => $dominios,
            'totalPorDominio'       => $totalPorDominio,
            'respondidoPorDominio'  => $respondidoPorDominio,
        ]) ?>

        <?php /* Relieve sutil y solo en el contenedor: 75 filas con sombra propia
                 convertirían la tabla en un relieve y no en un dato legible. */ ?>
        <div class="rv-relieve-sutil rv-tabla overflow-x-auto">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="bg-elevado text-xs uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_codigo')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_proceso')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_enunciado')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_resp')) ?></th>
                        <th class="px-4 py-3 font-semibold"><?= e($vista->t('eval.col_madurez')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde" data-filas-por-dominio>
                <?php foreach ($controles as $control): ?>
                    <?php
                    $evaluacion = $evaluaciones[$control->id] ?? null;
                    $estado = $evaluacion?->estado;
                    $tono = $tonoEstado[$estado] ?? null;
                    $claveDominio = $procesos[$control->proceso]->dominio ?? null;
                    ?>
                    <tr class="align-top hover:bg-elevado"
                        data-dominio="<?= e((string) $claveDominio) ?>"
                        <?= $claveDominio !== $dominioActivo ? 'hidden' : '' ?>>
                        <?php /* Código del control: identificador, luego mono y oro. */ ?>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="<?= e($vista->url('evaluacion/' . $auditoria->id . '/controles/' . $control->id)) ?>"
                               class="rv-id font-medium hover:underline">
                                <?= e($control->id) ?>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-texto-2">
                            <?= e($procesos[$control->proceso]->nombre ?? '—') ?>
                        </td>
                        <td class="rv-titulo px-4 py-3 text-[1rem] text-texto-2">
                            <?= e(mb_strimwidth($control->enunciado, 0, 110, '…')) ?>
                        </td>
                        <td class="px-4 py-3">
                            <?= $tono === null
                                ? '<span class="text-na">—</span>'
                                : pill($tono[0], $tono[1]) ?>
                        </td>
                        <td class="px-4 py-3 tabular text-texto-2">
                            <?= $evaluacion?->madurez === null
                                ? '<span class="text-na">—</span>'
                                : e(number_format((float) $evaluacion->madurez, 1, ',', '')) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
/*
 * Filtro de la tabla de controles por dominio — ver el comentario junto a
 * data-filas-por-dominio en evaluacion/mostrar.php.
 *
 * Independiente de assets/js/instrumento.js a propósito: ese archivo lleva
 * su propio almacén en localStorage para el instrumento público sin sesión,
 * y aquí los datos ya vienen calculados y guardados por el servidor. Mezclar
 * los dos habría significado hacer que este HTML hablara el formato JSON de
 * ese script solo para reutilizar una función de treinta líneas.
 */
(function () {
    'use strict';

    var lista = document.querySelector('[data-tabs-dominio="auditoria"]');
    var cuerpo = document.querySelector('[data-filas-por-dominio]');

    if (!lista || !cuerpo) {
        return;
    }

    var tabs = Array.prototype.slice.call(lista.querySelectorAll('[data-tab-dominio]'));
    var filas = Array.prototype.slice.call(cuerpo.querySelectorAll('[data-dominio]'));

    function activar(clave, moverFoco) {
        tabs.forEach(function (tab) {
            var activo = tab.dataset.tabDominio === clave;

            tab.setAttribute('aria-selected', String(activo));
            tab.tabIndex = activo ? 0 : -1;
            tab.classList.toggle('border-primario', activo);
            tab.classList.toggle('text-texto', activo);
            tab.classList.toggle('border-transparent', !activo);
            tab.classList.toggle('text-texto-2', !activo);
            tab.classList.toggle('hover:border-borde', !activo);

            if (activo && moverFoco) {
                tab.focus();
            }
        });

        filas.forEach(function (fila) {
            fila.hidden = fila.dataset.dominio !== clave;
        });
    }

    tabs.forEach(function (tab, indice) {
        tab.addEventListener('click', function () {
            activar(tab.dataset.tabDominio, false);
        });

        tab.addEventListener('keydown', function (evento) {
            var saltos = { ArrowRight: 1, ArrowLeft: -1 };
            var destino = null;

            if (saltos[evento.key] !== undefined) {
                destino = (indice + saltos[evento.key] + tabs.length) % tabs.length;
            } else if (evento.key === 'Home') {
                destino = 0;
            } else if (evento.key === 'End') {
                destino = tabs.length - 1;
            }

            if (destino !== null) {
                evento.preventDefault();
                activar(tabs[destino].dataset.tabDominio, true);
            }
        });
    });
})();
</script>
