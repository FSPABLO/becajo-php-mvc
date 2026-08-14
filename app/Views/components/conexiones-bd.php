<?php

declare(strict_types=1);

/**
 * Bases de datos conectadas, en rejilla por motor.
 *
 * Una ficha por motor con su logotipo y cuántas conexiones hay de ese tipo. Es
 * la previsualización del monitoreo continuo: el diagnóstico de salud de cada
 * instancia —latencia, espacio, bloqueos, último respaldo— llegará detrás de
 * estas mismas fichas.
 *
 * El conteo es el largo de la lista de instancias, nunca una cifra escrita al
 * lado: una cifra a mano junto a una lista es una cifra que deja de cuadrar.
 *
 * La rejilla es de dos columnas en todo ancho: la ficha ocupa dos quintos del
 * tablero y con tres columnas los nombres largos («PostgreSQL», «SQL Server»)
 * se cortaban en puntos suspensivos, que es perder el dato para ganar una fila.
 *
 * Los motores se ordenan por número de conexiones, de más a menos. El orden del
 * archivo de configuración es en el que alguien los fue anotando, y no dice
 * nada; el volumen sí, y es lo que se mira primero en un tablero.
 *
 * @var \App\Core\Vista $vista
 * @var list<array<string, mixed>> $conexiones  Filas de config/conexiones.php.
 */
$motores = [];

foreach ($conexiones as $fila) {
    $instancias = $fila['instancias'] ?? [];

    // Un motor registrado sin instancias no es un motor conectado: se calla en
    // vez de pintar una ficha con un cero, que ocuparía sitio sin decir nada.
    if ($instancias === []) {
        continue;
    }

    $fila['total'] = count($instancias);
    $motores[] = $fila;
}

usort($motores, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

$totalConexiones = array_sum(array_column($motores, 'total'));
?>
<?php if ($motores === []): ?>
    <p class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-4 py-8 text-center text-sm text-texto-2">
        <?= e($vista->t('bd.sin_conexiones')) ?>
    </p>
<?php else: ?>
    <ul class="grid grid-cols-2 gap-2.5">
        <?php foreach ($motores as $motor): ?>
            <?php
            /*
             * ¿Alguna de las instancias de este motor es la del propio
             * producto? Esa sí tiene estado comprobable: si esta página se está
             * pintando, la conexión está viva. El resto son del registro y
             * todavía no se sondean, así que no se les inventa un estado.
             */
            $propia = false;

            foreach ($motor['instancias'] as $instancia) {
                $propia = $propia || ($instancia['propia'] ?? false) === true;
            }

            // Los nombres de instancia van al título emergente: son el detalle
            // que se busca al pasar por encima, no algo que quepa en la ficha.
            $detalle = implode(' · ', array_map(
                static fn (array $i): string => (string) ($i['nombre'] ?? ''),
                $motor['instancias'],
            ));
            ?>
            <li class="rv-hundido rounded-rv border border-borde bg-fondo p-3"
                title="<?= e($motor['motor'] . ' — ' . $detalle) ?>">

                <div class="flex items-center gap-2">
                    <?php
                    /*
                     * Caja de alto fijo con la imagen contenida: los seis
                     * logotipos tienen proporciones distintas y sin la caja
                     * cada ficha crecería distinto y la rejilla quedaría
                     * dentada. object-contain los mete sin deformarlos.
                     */
                    ?>
                    <span class="grid h-6 w-6 flex-none place-items-center">
                        <img src="<?= e($vista->recurso((string) $motor['logo'])) ?>"
                             alt=""
                             width="<?= e((string) $motor['ancho']) ?>"
                             height="<?= e((string) $motor['alto']) ?>"
                             loading="lazy"
                             class="max-h-6 w-auto max-w-full object-contain">
                    </span>

                    <span class="min-w-0 flex-1 truncate text-[12.5px] font-semibold text-texto">
                        <?= e((string) $motor['motor']) ?>
                    </span>

                    <?php if ($propia): ?>
                        <?php
                        /*
                         * El punto NO va solo: lleva su etiqueta en el atributo
                         * title y el texto de abajo lo repite. Un punto verde a
                         * secas es color como único canal.
                         */
                        ?>
                        <span class="h-1.5 w-1.5 flex-none rounded-full bg-ok"
                              title="<?= e($vista->t('bd.enlace_activo')) ?>"
                              aria-hidden="true"></span>
                    <?php endif; ?>
                </div>

                <p class="tabular mt-2 text-lg font-semibold leading-none text-texto">
                    <?= e((string) $motor['total']) ?>
                </p>
                <p class="mt-1 text-[11px] text-texto-2">
                    <?= e($vista->t($motor['total'] === 1 ? 'bd.conexion' : 'bd.conexiones')) ?>
                    <?= $propia ? '· ' . e($vista->t('bd.enlace_activo')) : '' ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="mt-3 text-[11px] leading-relaxed text-texto-2">
        <?= e($vista->t('bd.total', (string) $totalConexiones, (string) count($motores))) ?>
        <?= e($vista->t('bd.previsualizacion')) ?>
    </p>
<?php endif; ?>
