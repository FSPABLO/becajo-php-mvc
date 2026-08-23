/**
 * Rivendel — comportamiento del sitio
 *
 * Se mantiene al mínimo a propósito: el menú móvil y los menús desplegables del
 * encabezado. Todo lo demás (desplazamiento suave, estados hover, relieves) es
 * CSS.
 *
 * No hay conmutador de tema: el producto tiene un solo tono, «Imladris de
 * noche», declarado en :root de assets/css/rivendel.css.
 */

/*
 * Menús desplegables del encabezado: «Nosotros», «Herramientas» y el del
 * perfil. Recorre todo [data-desplegable] y no una lista escrita aquí, así que
 * un menú nuevo en la barra no toca este archivo — el del perfil no lo hizo.
 */
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

/*
 * Barra lateral del módulo interno.
 *
 * Una sola idea —enseñar u ocultar la navegación— con DOS mecanismos, porque en
 * cada ancho significa algo distinto:
 *
 *   >= lg  se pliega. La barra sale por la izquierda y el contenido recupera el
 *          ancho. Es una preferencia de trabajo, así que se recuerda en una
 *          cookie y el servidor devuelve la página ya plegada.
 *   <  lg  es un cajón. Se abre sobre el contenido y se cierra al navegar. No
 *          se recuerda: ahí ocupa la pantalla entera y nadie quiere volver a
 *          encontrárselo abierto.
 *
 * Los dos botones —el de dentro de la barra y el de la barra superior— llaman a
 * lo mismo. Nunca están los dos a la vista: ver .rv-panel-mostrar en
 * rivendel.css.
 */
(function () {
    'use strict';

    const raiz = document.documentElement;
    const lateral = document.querySelector('[data-panel-lateral]');
    const superposicion = document.querySelector('[data-panel-superposicion]');
    const botones = document.querySelectorAll('[data-panel-alternar]');

    if (!lateral || !superposicion || botones.length === 0) {
        return;
    }

    const anchoEscritorio = window.matchMedia('(min-width: 1024px)');

    function recordar(oculta) {
        // Un año, como el idioma: es una preferencia de visualización, no algo
        // que deba caducar con la sesión. Sin httponly a propósito — la escribe
        // este guion, y lo que guarda no tiene ningún valor para un atacante.
        document.cookie = 'becajo_lateral=' + (oculta ? 'oculta' : 'visible')
            + ';path=/;max-age=' + (60 * 60 * 24 * 365) + ';samesite=Lax';
    }

    function estaVisible() {
        return anchoEscritorio.matches
            ? raiz.getAttribute('data-lateral') !== 'oculta'
            : !lateral.classList.contains('-translate-x-full');
    }

    function alternar(mostrar) {
        if (anchoEscritorio.matches) {
            if (mostrar) {
                raiz.removeAttribute('data-lateral');
            } else {
                raiz.setAttribute('data-lateral', 'oculta');
            }

            recordar(!mostrar);
        } else {
            lateral.classList.toggle('-translate-x-full', !mostrar);
            superposicion.classList.toggle('hidden', !mostrar);
        }

        sincronizar();
    }

    function sincronizar() {
        const visible = String(estaVisible());

        botones.forEach(function (boton) {
            boton.setAttribute('aria-expanded', visible);
        });
    }

    botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
            alternar(!estaVisible());
        });
    });

    superposicion.addEventListener('click', function () {
        alternar(false);
    });

    /*
     * Navegar cierra el cajón: sin esto queda abierto sobre la página nueva.
     * Solo en pantalla angosta — en ancha la barra plegada o desplegada es una
     * preferencia, y pulsar un enlace no es motivo para cambiarla.
     */
    lateral.querySelectorAll('a').forEach(function (enlace) {
        enlace.addEventListener('click', function () {
            if (!anchoEscritorio.matches) {
                alternar(false);
            }
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !anchoEscritorio.matches && estaVisible()) {
            alternar(false);
        }
    });

    /*
     * Al ensanchar la ventana hay que retirar el cajón y su superposición, que
     * si no se quedaría tapando la página. Se hace a mano y NO con alternar():
     * en ancho de escritorio esa llamada plegaría la barra y guardaría la
     * cookie, y redimensionar la ventana no es pedir que se pliegue nada.
     */
    anchoEscritorio.addEventListener('change', function () {
        lateral.classList.add('-translate-x-full');
        superposicion.classList.add('hidden');
        sincronizar();
    });

    // El servidor no puede saber en qué ancho se abrió la página: en angosta el
    // cajón nace cerrado aunque la preferencia guardada diga "desplegada".
    sincronizar();
})();

