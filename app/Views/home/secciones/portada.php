<?php

declare(strict_types=1);

/**
 * Banner de la portada: hero tipográfico sobre el instrumento.
 *
 * UNA SOLA COLUMNA, CENTRADA. No hay tarjeta a la derecha y no es un olvido.
 * Aquí hubo una de índice de riesgo —un 3,4 sobre 5,0 en rojo, de 52 px, con el
 * expediente inventado AUD-0042— y se retiró por tres razones que conviene que
 * no se deshagan:
 *
 *   - Era el objeto más pesado de la pantalla. Tamaño, color único y
 *     aislamiento apilados le ganaban la primera fijación al botón, que es lo
 *     único que la portada tiene que conseguir.
 *   - La cifra no tenía sujeto. Sin nadie a quien atribuirla, el mal resultado
 *     se lee del emisor: la consultora abría su sitio con un suspenso.
 *   - Contradecía a «Retos», que reprocha el «cumplimiento declarado sin
 *     evidencia». Exhibir un expediente inventado es exactamente eso, y el
 *     comprador de esta casa es justamente quien lo nota.
 *
 * Lo que queda debajo del argumento NO es un resultado: es la estructura del
 * método. El tamaño del instrumento y sus dominios dicen QUÉ se mide, nunca
 * cómo le salió a nadie. Un tablero es la salida del trabajo del cliente; la
 * portada vende el método.
 *
 * LAS CIFRAS SE CUENTAN, NO SE ESCRIBEN. Salen del repositorio que entrega el
 * controlador, así que el «75» del banner ES el número de controles que hay. Si
 * alguien añade uno, el banner lo refleja sin que nadie se acuerde de venir a
 * cambiarlo — que es justo lo que no pasaría con un valor en config.
 *
 * @var \App\Core\Vista $vista
 * @var array<string, mixed> $hero
 * @var list<\App\Models\Entidades\Dominio> $dominios
 * @var list<\App\Models\Entidades\Proceso> $procesos
 * @var list<\App\Models\Entidades\Control> $controles
 */
$dominios = $dominios ?? [];
$procesos = $procesos ?? [];
$controles = $controles ?? [];

/*
 * Controles por dominio. La cadena es control -> proceso -> dominio: el control
 * solo conoce el número de su proceso, y es el proceso el que sabe a qué
 * dominio pertenece. De ahí el índice intermedio, que evita recorrer los
 * procesos una vez por cada uno de los setenta y cinco controles.
 */
$dominioDelProceso = [];
foreach ($procesos as $proceso) {
    $dominioDelProceso[$proceso->numero] = $proceso->dominio;
}

$controlesPorDominio = [];
foreach ($controles as $control) {
    $clave = $dominioDelProceso[$control->proceso] ?? null;

    if ($clave !== null) {
        $controlesPorDominio[$clave] = ($controlesPorDominio[$clave] ?? 0) + 1;
    }
}

/** Las tres cifras del instrumento, contadas y no declaradas. */
$totales = [
    'controles' => count($controles),
    'procesos'  => count($procesos),
    'dominios'  => count($dominios),
];

/** Los iconos son máscaras; aquí solo se compone su ruta. */
$rutaIcono = static fn (string $archivo): string =>
    $vista->recurso('assets/images/icons/' . $archivo);
