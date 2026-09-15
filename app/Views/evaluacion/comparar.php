<?php

declare(strict_types=1);

/**
 * Comparar histórico — la ANTESALA: elegir la empresa.
 *
 * Esta pantalla no compara nada todavía: LISTA. Antes apilaba el histórico
 * completo de cada empresa una debajo de otra, dos gráficos y una tabla por
 * tarjeta, así que para mirar una había que pasar por todas. Ahora cada empresa
 * es una tarjeta que cabe de un vistazo —zona, último índice, hacia dónde va— y
 * el histórico entero vive en /evaluacion/comparar/{empresa}.
 *
 * La distribución —panel de facetas, recuento, orden y rejilla— es
 * components/rejilla-facetas, la misma que la antesala del monitor. Aquí solo
 * queda lo que es de ESTA pantalla: los rótulos de sus grupos y cómo se pinta la
 * ficha de una empresa.
 *
 * @var \App\Core\Vista $vista
 * @var \App\Models\Entidades\Usuario $usuario
 * @var int $total  Empresas de la cartera, sin filtrar.
 * @var list<array<string, mixed>> $empresas  Las que pasan el filtro, ordenadas.
 * @var array<string, array<string, int>> $facetas  Grupo → valor → cuántas.
 * @var array<string, list<string>> $seleccion  Grupo → valores marcados.
 * @var string|null $buscar
 * @var string $orden
 * @var list<string> $ordenes  El primero es el de por defecto.
 * @var array{aviso: string|null, error: string|null} $mensajes
 */

/*
 * Los rótulos viven en la VISTA y los valores en el controlador. Un filtro que
 * viaja en la URL como «VERDE» no puede llamarse «Riesgo bajo» en el enlace: la
 * dirección dejaría de valer al cambiar de idioma.
 */
$titulosGrupo = [
    'zona'      => $vista->t('eval.filtro_zona'),
    'estado'    => $vista->t('eval.filtro_estado'),
    'historico' => $vista->t('eval.filtro_historico'),
    'area'      => $vista->t('eval.area_evaluada'),
];

$etiquetasValor = [
    'zona' => [
        'VERDE'    => $vista->t('eval.zona_baja'),
        'AMARILLO' => $vista->t('eval.zona_media'),
        'ROJO'     => $vista->t('eval.zona_alta'),
        'SIN'      => $vista->t('eval.filtro_sin_indice'),
    ],
    'estado' => [
        'EN_PROGRESO' => $vista->t('eval.filtro_con_progreso'),
        'FINALIZADA'  => $vista->t('eval.filtro_finalizadas'),
    ],
    'historico' => [
        'TENDENCIA' => $vista->t('eval.filtro_con_tendencia'),
        'UNICA'     => $vista->t('eval.filtro_una_sola'),
    ],
];

// 'area' no aparece arriba a propósito: sus valores SON el dato que escribió el
// auditor, y traducirlos sería inventarle un nombre a su área evaluada.
$etiquetaValor = static fn (string $grupo, string $valor): string
    => $etiquetasValor[$grupo][$valor] ?? $valor;

$etiquetasOrden = [
    'reciente'   => $vista->t('eval.orden_empresa_reciente'),
    'auditorias' => $vista->t('eval.orden_empresa_auditorias'),
    'indice'     => $vista->t('eval.orden_empresa_indice'),
    'nombre'     => $vista->t('eval.orden_empresa_nombre'),
];

/*
 * Zona → tono de pill(). Es la misma correspondencia de evaluacion/resultados:
 * la zona de fn_zona tiene UN color en todo el producto, y el color nunca viaja
 * solo —pill() obliga a llevar ícono y etiqueta—.
 */
$tonoZona = ['VERDE' => 'ok', 'AMARILLO' => 'warn', 'ROJO' => 'bad'];

$etiquetaZona = [
    'VERDE'    => $vista->t('eval.zona_baja'),
    'AMARILLO' => $vista->t('eval.zona_media'),
    'ROJO'     => $vista->t('eval.zona_alta'),
];

$cifra = static fn (?float $v): string => $v === null ? '—' : number_format($v, 2, ',', '');

/*
 * LA FICHA ES MINIMALISTA a propósito, y lo que deja fuera lo deja fuera por
 * una razón: la variación, la miniatura de la serie y las áreas evaluadas son
 * lectura de histórico, y el histórico está a un clic. Aquí solo se ELIGE, y
 * para elegir bastan cuatro cosas: de quién es la carpeta, cuánta hay dentro,
 * cuándo se tocó por última vez y en qué estado quedó.
 *
 * El parámetro NO se llama $empresa: ese nombre es el de la consultora —el marco
 * del módulo recibe sus datos y los pinta en el encabezado y el pie—, y
 * reutilizarlo lo dejaría pisado para el resto de la vista.
 */
