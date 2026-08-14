<?php

declare(strict_types=1);

/**
 * Pestaña Referencias: fuentes consultadas, con enlace.
 *
 * @var list<array{titulo: string, fuente: string, enlace: string}> $referencias
 */
?>
<section>
    <h2 class="text-2xl font-extrabold tracking-tight text-texto">Referencias</h2>
    <p class="mt-3 max-w-3xl leading-relaxed text-texto-2">
        Las normas ISO/IEC son documentos de venta. Los enlaces conducen a la ficha oficial
        de cada una; el texto de ISO/IEC 27000 está disponible sin costo en el repositorio
        de normas de acceso público de ISO.
    </p>

    <ul class="rv-extruido mt-8 overflow-hidden rounded-rv-lg border border-borde bg-superficie">
        <?php foreach ($referencias as $referencia): ?>
            <li class="border-b border-borde last:border-b-0">
                <a href="<?= e($referencia['enlace']) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="group flex items-start justify-between gap-4 px-6 py-5 transition hover:bg-elevado">
                    <span>
                        <span class="block font-semibold text-texto group-hover:text-primario">
                            <?= e($referencia['titulo']) ?>
                        </span>
                        <span class="mt-1 block text-sm text-texto-2"><?= e($referencia['fuente']) ?></span>
                    </span>
                    <span class="mt-0.5 shrink-0 text-texto-2 transition group-hover:text-primario">
                        <?= icono('enlace', 'h-4 w-4') ?>
                        <span class="sr-only">Abrir en una pestaña nueva</span>
                    </span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="rv-extruido mt-8 rounded-rv-lg border border-borde bg-superficie p-6">
        <h3 class="font-bold text-texto">Sobre el alcance de este instrumento</h3>
        <p class="mt-2 text-sm leading-relaxed text-texto-2">
            Es una herramienta de diagnóstico interno. Su resultado orienta la priorización
            de mejoras y sirve como preparación para una auditoría, pero no sustituye una
            auditoría de certificación conducida por un organismo acreditado conforme a
            ISO/IEC 27006.
        </p>
    </div>
</section>
