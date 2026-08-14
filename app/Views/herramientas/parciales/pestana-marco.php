<?php

declare(strict_types=1);

/**
 * Pestaña Marco ISO: contenido estático.
 *
 * @var list<array{norma: string, titulo: string, aporte: string}>   $marco
 * @var list<array{nivel: int, nombre: string, descripcion: string}> $escala
 */
?>
<section>
    <h2 class="text-2xl font-extrabold tracking-tight text-texto">
        Las doce normas y su aporte al instrumento
    </h2>
    <p class="mt-3 max-w-3xl leading-relaxed text-texto-2">
        La familia ISO/IEC 27000 no es una lista de requisitos intercambiables: cada norma
        cumple una función distinta. A continuación se indica qué parte del instrumento se
        sostiene en cada una.
    </p>

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        <?php foreach ($marco as $norma): ?>
            <article class="rv-extruido rv-interactivo rounded-rv-lg border border-borde bg-superficie p-6">
                <div class="flex flex-wrap items-center gap-2">
                    <?php /* Es literalmente una referencia normativa: le
                             corresponde el badge del sistema, no una pastilla
                             propia con el color de acento. */ ?>
                    <span class="rv-badge-norma">
                        <?= e($norma['norma']) ?>
                    </span>
                </div>
                <h3 class="mt-3 font-bold text-texto"><?= e($norma['titulo']) ?></h3>
                <p class="mt-2 text-sm leading-relaxed text-texto-2"><?= e($norma['aporte']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-14">
    <h2 class="text-2xl font-extrabold tracking-tight text-texto">
        Escala de madurez
    </h2>
    <p class="mt-3 max-w-3xl leading-relaxed text-texto-2">
        La madurez no mide si el control existe, sino cómo se sostiene en el tiempo. Un
        control puede existir y estar en nivel 1: se ejecuta, pero depende de que la persona
        correcta esté ese día.
    </p>

    <div class="rv-extruido mt-8 overflow-hidden rounded-rv-lg border border-borde bg-superficie">
        <?php foreach ($escala as $nivel): ?>
            <div class="flex gap-4 border-b border-borde px-6 py-5 last:border-b-0">
                <span class="rv-extruido-sm tabular grid h-10 w-10 shrink-0 place-items-center rounded-rv bg-elevado text-lg font-extrabold text-texto">
                    <?= e((string) $nivel['nivel']) ?>
                </span>
                <div>
                    <h3 class="font-bold text-texto"><?= e($nivel['nombre']) ?></h3>
                    <p class="mt-1 text-sm leading-relaxed text-texto-2"><?= e($nivel['descripcion']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-14">
    <h2 class="text-2xl font-extrabold tracking-tight text-texto">
        Cómo se calcula el resultado
    </h2>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <article class="rounded-rv-lg border border-borde bg-superficie p-6">
            <h3 class="font-bold text-texto">Cumplimiento</h3>
            <p class="mt-2 font-mono text-sm text-primario">sí ÷ (sí + no)</p>
            <p class="mt-3 text-sm leading-relaxed text-texto-2">
                Los controles marcados «no aplica» salen del denominador, igual que una
                exclusión justificada en la Declaración de Aplicabilidad (ISO/IEC 27001,
                cl. 6.1.3). Los controles sin evaluar no cuentan en ninguno de los dos lados.
            </p>
        </article>
        <article class="rounded-rv-lg border border-borde bg-superficie p-6">
            <h3 class="font-bold text-texto">Madurez promedio</h3>
            <p class="mt-2 font-mono text-sm text-primario">Σ madurez ÷ n calificados</p>
            <p class="mt-3 text-sm leading-relaxed text-texto-2">
                Solo entran los controles con una madurez registrada. Un control sin
                calificar no vale cero: vale nada, y por eso se excluye del promedio.
            </p>
        </article>
        <article class="rounded-rv-lg border border-borde bg-superficie p-6">
            <h3 class="font-bold text-texto">Denominador en cero</h3>
            <p class="mt-2 font-mono text-sm text-primario">—</p>
            <p class="mt-3 text-sm leading-relaxed text-texto-2">
                Cuando ningún control del proceso entra en el cálculo, se muestra un guion.
                Un cero por ciento afirmaría un incumplimiento que nadie verificó.
            </p>
        </article>
    </div>
</section>
