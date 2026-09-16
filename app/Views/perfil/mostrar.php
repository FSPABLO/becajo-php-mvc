<?php

declare(strict_types=1);

/**
 * La ficha del propio usuario.
 *
 * Tres bloques, en el orden en que se preguntan: quién soy (retrato, nombre,
 * id, descripción), cuándo trabajé (calendario del mes) y qué hice (la tabla,
 * de diez en diez).
 *
 * Va en la región del instrumento —`rv-claro rv-oro` sobre `bg-elevado`— como
 * el resto del módulo: es la misma sesión de trabajo y cambiar de color al
 * entrar aquí la haría parecer otro producto.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var \App\Models\Entidades\FotoPerfil|null $foto
 * @var int $limiteFoto             Tope real de subida en bytes, cruzado con php.ini.
 * @var string $mes                 Mes del calendario, YYYY-MM.
 * @var array<int, list<\App\Models\Entidades\Auditoria>> $porDia
 * @var string $mesAnterior
 * @var string $mesSiguiente
 * @var list<\App\Models\Entidades\Auditoria> $auditorias  Todas, para las cifras.
 * @var list<\App\Models\Entidades\Auditoria> $visibles    Las de esta página.
 * @var int $pagina
 * @var int $paginas
 * @var int $porPagina
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var array{aviso: string|null, error: string|null} $mensajes
 */
$errores = $errores ?? [];
$valores = $valores ?? [];

// Del intento fallido si lo hubo, y si no, de lo guardado. Volver del error con
// el campo repoblado desde la base borraría lo que se acaba de escribir.
$descripcion = array_key_exists('descripcion', $valores)
    ? (string) $valores['descripcion']
    : (string) ($usuario->descripcion ?? '');

$limiteMb = number_format($limiteFoto / (1024 * 1024), 1, ',', '');

$finalizadas = count(array_filter(
    $auditorias,
    static fn ($a): bool => $a->estaFinalizada(),
));
?>
<?php
/*
 * La misma región que el resto del módulo de auditorías. Ver la nota larga de
 * `evaluacion/mostrar` sobre por qué `rv-oro` y por qué la franja va a todo el
 * ancho con la caja centrada dentro.
 */