?>
<section id="inicio" class="relative overflow-hidden bg-fondo pt-16">

    <?php
    /*
     * El relleno inferior es deliberadamente menor que el superior: así asoma
     * el borde de «Retos» y la portada se lee como el principio de una página
     * y no como una pantalla completa. Sin ese corte, una composición aireada
     * en escritorio parece una página que no terminó de cargar.
     */
    ?>
    <div class="mx-auto max-w-[1180px] px-5 pb-[72px] pt-[86px]">

        <div class="mx-auto flex max-w-[760px] flex-col items-center gap-[26px] text-center">

            <?php /* Badge normativo: oro y mono, extruido. */ ?>
            <span class="rv-extruido rv-badge-norma px-[13px] py-[7px]">
                <?= e($hero['norma']) ?>
            </span>

            <h1 class="rv-titulo m-0 text-[2.5rem] font-medium leading-[1.08] tracking-[-0.01em] text-texto sm:text-5xl lg:text-[58px]">
                <?= e($hero['titulo']) ?>
            </h1>

            <p class="rv-titulo m-0 max-w-[56ch] text-xl leading-[1.55] text-texto-2">
                <?= e($hero['texto']) ?>
            </p>

            <div class="flex flex-wrap justify-center gap-3">
                <a href="<?= e($vista->destino($hero['cta_primario']['destino'])) ?>"
                   class="rv-extruido rv-interactivo rounded-rv-lg bg-primario px-[26px] py-[15px] text-[15px] font-semibold text-primario-texto">
                    <?= e($hero['cta_primario']['etiqueta']) ?>
                </a>
                <a href="<?= e($vista->destino($hero['cta_secundario']['destino'])) ?>"
                   class="rv-extruido rv-interactivo rounded-rv-lg bg-superficie px-[26px] py-[15px] text-[15px] font-medium text-texto">
                    <?= e($hero['cta_secundario']['etiqueta']) ?>
                </a>
            </div>
        </div>

        <?php if (($hero['cifras'] ?? []) !== [] && $totales['controles'] > 0): ?>
            <?php
            /*
             * El tamaño del instrumento. El icono va en ORO porque lo que
             * rotula es referencia normativa —controles, procesos y dominios de
             * ISO/IEC 27002—, que es el único significado que el sistema visual
             * le concede al oro (§5.2). Por eso mismo esta fila no puede
             * ganar nunca un pill de estado al lado: un icono dorado junto a
             * una escala de estado se leería como «riesgo medio».
             *
             * El PNG entra como máscara (.rv-icono-masc), así que el oro sale
             * del token y no del archivo: los iconos siguen la paleta.
             */
            ?>
            <dl class="mx-auto mt-[54px] grid max-w-[760px] grid-cols-3 gap-6 border-t border-borde pt-9">
                <?php foreach ($hero['cifras'] as $cifra): ?>
                    <?php $valor = $totales[$cifra['clave']] ?? null; ?>
                    <?php if ($valor !== null): ?>
                        <div class="flex flex-col items-center gap-1.5 text-center">

                            <span class="rv-icono-masc h-8 w-8 text-oro-texto"
                                  style="--rv-icono: url('<?= e($rutaIcono($cifra['icono'])) ?>')"
                                  aria-hidden="true"></span>

                            <dd class="tabular m-0 text-[26px] font-semibold leading-none text-texto">
                                <?= e((string) $valor) ?>
                            </dd>
                            <dt class="text-[11.5px] uppercase tracking-[0.05em] text-texto-2">
                                <?= e($cifra['etiqueta']) ?>
                            </dt>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>

        <?php if ($dominios !== []): ?>
            <?php
            /*
             * Los siete dominios con su número de controles: el índice del
             * método. Es lo que sustituye al tablero de resultados — enseña
             * densidad y orden, que es como se comunica competencia sin pedirle
             * al lector que juzgue el resultado de otro.
             *
             * Se usa el nombre corto porque el largo no cabe en una fila de
             * siete; si un dominio no lo trae, la entidad ya devuelve el
             * completo en su lugar.
             */
            ?>
            <div class="mx-auto mt-11 max-w-[900px] text-center">

                <span class="text-[11.5px] uppercase tracking-[0.05em] text-texto-2">
                    <?= e((string) ($hero['dominios_rotulo'] ?? '')) ?>
                </span>

                <ul class="m-0 mt-4 flex list-none flex-wrap justify-center gap-2 p-0">
                    <?php foreach ($dominios as $dominio): ?>
                        <li class="rv-extruido-sm flex items-baseline gap-2 rounded-rv bg-superficie px-3.5 py-2">
                            <span class="text-[13px] text-texto">
                                <?= e($dominio->corto) ?>
                            </span>
                            <?php /* Neutro, no oro: es un conteo, no una cláusula. */ ?>
                            <span class="tabular text-[12px] font-semibold text-texto-2">
                                <?= e((string) ($controlesPorDominio[$dominio->clave] ?? 0)) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
