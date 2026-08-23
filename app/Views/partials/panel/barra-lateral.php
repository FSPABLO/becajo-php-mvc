<?php

declare(strict_types=1);

/**
 * Barra lateral del módulo interno.
 *
 * Fija a la izquierda desde lg; por debajo se sale de pantalla y vuelve como
 * cajón sobre una superposición, con el botón de la barra superior.
 *
 * El menú llega ya armado y con el elemento activo resuelto desde
 * layouts/panel.php: aquí solo se pinta. El activo se marca con aria-current
 * ADEMÁS del color y del indicador vertical — el color nunca es el único canal,
 * tampoco en la navegación.
 *
 * @var \App\Core\Vista      $vista
 * @var array<string, mixed> $empresa
 * @var list<array{titulo: string, elementos: list<array{etiqueta: string, ruta: string, icono: string}>}> $grupos
 * @var string               $rutaActiva  Ruta del elemento activo, o '' si ninguno.
 * @var \App\Models\Entidades\Usuario|null $usuarioActual
 * @var bool|null            $lateralOculta
 */
$grupos        = $grupos ?? [];
$rutaActiva    = $rutaActiva ?? '';
$usuarioActual = $usuarioActual ?? null;
$lateralOculta = $lateralOculta ?? false;

// Iniciales del avatar. La regla vive en funciones.php porque el encabezado
// público pinta el mismo retrato en su botón de perfil.
$iniciales = $usuarioActual !== null ? iniciales($usuarioActual->nombre) : '';
?>
<?php
/*
 * Cierra el cajón al tocar fuera. Solo existe en pantallas angostas.
 *
 * El z-index va por encima de 30 a propósito: el instrumento de evaluación
 * tiene su propia franja de pestañas pegajosa en z-30 y, al venir después en
 * el documento, ganaría el empate y quedaría flotando sobre la superposición.
 */
?>
<div id="panel-superposicion"
     data-panel-superposicion
     class="fixed inset-0 z-[45] hidden bg-fondo/80 backdrop-blur-sm lg:hidden"
     aria-hidden="true"></div>

<?php
/*
 * .rv-oscuro devuelve la paleta de la noche dentro del cuerpo en pergamino del
 * módulo: la barra es oscura sobre lienzo claro. Sin esa clase heredaría el
 * pergamino y la separación entre navegación y trabajo desaparecería.
 */
