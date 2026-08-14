<?php

declare(strict_types=1);

/**
 * Preguntas frecuentes.
 *
 * Marco público, así que la primera franja abre con pt-16: la barra del sitio
 * es fija y el layout no reserva su alto (a diferencia del panel, que la lleva
 * pegajosa). Ver «Dos marcos» en CLAUDE.md.
 *
 * Las respuestas se leen, NO se despliegan. Un acordeón ahorra desplazamiento a
 * costa de esconder catorce respuestas para enseñar una, y quien llega aquí
 * suele traer dos o tres dudas a la vez, no una: buscar con Ctrl+F o imprimir
 * la página solo funcionan si el texto está puesto. La navegación rápida la
 * resuelve el índice de arriba, que sí es barato.
 *
 * Los grupos alternan lienzo —claro, oscuro, claro— con .rv-alterno, que es la
 * misma cadencia de sección de la portada y no un tema: cada grupo responde una
 * duda distinta, y el salto de lienzo dice dónde termina una y empieza la otra
 * sin gastar un borde. El último cierra en claro para que el bloque final y el
 * pie, ambos oscuros, se lean como una sola pieza de salida.
 *
 * La numeración corre de 01 a 15 a través de los tres grupos, no de 01 a 06 y
 * vuelta a empezar: la página promete quince respuestas y el contador es lo que
 * lo demuestra. Va en mono y oro porque es referencia, igual que en «Retos».
 *
 * @var \App\Core\Vista      $vista
 * @var array<string, mixed> $preguntas
 */
$grupos = $preguntas['grupos'] ?? [];
$cierre = $preguntas['cierre'] ?? null;

/** Contador continuo entre grupos. */
$numero = 0;
?>
<section class="bg-fondo pt-16">
    <div class="mx-auto max-w-[1180px] px-5 py-16">

        <div class="flex flex-col items-center gap-3 text-center">
            <span class="text-[11.5px] uppercase tracking-[0.14em] text-texto-2">
                <?= e($preguntas['eyebrow']) ?>
            </span>
            <h1 class="rv-titulo m-0 max-w-3xl text-[2.25rem] font-medium leading-[1.12] text-texto sm:text-5xl">
                <?= e($preguntas['titulo']) ?>
            </h1>
            <p class="rv-titulo m-0 max-w-2xl text-[17px] leading-[1.6] text-texto-2">
                <?= e($preguntas['texto']) ?>
            </p>
        </div>

        <?php if ($grupos !== []): ?>
            <?php
            /*
             * Índice de la página: anclas a los tres grupos. Es <nav> y lleva
             * su propio nombre accesible porque, en una página de una sola
             * columna, un lector de pantalla no puede distinguir esta fila de
             * enlaces de un bloque de contenido cualquiera.
             */
            ?>
            <nav class="mt-9 flex flex-col items-center gap-3"
                 aria-label="<?= e($preguntas['indice']) ?>">
                <span class="font-mono text-[11px] uppercase tracking-[0.12em] text-texto-2">
                    <?= e($preguntas['indice']) ?>
                </span>
                <ul class="flex flex-wrap justify-center gap-2.5">
                    <?php foreach ($grupos as $grupo): ?>
                        <li>
                            <a href="#<?= e($grupo['clave']) ?>"
                               class="rv-extruido rv-interactivo inline-flex rounded-full bg-superficie px-4 py-2 text-[13px] font-medium text-texto">
                                <?= e($grupo['titulo']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</section>

<?php foreach ($grupos as $indice => $grupo): ?>
    <?php
    /*
     * Grupos impares en oscuro y pares en claro (el primero es el índice 0, y
     * va claro). La clase se decide aquí y no en el contenido: la cadencia es
     * del diseño, no del texto, y config/contenido.php no debe poder romperla.
     */
    $claro = $indice % 2 === 0;

    /*
     * La tarjeta se separa del lienzo de su sección igual que en la portada:
     * sobre pergamino es neumorfismo puro —mismo relleno que el fondo, solo la
     * sombra la levanta— y sobre la noche necesita la superficie, porque ahí
     * el relieve solo no basta para despegarla.
     */
    $fondoTarjeta = $claro ? 'bg-fondo' : 'bg-superficie';
    ?>
    <section id="<?= e($grupo['clave']) ?>" class="<?= $claro ? 'rv-alterno ' : '' ?>bg-fondo">
        <div class="mx-auto max-w-[1180px] px-5 py-16">

            <div class="mb-[30px] flex flex-col gap-2">
                <h2 class="rv-titulo m-0 text-[2rem] font-medium leading-[1.15] text-texto sm:text-4xl">
                    <?= e($grupo['titulo']) ?>
                </h2>
                <p class="rv-titulo m-0 max-w-2xl text-base leading-[1.55] text-texto-2">
                    <?= e($grupo['texto']) ?>
                </p>
            </div>

            <?php
            /*
             * Dos columnas desde 900 px y una debajo. El mínimo es alto a
             * propósito: son respuestas de varias líneas, y una rejilla que
             * admitiera columnas más angostas las partiría en tiras de tres
             * palabras.
             */
            ?>
            <ul class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(340px,1fr))]">
                <?php foreach ($grupo['lista'] as $entrada): ?>
                    <?php $numero++; ?>
                    <li class="rv-extruido flex flex-col gap-2.5 rounded-[16px] <?= e($fondoTarjeta) ?> p-[22px]">
                        <span class="font-mono text-[11px] text-oro-texto">
                            <?= e(str_pad((string) $numero, 2, '0', STR_PAD_LEFT)) ?>
                        </span>
                        <h3 class="m-0 text-[16.5px] font-semibold leading-snug text-texto">
                            <?= e($entrada['pregunta']) ?>
                        </h3>
                        <p class="rv-titulo m-0 text-base leading-[1.6] text-texto-2">
                            <?= e($entrada['respuesta']) ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
<?php endforeach; ?>

<?php if ($cierre !== null): ?>
    <section class="bg-fondo">
        <div class="mx-auto max-w-[1180px] px-5 pb-20 pt-16">
            <div class="rv-extruido flex flex-col items-center gap-4 rounded-[18px] bg-superficie px-6 py-12 text-center">
                <h2 class="rv-titulo m-0 max-w-2xl text-[1.75rem] font-medium leading-[1.2] text-texto sm:text-[2rem]">
                    <?= e($cierre['titulo']) ?>
                </h2>
                <p class="rv-titulo m-0 max-w-2xl text-base leading-[1.6] text-texto-2">
                    <?= e($cierre['texto']) ?>
                </p>
                <div class="mt-1.5 flex flex-wrap justify-center gap-3">
                    <?php /* Relleno verde: el texto va en text-primario-texto, nunca en text-texto. */ ?>
                    <a href="<?= e($vista->destino($cierre['principal']['destino'])) ?>"
                       class="rv-extruido rv-interactivo rounded-rv-lg bg-primario px-[26px] py-[15px] text-[15px] font-semibold text-primario-texto">
                        <?= e($cierre['principal']['etiqueta']) ?>
                    </a>
                    <a href="<?= e($vista->destino($cierre['secundaria']['destino'])) ?>"
                       class="rv-extruido rv-interactivo rounded-rv-lg bg-elevado px-[26px] py-[15px] text-[15px] font-medium text-texto">
                        <?= e($cierre['secundaria']['etiqueta']) ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>
