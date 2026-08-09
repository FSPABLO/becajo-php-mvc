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

/* Carrusel de testimonios de la portada. */
(function () {
    'use strict';

    const carrusel = document.querySelector('[data-carrusel-testimonios]');

    if (!carrusel) {
        return;
    }

    const pista = carrusel.querySelector('[data-carrusel-pista]');
    const tarjetas = Array.prototype.slice.call(carrusel.querySelectorAll('[data-carrusel-tarjeta]'));
    const puntos = Array.prototype.slice.call(carrusel.querySelectorAll('[data-carrusel-punto]'));
    const anterior = carrusel.querySelector('[data-carrusel-anterior]');
    const siguiente = carrusel.querySelector('[data-carrusel-siguiente]');

    if (!pista || tarjetas.length === 0) {
        return;
    }

    const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

    function comportamiento() {
        return sinMovimiento.matches ? 'auto' : 'smooth';
    }

    /* Destaca la tarjeta del centro y enciende el punto que le corresponde. */
    function destacar(indice) {
        tarjetas.forEach(function (tarjeta, i) {
            tarjeta.classList.toggle('carrusel-testimonios__tarjeta--activa', i === indice);
        });

        puntos.forEach(function (punto, i) {
            punto.classList.toggle('carrusel-testimonios__punto--activo', i === indice);

            if (i === indice) {
                punto.setAttribute('aria-current', 'true');
            } else {
                punto.removeAttribute('aria-current');
            }
        });
    }

    /*
     * Cuál es la tarjeta del centro no se calcula: se observa. El observador
     * vigila una franja de apenas el 2 % del ancho de la pista, así que la única
     * que la cruza es la que el scroll-snap dejó encajada en el medio.
     *
     * Hacerlo así —y no con una cuenta sobre scrollLeft— es lo que mantiene el
     * destacado correcto cuando se arrastra con el dedo o con la rueda, sin que
     * el guion tenga que enterarse de cada gesto.
     */
    const observador = new IntersectionObserver(function (entradas) {
        entradas.forEach(function (entrada) {
            if (entrada.isIntersecting) {
                destacar(tarjetas.indexOf(entrada.target));
            }
        });
    }, { root: pista, rootMargin: '0px -49% 0px -49%', threshold: 0 });

    tarjetas.forEach(function (tarjeta) {
        observador.observe(tarjeta);
    });

    /*
     * Las flechas desplazan un ancho de tarjeta en lugar de saltar a un índice
     * calculado: así dos pulsaciones seguidas avanzan dos tarjetas aunque el
     * desplazamiento anterior siga en curso. El ancho se mide en cada pulsación
     * porque cambia con el tamaño de la ventana.
     */
    function mover(direccion) {
        pista.scrollBy({
            left: direccion * tarjetas[0].getBoundingClientRect().width,
            behavior: comportamiento()
        });
    }

    if (anterior) {
        anterior.addEventListener('click', function () {
            mover(-1);
        });
    }

    if (siguiente) {
        siguiente.addEventListener('click', function () {
            mover(1);
        });
    }

    /* Deja una tarjeta concreta en el centro de la pista. */
    function centrar(indice, suave) {
        const destino = tarjetas[indice];

        if (!destino) {
            return;
        }

        const areaPista = pista.getBoundingClientRect();
        const areaDestino = destino.getBoundingClientRect();

        pista.scrollBy({
            left: (areaDestino.left - areaPista.left) - (areaPista.width - areaDestino.width) / 2,
            behavior: suave ? comportamiento() : 'auto'
        });
    }

    /* Los puntos sí saltan a una tarjeta concreta. */
    puntos.forEach(function (punto, indice) {
        punto.addEventListener('click', function () {
            centrar(indice, true);
        });
    });

    /* Apaga la flecha del extremo al que ya no se puede avanzar. */
    function actualizarFlechas() {
        const restante = pista.scrollWidth - pista.clientWidth - pista.scrollLeft;

        if (anterior) {
            anterior.disabled = pista.scrollLeft <= 1;
        }

        if (siguiente) {
            siguiente.disabled = restante <= 1;
        }
    }

    pista.addEventListener('scroll', actualizarFlechas, { passive: true });
    window.addEventListener('resize', actualizarFlechas);

    /*
     * Arranca en el testimonio del medio: el tercero de cinco, el quinto de
     * diez. Así el carrusel abre con tanto recorrido a un lado como al otro, y
     * de paso nunca en un extremo: la pista tiene un hueco del ancho de media
     * pantalla a cada lado para que la primera y la última tarjeta también
     * puedan llegar al centro, y el precio es que ahí el costado queda vacío.
     * Empezando en el medio se ve de entrada como el diseño —tres tarjetas, la
     * central destacada— sin salto visible, porque va sin desplazamiento suave
     * y ocurre antes del primer pintado.
     *
     * Con un número par se toma la anterior al medio exacto: de diez, la quinta.
     */
    const inicial = Math.floor((tarjetas.length - 1) / 2);

    destacar(inicial);
    centrar(inicial, false);
    actualizarFlechas();
})();