?>
<aside id="panel-lateral"
       data-panel-lateral
       class="rv-oscuro rv-panel-lateral fixed inset-y-0 left-0 z-50 flex w-[264px] -translate-x-full flex-col border-r border-borde bg-superficie transition-transform duration-300 ease-out lg:translate-x-0"
       aria-label="<?= e($vista->t('panel.navegacion')) ?>">

    <div class="flex flex-none items-center gap-2 px-5 py-5">
        <?php
        /*
         * La marca vuelve al sitio público. Es el único enlace de salida que se
         * repite abajo: arriba porque el logotipo siempre lleva a la portada, y
         * abajo con su etiqueta para quien no da por hecho esa convención.
         */
        ?>
        <a href="<?= e($vista->url()) ?>" class="flex min-w-0 flex-1 items-center gap-[11px]">
            <img src="<?= e($vista->recurso('assets/images/branding/rivendel-logo-oscuro.png')) ?>"
                 alt="" class="h-9 w-auto flex-none">
            <span class="rv-marca truncate text-[15px] text-texto"><?= e($empresa['nombre']) ?></span>
        </a>

        <?php
        /*
         * Pliega la barra. En pantalla ancha la manda fuera y ensancha el
         * contenido; en el cajón de pantalla angosta simplemente lo cierra, que
         * es lo mismo que pide el gesto. Un solo botón para las dos cosas
         * porque para quien lo pulsa son la misma: quitar la navegación de en
         * medio. Quién la devuelve es el gemelo de la barra superior.
         */
        ?>
        <button type="button"
                data-panel-alternar
                class="rv-lateral-enlace -mr-1 flex-none rounded-rv p-2 text-texto-2"
                aria-expanded="<?= $lateralOculta ? 'false' : 'true' ?>"
                aria-controls="panel-lateral"
                aria-label="<?= e($vista->t('panel.ocultar_navegacion')) ?>"
                title="<?= e($vista->t('panel.ocultar_navegacion')) ?>">
            <?= icono('plegar', 'h-[18px] w-[18px]') ?>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 pb-4">
        <?php foreach ($grupos as $grupo): ?>
            <div>
                <p class="px-3 pb-2 font-mono text-[10.5px] uppercase tracking-[0.12em] text-texto-2">
                    <?= e($grupo['titulo']) ?>
                </p>

                <ul class="space-y-0.5">
                    <?php foreach ($grupo['elementos'] as $elemento): ?>
                        <?php $activo = $elemento['ruta'] === $rutaActiva; ?>
                        <li>
                            <a href="<?= e($vista->url($elemento['ruta'])) ?>"
                               <?= $activo ? 'aria-current="page"' : '' ?>
                               class="rv-lateral-enlace relative flex items-center gap-3 rounded-rv py-2.5 pl-4 pr-3 text-[13.5px] font-medium <?= $activo
                                   ? 'bg-primario/10 text-texto'
                                   : 'text-texto-2' ?>">
                                <?php if ($activo): ?>
                                    <?php /* Segundo canal del estado activo: no solo el tinte de fondo. */ ?>
                                    <span class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-full bg-primario" aria-hidden="true"></span>
                                <?php endif; ?>
                                <?= icono($elemento['icono'], 'h-[17px] w-[17px] shrink-0 ' . ($activo ? 'text-primario' : '')) ?>
                                <span><?= e($elemento['etiqueta']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="flex-none space-y-1 border-t border-borde px-3 py-3">
        <?php if ($usuarioActual !== null): ?>
            <?php
            /*
             * Ficha de sesión. El rol se imprime porque decide qué se ve: quien
             * no encuentra el catálogo en el menú necesita poder comprobar de un
             * vistazo que entró como auditor y no como administrador.
             */
            ?>
            <div class="mb-2 flex items-center gap-3 rounded-rv px-2 py-2">
                <span class="rv-extruido grid h-9 w-9 flex-none place-items-center rounded-full bg-elevado text-[12.5px] font-semibold text-texto"
                      aria-hidden="true"><?= e($iniciales) ?></span>
                <span class="min-w-0">
                    <span class="block truncate text-[13px] font-semibold text-texto"><?= e($usuarioActual->nombre) ?></span>
                    <span class="block truncate text-[11.5px] text-texto-2">
                        <?= e($usuarioActual->esAdministrador() ? $vista->t('panel.rol_admin') : $vista->t('panel.rol_auditor')) ?>
                    </span>
                </span>
            </div>

            <a href="<?= e($vista->url()) ?>"
               class="rv-lateral-enlace flex items-center gap-3 rounded-rv px-4 py-2.5 text-[13.5px] font-medium text-texto-2">
                <?= icono('enlace', 'h-[17px] w-[17px] shrink-0') ?>
                <span><?= e($vista->t('panel.volver_sitio')) ?></span>
            </a>

            <form method="post" action="<?= e($vista->url('salir')) ?>">
                <?= $vista->campoToken() ?>
                <button type="submit"
                        class="rv-lateral-enlace flex w-full items-center gap-3 rounded-rv px-4 py-2.5 text-left text-[13.5px] font-medium text-texto-2">
                    <?= icono('flecha', 'h-[17px] w-[17px] shrink-0') ?>
                    <span><?= e($vista->t('nav.salir')) ?></span>
                </button>
            </form>
        <?php else: ?>
            <a href="<?= e($vista->url('ingresar')) ?>"
               class="rv-extruido rv-interactivo block rounded-rv bg-texto px-4 py-2.5 text-center text-[13px] font-medium text-fondo">
                <?= e($vista->t('nav.ingresar')) ?>
            </a>
        <?php endif; ?>
    </div>
</aside>
