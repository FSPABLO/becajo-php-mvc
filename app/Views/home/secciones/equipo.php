<?php

declare(strict_types=1);

/**
 * Sección «Equipo».
 *
 * Encabezado centrado y rejilla auto-fit con un mínimo de 230 px, así que las
 * fichas se reacomodan solas sin puntos de quiebre propios.
 *
 * Cada ficha es un retrato cuadrado sobre el nombre y el rol. Mientras no haya
 * fotografías, el hueco rayado (.rv-retrato) muestra las iniciales: es un
 * espacio reservado explícito, no un adorno. En cuanto un integrante traiga
 * 'foto' en config/contenido.php, la imagen ocupa el mismo hueco sin tocar
 * esta vista.
 *
 * @var \App\Core\Vista $vista
 * @var list<\App\Models\Entidades\Integrante> $equipo
 */
?>
<section id="equipo" class="bg-fondo">
    <div class="mx-auto max-w-[1180px] px-5 py-16">

        <div class="mb-[30px] flex flex-col items-center gap-2 text-center">
            <span class="text-[11.5px] uppercase tracking-[0.14em] text-texto-2">
                Equipo
            </span>
            <h2 class="rv-titulo m-0 text-[2rem] font-medium leading-[1.15] text-texto sm:text-4xl">
                Quién audita
            </h2>
        </div>

        <ul class="grid gap-5 grid-cols-[repeat(auto-fit,minmax(230px,1fr))]">
            <?php foreach ($equipo as $integrante): ?>
                <li class="rv-extruido flex flex-col gap-3.5 rounded-[16px] bg-superficie p-[22px]">

                    <?php if ($integrante->foto !== ''): ?>
                        <div class="rv-retrato">
                            <img src="<?= e($vista->recurso($integrante->foto)) ?>"
                                 alt="<?= e($integrante->nombre) ?>" loading="lazy" width="230" height="230">
                        </div>
                    <?php else: ?>
                        <?php /* Sin foto: el hueco lleva las iniciales, no una etiqueta de relleno. */ ?>
                        <div class="rv-retrato grid place-items-center" aria-hidden="true">
                            <span class="font-mono text-2xl text-texto-2"><?= e($integrante->iniciales) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-col items-center gap-[3px] text-center">
                        <h3 class="m-0 text-[15.5px] font-semibold leading-snug text-texto">
                            <?= e($integrante->nombre) ?>
                        </h3>
                        <?php
                        /*
                         * Solo el rol. El formato pide una línea corta bajo el
                         * nombre; 'descripcion' es una frase completa y aquí
                         * llenaría media ficha. Si hace falta ese detalle, va
                         * dentro de 'rol' («Auditor líder · ISO/IEC 27007»),
                         * no como segunda línea.
                         */
                        ?>
                        <p class="m-0 text-[12.5px] leading-snug text-texto-2">
                            <?= e($integrante->rol) ?>
                        </p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
