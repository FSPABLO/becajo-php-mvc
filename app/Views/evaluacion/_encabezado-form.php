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
    'mt-1.5 w-full rounded-rv border px-3.5 py-2.5 text-texto outline-none transition '
    . ($mal
        ? 'border-bad focus:border-bad focus:ring-2 focus:ring-bad/10'
        : 'border-borde focus:border-primario focus:ring-2 focus:ring-primario/30');
?>
<div>
    <label for="administrador" class="block text-sm font-semibold text-texto">
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
        <p class="mt-1.5 text-sm text-bad"><?= e($errores['administrador']) ?></p>
    <?php else: ?>
        <p class="mt-1.5 text-sm text-texto-2">
            <?= e($vista->t('eval.organizacion_registrada')) ?>
        </p>
    <?php endif; ?>
</div>

<div>
    <label for="area" class="block text-sm font-semibold text-texto"><?= e($vista->t('eval.area_evaluada')) ?></label>
    <input type="text" id="area" name="area" required maxlength="200"
           value="<?= e((string) ($valores['area'] ?? '')) ?>"
           class="<?= e($clases(isset($errores['area']))) ?>">
    <?php if (isset($errores['area'])): ?>
        <p class="mt-1.5 text-sm text-bad"><?= e($errores['area']) ?></p>
    <?php endif; ?>
</div>

<div>
    <label for="fecha" class="block text-sm font-semibold text-texto"><?= e($vista->t('eval.fecha_auditoria')) ?></label>
    <input type="date" id="fecha" name="fecha" required
           value="<?= e((string) ($valores['fecha'] ?? date('Y-m-d'))) ?>"
           class="<?= e($clases(isset($errores['fecha']))) ?>">
    <?php if (isset($errores['fecha'])): ?>
        <p class="mt-1.5 text-sm text-bad"><?= e($errores['fecha']) ?></p>
    <?php endif; ?>
</div>
