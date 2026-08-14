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
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

/*
 * Los tipos de auditoría son el MODELO DE MEDICIÓN contra el que se recorre el
 * instrumento. Son nombres propios de norma: no se traducen y no salen del
 * archivo de idiomas. Espejan la lista del plan Deluxe en config/contenido.php
 * —el disponible es contra lo que mide el instrumento hoy; los otros dos llegan
 * con el plan—, así que si allá se añade uno, aquí también.
 *
 * Hoy el esquema no guarda el tipo (los 75 controles son ISO y no hay columna
 * donde escribirlo), y por eso las opciones futuras van 'disabled': un campo
 * deshabilitado no se envía, así que el formulario no puede mandar un valor que
 * el sistema no sabría medir. El controlador lee solo los tres datos del
 * encabezado e ignora este campo mientras tanto.
 */
$tipos = [
    ['valor' => 'iso-27000',   'etiqueta' => 'ISO/IEC 27002 · 27007', 'disponible' => true],
    ['valor' => 'cobit-4.1',   'etiqueta' => 'COBIT 4.1',             'disponible' => false],
    ['valor' => 'nist-800-53', 'etiqueta' => 'NIST SP 800-53',        'disponible' => false],
];

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
                <label for="tipo" class="block text-sm font-semibold text-texto">
                    <?= e($vista->t('eval.tipo_auditoria')) ?>
                </label>
                <select id="tipo" name="tipo" class="<?= e($clasesCampo) ?>">
                    <?php foreach ($tipos as $tipo): ?>
                        <option value="<?= e($tipo['valor']) ?>"
                                <?= $tipo['disponible'] ? 'selected' : 'disabled' ?>>
                            <?= e($tipo['etiqueta']) ?><?= $tipo['disponible']
                                ? ''
                                : ' — ' . e($vista->t('eval.tipo_proximamente')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
