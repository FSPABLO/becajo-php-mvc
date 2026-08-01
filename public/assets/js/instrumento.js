/**
 * Becajo — instrumento de consultoría de bases de datos
 *
 * Todo el estado y todo el cálculo viven aquí, en un solo lugar. La pestaña
 * Instrumento y la pestaña Cuestionario escriben sobre EL MISMO objeto por
 * identificador de control: responder en una mueve la otra y mueve el tablero.
 *
 * Reglas de cálculo (Fase 4 del instrumento):
 *   - Cumplimiento = sí / (sí + no). «No aplica» sale del denominador, igual
 *     que una exclusión justificada en la Declaración de Aplicabilidad
 *     (ISO/IEC 27001, cl. 6.1.3). Los controles sin evaluar no cuentan en
 *     ninguno de los dos lados.
 *   - Denominador en cero: se muestra un guion. Nunca NaN, 0 % ni Infinity.
 *   - Madurez promedio: solo sobre los controles calificados. Un control sin
 *     calificar no vale cero.
 */
(function () {
    'use strict';

    const nodoCatalogo = document.getElementById('catalogo-instrumento');

    if (!nodoCatalogo) {
        return;
    }

    const catalogo = JSON.parse(nodoCatalogo.textContent);

    const CLAVE_ALMACEN = 'becajo.instrumento-bd.v1';
    const AMBITOS = ['instrumento', 'cuestionario'];
    const DIMENSIONES = ['integridad', 'confidencialidad', 'disponibilidad'];

    /*
     * Color del borde izquierdo de cada tarjeta. Estas clases aparecen también
     * en la leyenda que se dibuja en el servidor, de modo que Tailwind las
     * genera aunque aquí se apliquen después.
     */
    const BORDE_ESTADO = {
        si: 'border-l-exito-500',
        no: 'border-l-alerta-500',
        na: 'border-l-marina-300',
        '': 'border-l-slate-200'
    };

    const BORDE_RESPUESTA = {
        si: 'border-l-exito-500',
        parcial: 'border-l-aviso-500',
        no: 'border-l-alerta-500',
        na: 'border-l-marina-300',
        '': 'border-l-slate-200'
    };

    // ── Índices del catálogo ─────────────────────────────────────────────────

    const controlesPorId = {};
    const controlesPorProceso = {};
    const procesosPorNumero = {};
    const dominiosPorClave = {};

    catalogo.controles.forEach(function (control) {
        controlesPorId[control.id] = control;

        if (!controlesPorProceso[control.proceso]) {
            controlesPorProceso[control.proceso] = [];
        }

        controlesPorProceso[control.proceso].push(control);
    });

    catalogo.procesos.forEach(function (proceso) {
        procesosPorNumero[proceso.numero] = proceso;
    });

    catalogo.dominios.forEach(function (dominio) {
        dominiosPorClave[dominio.clave] = dominio;
    });

    const clavesDominio = catalogo.dominios.map(function (dominio) {
        return dominio.clave;
    });

    // ── Estado ───────────────────────────────────────────────────────────────

    function registroVacio() {
        return {
            estado: '',
            madurez: '',
            criterio: '',
            riesgos: [],
            hallazgo: '',
            recomendacion: '',
            respuesta: '',
            entrevistado: '',
            evidenciaAportada: '',
            notas: ''
        };
    }

    function avanceVacio() {
        const controles = {};

        catalogo.controles.forEach(function (control) {
            controles[control.id] = registroVacio();
        });

        return { identificacion: {}, controles: controles };
    }

    let avance = avanceVacio();

    /**
     * Fusiona lo leído con un avance vacío.
     *
     * Un archivo guardado con una versión anterior del catálogo puede traer
     * controles que ya no existen o carecer de campos nuevos. Se toma solo lo
     * que corresponde a un control vigente y se completa el resto.
     */
    function adoptar(datos) {
        const nuevo = avanceVacio();

        if (!datos || typeof datos !== 'object') {
            return nuevo;
        }

        if (datos.identificacion && typeof datos.identificacion === 'object') {
            nuevo.identificacion = datos.identificacion;
        }

        if (datos.controles && typeof datos.controles === 'object') {
            Object.keys(nuevo.controles).forEach(function (id) {
                const leido = datos.controles[id];

                if (!leido || typeof leido !== 'object') {
                    return;
                }

                Object.keys(nuevo.controles[id]).forEach(function (campo) {
                    if (leido[campo] === undefined || leido[campo] === null) {
                        return;
                    }

                    nuevo.controles[id][campo] = campo === 'riesgos'
                        ? (Array.isArray(leido.riesgos) ? leido.riesgos : [])
                        : String(leido[campo]);
                });
            });
        }

        return nuevo;
    }

    const avisoGuardado = document.querySelector('[data-estado-guardado]');
    let temporizadorGuardado = null;

    function guardar() {
        temporizadorGuardado = null;

        try {
            window.localStorage.setItem(CLAVE_ALMACEN, JSON.stringify(avance));
            anunciar('Avance guardado en este navegador a las ' + horaCorta() + '.');
        } catch (error) {
            anunciar('No fue posible guardar en este navegador. Use «Guardar avance» para no perder la captura.');
        }
    }

    /** Agrupa las pulsaciones seguidas en una sola escritura. */
    function guardarDiferido() {
        window.clearTimeout(temporizadorGuardado);
        temporizadorGuardado = window.setTimeout(guardar, 400);
    }

    /**
     * Escribe de inmediato lo que aún estuviera en espera.
     *
     * Sin esto, cerrar la pestaña o recargar dentro de los 400 ms siguientes a
     * la última tecla se lleva esa captura. En una entrevista de dos horas ese
     * detalle es la diferencia entre un descuido y volver a empezar.
     */
    function guardarPendiente() {
        if (temporizadorGuardado !== null) {
            window.clearTimeout(temporizadorGuardado);
            guardar();
        }
    }

    window.addEventListener('beforeunload', guardarPendiente);

    // En móvil, cambiar de aplicación no siempre dispara beforeunload.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            guardarPendiente();
        }
    });

    function anunciar(mensaje) {
        if (avisoGuardado) {
            avisoGuardado.textContent = mensaje;
        }
    }

    function horaCorta() {
        const ahora = new Date();

        return String(ahora.getHours()).padStart(2, '0') + ':'
             + String(ahora.getMinutes()).padStart(2, '0');
    }

    function recuperar() {
        try {
            const crudo = window.localStorage.getItem(CLAVE_ALMACEN);

            if (crudo) {
                avance = adoptar(JSON.parse(crudo));
                anunciar('Se recuperó el avance guardado en este navegador.');
            }
        } catch (error) {
            avance = avanceVacio();
        }
    }

    // ── Pestañas ─────────────────────────────────────────────────────────────

    const pestanas = Array.prototype.slice.call(document.querySelectorAll('[data-pestana]'));

    function activarPestana(clave, moverFoco) {
        pestanas.forEach(function (pestana) {
            const activa = pestana.dataset.pestana === clave;

            pestana.setAttribute('aria-selected', String(activa));
            pestana.tabIndex = activa ? 0 : -1;
            pestana.classList.toggle('bg-acento-500', activa);
            pestana.classList.toggle('text-marina-950', activa);
            pestana.classList.toggle('text-slate-600', !activa);
            pestana.classList.toggle('hover:bg-slate-100', !activa);
            pestana.classList.toggle('hover:text-marina-950', !activa);

            if (activa && moverFoco) {
                pestana.focus();
            }
        });

        document.querySelectorAll('[data-panel]').forEach(function (panel) {
            panel.hidden = panel.dataset.panel !== clave;
        });
    }

    pestanas.forEach(function (pestana, indice) {
        pestana.addEventListener('click', function () {
            activarPestana(pestana.dataset.pestana, false);
        });

        pestana.addEventListener('keydown', function (evento) {
            const saltos = { ArrowRight: 1, ArrowLeft: -1 };
            let destino = null;

            if (saltos[evento.key] !== undefined) {
                destino = (indice + saltos[evento.key] + pestanas.length) % pestanas.length;
            } else if (evento.key === 'Home') {
                destino = 0;
            } else if (evento.key === 'End') {
                destino = pestanas.length - 1;
            }

            if (destino !== null) {
                evento.preventDefault();
                activarPestana(pestanas[destino].dataset.pestana, true);
            }
        });
    });

    // ── Dominio activo, búsqueda y filtros ───────────────────────────────────

    const dominioActivo = {};

    AMBITOS.forEach(function (ambito) {
        dominioActivo[ambito] = clavesDominio[0];
    });

    function panelDe(ambito) {
        return document.querySelector('[data-panel="' + ambito + '"]');
    }

    /** Muestra la sección del dominio activo y esconde las otras seis. */
    function mostrarDominio(ambito) {
        const panel = panelDe(ambito);

        if (!panel) {
            return;
        }

        panel.querySelectorAll('[data-seccion-dominio]').forEach(function (seccion) {
            seccion.hidden = seccion.dataset.seccionDominio !== dominioActivo[ambito];
        });
    }

    function pintarTabsDominio(ambito, moverFoco) {
        const panel = panelDe(ambito);

        if (!panel) {
            return;
        }

        panel.querySelectorAll('[data-tab-dominio]').forEach(function (tab) {
            const activo = tab.dataset.tabDominio === dominioActivo[ambito];

            tab.setAttribute('aria-selected', String(activo));
            tab.tabIndex = activo ? 0 : -1;
            tab.classList.toggle('border-acento-500', activo);
            tab.classList.toggle('text-marina-950', activo);
            tab.classList.toggle('border-transparent', !activo);
            tab.classList.toggle('text-slate-500', !activo);
            tab.classList.toggle('hover:border-slate-300', !activo);
            tab.classList.toggle('hover:text-marina-950', !activo);

            if (activo && moverFoco) {
                tab.focus();
            }
        });

        const anterior = panel.querySelector('[data-nav="anterior"]');
        const siguiente = panel.querySelector('[data-nav="siguiente"]');
        const indice = clavesDominio.indexOf(dominioActivo[ambito]);

        pintarNavegacion(anterior, clavesDominio[indice - 1], 'Dominio anterior');
        pintarNavegacion(siguiente, clavesDominio[indice + 1], 'Dominio siguiente');
    }

    /** Cambia de dominio dentro de un ámbito y deja todo consistente. */
    function activarDominio(ambito, clave, moverFoco) {
        if (clave === undefined || clave === dominioActivo[ambito]) {
            return;
        }

        dominioActivo[ambito] = clave;
        pintarTabsDominio(ambito, moverFoco);
        mostrarDominio(ambito);
    }

    function pintarNavegacion(boton, clave, textoPorOmision) {
        if (!boton) {
            return;
        }

        const etiqueta = boton.querySelector('[data-nav-etiqueta]');

        boton.disabled = clave === undefined;
        etiqueta.textContent = clave === undefined ? textoPorOmision : dominiosPorClave[clave].nombre;
    }

    function pintarAvances() {
        AMBITOS.forEach(function (ambito) {
            const panel = panelDe(ambito);

            if (!panel) {
                return;
            }

            const conteo = {};

            clavesDominio.forEach(function (clave) {
                conteo[clave] = { hechos: 0, total: 0 };
            });

            catalogo.procesos.forEach(function (proceso) {
                (controlesPorProceso[proceso.numero] || []).forEach(function (control) {
                    const registro = avance.controles[control.id] || registroVacio();
                    const valor = ambito === 'instrumento' ? registro.estado : registro.respuesta;

                    conteo[proceso.dominio].total += 1;

                    if (valor !== '') {
                        conteo[proceso.dominio].hechos += 1;
                    }
                });
            });

            panel.querySelectorAll('[data-avance-dominio]').forEach(function (nodo) {
                const datos = conteo[nodo.dataset.avanceDominio];
                const completo = datos.total > 0 && datos.hechos === datos.total;

                nodo.textContent = datos.hechos + '/' + datos.total;
                nodo.classList.toggle('bg-exito-500', completo);
                nodo.classList.toggle('text-white', completo);
                nodo.classList.toggle('bg-slate-100', !completo);
                nodo.classList.toggle('text-slate-500', !completo);
            });
        });
    }

    // ── Captura ──────────────────────────────────────────────────────────────

    function registro(id) {
        if (!avance.controles[id]) {
            avance.controles[id] = registroVacio();
        }

        return avance.controles[id];
    }

    document.addEventListener('change', function (evento) {
        const campo = evento.target.dataset ? evento.target.dataset.campo : null;
        const id = evento.target.dataset ? evento.target.dataset.control : null;

        if (evento.target.dataset && evento.target.dataset.identificacion) {
            avance.identificacion[evento.target.dataset.identificacion] = evento.target.value;
            guardarDiferido();
            return;
        }

        if (!campo || !id) {
            return;
        }

        if (campo === 'riesgo') {
            const riesgos = registro(id).riesgos;
            const posicion = riesgos.indexOf(evento.target.value);

            if (evento.target.checked && posicion === -1) {
                riesgos.push(evento.target.value);
            } else if (!evento.target.checked && posicion !== -1) {
                riesgos.splice(posicion, 1);
            }
        } else {
            registro(id)[campo] = evento.target.value;
        }

        guardarDiferido();
        refrescar();
    });

    document.addEventListener('input', function (evento) {
        const campo = evento.target.dataset ? evento.target.dataset.campo : null;
        const id = evento.target.dataset ? evento.target.dataset.control : null;

        if (evento.target.dataset && evento.target.dataset.identificacion) {
            avance.identificacion[evento.target.dataset.identificacion] = evento.target.value;
            guardarDiferido();
            return;
        }

        // Solo texto libre. Casillas, radios y listas se atienden en 'change'.
        const esTextoLibre = evento.target.tagName === 'TEXTAREA'
            || (evento.target.tagName === 'INPUT' && evento.target.type === 'text');

        if (!campo || !id || !esTextoLibre) {
            return;
        }

        registro(id)[campo] = evento.target.value;
        guardarDiferido();

        // El hallazgo es la justificación exigida a un «no aplica».
        if (campo === 'hallazgo') {
            pintarTarjeta(id);
        }
    });

    AMBITOS.forEach(function (ambito) {
        const panel = panelDe(ambito);

        if (!panel) {
            return;
        }

        const tabs = Array.prototype.slice.call(panel.querySelectorAll('[data-tab-dominio]'));

        tabs.forEach(function (tab, indice) {
            tab.addEventListener('click', function () {
                activarDominio(ambito, tab.dataset.tabDominio, false);
            });

            tab.addEventListener('keydown', function (evento) {
                const saltos = { ArrowRight: 1, ArrowLeft: -1 };
                let destino = null;

                if (saltos[evento.key] !== undefined) {
                    destino = (indice + saltos[evento.key] + tabs.length) % tabs.length;
                } else if (evento.key === 'Home') {
                    destino = 0;
                } else if (evento.key === 'End') {
                    destino = tabs.length - 1;
                }

                if (destino !== null) {
                    evento.preventDefault();
                    activarDominio(ambito, tabs[destino].dataset.tabDominio, true);
                }
            });
        });

        panel.querySelectorAll('[data-nav]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                const indice = clavesDominio.indexOf(dominioActivo[ambito]);
                const destino = boton.dataset.nav === 'anterior' ? indice - 1 : indice + 1;

                activarDominio(ambito, clavesDominio[destino], false);
                panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    });

    // ── Pintado de las tarjetas ──────────────────────────────────────────────

    function pintarTarjeta(id) {
        const datos = avance.controles[id] || registroVacio();

        const tarjetaControl = document.querySelector('[data-panel="instrumento"] [data-tarjeta="' + id + '"]');

        if (tarjetaControl) {
            tarjetaControl.dataset.estado = datos.estado;
            aplicarBorde(tarjetaControl, BORDE_ESTADO, datos.estado);

            const aviso = tarjetaControl.querySelector('[data-aviso-na]');
            aviso.hidden = !(datos.estado === 'na' && datos.hallazgo.trim() === '');
        }

        const tarjetaPregunta = document.querySelector('[data-panel="cuestionario"] [data-tarjeta="' + id + '"]');

        if (tarjetaPregunta) {
            tarjetaPregunta.dataset.respuesta = datos.respuesta;
            aplicarBorde(tarjetaPregunta, BORDE_RESPUESTA, datos.respuesta);
        }
    }

    function aplicarBorde(tarjeta, mapa, valor) {
        Object.keys(mapa).forEach(function (clave) {
            tarjeta.classList.remove(mapa[clave]);
        });

        tarjeta.classList.add(mapa[valor] !== undefined ? mapa[valor] : mapa['']);
    }

    /** Vuelca el estado sobre los campos del formulario. */
    function pintarFormularios() {
        document.querySelectorAll('[data-identificacion]').forEach(function (campo) {
            campo.value = avance.identificacion[campo.dataset.identificacion] || '';
        });

        document.querySelectorAll('[data-control][data-campo]').forEach(function (campo) {
            const datos = avance.controles[campo.dataset.control];

            if (!datos) {
                return;
            }

            if (campo.dataset.campo === 'riesgo') {
                campo.checked = datos.riesgos.indexOf(campo.value) !== -1;
            } else if (campo.type === 'radio') {
                campo.checked = datos[campo.dataset.campo] === campo.value;
            } else {
                campo.value = datos[campo.dataset.campo] || '';
            }
        });
    }

    // ── Cálculo y tablero ────────────────────────────────────────────────────

    function resumirControles(controles) {
        const resumen = {
            total: controles.length,
            si: 0,
            no: 0,
            na: 0,
            sin: 0,
            cumplimiento: null,
            madurez: null,
            riesgos: { integridad: 0, confidencialidad: 0, disponibilidad: 0 }
        };

        let sumaMadurez = 0;
        let calificados = 0;

        controles.forEach(function (control) {
            const datos = avance.controles[control.id] || registroVacio();

            if (datos.estado === 'si' || datos.estado === 'no' || datos.estado === 'na') {
                resumen[datos.estado] += 1;
            } else {
                resumen.sin += 1;
            }

            if (datos.madurez !== '') {
                sumaMadurez += Number(datos.madurez);
                calificados += 1;
            }

            DIMENSIONES.forEach(function (dimension) {
                if (datos.riesgos.indexOf(dimension) !== -1) {
                    resumen.riesgos[dimension] += 1;
                }
            });
        });

        const denominador = resumen.si + resumen.no;

        // Denominador en cero: no hay cumplimiento que reportar, hay un guion.
        resumen.cumplimiento = denominador === 0 ? null : resumen.si / denominador;
        resumen.madurez = calificados === 0 ? null : sumaMadurez / calificados;

        return resumen;
    }

    function porcentaje(proporcion) {
        return proporcion === null ? '—' : Math.round(proporcion * 100) + ' %';
    }

    function decimal(valor) {
        return valor === null ? '—' : (Math.round(valor * 10) / 10).toFixed(1);
    }

    function nombreNivel(promedio) {
        if (promedio === null) {
            return '—';
        }

        const nivel = catalogo.escala[Math.floor(promedio)];

        return nivel ? nivel.nivel + ' — ' + nivel.nombre : '—';
    }

    function pintarFila(fila, resumen) {
        fila.querySelector('[data-celda="si"]').textContent = resumen.si;
        fila.querySelector('[data-celda="no"]').textContent = resumen.no;
        fila.querySelector('[data-celda="na"]').textContent = resumen.na;
        fila.querySelector('[data-celda="cumplimiento"]').textContent = porcentaje(resumen.cumplimiento);
        fila.querySelector('[data-celda="barra"]').style.width =
            (resumen.cumplimiento === null ? 0 : Math.round(resumen.cumplimiento * 100)) + '%';
        fila.querySelector('[data-celda="madurez"]').textContent = decimal(resumen.madurez);
        fila.querySelector('[data-celda="nivel"]').textContent = nombreNivel(resumen.madurez);

        DIMENSIONES.forEach(function (dimension) {
            fila.querySelector('[data-celda="' + dimension + '"]').textContent = resumen.total === 0
                ? '—'
                : Math.round((resumen.riesgos[dimension] / resumen.total) * 100) + ' %';
        });
    }

    function pintarTablero() {
        catalogo.procesos.forEach(function (proceso) {
            const fila = document.querySelector('[data-fila-proceso="' + proceso.numero + '"]');

            if (fila) {
                pintarFila(fila, resumirControles(controlesPorProceso[proceso.numero] || []));
            }
        });

        const global = resumirControles(catalogo.controles);
        const filaTotal = document.querySelector('[data-fila-total]');

        if (filaTotal) {
            pintarFila(filaTotal, global);
        }

        const evaluados = global.si + global.no + global.na;

        asignar('[data-resumen="evaluados"]', evaluados + ' / ' + global.total);
        asignar('[data-resumen="cumplimiento"]', porcentaje(global.cumplimiento));
        asignar('[data-resumen="madurez"]', decimal(global.madurez));
        asignar('[data-resumen="brechas"]', String(global.no));
        asignar('[data-resumen="pendientes"]', String(global.sin));

        ['si', 'no', 'na', 'sin'].forEach(function (clave) {
            const segmento = document.querySelector('[data-segmento="' + clave + '"]');
            const conteo = document.querySelector('[data-conteo="' + clave + '"]');

            if (segmento) {
                segmento.style.width = (global.total === 0 ? 0 : (global[clave] / global.total) * 100) + '%';
            }

            if (conteo) {
                conteo.textContent = String(global[clave]);
            }
        });
    }

    function asignar(selector, texto) {
        const nodo = document.querySelector(selector);

        if (nodo) {
            nodo.textContent = texto;
        }
    }

    /** Un solo punto de repintado: nada queda desincronizado. */
    function refrescar() {
        catalogo.controles.forEach(function (control) {
            pintarTarjeta(control.id);
        });

        pintarAvances();
        pintarTablero();

        AMBITOS.forEach(function (ambito) {
            pintarTabsDominio(ambito, false);
            mostrarDominio(ambito);
        });
    }

    // ── Acciones ─────────────────────────────────────────────────────────────

    function nombreArchivo(extension) {
        const organizacion = (avance.identificacion.organizacion || 'instrumento-bd')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'instrumento-bd';

        const hoy = new Date().toISOString().slice(0, 10);

        return organizacion + '-' + hoy + '.' + extension;
    }

    function descargar(contenido, nombre, tipo) {
        const enlace = document.createElement('a');
        const url = URL.createObjectURL(new Blob([contenido], { type: tipo }));

        enlace.href = url;
        enlace.download = nombre;
        document.body.appendChild(enlace);
        enlace.click();
        document.body.removeChild(enlace);
        URL.revokeObjectURL(url);
    }

    const ETIQUETA_ESTADO = { si: 'Sí', no: 'No', na: 'No aplica', '': 'Sin evaluar' };
    const ETIQUETA_RESPUESTA = {
        si: 'Sí', parcial: 'Parcial', no: 'No', na: 'No aplica', '': 'Sin responder'
    };

    function celda(valor) {
        return '"' + String(valor === undefined || valor === null ? '' : valor)
            .replace(/\r\n|\r|\n/g, ' ')
            .replace(/"/g, '""') + '"';
    }

    /**
     * Exporta los 75 controles.
     *
     * Delimitador «;» y marca de orden de bytes al inicio: sin las dos cosas,
     * Excel en español abre el archivo en una sola columna y con las tildes
     * rotas.
     */
    function exportarCsv() {
        const encabezados = [
            'Dominio', 'Proceso', 'Nombre del proceso', 'Control', 'Referencia ISO',
            'Enunciado del control', 'Estado', 'Madurez', 'Nivel alcanzado', 'Criterio',
            'Riesgo integridad', 'Riesgo confidencialidad', 'Riesgo disponibilidad',
            'Hallazgo', 'Recomendación', 'Pregunta de auditoría', 'Respuesta',
            'Persona entrevistada', 'Evidencia aportada', 'Notas'
        ];

        const filas = [encabezados];

        catalogo.controles.forEach(function (control) {
            const datos = avance.controles[control.id] || registroVacio();
            const proceso = procesosPorNumero[control.proceso];
            const madurez = datos.madurez === '' ? '' : Number(datos.madurez);

            filas.push([
                dominiosPorClave[proceso.dominio].nombre,
                proceso.numero,
                proceso.nombre,
                control.id,
                control.iso,
                control.enunciado,
                ETIQUETA_ESTADO[datos.estado],
                madurez,
                madurez === '' ? '' : nombreNivel(madurez),
                datos.criterio,
                datos.riesgos.indexOf('integridad') !== -1 ? 'Sí' : 'No',
                datos.riesgos.indexOf('confidencialidad') !== -1 ? 'Sí' : 'No',
                datos.riesgos.indexOf('disponibilidad') !== -1 ? 'Sí' : 'No',
                datos.hallazgo,
                datos.recomendacion,
                control.pregunta,
                ETIQUETA_RESPUESTA[datos.respuesta],
                datos.entrevistado,
                datos.evidenciaAportada,
                datos.notas
            ]);
        });

        const csv = filas.map(function (fila) {
            return fila.map(celda).join(';');
        }).join('\r\n');

        // ﻿ es la marca de orden de bytes que Excel necesita para las tildes.
        descargar('﻿' + csv, nombreArchivo('csv'), 'text/csv;charset=utf-8;');
    }

    function exportarJson() {
        descargar(
            JSON.stringify(avance, null, 2),
            nombreArchivo('json'),
            'application/json;charset=utf-8;'
        );
    }

    function importarJson(archivo) {
        const lector = new FileReader();

        lector.onload = function () {
            try {
                avance = adoptar(JSON.parse(String(lector.result)));
                pintarFormularios();
                refrescar();
                guardar();
                anunciar('Avance cargado desde «' + archivo.name + '».');
            } catch (error) {
                window.alert('El archivo no tiene el formato esperado del instrumento.');
            }
        };

        lector.readAsText(archivo);
    }

    /**
     * Datos de demostración.
     *
     * Se generan con una secuencia pseudoaleatoria de semilla fija: el ejemplo
     * es siempre el mismo, y por eso los números del tablero se pueden
     * comprobar a mano. El proceso 24 queda íntegro en «no aplica» a propósito:
     * es el caso límite que debe mostrar un guion, no un cero por ciento.
     */
    function cargarEjemplo() {
        let semilla = 20260801;

        function siguiente() {
            // Lehmer (MINSTD): los productos caben en un entero exacto de IEEE 754.
            semilla = (semilla * 16807) % 2147483647;

            return semilla / 2147483647;
        }

        avance = avanceVacio();

        avance.identificacion = {
            organizacion: 'Institución financiera regional',
            base: 'CORE / PRODCORE1',
            motor: 'Oracle Database 19c',
            consultor: 'Equipo Becajo',
            fecha: new Date().toISOString().slice(0, 10)
        };

        catalogo.controles.forEach(function (control) {
            const datos = registroVacio();

            if (control.proceso === 24) {
                datos.estado = 'na';
                datos.respuesta = 'na';
                datos.hallazgo = 'La organización opera sus bases de datos en infraestructura propia. '
                    + 'No existen servicios gestionados ni alojados en la nube, por lo que el proceso '
                    + 'queda excluido del alcance.';
                datos.criterio = 'evidencia';
            } else {
                const sorteo = siguiente();

                if (sorteo < 0.55) {
                    datos.estado = 'si';
                    datos.respuesta = siguiente() < 0.8 ? 'si' : 'parcial';
                    datos.madurez = String(2 + Math.floor(siguiente() * 4));
                    datos.criterio = 'documentado';
                    datos.hallazgo = 'Se verificó el control con la evidencia solicitada.';
                    datos.recomendacion = 'Mantener la práctica y conservar la evidencia del periodo.';
                } else if (sorteo < 0.82) {
                    datos.estado = 'no';
                    datos.respuesta = 'no';
                    datos.madurez = String(Math.floor(siguiente() * 3));
                    datos.criterio = 'repetible';
                    datos.hallazgo = 'La práctica existe de forma informal, sin documento ni registro que la sustente.';
                    datos.recomendacion = 'Documentar el procedimiento y designar responsable con fecha compromiso.';
                } else if (sorteo < 0.9) {
                    datos.estado = 'na';
                    datos.respuesta = 'na';
                    datos.hallazgo = 'El componente evaluado no está presente en la arquitectura actual.';
                }

                if (datos.estado !== '') {
                    datos.entrevistado = 'Administrador de bases de datos';
                    datos.evidenciaAportada = 'Documento y consulta ejecutada en sesión';
                }
            }

            DIMENSIONES.forEach(function (dimension) {
                if (siguiente() < 0.55) {
                    datos.riesgos.push(dimension);
                }
            });

            avance.controles[control.id] = datos;
        });

        pintarFormularios();
        refrescar();
        guardar();
        anunciar('Se cargaron datos de ejemplo. Use «Limpiar todo» para retirarlos.');
    }

    function limpiar() {
        const confirmado = window.confirm(
            'Se borrarán todas las respuestas capturadas, incluidos hallazgos y recomendaciones. '
            + 'Esta acción no se puede deshacer.\n\n¿Desea continuar?'
        );

        if (!confirmado) {
            return;
        }

        avance = avanceVacio();
        pintarFormularios();
        refrescar();
        guardar();
        anunciar('Se limpió el instrumento.');
    }

    const acciones = {
        'exportar-csv': exportarCsv,
        'exportar-json': exportarJson,
        'importar-json': function () {
            document.querySelector('[data-archivo-avance]').click();
        },
        ejemplo: cargarEjemplo,
        imprimir: function () {
            window.print();
        },
        limpiar: limpiar
    };

    document.querySelectorAll('[data-accion]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const accion = acciones[boton.dataset.accion];

            if (accion) {
                accion();
            }
        });
    });

    const selectorArchivo = document.querySelector('[data-archivo-avance]');

    if (selectorArchivo) {
        selectorArchivo.addEventListener('change', function () {
            if (selectorArchivo.files && selectorArchivo.files[0]) {
                importarJson(selectorArchivo.files[0]);
            }

            selectorArchivo.value = '';
        });
    }

    // ── Arranque ─────────────────────────────────────────────────────────────

    recuperar();
    pintarFormularios();
    refrescar();
})();