$dibujarFicha = static function (array $ficha) use ($vista, $cifra, $tonoZona, $etiquetaZona): string {
    $ultima = $ficha['ultima'];
    $indice = $ficha['indice'];
    $zona   = $ficha['zona'];

    ob_start();
    ?>
    <?php
    /*
     * La tarjeta ENTERA es el enlace, y por eso no hay nada más que se pueda
     * pulsar dentro: un enlace dentro de otro no es HTML válido y el navegador
     * lo desarma por su cuenta. Sin un «ver histórico» al pie: lo que anuncia
     * que se pulsa es el relieve de .rv-interactivo, y el nombre ya es el texto
     * del enlace.
     */
    ?>
    <a href="<?= e($vista->url('evaluacion/comparar/' . rawurlencode((string) $ficha['organizacion']))) ?>"
       class="rv-extruido rv-interactivo flex h-full flex-col items-center gap-2 rounded-rv-lg border border-borde bg-superficie px-5 py-6 text-center">

        <?php
        /*
         * El ícono es IDENTIDAD, no estado: va en el acento y es el mismo en las
         * tres zonas. Si se tiñera de verde o de rojo sería un segundo canal de
         * estado sin etiqueta al lado, que es justo lo que pill() existe para
         * evitar.
         */
        ?>
        <?= icono('expediente', 'h-12 w-12 shrink-0 text-primario') ?>

        <h3 class="rv-titulo mt-1 text-base font-semibold leading-tight text-texto">
            <?= e((string) $ficha['organizacion']) ?>
        </h3>

        <p class="text-xs text-texto-2">
            <?= e($vista->t(
                'eval.auditorias_de_organizacion',
                (string) $ficha['total'],
                $ultima->fecha,
            )) ?>
        </p>

        <?php
        /*
         * El pie va anclado abajo (mt-auto) para que el estado quede a la misma
         * altura en toda la fila: con nombres de una y de tres líneas, las
         * pastillas bailaban.
         *
         * La cifra sola sería un número sin nombre, así que lleva su rótulo en
         * sr-only: lo que se ahorra es TINTA, no el dato — quien no ve la ficha
         * oye «último índice 0,70».
         */
        ?>
        <div class="mt-auto flex flex-wrap items-center justify-center gap-2 pt-3">
            <span class="tabular text-sm font-semibold <?= $indice === null ? 'text-na' : 'text-texto' ?>">
                <span class="sr-only"><?= e($vista->t('eval.ultimo_indice')) ?>:</span>
                <?= e($cifra($indice)) ?>
            </span>
            <?= pill(
                $zona === null ? 'na' : ($tonoZona[$zona] ?? 'na'),
                $zona === null
                    ? $vista->t('eval.filtro_sin_indice')
                    : ($etiquetaZona[$zona] ?? $zona),
            ) ?>
        </div>
    </a>
    <?php

    return (string) ob_get_clean();
};
?>
<section class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-8">

    <?php
    /*
     * La pantalla no tiene encabezado VISIBLE: el distintivo de norma y la
     * frase de entrada se retiraron por decisión de diseño. La miga de la barra
     * superior ya dice «Auditorías / Comparar histórico», y qué hacer aquí lo
     * dicen el panel de filtros y las fichas sin necesidad de anunciarlo.
     *
     * El <h1> NO desaparece: se queda en sr-only, igual que en «Mis
     * auditorías». La miga es navegación, no encabezado, y sin el h1 quien no
     * ve el diseño se queda sin saber en qué página entró —y se rompe el salto
     * por encabezados—. No cuesta un píxel.
     */
    ?>
    <h1 class="sr-only"><?= e($vista->t('eval.comparar_historico')) ?></h1>

    <?= $vista->renderizar('partials/mensajes', compact('mensajes')) ?>

    <?php if ($total === 0): ?>
        <?php
        /*
         * Sin cartera no se pinta el panel de filtros: filtrar una lista vacía
         * no lleva a ninguna parte, y cuatro grupos de casillas en cero se leen
         * como un error de carga. Lo que hace falta aquí es la primera
         * auditoría.
         */
        ?>
        <div class="rv-hundido rounded-rv-lg border border-borde bg-superficie px-6 py-16 text-center">
            <p class="font-semibold text-texto"><?= e($vista->t('eval.sin_auditorias_comparar')) ?></p>
            <a href="<?= e($vista->url('evaluacion/nueva')) ?>"
               class="rv-extruido rv-interactivo mt-6 inline-block rounded-rv bg-primario px-4 py-2.5 text-sm font-semibold text-primario-texto">
                <?= e($vista->t('eval.nueva_auditoria')) ?>
            </a>
        </div>
    <?php else: ?>
        <?= $vista->componente('rejilla-facetas', [
            'vista'            => $vista,
            'accion'           => 'evaluacion/comparar',
            'buscar'           => $buscar,
            'etiquetaBusqueda' => $vista->t('eval.buscar_etiqueta'),
            'marcadorBusqueda' => $vista->t('eval.buscar_marcador'),
            'facetas'          => $facetas,
            'seleccion'        => $seleccion,
            'titulosGrupo'     => $titulosGrupo,
            'etiquetaValor'    => $etiquetaValor,
            // Las áreas son el único grupo que crece con la cartera.
            'gruposCerrados'   => ['area'],
            'orden'            => $orden,
            'ordenes'          => $ordenes,
            'etiquetasOrden'   => $etiquetasOrden,
            'recuento'         => $vista->t('eval.empresas_rango', (string) count($empresas), (string) $total),
            'filas'            => $empresas,
            'ficha'            => $dibujarFicha,
            'vacioTitulo'      => $vista->t('eval.sin_empresas_filtro'),
            'vacioTexto'       => $vista->t('eval.sin_empresas_filtro_texto'),
        ]) ?>
    <?php endif; ?>
</section>
