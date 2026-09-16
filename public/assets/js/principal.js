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

/*
 * Zona de soltar archivos (el adjunto de la evidencia).
 *
 * Añade UNA cosa que el HTML no sabe hacer: arrastrar un archivo hasta un
 * elemento y que acabe dentro del <input type="file">. Todo lo demás —abrir el
 * diálogo al pulsar, el foco, el orden de tabulación— ya lo da el <label> que
 * envuelve al campo, y por eso sin este guion la zona sigue funcionando entera
 * salvo por el arrastre. Ver la cabecera de components/campo-archivo-evidencia.
 *
 * Va por DELEGACIÓN en el documento y no con un oyente por zona: en
 * /evaluacion/{id} hay 75 tarjetas y cada una se SUSTITUYE entera al guardarse
 * (mostrar.php la reemplaza por el HTML que devuelve el servidor). Con oyentes
 * propios, la primera tarjeta guardada se quedaría sin poder recibir archivos y
 * nadie se enteraría hasta intentarlo.
 */
(function () {
    'use strict';

    /*
     * DataTransfer es lo que permite meter el archivo soltado DENTRO del campo,
     * que es lo que hace que viaje en el formulario como si se hubiera elegido a
     * mano — sin él habría que mandarlo por separado y el envío sin guion
     * dejaría de coincidir con el envío con guion. Los navegadores que no lo
     * traen se quedan con la zona como botón, que sigue sirviendo.
     */
    const puedeAsignar = typeof DataTransfer === 'function';

    function zonaDe(evento) {
        return evento.target instanceof Element ? evento.target.closest('[data-soltar]') : null;
    }

    function pintarNombre(zona) {
        const campo = zona.querySelector('[data-soltar-campo]');
        const destino = zona.querySelector('[data-soltar-nombre]');
        const archivo = campo && campo.files ? campo.files[0] : null;

        if (!destino) {
            return;
        }

        destino.textContent = archivo ? archivo.name : '';

        /* Un solo atributo, y el intercambio de textos lo hace el CSS: así no
           hay forma de dejar visibles a la vez la invitación y el nombre. */
        if (archivo) {
            zona.setAttribute('data-elegido', '');
        } else {
            zona.removeAttribute('data-elegido');
        }
    }

    /*
     * dragover hay que interceptarlo SIEMPRE, aunque no se pinte nada: el
     * comportamiento por omisión del navegador es rechazar la soltada, así que
     * sin preventDefault el evento 'drop' no llega nunca.
     */
    document.addEventListener('dragover', function (evento) {
        const zona = zonaDe(evento);

        if (!zona) {
            return;
        }

        evento.preventDefault();
        zona.setAttribute('data-soltando', '');
    });

    /*
     * dragleave salta también al pasar de un hijo a otro DENTRO de la zona, y
     * apagar el resalte ahí lo dejaría parpadeando mientras se recorre. Se
     * comprueba a dónde fue el puntero: si sigue dentro, no ha salido.
     */
    document.addEventListener('dragleave', function (evento) {
        const zona = zonaDe(evento);

        if (!zona) {
            return;
        }

        const hacia = evento.relatedTarget;

        if (!(hacia instanceof Node) || !zona.contains(hacia)) {
            zona.removeAttribute('data-soltando');
        }
    });

    document.addEventListener('drop', function (evento) {
        const zona = zonaDe(evento);

        if (!zona) {
            return;
        }

        evento.preventDefault();
        zona.removeAttribute('data-soltando');

        const campo = zona.querySelector('[data-soltar-campo]');
        const sueltos = evento.dataTransfer ? evento.dataTransfer.files : null;

        if (!campo || !puedeAsignar || !sueltos || sueltos.length === 0) {
            return;
        }

        /* Solo el primero: el campo no es múltiple y la tabla guarda un adjunto
           por control. Coger el primero y callar es preferible a guardar uno de
           varios sin decir cuál. */
        const paquete = new DataTransfer();
        paquete.items.add(sueltos[0]);
        campo.files = paquete.files;

        pintarNombre(zona);
    });

    /* Y el camino normal: elegirlo desde el diálogo del sistema. */
    document.addEventListener('change', function (evento) {
        const zona = zonaDe(evento);

        if (zona) {
            pintarNombre(zona);
        }
    });

    /*
     * Soltar un archivo FUERA de una zona hace que el navegador lo abra y se
     * lleve la página por delante, con el formulario a medio llenar dentro. Se
     * corta en el documento, que es donde acaban los que no acertaron.
     */
    document.addEventListener('dragover', function (evento) {
        if (!zonaDe(evento)) {
            evento.preventDefault();
            evento.dataTransfer.dropEffect = 'none';
        }
    });

    document.addEventListener('drop', function (evento) {
        if (!zonaDe(evento)) {
            evento.preventDefault();
        }
    });
})();

/*
 * Buscador de la empresa auditada, en la cabecera del tablero.
 *
 * Encima de un campo de texto que YA funciona sin él: el formulario es un GET
 * normal, el <datalist> del navegador ya sugiere, y el controlador acepta el
 * nombre a medias. Lo que añade este guion es ver las coincidencias mientras se
 * escribe, recorrerlas con las flechas y enviar al elegir.
 *
 * La lista sale del <datalist> que ya está en el HTML. No hay una segunda copia
 * de los nombres ni una consulta por tecla al servidor: son las empresas que
 * este auditor auditó, no un catálogo, y ya viajaron con la página.
 *
 * Al arrancar QUITA el atributo `list`. Con los dos activos, el desplegable
 * nativo y este se dibujan uno encima del otro sobre el mismo campo y el
 * resultado es ilegible; el datalist se queda para quien no ejecuta el guion,
 * que es exactamente su trabajo.
 */
