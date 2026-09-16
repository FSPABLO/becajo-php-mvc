<?php

declare(strict_types=1);

/**
 * Campos del encabezado de una auditoría.
 *
 * Compartido por el alta y la edición: son los mismos cuatro datos, y tenerlos
 * duplicados garantizaría que un día se añada un campo en uno y no en el otro.
 * El formulario que lo envuelve (action, token, botón) lo pone cada vista.
 *
 * @var \App\Core\Vista $vista
 * @var array<string, string> $errores
 * @var array<string, mixed>  $valores
 * @var list<\App\Models\Entidades\Usuario> $administradores
 */
$errores = $errores ?? [];
$valores = $valores ?? [];

/*
 * Al entrevistado se le puede señalar de dos maneras, y las dos conviven:
 *
 *   registrado — una cuenta ADMIN_BD del sistema. Es el caso normal y el que
 *                existía antes; trae el nombre y la empresa ya escritos y
 *                enlaza la auditoría con la persona de verdad.
 *   manual     — nombre y empresa a mano. Un auditor entrevista a gente que no
 *                tiene cuenta aquí ni la va a tener, y obligarlo a registrarla
 *                antes de abrir la auditoría era pedirle que creara un usuario
 *                falso para poder trabajar.
 *
 * Son EXCLUYENTES por radio y no dos campos que se pisan: con un desplegable y
 * un campo de texto rellenos a la vez, «¿a quién se entrevistó?» tiene dos
 * respuestas y quien lea la fila elige una. La base opina lo mismo —
 * ck_auditoria_administrador exige exactamente uno de los dos lados.
 *
 * El cambio de lado es CSS y no JavaScript: `.rv-conmutador` de rivendel.css
 * empareja cada radio con su panel por selector de hermano. Sin guiones la
 * pantalla funciona igual —los radios son radios y el servidor lee
 * `origen_administrador` como siempre—, que es la misma razón por la que el
 * selector del monitor son enlaces dentro de un <details> y no un <select>
 * gobernado por script.
 */
$origen = ($valores['origen'] ?? '') === 'manual' ? 'manual' : 'registrado';

/*
 * Un error en el lado manual arrastra el formulario a ese lado aunque el radio
 * llegue en 'registrado': devolver el intento mostrando el panel equivocado
 * deja el mensaje de error hablando de un campo que no está a la vista.
 */
if (isset($errores['administrador_nombre']) || isset($errores['administrador_organizacion'])) {
    $origen = 'manual';
}

/*
 * Los campos van HUNDIDOS: en el sistema visual el inset significa «aquí se
 * recibe algo», y es lo que separa a simple vista lo que se rellena de lo que
 * solo se lee. El fondo se declara (bg-superficie) porque un <select> sin él
 * se queda con el gris del sistema operativo, que no pertenece a la paleta y
 * rompe la fila con los dos campos vecinos.
 *
 * El acolchado es el CORTO del sistema (px-3 py-2), no el de un formulario que
 * ocupa su propia pantalla: aquí son cuatro datos ya rellenos dentro de un
 * <details> que casi nunca se abre, y con py-2.5 el bloque medía más que la
 * portadilla de la auditoría que tiene encima.
 */
$clases = static fn (bool $mal): string =>
    'rv-hundido mt-1 w-full rounded-rv border bg-superficie px-3 py-2 text-texto outline-none transition '
    . ($mal
        ? 'border-bad focus:border-bad focus:ring-2 focus:ring-bad/10'
        : 'border-borde focus:border-primario focus:ring-2 focus:ring-primario/30');

/*
 * Las empresas que ya están en el sistema, para el <datalist> del campo libre.
 * Salen de $administradores, que la vista ya tiene en la mano: no hay consulta
 * nueva. Es la misma ayuda que el gráfico de evolución del panel — el campo
 * sigue siendo libre, pero escribir una empresa conocida deja de ser un examen
 * de memoria y, sobre todo, deja de producir tres grafías de la misma empresa.
 */
$empresasConocidas = [];

foreach ($administradores as $admin) {
    $empresasConocidas[$admin->organizacion] = true;
}

