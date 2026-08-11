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
/*
 * Estado de la remediación -> tono de la escala semántica (§4).
 *
 * PENDIENTE va neutro y no ámbar: todavía no ha pasado nada, solo está
 * abierto. El ámbar se reserva para EN_PROCESO, que tiene un plazo corriendo,
 * y el octógono de 'crit' para VENCIDO, que ya es incumplimiento.
 *
 * (Antes este mapa usaba 'verde-500' y 'verde-700', que no existían en la
 * paleta: CUMPLIDO se pintaba sin color.)
 */
$tonoEstado = [
    'PENDIENTE'  => 'na',
    'EN_PROCESO' => 'warn',
    'CUMPLIDO'   => 'ok',
    'VENCIDO'    => 'crit',
];

// Los que todavía no se dan por cumplidos: el apartado de arriba enlaza
// directo al control que falta revisar, esté o no ya programada la
// re-auditoría.
$pendientesDeRevisar = array_values(array_filter(
    $remediaciones,
    static fn ($remediacion) => $remediacion->estado !== 'CUMPLIDO',
));
?>
<section class="mx-auto w-full max-w-4xl px-6 py-8 lg:px-8">

    <nav class="mb-6 text-sm">
        <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>" class="text-primario hover:underline">
            ← Volver a la auditoría
        </a>
    </nav>

    <header class="mb-8">
        <h1 class="rv-titulo text-3xl font-semibold text-texto">Remediaciones</h1>
        <p class="mt-1 text-texto-2">
            Plazos de corrección de los hallazgos de la auditoría <?= e((string) $auditoria->id) ?>
            — ciclo Planificar-Hacer-Verificar-Actuar (ISO 9001 §8.5.2 / ISO-IEC 27001, cláusula 10).
        </p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($pendientesDeRevisar !== []): ?>
        <div class="mb-8 rounded-rv-lg border border-primario/30 bg-primario/5 p-5">
            <h2 class="text-sm font-bold text-texto">Controles pendientes de re-auditar</h2>
            <p class="mt-1 text-xs text-texto-2">
                Acceso directo al detalle de cada control con un plazo de corrección todavía abierto.
            </p>
            <ul class="mt-3 flex flex-wrap gap-2">
                <?php foreach ($pendientesDeRevisar as $pendiente): ?>
                    <?php
                        $idAuditoriaDetalle = $pendiente->idAuditoriaReauditoria ?? $auditoria->id;
                    ?>
                    <li>
                        <a href="<?= e($vista->url('evaluacion/' . $idAuditoriaDetalle . '/controles/' . $pendiente->codigoControl)) ?>"
                           class="rv-extruido rv-relieve-sutil rv-id inline-flex items-center gap-1.5 rounded-full border border-borde bg-superficie px-3 py-1 text-xs font-semibold hover:border-primario">
                            <?= e($pendiente->codigoControl) ?>
                            <?php if ($pendiente->tieneReauditoriaProgramada()): ?>
                                <span class="font-normal text-texto-2">· auditoría #<?= e((string) $pendiente->idAuditoriaReauditoria) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($remediaciones === []): ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto">Todavía no hay plazos de remediación en esta auditoría.</p>
            <p class="mt-1 text-sm text-texto-2">
                Para crear uno, abra un control ya evaluado y use el formulario de abajo con su código.
            </p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($remediaciones as $remediacion): ?>
                <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="flex flex-wrap items-center gap-2 font-semibold">
                                <span class="rv-id"><?= e($remediacion->codigoControl) ?></span>
                                <?= pill($tonoEstado[$remediacion->estado] ?? 'na', $remediacion->estado) ?>
                            </p>
                            <p class="rv-titulo mt-1 text-[1.05rem] text-texto-2"><?= e($remediacion->enunciadoControl) ?></p>
                            <p class="mt-2 text-xs text-texto-2">
                                Fecha límite: <strong class="tabular font-mono text-texto"><?= e($remediacion->fechaLimite) ?></strong>
                                <?php if ($remediacion->responsable !== null): ?>
                                    · Responsable: <?= e($remediacion->responsable) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($remediacion->tieneReauditoriaProgramada()): ?>
                        <p class="mt-3 text-sm text-texto-2">
                            Re-auditoría programada: auditoría
                            <a href="<?= e($vista->url('evaluacion/' . $remediacion->idAuditoriaReauditoria)) ?>"
                               class="text-primario hover:underline">#<?= e((string) $remediacion->idAuditoriaReauditoria) ?></a>.
                            Control a revisar:
                            <a href="<?= e($vista->url('evaluacion/' . $remediacion->idAuditoriaReauditoria . '/controles/' . $remediacion->codigoControl)) ?>"
                               class="font-semibold text-primario hover:underline">
                                <?= e($remediacion->codigoControl) ?> →
                            </a>
                        </p>
                    <?php elseif ($auditoriasSeguimiento === []): ?>
                        <p class="mt-3 text-sm text-texto-2">
                            No tiene otra auditoría propia para usar como seguimiento.
                            <a href="<?= e($vista->url('evaluacion/nueva')) ?>" class="text-primario hover:underline">Cree una auditoría de seguimiento</a>
                            para poder enlazarla aquí.
                        </p>
                    <?php else: ?>
                        <form method="post"
                              action="<?= e($vista->url('remediaciones/' . $remediacion->id . '/programar')) ?>"
                              class="mt-4 flex flex-wrap items-end gap-3">
                            <?= $vista->campoToken() ?>
                            <input type="hidden" name="volver" value="/evaluacion/<?= e((string) $auditoria->id) ?>/remediaciones">
                            <div>
                                <label class="block text-xs font-semibold text-texto">Auditoría de seguimiento</label>
                                <select name="id_auditoria_reauditoria" required
                                        class="mt-1 w-64 rounded-rv border border-borde px-3 py-1.5 text-sm">
                                    <option value="">Seleccione…</option>
                                    <?php foreach ($auditoriasSeguimiento as $candidata): ?>
                                        <option value="<?= e((string) $candidata->id) ?>">
                                            #<?= e((string) $candidata->id) ?> — <?= e($candidata->areaEvaluada) ?> (<?= e($candidata->fecha) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit"
                                    class="rounded-rv border border-borde px-3.5 py-1.5 text-sm font-semibold text-texto hover:border-primario">
                                Enlazar re-auditoría
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post"
                          action="<?= e($vista->url('remediaciones/' . $remediacion->id . '/estado')) ?>"
                          class="mt-3 flex flex-wrap items-center gap-2">
                        <?= $vista->campoToken() ?>
                        <input type="hidden" name="volver" value="/evaluacion/<?= e((string) $auditoria->id) ?>/remediaciones">
                        <label class="text-xs font-semibold text-texto">Cambiar estado:</label>
                        <?php foreach (['PENDIENTE', 'EN_PROCESO', 'CUMPLIDO'] as $opcion): ?>
                            <button type="submit" name="estado" value="<?= e($opcion) ?>"
                                    class="rounded-rv border border-borde px-2.5 py-1 text-xs font-semibold text-texto-2 hover:border-primario">
                                <?= e($opcion) ?>
                            </button>
                        <?php endforeach; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="rv-extruido mt-10 rounded-rv-lg border border-borde bg-superficie p-5">
        <h2 class="rv-titulo text-lg font-semibold text-texto">Crear un plazo de remediación</h2>

        <?php if ($controlesElegibles === []): ?>
            <p class="mt-1 text-sm text-texto-2">
                Todavía no hay ningún control con hallazgo («No») evaluado en esta auditoría.
                Complete el cuestionario antes de crear un plazo de remediación.
            </p>
        <?php else: ?>
            <p class="mt-1 text-sm text-texto-2">
                Seleccione un control con hallazgo («No») ya evaluado en esta auditoría.
            </p>

            <form method="post" id="form-nueva-remediacion" class="mt-4 flex flex-wrap items-end gap-3">
                <?= $vista->campoToken() ?>
                <div>
                    <label class="block text-xs font-semibold text-texto">Código del control</label>
                    <select name="codigo" id="codigo-remediacion" required
                            class="mt-1 w-64 rounded-rv border border-borde px-3 py-1.5 text-sm">
                        <option value="">Seleccione…</option>
                        <?php foreach ($controlesElegibles as $control): ?>
                            <option value="<?= e($control->id) ?>">
                                <?= e($control->id) ?> — <?= e(mb_strimwidth($control->enunciado, 0, 60, '…')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-texto">Fecha límite</label>
                    <input type="date" name="fecha_limite" required
                           class="mt-1 rounded-rv border border-borde px-3 py-1.5 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-texto">Responsable</label>
                    <input type="text" name="responsable" maxlength="150"
                           class="mt-1 rounded-rv border border-borde px-3 py-1.5 text-sm">
                </div>
                <button type="submit"
                        class="rv-extruido rv-interactivo rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
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
