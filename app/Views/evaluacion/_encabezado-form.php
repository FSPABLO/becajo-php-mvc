<?php

declare(strict_types=1);

/**
 * Campos del encabezado de una auditoría.
 *
 * Compartido por el alta y la edición: son los mismos cuatro datos, y tenerlos
 * duplicados garantizaría que un día se añada un campo en uno y no en el otro.
 * El formulario que lo envuelve (action, token, botón) lo pone cada vista.
 *
 * @var \App\Core\Vista $vista
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var list<\App\Models\Entidades\Usuario> $administradores
 */
$errores = $errores ?? [];
$valores = $valores ?? [];

$clases = static fn (bool $mal): string =>
    'mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-slate-900 outline-none transition '
    . ($mal
        ? 'border-alerta-500 focus:border-alerta-500 focus:ring-2 focus:ring-alerta-500/30'
        : 'border-slate-300 focus:border-acento-500 focus:ring-2 focus:ring-acento-500/30');
?>
<div>
    <label for="administrador" class="block text-sm font-semibold text-marina-950">
        <?= e($vista->t('eval.admin_entrevistado_label')) ?>
    </label>
    <select id="administrador" name="administrador" required
            class="<?= e($clases(isset($errores['administrador']))) ?>">
        <option value=""><?= e($vista->t('eval.seleccione')) ?></option>
        <?php foreach ($administradores as $admin): ?>
            <option value="<?= e((string) $admin->id) ?>"
                <?= (string) ($valores['administrador'] ?? '') === (string) $admin->id ? 'selected' : '' ?>>
                <?= e($admin->nombre) ?> — <?= e($admin->organizacion) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errores['administrador'])): ?>
        <p class="mt-1.5 text-sm text-alerta-600"><?= e($errores['administrador']) ?></p>
    <?php else: ?>
        <p class="mt-1.5 text-sm text-slate-500">
            <?= e($vista->t('eval.organizacion_registrada')) ?>
        </p>
    <?php endif; ?>
</div>

<div>
    <label for="area" class="block text-sm font-semibold text-marina-950"><?= e($vista->t('eval.area_evaluada')) ?></label>
    <input type="text" id="area" name="area" required maxlength="200"
           value="<?= e((string) ($valores['area'] ?? '')) ?>"
           class="<?= e($clases(isset($errores['area']))) ?>">
    <?php if (isset($errores['area'])): ?>
        <p class="mt-1.5 text-sm text-alerta-600"><?= e($errores['area']) ?></p>
    <?php endif; ?>
</div>

<div>
    <label for="fecha" class="block text-sm font-semibold text-marina-950"><?= e($vista->t('eval.fecha_auditoria')) ?></label>
    <input type="date" id="fecha" name="fecha" required
           value="<?= e((string) ($valores['fecha'] ?? date('Y-m-d'))) ?>"
           class="<?= e($clases(isset($errores['fecha']))) ?>">
    <?php if (isset($errores['fecha'])): ?>
        <p class="mt-1.5 text-sm text-alerta-600"><?= e($errores['fecha']) ?></p>
    <?php endif; ?>
</div>