$empresasConocidas = array_keys($empresasConocidas);
sort($empresasConocidas);

?>
<?php
/*
 * Es un <div role="group"> y no un <fieldset>, para poder poner el rótulo y el
 * conmutador en la MISMA fila. Un <legend> no sirve: el navegador lo dibuja
 * con reglas propias —recorta el borde del fieldset, ignora el flujo normal—
 * y no se le puede pedir de forma fiable que sea la primera celda de un flex.
 * Semánticamente no se pierde nada: <fieldset> se expone justamente como
 * role="group", y aria-labelledby le da el mismo nombre accesible que le daba
 * el <legend>. Es el mismo criterio con el que .rv-conmutador no usa las
 * variantes con nombre de Tailwind — no apoyarse en lo que puede cambiar bajo
 * los pies.
 */
?>
<div class="rv-conmutador" role="group" aria-labelledby="entrevistado-titulo">

    <?php
    /*
     * Los radios van ANTES de todo lo demás y como hermanos suyos: el selector
     * `~` de .rv-conmutador solo alcanza a lo que viene después en el mismo
     * nivel, tanto para los paneles como para las pastillas (que ahora están
     * un nivel más adentro, dentro de la fila del rótulo — de ahí el `~ *` de
     * rivendel.css, que ya contaba con eso). Se ocultan a la vista pero NO al
     * lector de pantalla ni al teclado —son el control de verdad, y las dos
     * pastillas son sus etiquetas—, así que las flechas siguen cambiando de
     * lado.
     */
    ?>
    <input type="radio" name="origen_administrador" value="registrado"
           id="origen-registrado" <?= $origen === 'registrado' ? 'checked' : '' ?>>
    <input type="radio" name="origen_administrador" value="manual"
           id="origen-manual" <?= $origen === 'manual' ? 'checked' : '' ?>>

    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <p id="entrevistado-titulo" class="text-sm font-semibold text-texto">
            <?= e($vista->t('eval.admin_entrevistado_label')) ?>
        </p>
        <div class="rv-hundido flex rounded-[9px] bg-fondo p-[3px]">
            <label for="origen-registrado" data-para="registrado"
                   class="rv-opcion cursor-pointer rounded-[7px] px-3 py-1.5 text-xs font-semibold text-texto-2 transition">
                <?= e($vista->t('eval.origen_registrado')) ?>
            </label>
            <label for="origen-manual" data-para="manual"
                   class="rv-opcion cursor-pointer rounded-[7px] px-3 py-1.5 text-xs font-semibold text-texto-2 transition">
                <?= e($vista->t('eval.origen_manual')) ?>
            </label>
        </div>
    </div>

    <?php
    /*
     * Panel 1 — cuenta registrada.
     *
     * El <select> NO lleva `required`: los dos paneles están en el DOM a la
     * vez y un campo obligatorio dentro del panel oculto bloquea el envío sin
     * poder decir dónde está el problema (el navegador no puede enfocar lo que
     * no se ve). Quien exige el dato es validarEncabezado(), que además sabe
     * cuál de los dos lados mirar.
     */
    ?>
    <div data-panel="registrado" class="mt-3">
        <label for="administrador" class="sr-only">
            <?= e($vista->t('eval.admin_entrevistado_label')) ?>
        </label>
        <select id="administrador" name="administrador"
                class="<?= e($clases(isset($errores['administrador']))) ?>">
            <option value=""><?= e($vista->t('eval.seleccione')) ?></option>
            <?php foreach ($administradores as $admin): ?>
                <option value="<?= e((string) $admin->id) ?>"
                    <?= (string) ($valores['administrador'] ?? '') === (string) $admin->id ? 'selected' : '' ?>>
                    <?= e($admin->nombre) ?> — <?= e($admin->organizacion) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($errores['administrador'])): ?>
            <p class="mt-1 text-[13px] text-bad"><?= e($errores['administrador']) ?></p>
        <?php else: ?>
            <p class="mt-1 text-[13px] text-texto-2">
                <?= e($vista->t('eval.organizacion_registrada')) ?>
            </p>
        <?php endif; ?>
    </div>

    <?php
    /*
     * Panel 2 — a mano. Nombre y empresa son DOS campos y no uno: la empresa
     * es la organización auditada, la que filtra el tablero y el histórico, y
     * partirla de un «Fulano, Empresa S.A.» al guardar es cómo se acaba con
     * tres grafías de la misma empresa en el mismo informe.
     */
    ?>
    <div data-panel="manual" class="mt-3 gap-x-4 gap-y-3 sm:grid-cols-2">
        <div>
            <label for="administrador_nombre" class="block text-sm font-medium text-texto">
                <?= e($vista->t('eval.admin_nombre')) ?>
            </label>
            <input type="text" id="administrador_nombre" name="administrador_nombre"
                   maxlength="150" autocomplete="off"
                   placeholder="<?= e($vista->t('eval.admin_nombre_marcador')) ?>"
                   value="<?= e((string) ($valores['nombre'] ?? '')) ?>"
                   class="<?= e($clases(isset($errores['administrador_nombre']))) ?>">
            <?php if (isset($errores['administrador_nombre'])): ?>
                <p class="mt-1 text-[13px] text-bad"><?= e($errores['administrador_nombre']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="administrador_organizacion" class="block text-sm font-medium text-texto">
                <?= e($vista->t('eval.admin_empresa')) ?>
            </label>
            <input type="text" id="administrador_organizacion" name="administrador_organizacion"
                   maxlength="200" autocomplete="off" list="empresas-conocidas"
                   placeholder="<?= e($vista->t('eval.admin_empresa_marcador')) ?>"
                   value="<?= e((string) ($valores['organizacion'] ?? '')) ?>"
                   class="<?= e($clases(isset($errores['administrador_organizacion']))) ?>">
            <datalist id="empresas-conocidas">
                <?php foreach ($empresasConocidas as $empresaConocida): ?>
                    <option value="<?= e($empresaConocida) ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <?php if (isset($errores['administrador_organizacion'])): ?>
                <p class="mt-1 text-[13px] text-bad"><?= e($errores['administrador_organizacion']) ?></p>
            <?php endif; ?>
        </div>

        <p class="text-[13px] leading-[1.5] text-texto-2 sm:col-span-2">
            <?= e($vista->t('eval.admin_manual_ayuda')) ?>
        </p>
    </div>
</div>

<?php
/*
 * Área y fecha comparten fila. Iban una debajo de otra y a todo el ancho, y en
 * /evaluacion/{id} —que es max-w-7xl— eso son dos campos de más de mil píxeles
 * para escribir «Gobierno» y una fecha.
 *
 * La segunda columna es fija (14rem) en vez de la mitad: una fecha ocupa lo que
 * ocupa, y darle la mitad del ancho disponible no la hace más fácil de teclear
 * — solo deja el resto en blanco. El área se queda con lo que sobre, que es el
 * campo que sí puede crecer. Por debajo de sm se apilan.
 */
?>
<div class="grid gap-x-4 gap-y-3 sm:grid-cols-[minmax(0,1fr)_14rem]">
    <div>
        <label for="area" class="block text-sm font-semibold text-texto"><?= e($vista->t('eval.area_evaluada')) ?></label>
        <input type="text" id="area" name="area" required maxlength="200"
               value="<?= e((string) ($valores['area'] ?? '')) ?>"
               class="<?= e($clases(isset($errores['area']))) ?>">
        <?php if (isset($errores['area'])): ?>
            <p class="mt-1 text-[13px] text-bad"><?= e($errores['area']) ?></p>
        <?php endif; ?>
    </div>

    <div>
        <label for="fecha" class="block text-sm font-semibold text-texto"><?= e($vista->t('eval.fecha_auditoria')) ?></label>
        <input type="date" id="fecha" name="fecha" required
               value="<?= e((string) ($valores['fecha'] ?? date('Y-m-d'))) ?>"
               class="<?= e($clases(isset($errores['fecha']))) ?>">
        <?php if (isset($errores['fecha'])): ?>
            <p class="mt-1 text-[13px] text-bad"><?= e($errores['fecha']) ?></p>
        <?php endif; ?>
    </div>
</div>
