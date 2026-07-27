<?php

declare(strict_types=1);

/** @var array<string, mixed> $retos */
?>
<section id="retos" class="bg-white py-24">
    <div class="mx-auto max-w-7xl px-6 lg:px-8">

        <div class="max-w-2xl">
            <h2 class="text-3xl font-extrabold tracking-tight text-marina-950 sm:text-4xl">
                <?= e($retos['titulo']) ?>
            </h2>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                <?= e($retos['texto']) ?>
            </p>
        </div>

        <dl class="mt-14 grid gap-x-10 gap-y-12 sm:grid-cols-2">
            <?php foreach ($retos['lista'] as $indice => $reto): ?>
                <div class="border-l-2 border-acento-500 pl-6">
                    <dt class="flex items-baseline gap-3 text-lg font-bold text-marina-950">
                        <span class="font-mono text-sm text-acento-600">
                            <?= str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) ?>
                        </span>
                        <?= e($reto['titulo']) ?>
                    </dt>
                    <dd class="mt-2 leading-relaxed text-slate-600">
                        <?= e($reto['texto']) ?>
                    </dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
</section>
