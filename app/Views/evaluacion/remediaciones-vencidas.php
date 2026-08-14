<?php

declare(strict_types=1);

/**
 * Panel global de remediaciones vencidas, para el administrador de BD.
 *
 * Vista funcional, no definitiva. Persona 4 puede reescribir el marcado.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var list<array<string, mixed>> $remediaciones  Filas crudas de sp_remediaciones_vencidas.
 */
?>
<section class="mx-auto w-full max-w-4xl px-6 py-8 lg:px-8">

    <header class="mb-8">
        <h1 class="rv-titulo text-3xl font-semibold text-texto">Remediaciones vencidas</h1>
        <p class="mt-1 text-texto-2">
            Hallazgos con plazo de corrección ya cumplido, en todas las organizaciones auditadas.
        </p>
    </header>

    <?php if ($remediaciones === []): ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto">No hay remediaciones vencidas en este momento.</p>
        </div>
    <?php else: ?>
        <div class="rv-extruido rv-relieve-sutil rv-tabla overflow-x-auto rounded-rv-lg border border-borde bg-superficie">
            <table class="w-full text-left text-sm">
                <thead class="bg-elevado text-xs font-semibold uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3">Organización</th>
                        <th class="px-4 py-3">Auditoría</th>
                        <th class="px-4 py-3">Control</th>
                        <th class="px-4 py-3">Fecha límite</th>
                        <th class="px-4 py-3">Responsable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <?php foreach ($remediaciones as $fila): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-texto"><?= e((string) ($fila['organizacion'] ?? '')) ?></td>
                            <td class="px-4 py-3">
                                <a href="<?= e($vista->url('evaluacion/' . $fila['id_auditoria'])) ?>"
                                   class="text-primario hover:underline">#<?= e((string) $fila['id_auditoria']) ?></a>
                            </td>
                            <td class="px-4 py-3"><?= e((string) ($fila['codigo_control'] ?? '')) ?></td>
                            <td class="px-4 py-3 font-mono tabular text-bad"><?= e((string) ($fila['fecha_limite'] ?? '')) ?></td>
                            <td class="px-4 py-3"><?= e((string) ($fila['responsable'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
