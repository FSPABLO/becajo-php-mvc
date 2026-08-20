<?php

declare(strict_types=1);

/**
 * Bitácora del sistema, para el administrador de BD.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var list<\App\Models\Entidades\RegistroBitacora> $registros
 */
?>
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <header class="mb-8">
        <h1 class="rv-titulo text-3xl font-semibold text-texto">Bitácora del sistema</h1>
        <p class="mt-1 text-texto-2">
            Las últimas <?= e((string) count($registros)) ?> acciones registradas, de todos los usuarios.
        </p>
    </header>

    <?php if ($registros === []): ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto">Todavía no hay ninguna acción registrada.</p>
        </div>
    <?php else: ?>
        <div class="rv-extruido rv-relieve-sutil rv-tabla overflow-x-auto rounded-rv-lg border border-borde bg-superficie">
            <table class="w-full text-left text-sm">
                <thead class="bg-elevado text-xs font-semibold uppercase tracking-wide text-texto-2">
                    <tr>
                        <th class="px-4 py-3">Fecha y hora</th>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Acción</th>
                        <th class="px-4 py-3">Entidad</th>
                        <th class="px-4 py-3">Detalle</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <?php foreach ($registros as $registro): ?>
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-texto-2">
                                <?= e($registro->fechaHora) ?>
                            </td>
                            <td class="px-4 py-3 text-texto"><?= e($registro->correoUsuario) ?></td>
                            <td class="px-4 py-3">
                                <span class="rounded-rv bg-elevado px-2 py-0.5 font-mono text-xs font-semibold text-texto-2">
                                    <?= e($registro->accion) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-texto-2">
                                <?= e($registro->entidad ?? '—') ?>
                                <?php if ($registro->idEntidad !== null): ?>
                                    <span class="font-mono text-xs">#<?= e($registro->idEntidad) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 text-texto-2" title="<?= e($registro->detalle ?? '') ?>">
                                <?= e($registro->detalle ?? '—') ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-texto-2">
                                <?= e($registro->direccionIp ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</section>