(function () {
    'use strict';

    const caja = document.querySelector('[data-buscador-empresa]');

    if (!caja) {
        return;
    }

    const campo = caja.querySelector('[data-buscador-campo]');
    const lista = caja.querySelector('[data-buscador-lista]');
    const fuente = campo ? document.getElementById(campo.getAttribute('list') || '') : null;

    if (!campo || !lista || !fuente) {
        return;
    }

    const empresas = Array.from(fuente.querySelectorAll('option'))
        .map(function (opcion) { return opcion.value; })
        .filter(function (valor) { return valor !== ''; });

    if (empresas.length === 0) {
        return;
    }

    /* Ya hay buscador propio: fuera el nativo, que si no se dibujan los dos. */
    campo.removeAttribute('list');
    campo.setAttribute('role', 'combobox');
    campo.setAttribute('aria-autocomplete', 'list');
    campo.setAttribute('aria-controls', lista.id);
    campo.setAttribute('aria-expanded', 'false');

    let visibles = [];
    let marcado = -1;

    /*
     * Comparar sin tildes y sin mayúsculas. «cooperativa» tiene que encontrar
     * «Cooperativa de Ejemplo R.L.» y «Ejemplo» también: se busca la subcadena
     * en cualquier posición, no solo al principio — el auditor recuerda antes
     * una palabra del medio que la razón social entera.
     *
     * El rango \u0300-\u036f son los diacríticos combinados que deja NFD; se
     * escribe así, y no con \p{Diacritic}, porque esa clase necesita el flag
     * unicode y navegadores que aquí no hace falta exigir.
     */
    function normalizar(texto) {
        return texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function cerrar() {
        lista.hidden = true;
        lista.innerHTML = '';
        campo.setAttribute('aria-expanded', 'false');
        campo.removeAttribute('aria-activedescendant');
        visibles = [];
        marcado = -1;
    }

    function marcar(indice) {
        const opciones = lista.querySelectorAll('[role="option"]');

        opciones.forEach(function (opcion, i) {
            const activa = i === indice;

            opcion.setAttribute('aria-selected', String(activa));
            opcion.classList.toggle('bg-primario/10', activa);
            opcion.classList.toggle('text-texto', activa);
        });

        marcado = indice;

        if (indice >= 0 && opciones[indice]) {
            campo.setAttribute('aria-activedescendant', opciones[indice].id);
            /* Que la marcada siga a la vista al recorrer con el teclado. */
            opciones[indice].scrollIntoView({ block: 'nearest' });
        } else {
            campo.removeAttribute('aria-activedescendant');
        }
    }

    function elegir(valor) {
        campo.value = valor;
        cerrar();
        /* Elegir una empresa ES la petición: pedir además que pulse el botón
           sería cobrarle un clic por algo que ya decidió. */
        if (caja.requestSubmit) {
            caja.requestSubmit();
        } else {
            caja.submit();
        }
    }

    function abrir() {
        const escrito = normalizar(campo.value.trim());

        visibles = escrito === ''
            ? empresas.slice()
            : empresas.filter(function (nombre) { return normalizar(nombre).indexOf(escrito) !== -1; });

        lista.innerHTML = '';

        /*
         * Sin coincidencias NO se abre un panel vacío: el campo es libre y el
         * controlador acepta lo escrito de todos modos, así que un desplegable
         * en blanco solo taparía el aviso de «no casó con ninguna».
         */
        if (visibles.length === 0) {
            cerrar();
            return;
        }

        visibles.forEach(function (nombre, i) {
            const opcion = document.createElement('li');

            opcion.id = 'empresa-opcion-' + i;
            opcion.setAttribute('role', 'option');
            opcion.setAttribute('aria-selected', 'false');
            opcion.className = 'cursor-pointer px-3 py-1.5 text-texto-2 hover:bg-primario/10 hover:text-texto';
            opcion.textContent = nombre;

            /* mousedown y no click: el click llega DESPUÉS del blur, y el blur
               ya habría cerrado la lista y borrado la opción bajo el cursor. */
            opcion.addEventListener('mousedown', function (evento) {
                evento.preventDefault();
                elegir(nombre);
            });

            lista.appendChild(opcion);
        });

        lista.hidden = false;
        campo.setAttribute('aria-expanded', 'true');
        marcar(-1);
    }

    campo.addEventListener('input', abrir);
    campo.addEventListener('focus', abrir);

    campo.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            cerrar();
            return;
        }

        if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
            if (lista.hidden) {
                abrir();
                return;
            }

            evento.preventDefault();

            const paso = evento.key === 'ArrowDown' ? 1 : -1;
            const total = visibles.length;

            /* Da la vuelta: desde el último, abajo lleva al primero. */
            marcar((marcado + paso + total + (marcado === -1 && paso === -1 ? 1 : 0)) % total);
            return;
        }

        /*
         * Enter con una opción marcada la elige; sin ninguna marcada NO se
         * intercepta, y el formulario se envía con lo tecleado — que es lo que
         * espera quien escribió el nombre entero y pulsó Intro.
         */
        if (evento.key === 'Enter' && marcado >= 0 && visibles[marcado]) {
            evento.preventDefault();
            elegir(visibles[marcado]);
        }
    });

    campo.addEventListener('blur', cerrar);
})();
