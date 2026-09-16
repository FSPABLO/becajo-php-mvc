/**
 * Rivendel — monitor de salud de bases de datos
 *
 * Dos comportamientos y ninguno más:
 *
 *   1. las fichas IP / IM / IA se comportan como pestañas y despliegan la tabla
 *      de procesos del índice elegido;
 *   2. el gráfico de memoria añade una lectura cada treinta segundos.
 *
 * Todo lo demás de la pantalla —el medidor, la tendencia, el semáforo— lo pinta
 * el servidor y se ve igual sin JavaScript. Si este archivo no carga, las tres
 * tablas de procesos quedan visibles una tras otra y el gráfico de memoria se
 * queda quieto en su última lectura: se pierde comodidad, no información.
 */

/*
 * ── Fichas de índice como pestañas ───────────────────────────────────────────
 *
 * Mismo trato que las pestañas del instrumento: role/aria los pone el servidor
 * y aquí solo se alternan, con navegación por flechas, Inicio y Fin. La ficha
 * activa se marca con relieve HUNDIDO y borde de acento, nunca cambiando su
 * color de semáforo: ese color es un dato de la instancia y no puede depender
 * de dónde esté el foco.
 */
(function () {
    'use strict';

    const fichas = Array.prototype.slice.call(
        document.querySelectorAll('[data-pestana-indice]')
    );

    if (fichas.length === 0) {
        return;
    }

    const paneles = Array.prototype.slice.call(
        document.querySelectorAll('[data-panel-indice]')
    );

    function activar(clave, moverFoco) {
        fichas.forEach(function (ficha) {
            const activa = ficha.dataset.pestanaIndice === clave;

            ficha.setAttribute('aria-selected', String(activa));
            ficha.tabIndex = activa ? 0 : -1;

            /*
             * Las cuatro clases se alternan JUNTAS. Dejar rv-extruido puesta
             * bajo rv-hundido haría que el relieve dependiera del orden en que
             * el navegador resuelve las reglas, y la ficha se vería a medio
             * hundir según el día.
             */
            ficha.classList.toggle('rv-hundido', activa);
            ficha.classList.toggle('border-primario', activa);
            ficha.classList.toggle('rv-extruido', !activa);
            ficha.classList.toggle('border-borde', !activa);

            const cheurón = ficha.querySelector('svg:last-of-type');

            if (cheurón) {
                cheurón.classList.toggle('rotate-180', activa);
            }

            if (activa && moverFoco) {
                ficha.focus();
            }
        });

        paneles.forEach(function (panel) {
            panel.hidden = panel.dataset.panelIndice !== clave;
        });
    }

    fichas.forEach(function (ficha, indice) {
        ficha.addEventListener('click', function () {
            activar(ficha.dataset.pestanaIndice, false);
        });

        ficha.addEventListener('keydown', function (evento) {
            const saltos = { ArrowRight: 1, ArrowLeft: -1, ArrowDown: 1, ArrowUp: -1 };
            let destino = null;

            if (saltos[evento.key] !== undefined) {
                destino = (indice + saltos[evento.key] + fichas.length) % fichas.length;
            } else if (evento.key === 'Home') {
                destino = 0;
            } else if (evento.key === 'End') {
                destino = fichas.length - 1;
            }

            if (destino !== null) {
                evento.preventDefault();
                activar(fichas[destino].dataset.pestanaIndice, true);
            }
        });
    });

    // Estado inicial: el que ya trae el marcado del servidor. Se reaplica para
    // que los paneles se oculten aunque la página llegara con todos visibles.
    const inicial = fichas.filter(function (f) {
        return f.getAttribute('aria-selected') === 'true';
    })[0] || fichas[0];

    activar(inicial.dataset.pestanaIndice, false);
}());

/*
 * ── Gráfico de memoria en vivo ───────────────────────────────────────────────
 *
 * MAQUETA: la lectura nueva la inventa este guion, no la trae el agente. Está
 * dicho en la propia tarjeta y no debe dejar de decirse mientras siga siendo
 * así. Cuando exista el extremo real, lo único que cambia es de dónde sale
 * `siguienteLectura()`; el redibujado se queda igual.
 *
 * La serie entra por `data-serie` y la geometría se lee del SVG que ya pintó el
 * servidor, así que el primer fotograma no lo dibuja JavaScript: si el guion
 * falla, queda el gráfico del servidor, correcto y quieto.
 */
