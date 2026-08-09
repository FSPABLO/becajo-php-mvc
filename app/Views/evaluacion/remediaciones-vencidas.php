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
<section class="mx-auto w-full max-w-4xl px-6 pt-24 pb-14">

    <header class="mb-8">
        <h1 class="text-3xl font-extrabold text-marina-950">Remediaciones vencidas</h1>
        <p class="mt-1 text-slate-600">
            Hallazgos con plazo de corrección ya cumplido, en todas las organizaciones auditadas.
        </p>
    </header>

    <?php if ($remediaciones === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 px-6 py-16 text-center">
            <p class="font-semibold text-marina-950">No hay remediaciones vencidas en este momento.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Organización</th>
                        <th class="px-4 py-3">Auditoría</th>
                        <th class="px-4 py-3">Control</th>
                        <th class="px-4 py-3">Fecha límite</th>
                        <th class="px-4 py-3">Responsable</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($remediaciones as $fila): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-marina-950"><?= e((string) ($fila['organizacion'] ?? '')) ?></td>
                            <td class="px-4 py-3">
                                <a href="<?= e($vista->url('evaluacion/' . $fila['id_auditoria'])) ?>"
                                   class="text-acento-600 hover:underline">#<?= e((string) $fila['id_auditoria']) ?></a>
                            </td>
                            <td class="px-4 py-3"><?= e((string) ($fila['codigo_control'] ?? '')) ?></td>
                            <td class="px-4 py-3 text-alerta-600 font-semibold"><?= e((string) ($fila['fecha_limite'] ?? '')) ?></td>
                            <td class="px-4 py-3"><?= e((string) ($fila['responsable'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
