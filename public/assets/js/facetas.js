/*
 * Filtrar mientras se escribe, en las antesalas con panel de facetas
 * (components/rejilla-facetas: /evaluacion/comparar y /monitoreo).
 *
 * Es una MEJORA sobre algo que ya funciona sin JavaScript: el panel es un
 * formulario GET con su botón, y sin guion se aplica pulsándolo. Con guion, cada
 * tecla —y cada casilla— vuelve a preguntar al servidor y sustituye lo que
 * cambió, sin recargar la página ni perder el cursor.
 *
 * EL GUION NO FILTRA. Pide la misma dirección con la cabecera X-Becajo-Asincrona
 * y recibe LA MISMA VISTA ya dibujada por PHP; de ahí saca las cuatro regiones
 * que cambian y las copia. Es la misma decisión que el guardado de un control en
 * evaluacion/mostrar: cómo se cuenta una faceta, cómo se ordena la rejilla y cómo
 * se pinta una ficha son reglas del producto, y una segunda copia en JavaScript
 * es una copia que un día dirá otra cosa. Además, filtrar aquí solo podría mirar
 * lo que ya está en el DOM —la ficha de una empresa no lleva las áreas
 * evaluadas— y dejaría mintiendo los recuentos del panel.
 *
 * Vivía como <script> dentro de evaluacion/comparar y salió a archivo al llegar
 * la segunda pantalla. De paso, la respuesta a cada tecla ya no tiene que
 * acordarse de dejarlo fuera: la vista no lo lleva dentro.
 *
 * El campo de búsqueda queda FUERA de las regiones que se sustituyen: es donde
 * está el cursor, y reemplazarlo con cada tecla lo perdería.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-facetas]');

    if (!form || !window.fetch) {
        return;
    }

    var campo = form.querySelector('[data-facetas-busqueda]');

    /* Las cuatro regiones que el servidor vuelve a dibujar. El recuento va
       aparte: no se sustituye, solo cambia su texto, porque una región viva que
       se reemplaza entera no siempre se anuncia. */
    var REGIONES = [
        '[data-facetas-limpiar]',
        '[data-facetas-grupos]',
        '[data-facetas-orden]',
        '[data-facetas-resultados]'
    ];

    var espera = null;
    var ultima = 0;

    if (campo) {
        campo.addEventListener('input', function () {
            /* Un cuarto de segundo: lo justo para que «cooperativa» sea una
               petición y no once, sin que se note al dejar de teclear. */
            window.clearTimeout(espera);
            espera = window.setTimeout(refrescar, 250);
        });
    }

    form.addEventListener('change', function (evento) {
        /* Una casilla es una decisión tomada; una letra puede ser media palabra,
           así que esta no espera. Va por delegación porque las casillas se
           sustituyen enteras y con ellas se irían sus oyentes. */
        if (evento.target !== campo) {
            refrescar();
        }
    });

    form.addEventListener('submit', function (evento) {
        /* El botón «Aplicar» y la tecla Enter siguen existiendo —son la salida
           sin guion—, pero aquí no hace falta recargar para obedecerlos. */
        evento.preventDefault();
        refrescar();
    });

    function refrescar() {
        window.clearTimeout(espera);

        var consulta = parametros();
        var url = form.action + (consulta ? '?' + consulta : '');
        var mia = ++ultima;

        fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Becajo-Asincrona': '1' }
        }).then(function (respuesta) {
            /* 401 o una redirección = la sesión caducó. Recargar deja que el
               servidor mande al ingreso, en vez de dejar la rejilla muda. */
            if (respuesta.status === 401 || respuesta.redirected) {
                window.location.reload();
                return null;
            }

            return respuesta.json();
        }).then(function (datos) {
            /* Dos teclas seguidas son dos peticiones y no tienen por qué volver
               en orden: la respuesta vieja no puede pisar a la nueva. */
            if (!datos || !datos.html || mia !== ultima) {
                return;
            }

            pintar(datos.html);

            /* replaceState y no pushState: cada tecla dejaría una entrada en el
               historial, y volver atrás sería deshacer el nombre letra a letra.
               La dirección sigue siendo compartible, que es lo que importa. */
            window.history.replaceState(null, '', url);
        }).catch(function () {
            /* Sin red se deja en pantalla lo último que respondió el servidor:
               fingir aquí un filtrado propio sería justo la segunda copia que
               este guion existe para no tener. El formulario sigue siendo un
               GET normal y el botón lo manda entero. */
        });
    }

    /* Se manda el formulario ENTERO —búsqueda, casillas y el orden vigente— y no
       solo lo que se acaba de tocar: el servidor decide con todo a la vista, que
       es lo mismo que hace al pulsar «Aplicar». Los campos vacíos se quedan
       fuera para que la dirección se pueda leer. */
    function parametros() {
        var datos = new URLSearchParams();

        new FormData(form).forEach(function (valor, clave) {
            if (String(valor) !== '') {
                datos.append(clave, valor);
            }
        });

        return datos.toString();
    }

    function pintar(html) {
        var fresco = document.createRange().createContextualFragment(html);
        var abiertos = grupos();
        var foco = document.activeElement;
        var marca = foco && foco.name && foco !== campo ? [foco.name, foco.value] : null;

        REGIONES.forEach(function (selector) {
            var viejo = document.querySelector(selector);
            var nuevo = fresco.querySelector(selector);

            if (viejo && nuevo) {
                viejo.innerHTML = nuevo.innerHTML;
            }
        });

        var recuento = document.querySelector('[data-facetas-recuento]');
        var recuentoNuevo = fresco.querySelector('[data-facetas-recuento]');

        if (recuento && recuentoNuevo) {
            recuento.textContent = recuentoNuevo.textContent;
        }

        restaurar(abiertos);
        devolverFoco(marca);
    }

    function grupos() {
        var abiertos = {};

        form.querySelectorAll('[data-grupo]').forEach(function (grupo) {
            abiertos[grupo.getAttribute('data-grupo')] = grupo.open;
        });

        return abiertos;
    }

    function restaurar(abiertos) {
        /* El servidor decide qué grupos NACEN abiertos, y eso vale para la
           primera carga. Si alguien desplegó un grupo cerrado y después
           escribió una letra, volvérselo a cerrar es deshacerle el gesto. */
        form.querySelectorAll('[data-grupo]').forEach(function (grupo) {
            var estado = abiertos[grupo.getAttribute('data-grupo')];

            if (estado !== undefined) {
                grupo.open = estado;
            }
        });
    }

    function devolverFoco(marca) {
        /* Las casillas se sustituyen enteras, así que quien las recorre con el
           teclado se quedaría sin foco justo al marcar una, y sin saber dónde
           estaba. Se busca por nombre y valor recorriendo, y no con un selector:
           el valor de un grupo de datos es texto libre y puede traer comillas. */
        if (!marca || document.activeElement !== document.body) {
            return;
        }

        var candidatas = form.querySelectorAll('[name="' + marca[0] + '"]');

        for (var i = 0; i < candidatas.length; i++) {
            if (candidatas[i].value === marca[1]) {
                candidatas[i].focus({ preventScroll: true });
                return;
            }
        }
    }
}());
