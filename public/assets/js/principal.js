/**
 * Becajo — comportamiento del sitio
 *
 * Se mantiene al mínimo a propósito: el menú móvil y los menús desplegables del
 * encabezado. Todo lo demás (desplazamiento suave, estados hover) es CSS.
 */

/* Menús desplegables del encabezado (Herramientas). */
(function () {
    'use strict';

    const desplegables = document.querySelectorAll('[data-desplegable]');

    if (desplegables.length === 0) {
        return;
    }

    const abiertos = [];

    desplegables.forEach(function (contenedor) {
        const boton = contenedor.querySelector('[data-desplegable-boton]');
        const panel = contenedor.querySelector('[data-desplegable-panel]');
        const flecha = contenedor.querySelector('[data-desplegable-flecha]');

        if (!boton || !panel) {
            return;
        }

        function alternar(abrir) {
            panel.classList.toggle('hidden', !abrir);
            boton.setAttribute('aria-expanded', String(abrir));

            if (flecha) {
                flecha.classList.toggle('rotate-180', abrir);
            }
        }

        function estaAbierto() {
            return !panel.classList.contains('hidden');
        }

        abiertos.push({ contenedor: contenedor, boton: boton, alternar: alternar, estaAbierto: estaAbierto });

        boton.addEventListener('click', function () {
            alternar(!estaAbierto());
        });

        // Cerrar al salir del menú con el tabulador.
        contenedor.addEventListener('focusout', function (evento) {
            if (!contenedor.contains(evento.relatedTarget)) {
                alternar(false);
            }
        });

        panel.querySelectorAll('a').forEach(function (enlace) {
            enlace.addEventListener('click', function () {
                alternar(false);
            });
        });
    });

    document.addEventListener('click', function (evento) {
        abiertos.forEach(function (item) {
            if (item.estaAbierto() && !item.contenedor.contains(evento.target)) {
                item.alternar(false);
            }
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape') {
            return;
        }

        abiertos.forEach(function (item) {
            if (item.estaAbierto()) {
                item.alternar(false);
                item.boton.focus();
            }
        });
    });
})();

/* Menú de navegación en pantallas angostas. */
(function () {
    'use strict';

    const boton = document.getElementById('boton-menu');
    const menu = document.getElementById('menu-movil');

    if (!boton || !menu) {
        return;
    }

    function alternarMenu(forzarCerrado) {
        const estaOculto = menu.classList.contains('hidden');
        const abrir = forzarCerrado === true ? false : estaOculto;

        menu.classList.toggle('hidden', !abrir);
        boton.setAttribute('aria-expanded', String(abrir));
        boton.setAttribute(
            'aria-label',
            abrir ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'
        );
    }

    boton.addEventListener('click', function () {
        alternarMenu();
    });

    menu.querySelectorAll('a').forEach(function (enlace) {
        enlace.addEventListener('click', function () {
            alternarMenu(true);
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !menu.classList.contains('hidden')) {
            alternarMenu(true);
            boton.focus();
        }
    });

    window.matchMedia('(min-width: 768px)').addEventListener('change', function (evento) {
        if (evento.matches) {
            alternarMenu(true);
        }
    });
})();

/* Selector de idioma del encabezado. */
(function () {
    'use strict';

    document.querySelectorAll('[data-selector-idioma]').forEach(function (selector) {
        selector.addEventListener('change', function () {
            const destino = window.location.pathname + window.location.search;
            const url = selector.getAttribute('data-ruta-idioma')
                + '?codigo=' + encodeURIComponent(selector.value)
                + '&destino=' + encodeURIComponent(destino);

            window.location.href = url;
        });
    });
})();
