<?php

declare(strict_types=1);

/**
 * Alta de una auditoría.
 *
 * Dos columnas a partir de lg: a la izquierda el formulario —en una tarjeta
 * EXTRUIDA que lo levanta del pergamino— y a la derecha la leyenda, HUNDIDA,
 * que explica qué se está creando. El relieve hace aquí el trabajo que haría un
 * borde: lo que se rellena sobresale, lo que solo se lee está incrustado.
 *
 * La leyenda va en cuerpo menor que el formulario y con el texto justo: es
 * apoyo, no lectura principal, y una columna de prosa más alta que la ficha
 * desequilibraría la pantalla hacia el lado que NO se rellena.
 *
 * No lleva enlace de regreso: «Mis auditorías» está en la barra lateral, a la
 * vista y a un clic. La cabecera de cada pantalla es para lo suyo.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Usuario> $administradores
 * @var list<\App\Models\Entidades\Estandar> $estandares
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

/*
 * La norma es el catálogo contra el que se recorre la auditoría y no cambia
 * después de creada. Las opciones salen de la tabla estandar; NIST sigue como
 * «próximamente» porque el plan Deluxe lo anuncia y todavía no tiene catálogo.
 * Una opción deshabilitada no se envía, así que no puede llegar al servidor.
 */
$elegida = (string) ($valores['estandar'] ?? \App\Models\Entidades\Estandar::ISO);
$proximas = ['NIST SP 800-53'];

// Mismo aspecto que los campos del parcial del encabezado: hundido, porque el
// inset es lo que significa «aquí se recibe algo».
$clasesCampo = 'rv-hundido mt-1.5 w-full rounded-rv border border-borde bg-superficie px-3.5 py-2.5 '
    . 'text-texto outline-none transition focus:border-primario focus:ring-2 focus:ring-primario/30';
?>
<section class="mx-auto w-full max-w-5xl px-6 py-8 lg:px-8">

    <header class="mb-6 flex items-start gap-4">
        <?php /* Disco extruido: la misma pieza del ícono de la barra lateral,
                 aquí levantada para que el encabezado tenga un ancla visual. */ ?>
        <span class="rv-extruido rv-relieve-pleno hidden h-12 w-12 shrink-0 items-center justify-center rounded-rv-lg border border-borde bg-superficie text-primario sm:flex">
            <?= icono('chispa', 'h-6 w-6') ?>
        </span>
        <div>
            <h1 class="rv-titulo text-3xl font-semibold text-texto">
                <?= e($vista->t('eval.nueva_auditoria')) ?>
            </h1>
            <p class="mt-1 text-texto-2"><?= e($vista->t('eval.nueva_subtitulo')) ?></p>
        </div>
    </header>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start">

        <form method="post" action="<?= e($vista->url('evaluacion/nueva')) ?>"
              class="rv-extruido-lg rv-relieve-pleno space-y-5 rounded-rv-lg border border-borde bg-superficie p-6 sm:p-8">
            <?= $vista->campoToken() ?>

            <div>
                <label for="estandar" class="block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.tipo_auditoria')) ?>
                </label>
                <select id="estandar" name="estandar" class="<?= e($clasesCampo) ?>">
                    <?php foreach ($estandares as $estandar): ?>
                        <option value="<?= e($estandar->codigo) ?>"
                                <?= $estandar->codigo === $elegida ? 'selected' : '' ?>>
                            <?= e($estandar->etiqueta()) ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ($proximas as $proxima): ?>
                        <option disabled>
                            <?= e($proxima) ?> — <?= e($vista->t('eval.tipo_proximamente')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['estandar'])): ?>
                    <p class="mt-1 text-[13px] text-bad"><?= e($errores['estandar']) ?></p>
                <?php endif; ?>
                <p class="mt-1.5 text-sm text-texto-2"><?= e($vista->t('eval.tipo_ayuda')) ?></p>
            </div>

            <?= $vista->renderizar('evaluacion/_encabezado-form', compact('errores', 'valores', 'administradores')) ?>

            <button type="submit"
                    class="w-full rv-extruido rv-relieve-pleno rv-interactivo rounded-rv bg-primario px-4 py-3 font-semibold text-primario-texto">
                <?= e($vista->t('eval.crear_auditoria')) ?>
            </button>
        </form>

        <?php
        /*
         * La leyenda. Quien entra por primera vez ve cuatro campos sueltos y
         * ningún indicio de qué desencadenan: el instrumento completo, el
         * índice de riesgo y las remediaciones salen de aquí. Va HUNDIDA para
         * que no compita con el formulario —es lectura, no captura— y se coloca
         * al lado, no debajo, porque debajo del botón ya nadie la lee.
         */
        ?>
        <aside class="rv-hundido rounded-rv-lg border border-borde bg-superficie p-5">

            <h2 class="rv-titulo text-base font-semibold text-texto">
                <?= e($vista->t('eval.nueva_leyenda_titulo')) ?>
            </h2>
            <p class="mt-2 text-[13px] leading-[1.55] text-texto-2">
                <?= e($vista->t('eval.nueva_leyenda_texto')) ?>
            </p>

            <div class="mt-4 border-t border-borde pt-4">
                <p class="text-[11px] uppercase tracking-[0.14em] text-texto-2">
                    <?= e($vista->t('eval.nueva_modelos_proximos')) ?>
                </p>
                <?php /* Badges de norma: el oro solo puede significar referencia
                         normativa, y esto lo es. No dice «plan de pago». */ ?>
                <ul class="mt-2 flex flex-wrap gap-2">
                    <?php foreach ($tipos as $tipo): ?>
                        <?php if (!$tipo['disponible']): ?>
                            <li><span class="rv-badge-norma"><?= e($tipo['etiqueta']) ?></span></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <p class="mt-2 text-[13px] leading-[1.55] text-texto-2">
                    <?= e($vista->t('eval.nueva_modelos_texto')) ?>
                </p>
                <p class="mt-3">
                    <a href="<?= e($vista->destino('#planes')) ?>"
                       class="text-[13px] font-semibold text-primario underline-offset-2 hover:underline">
                        <?= e($vista->t('eval.nueva_ver_plan')) ?>
                    </a>
                </p>
            </div>
        </aside>
    </div>
</section>
