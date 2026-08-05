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
    <h2 class="text-2xl font-extrabold tracking-tight text-marina-950">
        Las doce normas y su aporte al instrumento
    </h2>
    <p class="mt-3 max-w-3xl leading-relaxed text-slate-600">
        La familia ISO/IEC 27000 no es una lista de requisitos intercambiables: cada norma
        cumple una función distinta. A continuación se indica qué parte del instrumento se
        sostiene en cada una.
    </p>

    <div class="mt-8 grid gap-4 lg:grid-cols-2">
        <?php foreach ($marco as $norma): ?>
            <article class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-marina-950 px-2.5 py-1 font-mono text-xs font-bold text-acento-400">
                        <?= e($norma['norma']) ?>
                    </span>
                </div>
                <h3 class="mt-3 font-bold text-marina-950"><?= e($norma['titulo']) ?></h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600"><?= e($norma['aporte']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-14">
    <h2 class="text-2xl font-extrabold tracking-tight text-marina-950">
        Escala de madurez
    </h2>
    <p class="mt-3 max-w-3xl leading-relaxed text-slate-600">
        La madurez no mide si el control existe, sino cómo se sostiene en el tiempo. Un
        control puede existir y estar en nivel 1: se ejecuta, pero depende de que la persona
        correcta esté ese día.
    </p>

    <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <?php foreach ($escala as $nivel): ?>
            <div class="flex gap-4 border-b border-slate-100 px-6 py-5 last:border-b-0">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-lg font-extrabold text-marina-950">
                    <?= e((string) $nivel['nivel']) ?>
                </span>
                <div>
                    <h3 class="font-bold text-marina-950"><?= e($nivel['nombre']) ?></h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600"><?= e($nivel['descripcion']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-14">
    <h2 class="text-2xl font-extrabold tracking-tight text-marina-950">
        Cómo se calcula el resultado
    </h2>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <article class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="font-bold text-marina-950">Cumplimiento</h3>
            <p class="mt-2 font-mono text-sm text-acento-600">sí ÷ (sí + no)</p>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                Los controles marcados «no aplica» salen del denominador, igual que una
                exclusión justificada en la Declaración de Aplicabilidad (ISO/IEC 27001,
                cl. 6.1.3). Los controles sin evaluar no cuentan en ninguno de los dos lados.
            </p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="font-bold text-marina-950">Madurez promedio</h3>
            <p class="mt-2 font-mono text-sm text-acento-600">Σ madurez ÷ n calificados</p>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                Solo entran los controles con una madurez registrada. Un control sin
                calificar no vale cero: vale nada, y por eso se excluye del promedio.
            </p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="font-bold text-marina-950">Denominador en cero</h3>
            <p class="mt-2 font-mono text-sm text-acento-600">—</p>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                Cuando ningún control del proceso entra en el cálculo, se muestra un guion.
                Un cero por ciento afirmaría un incumplimiento que nadie verificó.
            </p>
        </article>
    </div>
</section>
