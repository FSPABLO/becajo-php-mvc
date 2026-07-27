/**
 * Becajo — comportamiento del sitio
 *
 * Se mantiene al mínimo a propósito: solo el menú móvil. Todo lo demás
 * (desplazamiento suave, estados hover) se resuelve con CSS.
 */
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
