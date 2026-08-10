<?php

declare(strict_types=1);

/**
 * Remediaciones (plazos de corrección) de una auditoría — punto 19.
 *
 * Vista funcional, no definitiva. Persona 4 puede reescribir el marcado
 * mientras conserve las rutas de los enlaces y los name= de los formularios.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var \App\Models\Entidades\Auditoria $auditoria
 * @var list<\App\Models\Entidades\Remediacion> $remediaciones
 * @var list<\App\Models\Entidades\Control> $controlesElegibles
 * @var list<\App\Models\Entidades\Auditoria> $auditoriasSeguimiento
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$colorEstado = [
    'PENDIENTE'  => 'bg-slate-100 text-slate-700',
    'EN_PROCESO' => 'bg-acento-500/10 text-acento-700',
    'CUMPLIDO'   => 'bg-verde-500/10 text-verde-700',
    'VENCIDO'    => 'bg-alerta-500/10 text-alerta-700',
];

// Los que todavía no se dan por cumplidos: el apartado de arriba enlaza
// directo al control que falta revisar, esté o no ya programada la
// re-auditoría.
$pendientesDeRevisar = array_values(array_filter(
    $remediaciones,
    static fn ($remediacion) => $remediacion->estado !== 'CUMPLIDO',
));
?>
<section class="mx-auto w-full max-w-4xl px-6 pt-24 pb-14">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="text-acento-600 hover:underline">
            ← Volver a la auditoría
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-marina-950">Remediaciones</h1>
        <p class="mt-1 text-slate-600">
            Plazos de corrección de los hallazgos de la auditoría <?= e((string) $auditoria->id) ?>
            — ciclo Planificar-Hacer-Verificar-Actuar (ISO 9001 §8.5.2 / ISO-IEC 27001, cláusula 10).
        </p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($pendientesDeRevisar !== []): ?>
        <div class="mb-8 rounded-2xl border border-acento-500/30 bg-acento-500/5 p-5">
            <h2 class="text-sm font-bold text-marina-950">Controles pendientes de re-auditar</h2>
            <p class="mt-1 text-xs text-slate-600">
                Acceso directo al detalle de cada control con un plazo de corrección todavía abierto.
            </p>
            <ul class="mt-3 flex flex-wrap gap-2">
                <?php foreach ($pendientesDeRevisar as $pendiente): ?>
                    <?php
                        $idAuditoriaDetalle = $pendiente->idAuditoriaReauditoria ?? $auditoria->id;
                    ?>
                    <li>
                        <a href="<?= e($vista->url('evaluacion/' . $idAuditoriaDetalle . '/controles/' . $pendiente->codigoControl)) ?>"
                           class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-marina-950 hover:border-acento-500">
                            <?= e($pendiente->codigoControl) ?>
                            <?php if ($pendiente->tieneReauditoriaProgramada()): ?>
                                <span class="font-normal text-slate-400">· auditoría #<?= e((string) $pendiente->idAuditoriaReauditoria) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($remediaciones === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center">
            <p class="font-semibold text-marina-950">Todavía no hay plazos de remediación en esta auditoría.</p>
            <p class="mt-1 text-sm text-slate-600">
                Para crear uno, abra un control ya evaluado y use el formulario de abajo con su código.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($remediaciones as $remediacion): ?>
                <div class="rounded-2xl border border-slate-200 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-marina-950">
                                <?= e($remediacion->codigoControl) ?>
                                <span class="ml-2 rounded-full px-2.5 py-0.5 text-xs font-semibold <?= e($colorEstado[$remediacion->estado] ?? 'bg-slate-100 text-slate-700') ?>">
                                    <?= e($remediacion->estado) ?>
                                </span>
                            </p>
                            <p class="mt-1 text-sm text-slate-600"><?= e($remediacion->enunciadoControl) ?></p>
                            <p class="mt-2 text-xs text-slate-500">
                                Fecha límite: <strong><?= e($remediacion->fechaLimite) ?></strong>
                                <?php if ($remediacion->responsable !== null): ?>
                                    · Responsable: <?= e($remediacion->responsable) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($remediacion->tieneReauditoriaProgramada()): ?>
                        <p class="mt-3 text-sm text-slate-600">
                            Re-auditoría programada: auditoría
                            <a href="<?= e($vista->url('evaluacion/' . $remediacion->idAuditoriaReauditoria)) ?>"
                               class="text-acento-600 hover:underline">#<?= e((string) $remediacion->idAuditoriaReauditoria) ?></a>.
                            Control a revisar:
                            <a href="<?= e($vista->url('evaluacion/' . $remediacion->idAuditoriaReauditoria . '/controles/' . $remediacion->codigoControl)) ?>"
                               class="font-semibold text-acento-600 hover:underline">
                                <?= e($remediacion->codigoControl) ?> →
                            </a>
                        </p>
                    <?php elseif ($auditoriasSeguimiento === []): ?>
                        <p class="mt-3 text-sm text-slate-500">
                            No tiene otra auditoría propia para usar como seguimiento.
                            <a href="<?= e($vista->url('evaluacion/nueva')) ?>" class="text-acento-600 hover:underline">Cree una auditoría de seguimiento</a>
                            para poder enlazarla aquí.
                        </p>
                    <?php else: ?>
                        <form method="post"
                              action="<?= e($vista->url('remediaciones/' . $remediacion->id . '/programar')) ?>"
                              class="mt-4 flex flex-wrap items-end gap-3">
                            <?= $vista->campoToken() ?>
                            <input type="hidden" name="volver" value="/evaluacion/<?= e((string) $auditoria->id) ?>/remediaciones">
                            <div>
                                <label class="block text-xs font-semibold text-marina-950">Auditoría de seguimiento</label>
                                <select name="id_auditoria_reauditoria" required
                                        class="mt-1 w-64 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                                    <option value="">Seleccione…</option>
                                    <?php foreach ($auditoriasSeguimiento as $candidata): ?>
                                        <option value="<?= e((string) $candidata->id) ?>">
                                            #<?= e((string) $candidata->id) ?> — <?= e($candidata->areaEvaluada) ?> (<?= e($candidata->fecha) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit"
                                    class="rounded-lg border border-slate-300 px-3.5 py-1.5 text-sm font-semibold text-marina-950 hover:border-acento-500">
                                Enlazar re-auditoría
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post"
                          action="<?= e($vista->url('remediaciones/' . $remediacion->id . '/estado')) ?>"
                          class="mt-3 flex flex-wrap items-center gap-2">
                        <?= $vista->campoToken() ?>
                        <input type="hidden" name="volver" value="/evaluacion/<?= e((string) $auditoria->id) ?>/remediaciones">
                        <label class="text-xs font-semibold text-marina-950">Cambiar estado:</label>
                        <?php foreach (['PENDIENTE', 'EN_PROCESO', 'CUMPLIDO'] as $opcion): ?>
                            <button type="submit" name="estado" value="<?= e($opcion) ?>"
                                    class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:border-acento-500">
                                <?= e($opcion) ?>
                            </button>
                        <?php endforeach; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-10 rounded-2xl border border-slate-200 p-5">
        <h2 class="text-lg font-bold text-marina-950">Crear un plazo de remediación</h2>

        <?php if ($controlesElegibles === []): ?>
            <p class="mt-1 text-sm text-slate-600">
                Todavía no hay ningún control con hallazgo («No») evaluado en esta auditoría.
                Complete el cuestionario antes de crear un plazo de remediación.
            </p>
        <?php else: ?>
            <p class="mt-1 text-sm text-slate-600">
                Seleccione un control con hallazgo («No») ya evaluado en esta auditoría.
            </p>

            <form method="post" id="form-nueva-remediacion" class="mt-4 flex flex-wrap items-end gap-3">
                <?= $vista->campoToken() ?>
                <div>
                    <label class="block text-xs font-semibold text-marina-950">Código del control</label>
                    <select name="codigo" id="codigo-remediacion" required
                            class="mt-1 w-64 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                        <option value="">Seleccione…</option>
                        <?php foreach ($controlesElegibles as $control): ?>
                            <option value="<?= e($control->id) ?>">
                                <?= e($control->id) ?> — <?= e(mb_strimwidth($control->enunciado, 0, 60, '…')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-marina-950">Fecha límite</label>
                    <input type="date" name="fecha_limite" required
                           class="mt-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-marina-950">Responsable</label>
                    <input type="text" name="responsable" maxlength="150"
                           class="mt-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm">
                </div>
                <button type="submit"
                        class="rounded-lg bg-marina-950 px-4 py-2 text-sm font-semibold text-white hover:bg-marina-900">
                    Crear plazo
                </button>
            </form>

            <script>
                // El código del control decide a qué URL se manda el formulario
                // (crearRemediacion vive bajo /evaluacion/{id}/controles/{codigo}/remediacion),
                // así que se arma justo antes de enviar en vez de fijarlo en el <form>.
                // El desplegable solo ofrece códigos reales, pero el valor igual se
                // arma aquí en vez de en el <form> porque depende de la selección.
                document.getElementById('form-nueva-remediacion').addEventListener('submit', function (evento) {
                    var codigo = document.getElementById('codigo-remediacion').value;
                    if (codigo === '') {
                        return;
                    }
                    this.action = <?= json_encode($vista->url('evaluacion/' . $auditoria->id . '/controles/')) ?> + codigo + '/remediacion';
                });
            </script>
        <?php endif; ?>
    </div>
</section>
