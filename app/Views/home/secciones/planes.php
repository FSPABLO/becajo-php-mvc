<?php

declare(strict_types=1);

/**
 * Sección «Planes» — va justo debajo de «Quién audita».
 *
 * Dos tarjetas comparables lado a lado. La estructura de cada una es la misma
 * en los dos planes (placa de precio arriba, acción en medio, lo incluido
 * abajo), porque comparar precios exige que las dos columnas se lean a la misma
 * altura; lo único que cambia entre ellas es el ÉNFASIS.
 *
 * Es una de las cuatro secciones claras de la cadencia (.rv-alterno): cierra la
 * portada en pergamino después del equipo, y contacto —la siguiente— vuelve al
 * lienzo oscuro. Por eso aquí solo se usan TOKENS: cualquier color fijado a
 * mano se rompería al invertir la paleta.
 *
 * El plan destacado se distingue por TRES canales a la vez: la palabra de la
 * insignia, el tinte dorado de la placa y el borde. El tinte solo no bastaría
 * — la regla del sistema visual es que el color nunca sea el único canal.
 *
 * DESVIACIÓN DECLARADA (§5.2): el oro está reservado a la referencia
 * normativa. Aquí lo cumplen los badges de marcos al pie de la tarjeta, pero
 * el tinte y el borde del plan de pago son ORNAMENTO —decisión de producto,
 * pedida explícitamente— y no significan «norma». El verde sigue siendo lo que
 * significa acción y estado: la insignia «Recomendado» y el botón no cambian.
 *
 * @var \App\Core\Vista      $vista
 * @var array<string, mixed> $planes
 */
?>
<section id="planes" class="rv-alterno bg-fondo">
    <div class="mx-auto max-w-[1180px] px-5 py-16">

        <div class="mb-[34px] flex flex-col items-center gap-2 text-center">
            <span class="text-[11.5px] uppercase tracking-[0.14em] text-texto-2">
                <?= e($planes['etiqueta']) ?>
            </span>
            <h2 class="rv-titulo m-0 text-[2rem] font-medium leading-[1.15] text-texto sm:text-4xl">
                <?= e($planes['titulo']) ?>
            </h2>
            <?php if (($planes['texto'] ?? '') !== ''): ?>
                <p class="rv-titulo m-0 max-w-[58ch] text-base leading-[1.55] text-texto-2 sm:text-lg">
                    <?= e($planes['texto']) ?>
                </p>
            <?php endif; ?>
        </div>

        <?php
        /*
         * items-stretch (el valor por defecto de grid) es intencional: las dos
         * tarjetas miden lo mismo aunque un plan liste una ventaja más, y así
         * la comparación no sugiere que la más alta valga más.
         */
        ?>
        <ul class="mx-auto grid max-w-[880px] gap-6 sm:grid-cols-2">
            <?php foreach ($planes['lista'] as $plan): ?>
                <?php $destacado = (bool) ($plan['destacado'] ?? false); ?>
                <?php
                /*
                 * Relieve PLENO y borde de token en las dos tarjetas.
                 *
                 * Sobre pergamino el realce del neumorfismo es casi blanco, así
                 * que las esquinas de arriba se perdían contra el lienzo: el
                 * par de sombras solo dibuja bien los lados que caen. El borde
                 * es lo que cierra la silueta; el relieve pleno es lo que le da
                 * peso. Ninguno de los dos basta por su cuenta.
                 */
                ?>
                <li class="rv-extruido-lg rv-relieve-pleno flex flex-col gap-5 rounded-[20px] border bg-superficie p-5 <?= $destacado ? 'border-oro/45' : 'border-borde' ?>">

                    <?php
                    /*
                     * Placa del precio. No lleva relieve propio: dos extruidos
                     * anidados se pelean por el mismo borde. Se separa de la
                     * tarjeta solo por el relleno, tinte primario en el plan
                     * destacado y el lienzo en el otro.
                     */
                    ?>
                    <div class="flex flex-col gap-4 rounded-[15px] p-6 <?= $destacado ? 'bg-oro-tinte' : 'bg-fondo' ?>">

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-3 py-1 text-[11px] uppercase tracking-[0.14em] <?= $destacado ? 'border-oro/50 text-oro-texto' : 'border-borde text-texto-2' ?>">
                                <?= e($plan['nombre']) ?>
                            </span>
                            <?php if (($plan['insignia'] ?? '') !== ''): ?>
                                <span class="rounded-full bg-primario px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-primario-texto">
                                    <?= e($plan['insignia']) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php /* La cifra en Inter tabular: es un dato, no un título. */ ?>
                        <p class="m-0 flex items-baseline gap-1">
                            <span class="tabular text-[2.6rem] font-semibold leading-none text-texto">
                                <?= e($plan['precio']) ?>
                            </span>
                            <span class="text-[15px] text-texto-2">
                                <?= e($plan['periodo']) ?>
                            </span>
                        </p>

                        <p class="m-0 text-[13.5px] leading-[1.5] text-texto-2">
                            <?= e($plan['resumen']) ?>
                        </p>
                    </div>

                    <?php
                    /*
                     * destino() y no url(): la acción del plan pagado es el
                     * ancla #contacto de esta misma portada y la del gratuito
                     * una ruta del sitio; el ayudante resuelve las dos.
                     */
                    ?>
                    <a href="<?= e($vista->destino($plan['accion']['destino'])) ?>"
                       class="rv-extruido rv-interactivo block rounded-rv px-6 py-3.5 text-center text-[14px] font-semibold <?= $destacado ? 'bg-primario text-primario-texto' : 'border border-borde bg-elevado text-texto' ?>">
                        <?= e($plan['accion']['etiqueta']) ?>
                    </a>

                    <?php /* mt-auto pega la lista al pie: las dos listas quedan a la misma altura. */ ?>
                    <div class="mt-auto border-t border-borde pt-5">
                        <ul class="flex flex-col gap-3">
                            <?php foreach ($plan['incluye'] as $ventaja): ?>
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-[3px] shrink-0 text-primario">
                                        <?= icono('check', 'h-4 w-4') ?>
                                    </span>
                                    <span class="text-[13.5px] leading-[1.5] text-texto-2">
                                        <?= e($ventaja) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if (($plan['normas'] ?? null) !== null): ?>
                        <?php
                        /*
                         * Los marcos van en .rv-badge-norma —mono sobre tinte
                         * dorado— porque son referencia normativa, que es lo
                         * único que el oro puede significar (§5.2). Aquí el oro
                         * NO está diciendo «plan premium»: está diciendo «esto
                         * es una norma».
                         */
                        ?>
                        <div class="border-t border-borde pt-5">
                            <p class="m-0 mb-2.5 text-[11px] uppercase tracking-[0.14em] text-texto-2">
                                <?= e($plan['normas']['etiqueta']) ?>
                            </p>
                            <ul class="flex flex-wrap gap-2">
                                <?php foreach ($plan['normas']['lista'] as $norma): ?>
                                    <li><span class="rv-badge-norma"><?= e($norma) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
