<?php

declare(strict_types=1);

/**
 * Funciones globales disponibles en las vistas.
 *
 * Se mantienen al mínimo a propósito: la lógica vive en clases, no aquí.
 */

if (!function_exists('e')) {
    /**
     * Escapa una cadena antes de imprimirla en HTML.
     *
     * Regla del proyecto: TODO dato que se imprima pasa por aquí. Con contenido
     * fijo es una formalidad; con datos venidos de la base de datos es lo que
     * impide una inyección de HTML o JavaScript (XSS).
     */
    function e(?string $valor): string
    {
        return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /**
     * Lee una variable de entorno con un valor por defecto.
     *
     * La usa config/base_datos.php para que las credenciales vivan en el
     * entorno (docker-compose.yml, panel del hosting) y no en el código.
     * getenv() devuelve false cuando la variable no existe y '' cuando existe
     * pero está vacía: ambos casos se tratan igual, porque una credencial en
     * blanco no es una credencial.
     */
    function env(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = getenv($clave);

        return ($valor === false || $valor === '') ? $porDefecto : $valor;
    }
}

if (!function_exists('icono')) {
    /**
     * Devuelve el SVG de un ícono del catálogo interno.
     *
     * Los íconos van en línea (no como fuente ni imagen externa) para que el
     * sitio funcione sin conexión y sin peticiones adicionales.
     */
    function icono(string $nombre, string $clases = 'h-6 w-6'): string
    {
        static $trazos = [
            'servidor' => '<rect x="3" y="4" width="18" height="6" rx="2"/><rect x="3" y="14" width="18" height="6" rx="2"/><path d="M7 7h.01M7 17h.01"/>',
            'rayo'     => '<path d="M13 2 4.5 13.5H11l-1 8.5 8.5-11.5H12l1-8.5Z"/>',
            'escudo'   => '<path d="M12 3 4 6v6c0 4.5 3.2 8.4 8 9.5 4.8-1.1 8-5 8-9.5V6l-8-3Z"/><path d="m9 12 2 2 4-4"/>',
            'respaldo' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>',
            'grafica'  => '<path d="M3 3v18h18"/><path d="m7 14 3-4 3 3 4-6"/>',
            'usuarios' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
            'flecha'   => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            'check'    => '<path d="m5 12 5 5L20 7"/>',
            'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            // Armazón del módulo: la silueta de la barra lateral y el gesto de
            // plegarla. El doble cheurón dice "hasta el borde", no "uno atrás".
            'lateral'  => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/>',
            'plegar'   => '<path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/>',

            // Catálogo del instrumento de consultoría
            'herramienta' => '<path d="M14.7 6.3a4 4 0 0 1-5 5L5 16v3h3l4.7-4.7a4 4 0 0 0 5-5l-2.4 2.4-2.1-2.1 2.5-2.3Z"/>',
            'chevron'     => '<path d="m6 9 6 6 6-6"/>',
            'buscar'      => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'llave'       => '<circle cx="7.5" cy="15.5" r="3.5"/><path d="m10 13 8-8"/><path d="m15 8 2 2"/><path d="m18 5 2 2"/>',
            'disco'       => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
            'documento'   => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/>',
            'tablero'     => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
            'libro'       => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v15H6.5A2.5 2.5 0 0 0 4 19.5Z"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20v5H6.5A2.5 2.5 0 0 1 4 19.5Z"/>',
            'enlace'      => '<path d="M14 4h6v6"/><path d="M20 4 11 13"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>',
            'descargar'   => '<path d="M12 3v12"/><path d="m7.5 10.5 4.5 4.5 4.5-4.5"/><path d="M4 19h16"/>',
            'importar'    => '<path d="M12 15V3"/><path d="m7.5 7.5 4.5-4.5 4.5 4.5"/><path d="M4 19h16"/>',
            'imprimir'    => '<path d="M7 8V3h10v5"/><path d="M7 18H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2"/><rect x="7" y="14" width="10" height="7" rx="1"/>',
            'basura'      => '<path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/><path d="M9 7V4h6v3"/>',
            'chispa'      => '<path d="M12 3v5M12 16v5M3 12h5M16 12h5"/><path d="m6.5 6.5 3 3M14.5 14.5l3 3M17.5 6.5l-3 3M9.5 14.5l-3 3"/>',
            'pregunta'    => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3 2.4c-.6.2-1 .8-1 1.4v.4"/><path d="M11.5 17h.01"/>',
            'alerta'      => '<path d="M12 4 2.5 20h19L12 4Z"/><path d="M12 10v4"/><path d="M12 17h.01"/>',
            // Monitor de salud. El corazón es la metáfora del módulo —signos
            // vitales de una instancia— y no se repite en ninguna otra
            // entrada del menú, así que no hay dos lecturas del mismo trazo.
            'corazon'     => '<path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2-1.5-1.5-2.7-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z"/>',

            /*
             * Un ícono fijo por cada uno de los 7 dominios del instrumento
             * (ver iconoDominio() más abajo). 'disco', 'llave' y 'respaldo' ya
             * existían arriba y se reutilizan aquí porque ningún otro ícono
             * de esta lista los usa como pestaña principal — así que no hay
             * ambigüedad de leer el mismo trazo con dos significados en la
             * misma pantalla. 'balanza', 'engranaje' y 'candado' son nuevos.
             */
            'balanza'     => '<path d="M12 3v18"/><path d="M6 21h12"/><path d="m12 3-6.5 4.5M12 3l6.5 4.5"/><path d="M2 7.5h7l-3.5 6-3.5-6Z"/><path d="M15 7.5h7l-3.5 6-3.5-6Z"/>',
            'engranaje'   => '<path d="M12 2 20 6.5v11L12 22 4 17.5v-11Z"/><circle cx="12" cy="12" r="3.2"/>',
            'candado'     => '<rect x="4.5" y="11" width="15" height="9.5" rx="2"/><path d="M7.5 11V7.2a4.5 4.5 0 0 1 9 0V11"/>',
            'ojo'         => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',

            /*
             * Escala semántica de estado del sistema visual de Rivendel.
             *
             * Existen porque el color NUNCA puede ser el único canal: todo
             * estado se comunica a la vez por color, ícono y etiqueta de
             * texto, para que se lea igual con daltonismo o impreso en
             * escala de grises. Cada nombre corresponde a una fila de la
             * tabla §4 del sistema visual.
             */
            'circle-check'   => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
            'alert-circle'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5"/><path d="M12 16h.01"/>',
            'alert-triangle' => '<path d="M12 4 2.5 20h19L12 4Z"/><path d="M12 10v4"/><path d="M12 17h.01"/>',
            'alert-octagon'  => '<path d="M8.4 2.5h7.2l5.9 5.9v7.2l-5.9 5.9H8.4l-5.9-5.9V8.4l5.9-5.9Z"/><path d="M12 7.5v5"/><path d="M12 16h.01"/>',
            'minus'          => '<path d="M6 12h12"/>',
            // Aspa de la casilla de resultado del monitor. Es el negativo de
            // 'check', no un octógono ni un triángulo: aquí no se comunica
            // severidad, solo «esta comprobación no pasó».
            'aspa'           => '<path d="m7 7 10 10"/><path d="m17 7-10 10"/>',

        ];

        $trazo = $trazos[$nombre] ?? $trazos['check'];

        // width/height de respaldo: si el CDN de Tailwind no carga a tiempo,
        // el ícono se queda en 24x24 en vez de estirarse a lo bruto.
        return '<svg class="' . e($clases) . '" width="24" height="24" viewBox="0 0 24 24" fill="none" '
             . 'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" '
             . 'stroke-linejoin="round" aria-hidden="true">' . $trazo . '</svg>';
    }
}

if (!function_exists('iconoDominio')) {
    /**
     * Ícono fijo por dominio del instrumento: los 7 dominios son un catálogo
     * cerrado (config/instrumento-bd.php), así que el mapeo vive en un solo
     * lugar en vez de repetirse en cada vista que pinta un dominio (tabs del
     * instrumento, tabla del catálogo, cabecera de cada pestaña de dominio).
     * Si algún día se agrega un octavo dominio, esta es la única línea que
     * hay que tocar para que deje de caer en el símbolo por defecto.
     */
    function iconoDominio(string $claveDominio, string $clases = 'h-4 w-4'): string
    {
        static $porDominio = [
            'gobierno'       => 'balanza',       // Gobierno y riesgo
            'configuracion'  => 'engranaje',     // Configuración y cambios
            'almacenamiento' => 'disco',         // Memoria y almacenamiento
            'accesos'        => 'llave',         // Accesos y privilegios
            'dato'           => 'candado',       // Protección del dato
            'continuidad'    => 'respaldo',      // Continuidad
            'vigilancia'     => 'ojo',           // Vigilancia y terceros
        ];

        return icono($porDominio[$claveDominio] ?? 'minus', $clases);
    }
}

if (!function_exists('iniciales')) {
    /**
     * Iniciales para el avatar: la primera letra de las dos primeras palabras.
     *
     * No hay fotografía de usuario en el esquema, así que el retrato del
     * producto son las iniciales sobre un disco. Vive aquí y no en cada barra
     * porque lo pintan dos —la lateral del módulo y el encabezado público— y
     * dos copias se separan en cuanto alguien decida que son tres letras.
     *
     * Con mb_* porque «Ángela» empieza por dos bytes y substr() la partiría a
     * la mitad, imprimiendo un carácter roto.
     */
    function iniciales(string $nombre): string
    {
        $letras = '';

        foreach (array_slice(preg_split('/\s+/u', trim($nombre)) ?: [], 0, 2) as $palabra) {
            if ($palabra !== '') {
                $letras .= mb_strtoupper(mb_substr($palabra, 0, 1, 'UTF-8'), 'UTF-8');
            }
        }

        return $letras;
    }
}

if (!function_exists('pill')) {
    /**
     * Dibuja una etiqueta de estado del sistema visual de Rivendel.
     *
     * Existe para que la regla "el color nunca es el único canal" no dependa
     * de que cada vista se acuerde: aquí el ícono y el texto van siempre
     * juntos, y quien la llama no puede pintar solo el color.
     *
     * Contorno y texto teñido, sin relleno sólido: cuatro rellenos saturados
     * compitiendo en una tabla de 75 filas destruyen la jerarquía de lectura.
     *
     * @param string $tono     opt | ok | warn | bad | crit | na
     * @param string $etiqueta Texto visible; ya traducido por quien llama.
     */
    function pill(string $tono, string $etiqueta): string
    {
        static $iconos = [
            'ok'   => 'circle-check',
            'warn' => 'alert-circle',
            'bad'  => 'alert-triangle',
            'na'   => 'minus',
            // 'crit' comparte color con 'bad' y se distingue por el ícono:
            // un octógono es la señal de alto, no una advertencia más.
            'crit' => 'alert-octagon',
            /*
             * 'opt' es la quinta banda de la escala del monitor de salud
             * (§5.2 del plan de la parte 2) y se resuelve igual que 'crit':
             * comparte el color de 'ok' y se distingue por el ícono. El
             * sistema visual sigue teniendo CUATRO niveles de color y pasa a
             * tener CINCO de significado — que es justo lo que se necesita.
             * Un quinto color, o un verde más claro, contradice el sistema
             * visual y además no sobrevive a la impresión en gris.
             */
            'opt'  => 'escudo',
        ];

        $clase = match ($tono) {
            'crit'  => 'bad',
            'opt'   => 'ok',
            default => $tono,
        };

        $icono = $iconos[$tono] ?? 'minus';

        return '<span class="rv-pill rv-pill--' . e($clase) . '">'
             . icono($icono, 'h-3.5 w-3.5 shrink-0')
             . '<span>' . e($etiqueta) . '</span>'
             . '</span>';
    }
}

if (!function_exists('tonoBanda')) {
    /**
     * Traduce una banda de la escala del monitor al tono de pill().
     *
     * Las cinco bandas del §5.2 del plan de la parte 2 son UN SOLO vocabulario
     * en métrica, componente e índice, así que esta correspondencia se escribe
     * UNA vez. Repetirla dentro de cada vista es exactamente la «tabla de
     * traducción» que el plan quiere evitar: en cuanto hay dos copias, una se
     * queda vieja y la misma banda se pinta de dos colores en dos pantallas.
     *
     * Null no es una banda: es la ausencia de dato (una métrica que no se pudo
     * recolectar, un componente sin métricas, un índice que no se publica). Va
     * a 'na', que es gris y dice «no se sabe», y NO a un color de estado.
     */
    function tonoBanda(?string $banda): string
    {
        return match ($banda) {
            'OPTIMO'      => 'opt',
            'SALUDABLE'   => 'ok',
            'ADVERTENCIA' => 'warn',
            'DEGRADADO'   => 'bad',
            'CRITICO'     => 'crit',
            default       => 'na',
        };
    }
}

if (!function_exists('semaforo')) {
    /**
     * La luz del semáforo de una banda.
     *
     * NO es una segunda escala: es un AGRUPAMIENTO de las cinco bandas del
     * §5.2, con las mismas fronteras de siempre (40 / 60 / 75 / 90).
     *
     *   rojo      CRÍTICO [0,40] y DEGRADADO (40,60]
     *   amarillo  ADVERTENCIA (60,75]
     *   verde     SALUDABLE (75,90] y ÓPTIMO (90,100]
     *
     * Esa distinción importa. Un semáforo con cortes propios —pongamos 30 y
     * 60— sería una escala rival: un índice de 65 saldría «verde» en el
     * tablero y ADVERTENCIA en la alerta, en la misma pantalla y sobre el
     * mismo número. Al agrupar las bandas existentes no hay forma de que las
     * dos lecturas se contradigan, y la pill puede seguir diciendo la banda
     * exacta mientras el color dice si hay que levantarse.
     *
     * Se deriva de la BANDA y no del número por el mismo motivo: la banda ya
     * la decidió el motor sobre el valor sin redondear, y recalcularla aquí
     * sobre la cifra publicada haría que en las fronteras el color y la
     * etiqueta discreparan.
     */
    function semaforo(?string $banda): string
    {
        return match ($banda) {
            'OPTIMO', 'SALUDABLE' => 'verde',
            'ADVERTENCIA'         => 'amarillo',
            'DEGRADADO', 'CRITICO' => 'rojo',
            default               => 'na',
        };
    }
}

if (!function_exists('semaforoGeneral')) {
    /**
     * La luz del conjunto a partir de las luces de sus partes.
     *
     * **Rojo si alguna parte está en rojo; verde solo si TODAS están en
     * verde.** Es la regla del eslabón más débil del §5.4 aplicada al color en
     * vez de al número: un promedio en verde con un componente en rojo es
     * exactamente la emergencia que el promedio esconde.
     *
     * Una parte sin dato no cuenta como verde ni como roja: impide el verde
     * —no se puede afirmar que todo está bien sin haberlo medido— pero no
     * enciende el rojo, porque tampoco se ha medido nada malo. Queda en ámbar,
     * que es lo que significa «no lo sé del todo».
     *
     * @param list<string> $luces Resultados de semaforo(), uno por parte.
     */
    function semaforoGeneral(array $luces): string
    {
        if ($luces === []) {
            return 'na';
        }

        if (in_array('rojo', $luces, true)) {
            return 'rojo';
        }

        foreach ($luces as $luz) {
            if ($luz !== 'verde') {
                return 'amarillo';
            }
        }

        return 'verde';
    }
}

if (!function_exists('tonoSemaforo')) {
    /**
     * Del semáforo al tono de pill(), en un solo sitio.
     *
     * Tres luces sobre la escala de color que ya existe: no se inventa ningún
     * color nuevo, se usan tres de los cuatro niveles.
     */
    function tonoSemaforo(string $luz): string
    {
        return match ($luz) {
            'rojo'     => 'bad',
            'amarillo' => 'warn',
            'verde'    => 'ok',
            default    => 'na',
        };
    }
}