?>
<div class="rv-claro rv-oro bg-elevado">
<section class="mx-auto w-full max-w-7xl px-6 py-8 lg:px-8">

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php
    /*
     * 1. QUIÉN SOY y CUÁNDO TRABAJÉ, en la misma fila.
     *
     * La ficha ocupaba el ancho entero y le sobraba la mitad derecha —el
     * retrato, cuatro líneas de identidad y tres cifras no llenan 80rem—,
     * mientras el calendario estaba abajo empujando la tabla. Puestos en
     * paralelo, la fila de arriba responde de una vez las dos preguntas con las
     * que se entra a la ficha: quién soy y cuándo he estado trabajando.
     *
     * El calendario va a ancho FIJO y la ficha se queda con lo que sobre. Fijo
     * NO quiere decir estrecho: son 28rem, que a siete columnas dejan casillas
     * de unos 55 px — con las 22rem de antes bajaban de 40 y el número, el
     * contador y el día de la semana se apiñaban en algo que había que mirar de
     * cerca. Una cuadrícula tampoco gana por estirarse sin medida, porque a
     * partir de cierto ancho solo separa los días; los 28rem son el punto en el
     * que la casilla se lee de un vistazo y la ficha sigue teniendo sitio de
     * sobra para el campo de la descripción.
     *
     * Las dos tarjetas comparten ALTURA. La rejilla no lleva `items-start`, así
     * que se estiran a la más alta y el calendario reparte lo que le sobra
     * entre sus semanas —ver ahí—. Con `items-start` cada una medía su
     * contenido y bajo el calendario quedaba un hueco muerto de casi doscientos
     * píxeles, que en dos tarjetas contiguas se lee como algo que no cargó.
     *
     * Por debajo de xl se apilan, y ahí el orden vuelve a ser el natural: la
     * identidad primero y el calendario después. Apiladas no hay altura que
     * igualar, y cada una vuelve a medir lo suyo.
     */
    ?>
    <div class="mb-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_28rem]">

    <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie p-6">
        <?php
        /*
         * EL RETRATO OCUPA LAS DOS FILAS, y de ahí sale su tamaño.
         *
         * Antes la ficha eran dos piezas apiladas —retrato e identidad arriba,
         * descripción a lo ancho debajo— y el retrato solo disponía del alto de
         * la fila de arriba. Agrandarlo estiraba la ficha entera, y con ella el
         * calendario de al lado, que le iguala la altura.
         *
         * Con el retrato como columna que cruza las dos filas, quien decide el
         * alto de la ficha vuelve a ser la columna derecha —identidad, cifras y
         * descripción, exactamente lo que la decidía antes— y el retrato se
         * limita a rellenar lo que esa columna deja. Crece sin empujar nada:
         * es el mismo truco que el calendario usa con sus semanas.
         *
         * Y el ANCHO de la columna sube de 11rem a 13rem (208 px). Es la única
         * medida del retrato que queda escrita, porque el alto ya no lo decide
         * él: ensancharlo lo agranda sin mover un píxel la altura de la ficha,
         * que es justo lo que se pedía. Los 32 px salen de la columna derecha,
         * que los tenía de sobra.
         *
         * Lo que cambia respecto de antes: la descripción ya no va a todo el
         * ancho de la ficha, sino al de la columna derecha. Sigue siendo un
         * campo ancho —unos 470 px, unos setenta caracteres por línea—, que era
         * lo que se buscaba al sacarla de la columna estrecha del retrato.
         */
        ?>
        <div class="grid gap-x-6 gap-y-6 sm:grid-cols-[13rem_minmax(0,1fr)]">

            <?php
            /*
             * El retrato y su formulario. El campo de archivo y el botón de
             * quitar comparten <form> porque comparten destino: `quitar_foto`
             * gana sobre el archivo, y el controlador lo resuelve mirando la
             * casilla antes que el adjunto.
             */
            ?>
            <form method="post" action="<?= e($vista->url('perfil/foto')) ?>"
                  enctype="multipart/form-data"
                  class="flex flex-col items-center gap-3 sm:row-span-2">
                <?= $vista->campoToken() ?>

                <?php
                /*
                 * Rectángulo VERTICAL, y su alto NO está escrito: es el que
                 * sobra en la columna después de los controles de abajo. En
                 * pantalla ancha ronda los 380 px —contra los 112 del disco
                 * original— y crece o encoge con la ficha sin que nadie lo
                 * recalcule.
                 *
                 * El envoltorio `flex-1` es lo que lo hace posible: dentro de
                 * esta columna flexible es la única pieza elástica, así que se
                 * queda con todo lo que no usan el botón, la ayuda y la casilla.
                 * `min-h-0` porque un elemento de flex no baja de su contenido
                 * si no se le dice, y el suelo de 14rem es para cuando la ficha
                 * se apila en pantalla angosta y ya no hay sobrante que repartir.
                 *
                 * Las iniciales van en text-5xl: en un hueco de este tamaño las
                 * de 3xl flotaban en el centro de un cartel casi vacío, y no son
                 * un respaldo de emergencia sino el estado normal de casi todas
                 * las cuentas.
                 */
                ?>
                <div class="min-h-0 w-full flex-1">
                    <?= $vista->componente('avatar-usuario', [
                        'vista'   => $vista,
                        'usuario' => $usuario,
                        'foto'    => $foto,
                        'tamano'  => 'h-full min-h-[14rem] w-full',
                        'texto'   => 'text-5xl',
                        'forma'   => 'rounded-rv-lg',
                    ]) ?>
                </div>

                <?php
                /*
                 * El campo va en `.sr-only` dentro de un <label>, igual que la
                 * zona de soltar de la evidencia: pulsar la etiqueta abre el
                 * diálogo porque eso hace un label sobre su campo, y el campo
                 * sigue en el orden de tabulación y en el árbol de
                 * accesibilidad. Sin una línea de guion.
                 */
                ?>
                <label class="rv-extruido-sm rv-interactivo-sm cursor-pointer rounded-rv border border-borde px-3 py-1.5 text-xs font-semibold text-texto transition focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-oro">
                    <input type="file" name="foto" class="sr-only"
                           accept="image/png,image/jpeg,image/webp,image/gif"
                           onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                    <?= e($vista->t($foto === null ? 'perfil.subir_foto' : 'perfil.cambiar_foto')) ?>
                </label>

                <?php
                /*
                 * `onchange` envía en cuanto se elige la imagen, para no cobrar
                 * un segundo clic por algo que ya se decidió. Es una comodidad y
                 * NO un requisito: sin guion queda el botón de abajo, que envía
                 * el mismo formulario a mano. Por eso el botón existe siempre y
                 * no solo cuando falta JavaScript — esconderlo con CSS habría
                 * sido esconder la única salida del que no lo ejecuta.
                 */
                ?>
                <button type="submit" class="text-[11px] font-medium text-primario hover:underline">
                    <?= e($vista->t('perfil.subir_ahora')) ?>
                </button>

                <p class="max-w-[13rem] text-center text-[11px] leading-snug text-texto-2">
                    <?= e($vista->t('perfil.foto_ayuda', $limiteMb)) ?>
                </p>

                <?php if ($foto !== null): ?>
                    <label class="inline-flex cursor-pointer items-center gap-1.5 text-[11px] text-texto-2">
                        <input type="checkbox" name="quitar_foto" value="1"
                               class="h-3.5 w-3.5 rounded border-borde text-primario focus:ring-primario">
                        <?= e($vista->t('perfil.quitar_foto')) ?>
                    </label>
                <?php endif; ?>

                <?php if (isset($errores['foto'])): ?>
                    <p class="max-w-[13rem] text-center text-[11px] text-bad"><?= e($errores['foto']) ?></p>
                <?php endif; ?>
            </form>

            <div class="min-w-0">
                <h1 class="rv-titulo text-3xl font-semibold text-texto"><?= e($usuario->nombre) ?></h1>

                <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-texto-2">
                    <span><?= e($usuario->esAdministrador() ? $vista->t('panel.rol_admin') : $vista->t('panel.rol_auditor')) ?></span>
                    <span aria-hidden="true">·</span>
                    <span class="truncate"><?= e($usuario->organizacion) ?></span>
                </p>

                <p class="mt-1 truncate text-sm text-texto-2"><?= e($usuario->correo) ?></p>

                <?php
                /*
                 * El identificador, en la voz de los códigos: monoespaciada y en
                 * oro. Es una referencia —el número con el que esta cuenta
                 * aparece en la base—, que es exactamente lo que `.rv-id`
                 * significa en el sistema.
                 */
                ?>
                <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-texto-2">
                    <span class="uppercase tracking-wide"><?= e($vista->t('perfil.id')) ?></span>
                    <span class="rv-id"><?= e(str_pad((string) $usuario->id, 4, '0', STR_PAD_LEFT)) ?></span>
                </p>

                <?php
                /*
                 * Cuatro cifras de cartera. Salen de la lista COMPLETA y no de
                 * la página visible: un resumen que encoge al pasar de página no
                 * es un resumen.
                 */
                ?>
                <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 border-t border-borde pt-4">
                    <?php foreach ([
                        [$vista->t('perfil.total_auditorias'), (string) count($auditorias)],
                        [$vista->t('perfil.finalizadas'), (string) $finalizadas],
                        [$vista->t('perfil.en_progreso'), (string) (count($auditorias) - $finalizadas)],
                    ] as [$etiqueta, $cifra]): ?>
                        <span class="min-w-0">
                            <span class="block text-xs text-texto-2"><?= e($etiqueta) ?></span>
                            <span class="tabular block text-xl font-extrabold text-texto"><?= e($cifra) ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            /*
             * La descripción, en su propio formulario y separada del de la foto
             * a propósito: son dos envíos distintos —uno lleva un binario y el
             * otro no— y meterlos juntos obligaría a subir la imagen otra vez
             * cada vez que se corrige una coma.
             */
            ?>
            <form method="post" action="<?= e($vista->url('perfil')) ?>" class="border-t border-borde pt-5">
            <?= $vista->campoToken() ?>

            <label for="descripcion" class="block text-sm font-semibold text-texto">
                <?= e($vista->t('perfil.descripcion')) ?>
            </label>
            <p class="mt-0.5 text-xs text-texto-2"><?= e($vista->t('perfil.descripcion_ayuda')) ?></p>

            <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                      placeholder="<?= e($vista->t('perfil.descripcion_marcador')) ?>"
                      class="rv-hundido mt-2 w-full rounded-rv border bg-superficie px-3 py-2 text-sm leading-relaxed text-texto outline-none transition placeholder:text-texto-2 <?= isset($errores['descripcion'])
                          ? 'border-bad focus:border-bad focus:ring-1 focus:ring-bad'
                          : 'border-borde focus:border-primario focus:ring-1 focus:ring-primario' ?>"><?= e($descripcion) ?></textarea>

            <?php if (isset($errores['descripcion'])): ?>
                <p class="mt-1.5 text-sm text-bad"><?= e($errores['descripcion']) ?></p>
            <?php endif; ?>

            <div class="mt-3 flex justify-end">
                <button type="submit"
                        class="rv-extruido-sm rv-interactivo-sm rounded-rv bg-primario px-4 py-2 text-sm font-semibold text-primario-texto">
                    <?= e($vista->t('perfil.guardar_descripcion')) ?>
                </button>
            </div>
            </form>
        </div>
    </div>

        <?= $vista->componente('calendario-auditorias', [
            'vista'        => $vista,
            'mes'          => $mes,
            'porDia'       => $porDia,
            'mesAnterior'  => $mesAnterior,
            'mesSiguiente' => $mesSiguiente,
            'rutaBaseMes'  => $vista->url('perfil'),
        ]) ?>
    </div>

    <?php
    /*
     * 2. QUÉ HICE, a todo el ancho. Es una tabla de cinco columnas y diez filas
     * y crece bien; estaba en media fila junto al calendario, con el nombre de
     * la organización recortado para caber.
     */
    ?>
    <div class="rv-extruido rounded-rv-lg border border-borde bg-superficie">
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-borde px-5 py-4">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-texto">
                <?= e($vista->t('perfil.mis_auditorias')) ?>
            </h2>
            <?php if ($auditorias !== []): ?>
                <p class="tabular text-xs text-texto-2">
                    <?= e($vista->t(
                        'eval.rango',
                        (string) (($pagina - 1) * $porPagina + 1),
                        (string) min($pagina * $porPagina, count($auditorias)),
                        (string) count($auditorias),
                    )) ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($auditorias === []): ?>
            <p class="px-5 py-12 text-center text-sm text-texto-2">
                <?= e($vista->t('eval.sin_auditorias')) ?>
            </p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[34rem] text-left text-sm">
                    <thead class="border-b border-borde text-xs uppercase tracking-wide text-texto-2">
                        <tr>
                            <th class="px-5 py-3 font-semibold">#</th>
                            <th class="px-3 py-3 font-semibold"><?= e($vista->t('eval.col_organizacion')) ?></th>
                            <th class="px-3 py-3 font-semibold"><?= e($vista->t('eval.col_fecha')) ?></th>
                            <th class="px-5 py-3 font-semibold"><?= e($vista->t('eval.col_estado')) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-borde">
                    <?php foreach ($visibles as $auditoria): ?>
                        <tr>
                            <td class="px-5 py-2.5">
                                <a href="<?= e($vista->url('evaluacion/' . $auditoria->id)) ?>"
                                   class="font-semibold text-primario hover:underline">
                                    <?= e($vista->t('eval.auditoria_n', (string) $auditoria->id)) ?>
                                </a>
                                <span class="block truncate text-xs text-texto-2"><?= e($auditoria->areaEvaluada) ?></span>
                            </td>
                            <td class="px-3 py-2.5 text-texto-2"><?= e($auditoria->organizacion) ?></td>
                            <td class="tabular px-3 py-2.5 text-texto-2"><?= e($auditoria->fecha) ?></td>
                            <td class="px-5 py-2.5">
                                <?= $auditoria->estaFinalizada()
                                    ? pill('ok', $vista->t('eval.finalizada'))
                                    : pill('warn', $vista->t('eval.en_progreso')) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php
            /*
             * Paginación de enlaces, no de guion: cada página tiene su URL.
             * Solo aparece si hay más de una — un paginador de una página
             * es un mando que no hace nada.
             */
            ?>
            <?php if ($paginas > 1): ?>
                <nav class="flex items-center justify-between gap-3 border-t border-borde px-5 py-3 text-sm"
                     aria-label="<?= e($vista->t('eval.paginacion')) ?>">
                    <?php
                    $enlacePagina = static fn (int $n): string =>
                        $vista->url('perfil') . '?mes=' . rawurlencode($mes) . '&pagina=' . $n;
                    ?>
                    <?php if ($pagina > 1): ?>
                        <a href="<?= e($enlacePagina($pagina - 1)) ?>"
                           class="font-semibold text-primario hover:underline">← <?= e($vista->t('eval.anterior')) ?></a>
                    <?php else: ?><span></span><?php endif; ?>

                    <span class="tabular text-xs text-texto-2">
                        <?= e($vista->t('perfil.pagina_de', (string) $pagina, (string) $paginas)) ?>
                    </span>

                    <?php if ($pagina < $paginas): ?>
                        <a href="<?= e($enlacePagina($pagina + 1)) ?>"
                           class="font-semibold text-primario hover:underline"><?= e($vista->t('eval.siguiente')) ?> →</a>
                    <?php else: ?><span></span><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
</div>
