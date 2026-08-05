<?php

declare(strict_types=1);

/**
 * Un campo de formulario del catálogo, con su etiqueta y su error.
 *
 * Los tres formularios (dominio, proceso, control) repiten la misma estructura
 * doce veces entre todos. Tenerla en un sitio evita que un día se corrija el
 * marcado de accesibilidad en uno y no en los otros.
 *
 * @var string      $campo        Atributo name=.
 * @var string      $etiqueta
 * @var string      $valor
 * @var string|null $error
 * @var string|null $tipo         text (por defecto), number, textarea, select.
 * @var string|null $ayuda
 * @var bool|null   $obligatorio
 * @var bool|null   $bloqueado    Solo lectura (claves ya asignadas).
 * @var int|null    $maximo       maxlength.
 * @var int|null    $filas        Filas del textarea.
 * @var list<array{valor: string, texto: string}>|null $opciones  Para select.
 */
$tipo = $tipo ?? 'text';
$obligatorio = $obligatorio ?? false;
$bloqueado = $bloqueado ?? false;
$filas = $filas ?? 3;
$opciones = $opciones ?? [];
$hayError = ($error ?? null) !== null;

$clases = 'mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-slate-900 outline-none transition '
    . ($hayError
        ? 'border-alerta-500 focus:border-alerta-500 focus:ring-2 focus:ring-alerta-500/30'
        : 'border-slate-300 focus:border-acento-500 focus:ring-2 focus:ring-acento-500/30')
    . ($bloqueado ? ' bg-slate-100 text-slate-500' : '');

$atributos = 'id="' . e($campo) . '" name="' . e($campo) . '"'
    . ($obligatorio ? ' required' : '')
    . ($bloqueado ? ' readonly' : '')
    . (isset($maximo) ? ' maxlength="' . e((string) $maximo) . '"' : '')
    . ($hayError ? ' aria-invalid="true" aria-describedby="error-' . e($campo) . '"' : '');
?>
<div>
    <label for="<?= e($campo) ?>" class="block text-sm font-semibold text-marina-950">
        <?= e($etiqueta) ?>
    </label>

    <?php if ($tipo === 'textarea'): ?>
        <textarea <?= $atributos ?> rows="<?= e((string) $filas) ?>"
                  class="<?= e($clases) ?>"><?= e($valor) ?></textarea>

    <?php elseif ($tipo === 'select'): ?>
        <select <?= $atributos ?> class="<?= e($clases) ?>">
            <option value="">— Seleccione —</option>
            <?php foreach ($opciones as $opcion): ?>
                <option value="<?= e($opcion['valor']) ?>" <?= $valor === $opcion['valor'] ? 'selected' : '' ?>>
                    <?= e($opcion['texto']) ?>
                </option>
            <?php endforeach; ?>
        </select>

    <?php else: ?>
        <input type="<?= e($tipo) ?>" <?= $atributos ?>
               value="<?= e($valor) ?>" class="<?= e($clases) ?>">
    <?php endif; ?>

    <?php if ($hayError): ?>
        <p id="error-<?= e($campo) ?>" class="mt-1.5 text-sm text-alerta-600"><?= e($error) ?></p>
    <?php elseif (!empty($ayuda)): ?>
        <p class="mt-1.5 text-sm text-slate-500"><?= e($ayuda) ?></p>
    <?php endif; ?>
</div>
