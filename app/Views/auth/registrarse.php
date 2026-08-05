<?php

declare(strict_types=1);

/**
 * Alta de cuenta de auditor.
 *
 * Vista funcional, no definitiva (ver la nota de auth/ingresar.php).
 *
 * Los errores llegan indexados por campo y se pintan junto a su casilla: una
 * lista de errores arriba obliga a buscar a cuál corresponde cada uno. Los
 * valores del intento anterior se devuelven para no obligar a reescribirlo
 * todo por una contraseña corta — salvo las contraseñas, que nunca se
 * repueblan.
 *
 * No hay campo de rol a propósito: toda cuenta creada aquí nace como AUDITOR.
 *
 * @var \App\Core\Vista       $vista
 * @var array<string, mixed>  $empresa
 * @var array<string, string> $errores
 * @var array<string, string> $valores
 * @var array{aviso: string|null, error: string|null} $mensajes
 * @var int                   $minimoClave
 */
$errores = $errores ?? [];
$valores = $valores ?? [];

$clasesCampo = static fn (bool $conError): string =>
    'mt-1.5 w-full rounded-lg border px-3.5 py-2.5 text-slate-900 outline-none transition '
    . ($conError
        ? 'border-alerta-500 focus:border-alerta-500 focus:ring-2 focus:ring-alerta-500/30'
        : 'border-slate-300 focus:border-acento-500 focus:ring-2 focus:ring-acento-500/30');
?>
<section class="mx-auto flex min-h-[70vh] w-full max-w-md flex-col justify-center px-6 py-16">

    <header class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-widest text-acento-500">
            Evaluación de riesgo ISO/IEC 27002
        </p>
        <h1 class="mt-2 text-3xl font-extrabold text-marina-950">Crear cuenta</h1>
        <p class="mt-2 text-sm text-slate-600">
            La cuenta se crea con perfil de auditor.
        </p>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <form method="post" action="<?= e($vista->url('registrarse')) ?>" class="space-y-5">

        <?= $vista->campoToken() ?>

        <div>
            <label for="nombre" class="block text-sm font-semibold text-marina-950">
                Nombre completo
            </label>
            <input type="text" id="nombre" name="nombre" required autofocus maxlength="150"
                   autocomplete="name"
                   value="<?= e($valores['nombre'] ?? '') ?>"
                   <?= isset($errores['nombre']) ? 'aria-invalid="true" aria-describedby="error-nombre"' : '' ?>
                   class="<?= e($clasesCampo(isset($errores['nombre']))) ?>">
            <?php if (isset($errores['nombre'])): ?>
                <p id="error-nombre" class="mt-1.5 text-sm text-alerta-600"><?= e($errores['nombre']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="correo" class="block text-sm font-semibold text-marina-950">
                Correo electrónico
            </label>
            <input type="email" id="correo" name="correo" required maxlength="150"
                   autocomplete="username"
                   value="<?= e($valores['correo'] ?? '') ?>"
                   <?= isset($errores['correo']) ? 'aria-invalid="true" aria-describedby="error-correo"' : '' ?>
                   class="<?= e($clasesCampo(isset($errores['correo']))) ?>">
            <?php if (isset($errores['correo'])): ?>
                <p id="error-correo" class="mt-1.5 text-sm text-alerta-600"><?= e($errores['correo']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="organizacion" class="block text-sm font-semibold text-marina-950">
                Organización
            </label>
            <input type="text" id="organizacion" name="organizacion" required maxlength="200"
                   autocomplete="organization"
                   value="<?= e($valores['organizacion'] ?? '') ?>"
                   <?= isset($errores['organizacion']) ? 'aria-invalid="true" aria-describedby="error-organizacion"' : '' ?>
                   class="<?= e($clasesCampo(isset($errores['organizacion']))) ?>">
            <?php if (isset($errores['organizacion'])): ?>
                <p id="error-organizacion" class="mt-1.5 text-sm text-alerta-600"><?= e($errores['organizacion']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="clave" class="block text-sm font-semibold text-marina-950">
                Contraseña
            </label>
            <input type="password" id="clave" name="clave" required
                   minlength="<?= e((string) $minimoClave) ?>"
                   autocomplete="new-password"
                   <?= isset($errores['clave']) ? 'aria-invalid="true" aria-describedby="error-clave"' : '' ?>
                   class="<?= e($clasesCampo(isset($errores['clave']))) ?>">
            <?php if (isset($errores['clave'])): ?>
                <p id="error-clave" class="mt-1.5 text-sm text-alerta-600"><?= e($errores['clave']) ?></p>
            <?php else: ?>
                <p class="mt-1.5 text-sm text-slate-500">
                    Mínimo <?= e((string) $minimoClave) ?> caracteres.
                </p>
            <?php endif; ?>
        </div>

        <div>
            <label for="confirmacion" class="block text-sm font-semibold text-marina-950">
                Repetir contraseña
            </label>
            <input type="password" id="confirmacion" name="confirmacion" required
                   autocomplete="new-password"
                   <?= isset($errores['confirmacion']) ? 'aria-invalid="true" aria-describedby="error-confirmacion"' : '' ?>
                   class="<?= e($clasesCampo(isset($errores['confirmacion']))) ?>">
            <?php if (isset($errores['confirmacion'])): ?>
                <p id="error-confirmacion" class="mt-1.5 text-sm text-alerta-600"><?= e($errores['confirmacion']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-marina-950 px-4 py-3 font-semibold text-white transition hover:bg-marina-900">
            Crear cuenta
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        ¿Ya tiene cuenta?
        <a href="<?= e($vista->url('ingresar')) ?>"
           class="font-semibold text-acento-600 underline-offset-2 hover:underline">
            Iniciar sesión
        </a>
    </p>
</section>