(function () {
    'use strict';

    const tarjeta = document.querySelector('[data-memoria]');

    if (!tarjeta) {
        return;
    }

    const svg = tarjeta.querySelector('[data-memoria-svg]');
    const linea = tarjeta.querySelector('[data-memoria-linea]');
    const area = tarjeta.querySelector('[data-memoria-area]');
    const punta = tarjeta.querySelector('[data-memoria-punta]');
    const rotuloActual = tarjeta.querySelector('[data-memoria-actual]');
    const rotuloPct = tarjeta.querySelector('[data-memoria-pct]');

    if (!svg || !linea || !area || !punta) {
        return;
    }

    const total = parseFloat(tarjeta.dataset.total);
    const aceptacion = parseFloat(tarjeta.dataset.aceptacion);
    const peligro = parseFloat(tarjeta.dataset.peligro);
    const cadencia = parseInt(tarjeta.dataset.cadencia, 10);

    /*
     * La geometría la ENTREGA el servidor, que es quien la decidió al pintar el
     * primer fotograma. Recalcularla aquí —o despejarla de los puntos ya
     * dibujados— sería una segunda copia de los mismos números, y en cuanto el
     * componente PHP cambiara el alto del lienzo el guion seguiría dibujando
     * sobre uno que ya no existe.
     */
    const izq = parseFloat(tarjeta.dataset.izq);
    const paso = parseFloat(tarjeta.dataset.paso);
    const base = parseFloat(tarjeta.dataset.base);
    const altoPlot = parseFloat(tarjeta.dataset.altoPlot);

    let serie = tarjeta.dataset.serie.split(',').map(Number).filter(function (v) {
        return !isNaN(v);
    });

    if (serie.length < 2 || !(total > 0) || !(altoPlot > 0) || isNaN(paso)) {
        return;
    }

    const x = function (i) { return izq + paso * i; };
    const y = function (v) { return base - (v / total) * altoPlot; };

    // Mismo formato que el servidor: espacio como separador de millares. Se
    // escribe a mano y no con toLocaleString porque el separador depende de la
    // configuración regional del navegador, y entonces la cifra que refresca el
    // guion dejaría de parecerse a la que pintó PHP.
    function formatoMb(v) {
        return String(Math.round(v)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' MB';
    }

    /*
     * La lectura siguiente. Camina desde la última en pasos pequeños y con una
     * leve tendencia a subir, que es como se comporta la memoria de una base en
     * marcha; se recorta al total porque no puede usarse más de lo asignado.
     */
    function siguienteLectura() {
        const ultima = serie[serie.length - 1];
        const deriva = total * 0.0016;
        const ruido = (Math.random() - 0.42) * total * 0.012;

        return Math.max(0, Math.min(total, ultima + deriva + ruido));
    }

    function redibujar() {
        const puntos = serie.map(function (v, i) {
            return x(i).toFixed(2) + ',' + y(v).toFixed(2);
        });

        linea.setAttribute('points', puntos.join(' '));

        const trazo = puntos.map(function (p) { return 'L ' + p.replace(',', ' '); }).join(' ');

        area.setAttribute(
            'd',
            'M ' + izq.toFixed(2) + ' ' + base.toFixed(2) + ' ' + trazo
                + ' L ' + x(serie.length - 1).toFixed(2) + ' ' + base.toFixed(2) + ' Z'
        );

        const actual = serie[serie.length - 1];

        punta.setAttribute('cx', x(serie.length - 1).toFixed(2));
        punta.setAttribute('cy', y(actual).toFixed(2));

        if (rotuloActual) {
            rotuloActual.textContent = formatoMb(actual);
        }

        if (rotuloPct) {
            rotuloPct.textContent = (actual / total * 100).toFixed(1).replace('.', ',');
        }

        /*
         * El estado NO se reconstruye a mano. Las tres pills —normal, sobre
         * aceptación y sobre peligro— vienen pintadas por el servidor, con su
         * icono y su etiqueta ya traducidos, y aquí solo se enseña la que toca.
         *
         * Fabricar la pill desde JavaScript obligaría a traer el icono y el
         * texto de las tres a mano; en cuanto una se olvidara, el color habría
         * cambiado y el texto no, dejando el color como único canal fiable —
         * exactamente lo que el sistema visual prohíbe.
         */
        const nuevo = actual >= peligro ? 'bad' : (actual >= aceptacion ? 'warn' : 'ok');

        tarjeta.querySelectorAll('[data-memoria-luz]').forEach(function (pill) {
            pill.hidden = pill.dataset.memoriaLuz !== nuevo;
        });

        // La descripción accesible también se actualiza: un aria-label que se
        // queda en la primera lectura es peor que no tenerlo.
        if (svg.dataset.plantillaResumen) {
            svg.setAttribute(
                'aria-label',
                svg.dataset.plantillaResumen
                    .replace('{usado}', formatoMb(actual))
                    .replace('{pct}', (actual / total * 100).toFixed(1).replace('.', ','))
            );
        }
    }

    window.setInterval(function () {
        /*
         * Ventana deslizante: entra una lectura y sale la más antigua, de modo
         * que el gráfico siempre cubre el mismo tramo de tiempo. Sin descartar
         * por la izquierda, los puntos se irían apretando hasta volverse una
         * mancha y el eje dejaría de significar nada.
         */
        serie.push(siguienteLectura());
        serie.shift();

        redibujar();
    }, cadencia * 1000);
}());
