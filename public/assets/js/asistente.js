/**
 * Asistente del módulo — lanzador de la esquina y panel de la derecha.
 *
 * Tres cosas, y ninguna de ellas pinta estilo: el aspecto de cada estado vive en
 * la sección «Asistente» de rivendel.css y el marcado en
 * partials/panel/asistente. Este guion solo pone y quita ATRIBUTOS.
 *
 *   1. Cercanía. El lanzador aparece cuando el cursor se acerca a la esquina.
 *      Se mide la distancia y no se usa :hover sobre una zona invisible grande
 *      porque esa zona taparía lo que haya debajo y se tragaría sus clics.
 *   2. Abrir y cerrar, con el mismo reparto que la barra lateral: en pantalla
 *      ancha es una preferencia que se recuerda en cookie (y el servidor la
 *      devuelve ya aplicada); en angosta es un cajón que no se recuerda.
 *   3. La conversación. Manda el formulario a POST /asistente y SUSTITUYE el
 *      globo de espera por el HTML que devuelve el servidor, que ya trae el
 *      texto de Lembas y las fichas con los datos. El guion no dibuja ninguna
 *      respuesta: lo único que escribe él es el aviso de un fallo de red,
 *      cuando no llegó nada que pintar.
 */
(function () {
    'use strict';

    const raiz = document.documentElement;
    const lanzador = document.querySelector('[data-asistente-lanzador]');
    const panel = document.querySelector('[data-asistente-panel]');

    if (!lanzador || !panel) {
        return;
    }

    const botonAbrir = lanzador.querySelector('[data-asistente-abrir]');
    const botonCerrar = panel.querySelector('[data-asistente-cerrar]');
    const formulario = panel.querySelector('[data-asistente-formulario]');
    const campo = panel.querySelector('[data-asistente-campo]');
    const conversacion = panel.querySelector('[data-asistente-conversacion]');

    if (!botonAbrir || !botonCerrar || !formulario || !campo || !conversacion) {
        return;
    }

    const anchoEscritorio = window.matchMedia('(min-width: 1024px)');
    const sinCursor = window.matchMedia('(hover: none)');

    /* Nace con `hidden`: sin este guion no hay nada que abrir. */
    lanzador.hidden = false;

    /* ── Abrir y cerrar ─────────────────────────────────────────────────── */

    function recordar(abierto) {
        // Un año, como la barra lateral: es una preferencia de trabajo, no algo
        // que deba caducar con la sesión.
        document.cookie = 'becajo_asistente=' + (abierto ? 'abierto' : 'cerrado')
            + ';path=/;max-age=' + (60 * 60 * 24 * 365) + ';samesite=Lax';
    }

    function estaAbierto() {
        return anchoEscritorio.matches
            ? raiz.getAttribute('data-asistente') === 'abierto'
            : raiz.hasAttribute('data-asistente-cajon');
    }

    function sincronizar() {
        const abierto = String(estaAbierto());

        botonAbrir.setAttribute('aria-expanded', abierto);
        botonCerrar.setAttribute('aria-expanded', abierto);
    }

    function alternar(abrir) {
        if (anchoEscritorio.matches) {
            if (abrir) {
                raiz.setAttribute('data-asistente', 'abierto');
            } else {
                raiz.removeAttribute('data-asistente');
            }

            recordar(abrir);
        } else if (abrir) {
            raiz.setAttribute('data-asistente-cajon', '');
        } else {
            raiz.removeAttribute('data-asistente-cajon');
        }

        sincronizar();

        if (abrir) {
            /*
             * El foco va al campo, que es a lo que se viene. En táctil no: abrir
             * el teclado del sistema tapa media pantalla antes de que se haya
             * leído nada. preventScroll porque el panel todavía está entrando
             * por la derecha y el navegador intentaría desplazar hasta él.
             */
            if (!sinCursor.matches) {
                campo.focus({ preventScroll: true });
            }
        } else {
            lanzador.removeAttribute('data-cerca');
            /*
             * El foco vuelve a quien abrió. Si se cerró con el ratón, el
             * navegador no le dibuja el anillo de foco y el lanzador sigue
             * escondido; si fue con el teclado, sí, y aparece — que es justo
             * donde quien tabula espera encontrarse.
             */
            botonAbrir.focus({ preventScroll: true });
        }
    }

    botonAbrir.addEventListener('click', function () {
        alternar(true);
    });

    botonCerrar.addEventListener('click', function () {
        alternar(false);
    });

    /*
     * Escape cierra. En pantalla ancha, solo con el foco DENTRO del panel: la
     * página sigue viva a su lado, y un Escape pulsado para cerrar un
     * desplegable de la página no es pedir que se vaya el asistente.
     */
    document.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Escape' || !estaAbierto()) {
            return;
        }

        if (anchoEscritorio.matches && !panel.contains(document.activeElement)) {
            return;
        }

        alternar(false);
    });

    /*
     * Al cruzar el ancho de lg se retira el cajón, que en escritorio se quedaría
     * cubriendo la página sin hueco. Se hace a mano y NO con alternar(): esa
     * llamada escribiría la cookie, y redimensionar la ventana no es pedir nada.
     */
    anchoEscritorio.addEventListener('change', function () {
        raiz.removeAttribute('data-asistente-cajon');
        sincronizar();
    });

    sincronizar();

    /* ── Cercanía del cursor ────────────────────────────────────────────── */

    /*
     * Dos radios y no uno: aparece a 140 px del centro del botón y no se va
     * hasta pasar de 200. Con un solo umbral, un cursor quieto justo en el borde
     * haría parpadear el anillo con cada píxel de temblor de la mano.
     */
    const RADIO_APARECE = 140;
    const RADIO_DESAPARECE = 200;

    let cursorX = 0;
    let cursorY = 0;
    let medicionPendiente = false;

    function medir() {
        medicionPendiente = false;

        if (estaAbierto()) {
            return;
        }

        const caja = botonAbrir.getBoundingClientRect();

        // Sin caja no hay centro: el lanzador está fuera del dibujo.
        if (caja.width === 0) {
            return;
        }

        const distancia = Math.hypot(
            cursorX - (caja.left + caja.width / 2),
            cursorY - (caja.top + caja.height / 2)
        );
        const cerca = lanzador.hasAttribute('data-cerca');

        if (!cerca && distancia <= RADIO_APARECE) {
            lanzador.setAttribute('data-cerca', '');
        } else if (cerca && distancia > RADIO_DESAPARECE) {
            lanzador.removeAttribute('data-cerca');
        }
    }

    /*
     * Una medición por fotograma como mucho: pointermove llega decenas de veces
     * por segundo y leer la caja en cada uno fuerza cálculos de maquetación que
     * nadie va a ver.
     */
    document.addEventListener('pointermove', function (evento) {
        if (evento.pointerType === 'touch') {
            return;
        }

        cursorX = evento.clientX;
        cursorY = evento.clientY;

        if (!medicionPendiente) {
            medicionPendiente = true;
            window.requestAnimationFrame(medir);
        }
    }, { passive: true });

    /* El cursor salió de la ventana: ya no está cerca de nada. */
    raiz.addEventListener('mouseleave', function () {
        lanzador.removeAttribute('data-cerca');
    });

    /* ── Conversación ───────────────────────────────────────────────────── */

    const plantillaUsuario = panel.querySelector('[data-asistente-plantilla="usuario"]');
    const plantillaPensando = panel.querySelector('[data-asistente-plantilla="pensando"]');
    const plantillaAsistente = panel.querySelector('[data-asistente-plantilla="asistente"]');
    const bienvenida = panel.querySelector('[data-asistente-bienvenida]');
    const botonEnviar = formulario.querySelector('[type="submit"]');
    const botonNueva = panel.querySelector('[data-asistente-nueva]');
    const errorRed = panel.getAttribute('data-asistente-error-red') || '';
    const urlOlvidar = panel.getAttribute('data-asistente-olvidar') || '';

    let enviando = false;

    /* La conversación repintada al cambiar de pantalla: se abre por el final. */
    conversacion.scrollTop = conversacion.scrollHeight;

    /*
     * Clona una <template> dibujada por PHP y la añade al final. El texto, si
     * lo hay, entra con textContent y nunca como HTML: es lo que escribió el
     * usuario. Devuelve el elemento añadido, para poder sustituirlo después.
     */
    function agregar(plantilla, texto) {
        if (!plantilla) {
            return null;
        }

        const fragmento = plantilla.content.cloneNode(true);
        const elemento = fragmento.firstElementChild;
        const destino = fragmento.querySelector('[data-asistente-texto]');

        if (destino && typeof texto === 'string') {
            destino.textContent = texto;
        }

        conversacion.appendChild(fragmento);
        conversacion.scrollTop = conversacion.scrollHeight;

        return elemento;
    }

    /*
     * Sustituye el globo de espera por la respuesta del servidor.
     *
     * insertAdjacentHTML sin miedo, y es el único sitio donde se hace: ese HTML
     * lo dibujó partials/panel/asistente/turno con todo dato pasado por e(), y
     * llega de nuestra propia ruta. El texto del modelo viaja DENTRO de él ya
     * escapado, no suelto.
     *
     * Se desplaza hasta el PRINCIPIO del turno nuevo y no hasta el final del
     * panel: una ficha de resumen es más alta que el panel, y bajar hasta el
     * fondo dejaría a la vista su pie en vez de su título.
     */
    function sustituir(espera, html) {
        if (!espera) {
            conversacion.insertAdjacentHTML('beforeend', html);
            return;
        }

        espera.insertAdjacentHTML('afterend', html);
        const nuevo = espera.nextElementSibling;
        espera.remove();

        if (nuevo) {
            nuevo.scrollIntoView({ block: 'start', behavior: 'auto' });
        }
    }

    function sustituirConError(espera) {
        if (espera) {
            espera.remove();
        }

        agregar(plantillaAsistente, errorRed);
    }

    function ocupado(estado) {
        enviando = estado;
        formulario.setAttribute('aria-busy', String(estado));

        if (botonEnviar) {
            botonEnviar.disabled = estado;
        }
    }

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();

        const texto = campo.value.trim();

        /*
         * Una pregunta a la vez. El campo no se bloquea —se puede ir escribiendo
         * la siguiente—, pero el envío sí: dos respuestas cruzadas dejarían el
         * historial de la sesión en un orden distinto del de la pantalla.
         */
        if (texto === '' || enviando) {
            campo.focus();
            return;
        }

        /* Empezada la conversación, la bienvenida y sus sugerencias sobran. */
        if (bienvenida) {
            bienvenida.hidden = true;
        }

        // El FormData se toma ANTES de vaciar el campo: lleva el token y la ruta.
        const datos = new FormData(formulario);

        agregar(plantillaUsuario, texto);
        campo.value = '';

        const espera = agregar(plantillaPensando);
        ocupado(true);

        fetch(formulario.action, {
            method: 'POST',
            body: datos,
            credentials: 'same-origin',
            headers: { 'X-Becajo-Asincrona': '1' }
        })
            .then(function (respuesta) {
                // Los errores también traen su globo en JSON (401, 429, 502…).
                return respuesta.json().catch(function () { return null; });
            })
            .then(function (json) {
                if (json && typeof json.html === 'string') {
                    sustituir(espera, json.html);
                } else {
                    sustituirConError(espera);
                }
            })
            .catch(function () {
                sustituirConError(espera);
            })
            .finally(function () {
                ocupado(false);
            });
    });

    /*
     * Nueva conversación: olvida el historial en el servidor y vacía el panel.
     * El panel se vacía SOLO si el servidor confirmó: vaciarlo antes haría
     * creer que se olvidó algo que la sesión todavía le va a reenviar al modelo.
     */
    if (botonNueva) {
        botonNueva.addEventListener('click', function () {
            const token = formulario.querySelector('[name="_token"]');

            if (enviando || !token || urlOlvidar === '') {
                return;
            }

            const datos = new FormData();
            datos.append('_token', token.value);

            fetch(urlOlvidar, {
                method: 'POST',
                body: datos,
                credentials: 'same-origin',
                headers: { 'X-Becajo-Asincrona': '1' }
            })
                .then(function (respuesta) {
                    if (!respuesta.ok) {
                        throw new Error('olvidar');
                    }

                    Array.prototype.slice.call(conversacion.children).forEach(function (hijo) {
                        if (hijo !== bienvenida) {
                            hijo.remove();
                        }
                    });

                    if (bienvenida) {
                        bienvenida.hidden = false;
                    }

                    conversacion.scrollTop = 0;
                    campo.focus();
                })
                .catch(function () {
                    agregar(plantillaAsistente, errorRed);
                });
        });
    }

    /*
     * Intro envía y Mayús + Intro parte la línea, que es lo que espera quien
     * viene de cualquier chat. isComposing deja en paz el Intro con el que un
     * teclado de composición (acentos, CJK) confirma un carácter.
     */
    campo.addEventListener('keydown', function (evento) {
        if (evento.key !== 'Enter' || evento.shiftKey || evento.isComposing) {
            return;
        }

        evento.preventDefault();

        if (formulario.requestSubmit) {
            formulario.requestSubmit();
        } else {
            formulario.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });

    /*
     * Una sugerencia RELLENA el campo y no envía: quien la elige suele querer
     * ajustarla («…del dominio de accesos») antes de preguntar.
     */
    panel.querySelectorAll('[data-asistente-sugerencia]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            campo.value = boton.textContent.trim();
            campo.focus();
            campo.setSelectionRange(campo.value.length, campo.value.length);
        });
    });
})();