/*
 * Carrusel de testimonios de la portada.
 *
 * Los cinco testimonios ya vienen del servidor dentro del HTML. Este guion no
 * rellena contenido: solo reparte tres papeles —anterior, activo, siguiente— y
 * esconde el resto. Por eso la sección se lee entera aunque el guion falle.
 *
 * El recorrido es CIRCULAR y ahí está el módulo: desde el primero, «anterior»
 * lleva al último; desde el último, «siguiente» vuelve al primero. Sin el
 * módulo habría que apagar las flechas en los extremos, que es justo lo que el
 * diseño no quiere.
 */
(function () {
    'use strict';

    const carrusel = document.querySelector('[data-carrusel-testimonios]');

    if (!carrusel) {
        return;
    }

    const pista = carrusel.querySelector('[data-carrusel-pista]');
    const tarjetas = Array.prototype.slice.call(carrusel.querySelectorAll('[data-carrusel-tarjeta]'));

    if (!pista || tarjetas.length === 0) {
        return;
    }

    const total = tarjetas.length;

    /*
     * Con cuál abre. Lo decide el contenido y lo imprime la vista en
     * data-inicial; leerlo de ahí —y no empezar en 0— es lo que evita que la
     * ficha central salte a otra en cuanto carga el guion.
     */
    const declarado = Number(pista.getAttribute('data-inicial'));
    let indice = Number.isInteger(declarado) && declarado >= 0 && declarado < total
        ? declarado
        : 0;
    // Alterna 'a'/'b' en cada cambio: una animación no se reinicia si se le
    // vuelve a asignar el mismo nombre de fotogramas. Ver rivendel.css.
    let turno = 'b';

    /* El resto de la aritmética del carrusel sale de aquí. */
    function normalizar(i) {
        return ((i % total) + total) % total;
    }

    function pintar(sentido) {
        const anterior  = normalizar(indice - 1);
        const siguiente = normalizar(indice + 1);

        // Sin sentido no hay animación: es el primer pintado, no un cambio.
        if (sentido) {
            turno = turno === 'a' ? 'b' : 'a';
            pista.setAttribute('data-turno', turno);
            pista.setAttribute('data-sentido', sentido);
        }

        tarjetas.forEach(function (tarjeta, i) {
            let posicion = 'oculto';

            if (i === indice) {
                posicion = 'activo';
            } else if (i === anterior) {
                posicion = 'anterior';
            } else if (i === siguiente) {
                posicion = 'siguiente';
            }

            tarjeta.setAttribute('data-posicion', posicion);

            // Con dos testimonios, el anterior y el siguiente son el mismo:
            // gana 'anterior' por el orden de arriba y el hueco derecho queda
            // vacío, que es preferible a repetir la misma ficha dos veces.
            tarjeta.querySelectorAll('[data-carrusel-punto]').forEach(function (punto, p) {
                punto.setAttribute('aria-selected', String(p === indice));
            });
        });
    }

    function ir(destino, sentido) {
        indice = normalizar(destino);
        pintar(sentido);
    }

    carrusel.querySelectorAll('[data-carrusel-anterior]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            ir(indice - 1, 'anterior');
        });
    });

    carrusel.querySelectorAll('[data-carrusel-siguiente]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            ir(indice + 1, 'siguiente');
        });
    });

    /*
     * Pulsar una ficha lateral navega hacia ella. El sentido no está escrito
     * en el HTML: se lee del lado que la ficha ocupa AHORA, porque al girar el
     * carrusel la misma ficha pasa de un lado al otro.
     */
    carrusel.querySelectorAll('[data-carrusel-ir]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const tarjeta = boton.closest('[data-carrusel-tarjeta]');

            if (!tarjeta) {
                return;
            }

            const lado = tarjeta.getAttribute('data-posicion');

            if (lado === 'anterior') {
                ir(indice - 1, 'anterior');
            } else if (lado === 'siguiente') {
                ir(indice + 1, 'siguiente');
            }
        });
    });

    /*
     * Los puntos saltan a un testimonio concreto. El sentido de la animación se
     * decide por el camino más corto en el círculo, para que saltar del primero
     * al último entre por la izquierda y no cruce toda la fila.
     */
    carrusel.querySelectorAll('[data-carrusel-punto]').forEach(function (punto) {
        punto.addEventListener('click', function () {
            const destino = Number(punto.getAttribute('data-indice'));

            if (Number.isNaN(destino) || destino === indice) {
                return;
            }

            const avance = normalizar(destino - indice);
            ir(destino, avance <= total - avance ? 'siguiente' : 'anterior');
        });
    });

    /* Flechas del teclado sobre la pista, que es enfocable. */
    pista.addEventListener('keydown', function (evento) {
        if (evento.key === 'ArrowLeft') {
            evento.preventDefault();
            ir(indice - 1, 'anterior');
        } else if (evento.key === 'ArrowRight') {
            evento.preventDefault();
            ir(indice + 1, 'siguiente');
        }
    });

    // Primer pintado sin sentido: no hay de dónde venir, así que no se anima.
    pintar(null);
})();
