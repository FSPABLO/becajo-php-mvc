<?php

declare(strict_types=1);

/**
 * Datos de MUESTRA del monitor de salud — maqueta del frente 4.
 *
 * ── Qué es este archivo y qué NO es ──────────────────────────────────────────
 *
 * Es una maqueta. Ninguna cifra de aquí sale de una instancia real: son
 * muestras sintéticas escritas a mano para dibujar la pantalla mientras el
 * módulo se conecta de verdad. La vista lo advierte de forma permanente, y sin
 * ese aviso no debe publicarse: el §12 del plan es explícito en que «ninguna
 * cifra de salud se muestra sin provenir de una muestra realmente tomada».
 *
 * NO consulta Oracle, NO usa RepositorioMonitorArreglo ni RepositorioMonitorOracle
 * (frente 2) y NO invoca MotorCalculoReal (frente 3). Es deliberado: la maqueta
 * tiene que poder dibujarse sin que ninguno de esos frentes esté terminado ni
 * fusionado, igual que el frente 4 «trabaja contra el repositorio de arreglo
 * desde el primer día» según el §11 del plan.
 *
 * ── Por qué guarda muestras YA EVALUADAS ─────────────────────────────────────
 *
 * El contrato de la muestra pone la frontera en un sitio muy concreto: el
 * recolector no razona y el motor no consulta. Lo que llega a la pantalla es
 * una MUESTRA EVALUADA —mediciones normalizadas, componentes topados, índice y
 * alertas—, así que eso es lo que guarda este archivo. La vista se limita a
 * pintar: no normaliza, no promedia y no decide bandas. Cuando el módulo se
 * conecte, lo único que cambia es de dónde sale este arreglo.
 *
 * ── La aritmética está comprobada ────────────────────────────────────────────
 *
 * Los números de PRODCORE1 reproducen el caso trabajado del §5.4 del plan: IP
 * topado en 40,0 por una métrica CRÍTICA, IM topado en 75,0, IA sin tope, y un
 * ISBD bruto de 70,4 que el eslabón más débil deja en 40,0 · CRÍTICO. Es decir:
 * 70,4 se habría publicado como ADVERTENCIA —una base que aguanta el fin de
 * semana— y la instancia está a trece sesiones de rechazar conexiones.
 *
 * Cada instancia demuestra a propósito un caso límite distinto:
 *
 *   PRODCORE1  el eslabón más débil (§5.4) y una métrica SIN DATO que sale del
 *              denominador (invariante 3)
 *   FREEPDB1   el tope de SALUDABLE mordiendo en una instancia sana
 *   BODEGA2    cobertura bajo el piso: se publica «muestra incompleta», no un
 *              número (invariante 4)
 *   LEGADO1    instancia caída, con la antigüedad del último dato al frente
 *
 * @see documentacion/Parte II/EIF402_Monitor_de_Salud_parte_2.md  §5.2 a §6.1, §9
 * @see documentacion/Parte II/contrato-muestra.md
 * @see documentacion/Parte II/catalogo-metricas-v0.md
 */

return [
    /*
     * Piso de cobertura del invariante 4. Es dato, no código: igual que los
     * umbrales del catálogo (B-8), se lee de la configuración y no de una
     * constante escrita dentro de una vista.
     */
    'piso_cobertura' => 80.0,

    // Pesos del §5.6.1, por consecuencia de falla. CONSULTAS no es sumando.
    'pesos' => ['PROCESOS' => 0.30, 'MEMORIA' => 0.35, 'ARCHIVOS' => 0.35],

    /*
     * ── QUÉ MIDE CADA MÉTRICA ────────────────────────────────────────────────
     *
     * El código —`M-PRO-01`— identifica la ficha del catálogo, pero no dice
     * nada de lo que observa. En la cabecera de la tabla de procesos hay sitio
     * para el código y para nada más, así que el nombre y la explicación se
     * consultan al pasar por encima.
     *
     * Es un RESUMEN, no la ficha: la ficha completa —función de medición,
     * cuatro umbrales, modo de falla, anclaje normativo— vive en
     * documentacion/Parte II/catalogo-metricas-v0.md y no se duplica aquí.
     * Lo que se guarda es lo mínimo para responder «¿y esto qué mide?» sin
     * salir de la pantalla.
     *
     * Donde una métrica es MAYOR ES MEJOR se dice con todas las letras: son
     * tres de las quince y, sin el aviso, un 18,8 se lee como bueno cuando es
     * lo contrario.
     */
    'catalogo_metricas' => [

        'M-PRO-01' => [
            'nombre'      => 'Utilización de sesiones',
            'descripcion' => 'Qué proporción de las sesiones que la instancia admite está en uso. '
                           . 'Al llegar al techo, las conexiones nuevas se rechazan con ORA-00018 '
                           . 'aunque la base esté sana por dentro.',
        ],
        'M-PRO-02' => [
            'nombre'      => 'Utilización de procesos',
            'descripcion' => 'Cuánto del arreglo de procesos del sistema operativo está ocupado. '
                           . 'Se agota antes que el de sesiones en instancias con servidor '
                           . 'dedicado, y su falla (ORA-00020) es igual de abrupta.',
        ],
        'M-PRO-03' => [
            'nombre'      => 'Procesos de fondo obligatorios presentes',
            'descripcion' => 'Compuerta: comprueba que los cinco procesos de fondo —CKPT, DBW0, '
                           . 'LGWR, PMON y SMON— están vivos en esta muestra. No tiene término '
                           . 'medio: vale 1 o 0.',
        ],
        'M-PRO-04' => [
            'nombre'      => 'Espera media de escritura de redo',
            'descripcion' => 'Milisegundos que tarda de media una escritura del registro de '
                           . 'rehacer. Es la latencia que siente la aplicación al confirmar, así '
                           . 'que se lee como experiencia de usuario y no como infraestructura.',
        ],
        'M-PRO-05' => [
            'nombre'      => 'Reinicio de proceso de fondo detectado',
            'descripcion' => 'Compuerta: compara la huella de cada proceso de fondo con la de la '
                           . 'muestra anterior. Ve lo que M-PRO-03 no puede ver — un proceso que '
                           . 'cayó y volvió a levantarse entre dos muestras.',
        ],
        'M-PRO-06' => [
            'nombre'      => 'Antigüedad del punto de control',
            'descripcion' => 'Cuánto se ha atrasado el punto de control frente al objetivo de '
                           . 'MTTR. Dice cuánto tardaría la recuperación si la instancia cayera '
                           . 'ahora mismo.',
        ],

        'M-MEM-01' => [
            'nombre'      => 'Aciertos de caché de PGA',
            'descripcion' => 'Proporción del trabajo de PGA que se resolvió íntegramente en '
                           . 'memoria, sin pasar por disco. MAYOR ES MEJOR: es la medida de '
                           . 'resultado de la PGA, mientras que M-MEM-02 es la de consumo.',
        ],
        'M-MEM-02' => [
            'nombre'      => 'PGA asignada sobre el objetivo',
            'descripcion' => 'Cuánta memoria privada de sesión se ha asignado frente al objetivo '
                           . 'configurado. Al pasarse, el trabajo no falla: se traslada al '
                           . 'tablespace temporal y todo se vuelve más lento en silencio.',
        ],
        'M-MEM-03' => [
            'nombre'      => 'Memoria libre de la shared pool',
            'descripcion' => 'Cuánto espacio libre queda en la zona de la SGA donde viven los '
                           . 'planes de ejecución y el diccionario en caché. MAYOR ES MEJOR: aquí '
                           . 'el espacio libre es el margen de maniobra.',
        ],

        'M-ARC-01' => [
            'nombre'      => 'Utilización del peor tablespace',
            'descripcion' => 'Ocupación del tablespace permanente peor situado, nunca el '
                           . 'promedio: un promedio sano esconde el archivo que está a punto de '
                           . 'reventar. Al llenarse, la escritura falla con ORA-01653.',
        ],
        'M-ARC-02' => [
            'nombre'      => 'Datafiles en estado válido',
            'descripcion' => 'Compuerta: comprueba que todos los archivos de datos están en '
                           . 'línea. Uno fuera de línea deja inaccesible su parte de los datos '
                           . 'aunque el resto de la instancia responda con normalidad.',
        ],
        'M-ARC-03' => [
            'nombre'      => 'Grupos de redo sin miembros inválidos',
            'descripcion' => 'Compuerta sobre los miembros inutilizables de los grupos del '
                           . 'registro de rehacer. Con todos los grupos inservibles la base se '
                           . 'detiene, porque no puede rotar el registro.',
        ],
        'M-ARC-04' => [
            'nombre'      => 'Utilización del peor tablespace temporal',
            'descripcion' => 'Ocupación del tablespace temporal peor situado: el espacio de '
                           . 'trabajo de lo que no cupo en PGA. Al agotarse, la consulta en curso '
                           . 'falla con ORA-01652, pero no se pierden datos permanentes.',
        ],
        'M-ARC-05' => [
            'nombre'      => 'Utilización del peor tablespace sin crecimiento automático',
            'descripcion' => 'Ocupación del peor tablespace que NO puede autoextenderse. Cubre el '
                           . 'punto ciego de M-ARC-01: con crecimiento automático el porcentaje '
                           . 'se mide contra el máximo alcanzable y casi nunca alarma.',
        ],

        'M-CON-01' => [
            'nombre'      => 'Sentencias sobre el umbral de tiempo por ejecución',
            'descripcion' => 'Cuántas sentencias del top-20 superan el tiempo por ejecución '
                           . 'pactado. Es un conteo, no una proporción, y mide el trabajo que se '
                           . 'le pide a la base, no su salud.',
        ],
    ],

    /*
     * ── QUÉ EVALÚA CADA ÍNDICE ───────────────────────────────────────────────
     *
     * El catálogo es COMÚN a todas las instancias y solo declara la estructura:
     * qué procesos y recursos mira cada índice, con qué métricas del catálogo se
     * juzga cada uno, y qué hace ese proceso.
     *
     * Los VALORES no están aquí. El controlador cruza esta tabla con las
     * `mediciones` de la instancia que se esté mirando, así que un proceso no
     * puede enseñar una cifra distinta de la que enseña su métrica: hay una sola
     * fuente y es la muestra. Duplicar los valores por instancia habría sido la
     * forma más rápida de que las dos tablas dejaran de coincidir.
     *
     * `metricas` son códigos del catálogo de métricas (documentacion/Parte II/
     * catalogo-metricas-v0.md). El orden importa: es el que se pinta.
     *
     * `descripcion` y `recomendacion` van SEPARADAS y no en un solo campo de
     * prosa: son dos columnas de la tabla y responden dos preguntas distintas
     * —qué es esto, y qué hago con ello—. Juntas, quien buscaba la acción tenía
     * que leerse la definición entera cada vez.
     */
    'catalogo_procesos' => [

        'PROCESOS' => [
            [
                'nombre'        => 'PMON',
                'metricas'      => ['M-PRO-03', 'M-PRO-05'],
                'descripcion'   => 'Monitor de procesos. Limpia lo que dejan '
                                   . 'las sesiones que terminan de forma '
                                   . 'anormal: deshace su transacción, libera '
                                   . 'los bloqueos que retenían y devuelve su '
                                   . 'hueco al arreglo de procesos. Si PMON no '
                                   . 'está, la instancia no está.',
                'recomendacion' => 'Se recomienda alertar a la primera '
                                   . 'muestra en que falte, sin esperar '
                                   . 'confirmación: no hay degradación parcial '
                                   . 'de este proceso.',
            ],
            [
                'nombre'        => 'SMON',
                'metricas'      => ['M-PRO-03', 'M-PRO-05'],
                'descripcion'   => 'Monitor del sistema. Recupera la '
                                   . 'instancia al arrancar tras una caída, '
                                   . 'fusiona los extents libres contiguos y '
                                   . 'limpia los segmentos temporales que '
                                   . 'quedaron huérfanos.',
                'recomendacion' => 'Se recomienda mirarlo junto a M-ARC-04: '
                                   . 'cuando SMON se atrasa, el tablespace '
                                   . 'temporal es el primero en notarlo.',
            ],
            [
                'nombre'        => 'DBW0',
                'metricas'      => ['M-PRO-03', 'M-PRO-05'],
                'descripcion'   => 'Escritor de base de datos. Baja a los '
                                   . 'datafiles los bloques sucios del buffer '
                                   . 'cache para que haya sitio libre donde '
                                   . 'leer los siguientes.',
                'recomendacion' => 'Se recomienda no leerlo solo: si DBW0 '
                                   . 'está vivo pero el punto de control se '
                                   . 'atrasa (M-PRO-06), el cuello de botella '
                                   . 'es la E/S de disco y no el proceso.',
            ],
            [
                'nombre'        => 'LGWR',
                'metricas'      => ['M-PRO-03', 'M-PRO-04'],
                'descripcion'   => 'Escritor del registro de rehacer. Vuelca '
                                   . 'el búfer de redo a los archivos de log en '
                                   . 'cada COMMIT, y por eso su latencia es la '
                                   . 'latencia que siente la aplicación al '
                                   . 'confirmar.',
                'recomendacion' => 'Se recomienda tratar M-PRO-04 como '
                                   . 'métrica de experiencia de usuario, no de '
                                   . 'infraestructura: por encima de 20 ms los '
                                   . 'COMMIT se notan desde fuera.',
            ],
            [
                'nombre'        => 'CKPT',
                'metricas'      => ['M-PRO-03', 'M-PRO-06'],
                'descripcion'   => 'Proceso de punto de control. Marca hasta '
                                   . 'dónde está garantizado el contenido en '
                                   . 'disco y actualiza las cabeceras de los '
                                   . 'datafiles. Cuanto más atrasado va, más '
                                   . 'tarda la recuperación tras una caída.',
                'recomendacion' => 'Se recomienda comparar M-PRO-06 con el '
                                   . 'objetivo de MTTR pactado con el negocio, '
                                   . 'no con un número absoluto.',
            ],
            [
                'nombre'        => 'Cupo de sesiones',
                'metricas'      => ['M-PRO-01'],
                'descripcion'   => 'No es un proceso de fondo: es el techo de '
                                   . 'sesiones concurrentes que la instancia '
                                   . 'admite. Al agotarse, las conexiones '
                                   . 'nuevas se rechazan con ORA-00018 aunque '
                                   . 'la base esté perfectamente sana por '
                                   . 'dentro.',
                'recomendacion' => 'Se recomienda medirlo contra el límite '
                                   . 'efectivo de V$RESOURCE_LIMIT y nunca '
                                   . 'contra una cifra supuesta.',
            ],
            [
                'nombre'        => 'Cupo de procesos',
                'metricas'      => ['M-PRO-02'],
                'descripcion'   => 'Techo del arreglo de procesos del sistema '
                                   . 'operativo. Se agota antes que el de '
                                   . 'sesiones en instancias con servidor '
                                   . 'dedicado, y su falla (ORA-00020) es igual '
                                   . 'de abrupta.',
                'recomendacion' => 'Se recomienda vigilarlo junto al cupo de '
                                   . 'sesiones: suben juntos y quien avisa '
                                   . 'primero depende de la configuración, no '
                                   . 'de la carga.',
            ],
        ],

        'MEMORIA' => [
            [
                'nombre'        => 'Shared pool',
                'metricas'      => ['M-MEM-03'],
                'descripcion'   => 'Zona de la SGA donde viven los planes de '
                                   . 'ejecución y el diccionario de datos en '
                                   . 'caché. Cuando se queda sin espacio libre, '
                                   . 'Oracle empieza a expulsar planes y a '
                                   . 'recompilar sentencias que ya tenía '
                                   . 'resueltas, y el coste aparece como CPU, '
                                   . 'no como memoria.',
                'recomendacion' => 'Se recomienda no perseguir el 100 % de '
                                   . 'ocupación: aquí el espacio libre es el '
                                   . 'margen de maniobra.',
            ],
            [
                'nombre'        => 'PGA',
                'metricas'      => ['M-MEM-02'],
                'descripcion'   => 'Área global de programa: la memoria '
                                   . 'privada de cada sesión para '
                                   . 'ordenamientos, agrupaciones y uniones por '
                                   . 'hash. Al pasarse del objetivo, el trabajo '
                                   . 'no falla: se traslada al tablespace '
                                   . 'temporal y todo se vuelve más lento en '
                                   . 'silencio.',
                'recomendacion' => 'Se recomienda leerla junto a M-ARC-04, '
                                   . 'que es donde aterriza lo que no cupo.',
            ],
            [
                'nombre'        => 'Caché de PGA',
                'metricas'      => ['M-MEM-01'],
                'descripcion'   => 'Proporción del trabajo de PGA que se '
                                   . 'resolvió íntegramente en memoria, sin '
                                   . 'pasar por disco. Es la medida de '
                                   . 'resultado de la PGA, mientras que '
                                   . 'M-MEM-02 es la de consumo.',
                'recomendacion' => 'Se recomienda actuar cuando esta baja '
                                   . 'aunque la asignada esté dentro del '
                                   . 'objetivo: significa que el objetivo se '
                                   . 'quedó corto para la carga real.',
            ],
        ],

        'ARCHIVOS' => [
            [
                'nombre'        => 'Tablespaces permanentes',
                'metricas'      => ['M-ARC-01'],
                'descripcion'   => 'Espacio ocupado en el tablespace peor '
                                   . 'situado, nunca el promedio: un promedio '
                                   . 'sano esconde el archivo que está a punto '
                                   . 'de reventar. Al llenarse, la escritura '
                                   . 'falla con ORA-01653 y la transacción se '
                                   . 'pierde.',
                'recomendacion' => 'Se recomienda revisar el crecimiento '
                                   . 'semanal además del porcentaje: el '
                                   . 'porcentaje dice dónde está, la pendiente '
                                   . 'dice cuándo llega.',
            ],
            [
                'nombre'        => 'Datafiles',
                'metricas'      => ['M-ARC-02'],
                'descripcion'   => 'Compuerta: o todos los archivos de datos '
                                   . 'están en línea, o no lo están. Un '
                                   . 'datafile fuera de línea deja inaccesible '
                                   . 'su parte de los datos aunque el resto de '
                                   . 'la instancia responda con normalidad.',
                'recomendacion' => 'Se recomienda no promediarla con nada: '
                                   . 'cerrada manda el índice de archivos a '
                                   . 'crítico sin discusión.',
            ],
            [
                'nombre'        => 'Grupos de redo',
                'metricas'      => ['M-ARC-03'],
                'descripcion'   => 'Compuerta sobre los miembros inválidos de '
                                   . 'los grupos de redo. Con todos los grupos '
                                   . 'inutilizables la base se detiene, porque '
                                   . 'no puede rotar el registro.',
                'recomendacion' => 'Se recomienda mantener al menos dos '
                                   . 'miembros por grupo en discos distintos: '
                                   . 'la métrica mide validez, no redundancia.',
            ],
            [
                'nombre'        => 'Tablespace temporal',
                'metricas'      => ['M-ARC-04'],
                'descripcion'   => 'Espacio de trabajo para lo que no cupo en '
                                   . 'PGA. Al agotarse, la consulta o el índice '
                                   . 'que se estaba construyendo falla con '
                                   . 'ORA-01652, pero no se pierden datos '
                                   . 'permanentes.',
                'recomendacion' => 'Se recomienda dimensionarlo a partir del '
                                   . 'pico observado y no del promedio: lo '
                                   . 'consume una sola consulta grande, no el '
                                   . 'uso diario.',
            ],
            [
                'nombre'        => 'Tablespace sin autoextend',
                'metricas'      => ['M-ARC-05'],
                'descripcion'   => 'Cubre el punto ciego de M-ARC-01: con '
                                   . 'crecimiento automático activo, el '
                                   . 'porcentaje se mide contra el máximo '
                                   . 'alcanzable y se queda en óptimo por mucho '
                                   . 'que crezca el archivo. Sin autoextend, '
                                   . 'ese mismo porcentaje sí significa «qué '
                                   . 'tan lleno está».',
                'recomendacion' => 'Se recomienda tratarlo como el aviso '
                                   . 'temprano de los dos.',
            ],
        ],
    ],

    'instancias' => [

        // ── PRODCORE1 · el caso trabajado del §5.4 ───────────────────────────
        'PRODCORE1' => [
            'clave'       => 'PRODCORE1',
            'motor'       => 'Oracle Database 19c',
            'entorno'     => 'Producción',
            'hace_min'    => 4,
            'muestra'     => 'COMPLETA',
            'duracion_ms' => 842,
            'contextos'   => [
                'RAIZ'       => ['estado' => 'OK', 'duracion_ms' => 611],
                'CONTENEDOR' => ['estado' => 'OK', 'duracion_ms' => 231],
            ],
            'cobertura'   => ['obtenidas' => 14, 'planificadas' => 15, 'pct' => 93.3],
            'isbd'        => 40.0,
            'isbd_bruto'  => 70.4,
            'banda'       => 'CRITICO',
            'tope'        => ['valor' => 40.0, 'por' => 'PROCESOS', 'estado' => 'CRITICO'],

            'componentes' => [
                ['clave' => 'PROCESOS',  'peso' => 0.30, 'bruto' => 65.7,  'publicado' => 40.0,  'banda' => 'CRITICO',     'tope' => 40.0, 'en_isbd' => true],
                ['clave' => 'MEMORIA',   'peso' => 0.35, 'bruto' => 76.3,  'publicado' => 75.0,  'banda' => 'ADVERTENCIA', 'tope' => 75.0, 'en_isbd' => true],
                ['clave' => 'ARCHIVOS',  'peso' => 0.35, 'bruto' => 91.9,  'publicado' => 91.9,  'banda' => 'OPTIMO',      'tope' => null, 'en_isbd' => true],
                ['clave' => 'CONSULTAS', 'peso' => null, 'bruto' => 50.0,  'publicado' => 50.0,  'banda' => 'DEGRADADO',   'tope' => null, 'en_isbd' => false],
            ],

            'mediciones' => [
                ['codigo' => 'M-PRO-01', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 96.0, 'unidad' => '%', 'partes' => '309 de 322 sesiones', 's' => 32.0, 'banda' => 'CRITICO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-02', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 62.0, 'unidad' => '%', 'partes' => '124 de 200 procesos', 's' => 81.0, 'banda' => 'SALUDABLE', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-03', 'componente' => 'PROCESOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '5 de 5: CKPT DBW0 LGWR PMON SMON', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-PRO-04', 'componente' => 'PROCESOS', 'tipo' => 'tasa', 'peso' => 2, 'estado' => 'OK', 'u' => 6.4, 'unidad' => 'ms', 'partes' => 'Derivada de 8 412 esperas acumuladas', 's' => 85.8, 'banda' => 'SALUDABLE', 'umbrales' => [5, 10, 20, 40], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-05', 'componente' => 'PROCESOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => 'Sin reinicios: 5 huellas iguales a la muestra anterior', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-PRO-06', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 62.0, 'unidad' => '%', 'partes' => 'MTTR estimado 31 s sobre objetivo 50 s', 's' => 81.0, 'banda' => 'SALUDABLE', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],

                ['codigo' => 'M-MEM-01', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 98.4, 'unidad' => '%', 'partes' => 'Aciertos de caché de PGA', 's' => 99.7, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'mayor'],
                ['codigo' => 'M-MEM-02', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 76.0, 'unidad' => '%', 'partes' => '389 MB de 512 MB de objetivo', 's' => 69.0, 'banda' => 'ADVERTENCIA', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-MEM-03', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 18.8, 'unidad' => '%', 'partes' => 'Memoria libre de la shared pool', 's' => 63.8, 'banda' => 'ADVERTENCIA', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'mayor'],

                ['codigo' => 'M-ARC-01', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 41.0, 'unidad' => '%', 'partes' => 'USERS: 1 049 MB de 2 560 MB', 's' => 91.8, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-ARC-02', 'componente' => 'ARCHIVOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '4 de 4 datafiles en línea', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-ARC-03', 'componente' => 'ARCHIVOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '2 grupos de redo, 0 miembros inválidos', 'banda' => 'OPTIMO'],

                /*
                 * INVARIANTE 3 — sin dato no es cero. Esta métrica no se pudo
                 * recolectar, así que sale del denominador de ARCHIVOS y se
                 * anota con su motivo. Publicarla como 0 convertiría una falla
                 * de permisos del agente en una falla de la base de datos.
                 */
                ['codigo' => 'M-ARC-04', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'SIN_DATO', 'motivo' => 'ORA-00942: la cuenta del agente no tiene GRANT sobre V$TEMP_SPACE_HEADER', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],

                ['codigo' => 'M-ARC-05', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 40.0, 'unidad' => '%', 'partes' => 'SYSAUX: 210 MB de 525 MB, sin AUTOEXTEND', 's' => 92.0, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],

                ['codigo' => 'M-CON-01', 'componente' => 'CONSULTAS', 'tipo' => 'conteo', 'peso' => null, 'estado' => 'OK', 'u' => 5.0, 'unidad' => '', 'partes' => '5 sentencias del top-20 sobre 200 ms por ejecución', 's' => 50.0, 'banda' => 'DEGRADADO', 'umbrales' => [1, 2, 4, 6], 'sentido' => 'menor'],
            ],

            /*
             * TENDENCIA — cuarenta lecturas, una cada cinco minutos.
             *
             * `topadas` son los indices de las lecturas en las que MORDIO el
             * eslabon mas debil: el promedio ponderado daba mas, pero un
             * componente en mal estado bajo el indice hasta la frontera de su
             * banda. Por eso esas lecturas caen CLAVADAS en 40, 60, 75 o 90 y
             * la serie dibuja mesetas planas justo sobre la linea de la banda:
             * es la firma visual de la regla, no un artefacto del dibujo.
             *
             * Una lectura null es una muestra que no publico indice porque su
             * cobertura quedo bajo el piso. No es un cero.
             */
            'tendencia' => [
                'cadencia_min' => 5,
                'topadas' => [18, 19, 22, 25, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39],
                'lecturas' => [
                    91.2, 91.9, 94.0, 91.3, 91.5, 92.0, 89.6, 91.6, 92.3, 93.3,
                    85.1, 86.3, 85.0, 89.4, 88.7, 84.8, 90.4, 90.3, 75.0, 75.0,
                    73.8, 72.6, 75.0, 73.0, 74.0, 60.0, 58.3, 60.0, 60.0, 60.0,
                    60.0, 60.0, 60.0, 40.0, 40.0, 40.0, 40.0, 40.0, 40.0, 40.0,
                ],
            ],

            /*
             * MEMORIA EN EL TIEMPO — lo que dibuja el gráfico que se refresca.
             *
             * `serie` son megabytes OCUPADOS (SGA + PGA), no un porcentaje: el
             * eje vertical es espacio y el techo es `total_mb`. Los dos umbrales
             * se declaran en porcentaje del total porque así se pactan con el
             * DBA, y el gráfico los convierte a MB para dibujarlos sobre la
             * misma escala que la serie — dos escalas en un dibujo dejarían que
             * el tope elegido decidiera qué línea va encima.
             *
             * `uptime_min` es el tiempo activo de la instancia en la ÚLTIMA
             * lectura; el eje horizontal se rotula hacia atrás desde ahí, que es
             * lo que pide un eje de «tiempo activo» y no de hora del reloj.
             *
             * Esta instancia sube: cruza el umbral de aceptación por la mitad de
             * la ventana y se acerca al de peligro sin llegar. Es el caso que
             * hace falta ver para saber si el gráfico sirve.
             */
            'memoria' => [
                'total_mb'              => 2048,
                'umbral_aceptacion_pct' => 70,
                'umbral_peligro_pct'    => 90,
                'cadencia_seg'          => 30,
                'uptime_min'            => 738,
                'serie' => [
                    1180, 1195, 1188, 1210, 1232, 1225, 1248, 1260, 1255, 1279,
                    1291, 1286, 1310, 1325, 1318, 1342, 1356, 1349, 1371, 1388,
                    1402, 1396, 1419, 1434, 1428, 1451, 1467, 1460, 1482, 1498,
                    1512, 1506, 1529, 1547, 1541, 1566, 1584, 1601, 1623, 1648,
                ],
            ],
        ],

        // ── FREEPDB1 · sana, y aun así el tope de SALUDABLE muerde ───────────
        'FREEPDB1' => [
            'clave'       => 'FREEPDB1',
            'motor'       => 'Oracle Database 23ai Free',
            'entorno'     => 'Desarrollo',
            'hace_min'    => 3,
            'muestra'     => 'COMPLETA',
            'duracion_ms' => 517,
            'contextos'   => [
                'RAIZ'       => ['estado' => 'OK', 'duracion_ms' => 344],
                'CONTENEDOR' => ['estado' => 'OK', 'duracion_ms' => 173],
            ],
            'cobertura'   => ['obtenidas' => 15, 'planificadas' => 15, 'pct' => 100.0],
            'isbd'        => 90.0,
            'isbd_bruto'  => 93.6,
            'banda'       => 'SALUDABLE',
            /*
             * El tope de SALUDABLE es la fila nueva del §5.4 y aquí se ve por
             * qué hace falta: con 93,6 de bruto esta instancia se anunciaría
             * como ÓPTIMA teniendo un componente que apenas está saludable.
             */
            'tope'        => ['valor' => 90.0, 'por' => 'MEMORIA', 'estado' => 'SALUDABLE'],

            'componentes' => [
                ['clave' => 'PROCESOS',  'peso' => 0.30, 'bruto' => 93.3,  'publicado' => 93.3,  'banda' => 'OPTIMO',    'tope' => null, 'en_isbd' => true],
                ['clave' => 'MEMORIA',   'peso' => 0.35, 'bruto' => 92.2,  'publicado' => 90.0,  'banda' => 'SALUDABLE', 'tope' => 90.0, 'en_isbd' => true],
                ['clave' => 'ARCHIVOS',  'peso' => 0.35, 'bruto' => 97.6,  'publicado' => 97.6,  'banda' => 'OPTIMO',    'tope' => null, 'en_isbd' => true],
                ['clave' => 'CONSULTAS', 'peso' => null, 'bruto' => 100.0, 'publicado' => 100.0, 'banda' => 'OPTIMO',    'tope' => null, 'en_isbd' => false],
            ],

            'mediciones' => [
                ['codigo' => 'M-PRO-01', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 34.2, 'unidad' => '%', 'partes' => '110 de 322 sesiones', 's' => 93.2, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-02', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 43.0, 'unidad' => '%', 'partes' => '86 de 200 procesos', 's' => 91.4, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-03', 'componente' => 'PROCESOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '5 de 5: CKPT DBW0 LGWR PMON SMON', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-PRO-04', 'componente' => 'PROCESOS', 'tipo' => 'tasa', 'peso' => 2, 'estado' => 'OK', 'u' => 1.2, 'unidad' => 'ms', 'partes' => 'Derivada de 1 240 esperas acumuladas', 's' => 97.6, 'banda' => 'OPTIMO', 'umbrales' => [5, 10, 20, 40], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-05', 'componente' => 'PROCESOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => 'Sin reinicios: 5 huellas iguales a la muestra anterior', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-PRO-06', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 44.0, 'unidad' => '%', 'partes' => 'MTTR estimado 22 s sobre objetivo 50 s', 's' => 91.2, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],

                ['codigo' => 'M-MEM-01', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 100.0, 'unidad' => '%', 'partes' => 'Aciertos de caché de PGA', 's' => 100.0, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'mayor'],
                ['codigo' => 'M-MEM-02', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 38.0, 'unidad' => '%', 'partes' => '195 MB de 512 MB de objetivo', 's' => 92.4, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-MEM-03', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 42.0, 'unidad' => '%', 'partes' => 'Memoria libre de la shared pool', 's' => 84.0, 'banda' => 'SALUDABLE', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'mayor'],

                ['codigo' => 'M-ARC-01', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 12.4, 'unidad' => '%', 'partes' => 'USERS: 63 MB de 512 MB', 's' => 97.5, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-ARC-02', 'componente' => 'ARCHIVOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '4 de 4 datafiles en línea', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-ARC-03', 'componente' => 'ARCHIVOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '2 grupos de redo, 0 miembros inválidos', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-ARC-04', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 6.0, 'unidad' => '%', 'partes' => 'TEMP: 2 MB de 33 MB', 's' => 98.8, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-ARC-05', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 18.0, 'unidad' => '%', 'partes' => 'SYSAUX: 94 MB de 525 MB, sin AUTOEXTEND', 's' => 96.4, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],

                ['codigo' => 'M-CON-01', 'componente' => 'CONSULTAS', 'tipo' => 'conteo', 'peso' => null, 'estado' => 'OK', 'u' => 0.0, 'unidad' => '', 'partes' => 'Ninguna sentencia del top-20 sobre 200 ms por ejecución', 's' => 100.0, 'banda' => 'OPTIMO', 'umbrales' => [1, 2, 4, 6], 'sentido' => 'menor'],
            ],

            /*
             * TENDENCIA — cuarenta lecturas, una cada cinco minutos.
             *
             * `topadas` son los indices de las lecturas en las que MORDIO el
             * eslabon mas debil: el promedio ponderado daba mas, pero un
             * componente en mal estado bajo el indice hasta la frontera de su
             * banda. Por eso esas lecturas caen CLAVADAS en 40, 60, 75 o 90 y
             * la serie dibuja mesetas planas justo sobre la linea de la banda:
             * es la firma visual de la regla, no un artefacto del dibujo.
             *
             * Una lectura null es una muestra que no publico indice porque su
             * cobertura quedo bajo el piso. No es un cero.
             */
            'tendencia' => [
                'cadencia_min' => 5,
                'topadas' => [1, 2, 3, 5, 6, 7, 8, 12, 14, 18, 19, 20, 21, 24, 26, 29, 31, 32, 33, 35, 38, 39],
                'lecturas' => [
                    88.3, 90.0, 90.0, 90.0, 88.7, 90.0, 90.0, 90.0, 90.0, 88.8,
                    87.2, 89.7, 90.0, 87.7, 90.0, 88.8, 88.8, 87.5, 90.0, 90.0,
                    90.0, 90.0, 87.4, 89.4, 90.0, 88.0, 90.0, 89.7, 88.3, 90.0,
                    88.0, 90.0, 90.0, 90.0, 89.5, 90.0, 88.5, 87.5, 90.0, 90.0,
                ],
            ],

            // Instancia de desarrollo: memoria pequeña y holgada, muy por
            // debajo del umbral de aceptación. Es el contraste que hace legible
            // la subida de PRODCORE1.
            'memoria' => [
                'total_mb'              => 1024,
                'umbral_aceptacion_pct' => 70,
                'umbral_peligro_pct'    => 90,
                'cadencia_seg'          => 30,
                'uptime_min'            => 205,
                'serie' => [
                    498, 512, 505, 527, 519, 534, 522, 541, 530, 548,
                    536, 519, 527, 545, 552, 538, 561, 549, 566, 554,
                    571, 558, 543, 562, 575, 561, 580, 567, 585, 572,
                    590, 577, 563, 581, 594, 579, 598, 584, 602, 588,
                ],
            ],

        ],

        // ── BODEGA2 · cobertura bajo el piso: no hay ISBD que publicar ───────
        'BODEGA2' => [
            'clave'       => 'BODEGA2',
            'motor'       => 'Oracle Database 19c',
            'entorno'     => 'Analítica',
            'hace_min'    => 6,
            'muestra'     => 'PARCIAL',
            'duracion_ms' => 3180,
            /*
             * El campo `contextos` del contrato existe justo para esto: sin él,
             * cinco lecturas en error no dirían si fallaron cinco métricas o
             * una sola conexión.
             */
            'contextos'   => [
                'RAIZ'       => ['estado' => 'OK',    'duracion_ms' => 588],
                'CONTENEDOR' => ['estado' => 'ERROR', 'duracion_ms' => 2592, 'motivo' => 'ORA-12514: el listener no conoce el servicio solicitado'],
            ],
            'cobertura'   => ['obtenidas' => 10, 'planificadas' => 15, 'pct' => 66.7],
            'isbd'        => null,
            'isbd_bruto'  => null,
            'banda'       => null,
            'tope'        => null,

            'componentes' => [
                ['clave' => 'PROCESOS',  'peso' => 0.30, 'bruto' => 87.7, 'publicado' => 87.7, 'banda' => 'SALUDABLE', 'tope' => null, 'en_isbd' => true],
                ['clave' => 'MEMORIA',   'peso' => 0.35, 'bruto' => 93.3, 'publicado' => 93.3, 'banda' => 'OPTIMO',    'tope' => null, 'en_isbd' => true],
                ['clave' => 'ARCHIVOS',  'peso' => 0.35, 'bruto' => null, 'publicado' => null, 'banda' => null,        'tope' => null, 'en_isbd' => true],
                ['clave' => 'CONSULTAS', 'peso' => null, 'bruto' => null, 'publicado' => null, 'banda' => null,        'tope' => null, 'en_isbd' => false],
            ],

            'mediciones' => [
                ['codigo' => 'M-PRO-01', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 51.0, 'unidad' => '%', 'partes' => '164 de 322 sesiones', 's' => 89.3, 'banda' => 'SALUDABLE', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-02', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 39.0, 'unidad' => '%', 'partes' => '78 de 200 procesos', 's' => 92.2, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-03', 'componente' => 'PROCESOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '5 de 5: CKPT DBW0 LGWR PMON SMON', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-PRO-04', 'componente' => 'PROCESOS', 'tipo' => 'tasa', 'peso' => 2, 'estado' => 'OK', 'u' => 4.1, 'unidad' => 'ms', 'partes' => 'Derivada de 22 907 esperas acumuladas', 's' => 91.8, 'banda' => 'OPTIMO', 'umbrales' => [5, 10, 20, 40], 'sentido' => 'menor'],
                ['codigo' => 'M-PRO-05', 'componente' => 'PROCESOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => 'Sin reinicios: 5 huellas iguales a la muestra anterior', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-PRO-06', 'componente' => 'PROCESOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 68.0, 'unidad' => '%', 'partes' => 'MTTR estimado 34 s sobre objetivo 50 s', 's' => 76.5, 'banda' => 'SALUDABLE', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],

                ['codigo' => 'M-MEM-01', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 99.1, 'unidad' => '%', 'partes' => 'Aciertos de caché de PGA', 's' => 99.8, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'mayor'],
                ['codigo' => 'M-MEM-02', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'OK', 'u' => 47.0, 'unidad' => '%', 'partes' => '241 MB de 512 MB de objetivo', 's' => 90.6, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-MEM-03', 'componente' => 'MEMORIA', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'OK', 'u' => 55.0, 'unidad' => '%', 'partes' => 'Memoria libre de la shared pool', 's' => 91.0, 'banda' => 'OPTIMO', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'mayor'],

                ['codigo' => 'M-ARC-01', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 3, 'estado' => 'SIN_DATO', 'motivo' => 'Contexto CONTENEDOR sin conexión', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-ARC-02', 'componente' => 'ARCHIVOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'SIN_DATO', 'motivo' => 'Contexto CONTENEDOR sin conexión'],
                ['codigo' => 'M-ARC-03', 'componente' => 'ARCHIVOS', 'tipo' => 'compuerta', 'peso' => null, 'estado' => 'OK', 'compuerta' => 'ABIERTA', 'partes' => '3 grupos de redo, 0 miembros inválidos', 'banda' => 'OPTIMO'],
                ['codigo' => 'M-ARC-04', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'SIN_DATO', 'motivo' => 'Contexto CONTENEDOR sin conexión', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],
                ['codigo' => 'M-ARC-05', 'componente' => 'ARCHIVOS', 'tipo' => 'proporcion', 'peso' => 2, 'estado' => 'SIN_DATO', 'motivo' => 'Contexto CONTENEDOR sin conexión', 'umbrales' => [50, 70, 85, 95], 'sentido' => 'menor'],

                ['codigo' => 'M-CON-01', 'componente' => 'CONSULTAS', 'tipo' => 'conteo', 'peso' => null, 'estado' => 'SIN_DATO', 'motivo' => 'Contexto CONTENEDOR sin conexión', 'umbrales' => [1, 2, 4, 6], 'sentido' => 'menor'],
            ],

            /*
             * TENDENCIA — cuarenta lecturas, una cada cinco minutos.
             *
             * `topadas` son los indices de las lecturas en las que MORDIO el
             * eslabon mas debil: el promedio ponderado daba mas, pero un
             * componente en mal estado bajo el indice hasta la frontera de su
             * banda. Por eso esas lecturas caen CLAVADAS en 40, 60, 75 o 90 y
             * la serie dibuja mesetas planas justo sobre la linea de la banda:
             * es la firma visual de la regla, no un artefacto del dibujo.
             *
             * Una lectura null es una muestra que no publico indice porque su
             * cobertura quedo bajo el piso. No es un cero.
             */
            'tendencia' => [
                'cadencia_min' => 5,
                'topadas' => [],
                'lecturas' => [
                    90.9, 91.2, 92.1, 89.3, 91.3, 92.5, 92.6, 88.9, 91.3, 91.1,
                    90.5, 92.9, 90.6, 90.2, 92.8, 91.2, 89.5, 89.5, 90.5, 88.2,
                    91.1, 91.7, 90.1, 92.3, 92.7, 90.8, 91.4, 88.5, 88.2, 93.0,
                    89.4, 89.0, 92.6, 91.8, null, null, null, null, null, null,
                ],
            ],

            /*
             * La memoria SÍ se sigue leyendo aunque el índice no se publique: la
             * conexión que falló es la del contenedor y estas lecturas vienen de
             * la raíz. Es la razón de ser del campo `contextos` del contrato —
             * un fallo de conexión no apaga todas las señales, solo las suyas.
             */
            'memoria' => [
                'total_mb'              => 4096,
                'umbral_aceptacion_pct' => 70,
                'umbral_peligro_pct'    => 90,
                'cadencia_seg'          => 30,
                'uptime_min'            => 4321,
                'serie' => [
                    2352, 2371, 2360, 2389, 2402, 2385, 2418, 2431, 2409, 2444,
                    2456, 2438, 2470, 2483, 2461, 2495, 2508, 2486, 2519, 2531,
                    2510, 2543, 2556, 2534, 2567, 2579, 2558, 2591, 2604, 2582,
                    2615, 2627, 2606, 2639, 2651, 2630, 2662, 2675, 2653, 2686,
                ],
            ],

        ],

        // ── LEGADO1 · la instancia no respondió ──────────────────────────────
        'LEGADO1' => [
            'clave'   => 'LEGADO1',
            'motor'   => 'Oracle Database 12c',
            'entorno' => 'Legado',
            /*
             * 134 minutos. La antigüedad es EL dato de esta fila: un tablero
             * que enseña una cifra de hace dos horas como si fuera de ahora es
             * la falla más común de los monitores caseros (§9).
             */
            'hace_min'    => 134,
            'muestra'     => 'FALLIDA',
            'duracion_ms' => 30000,
            'contextos'   => [
                'RAIZ'       => ['estado' => 'ERROR', 'duracion_ms' => 15000, 'motivo' => 'ORA-12541: no hay listener'],
                'CONTENEDOR' => ['estado' => 'ERROR', 'duracion_ms' => 15000, 'motivo' => 'ORA-12541: no hay listener'],
            ],
            'cobertura'  => ['obtenidas' => 0, 'planificadas' => 15, 'pct' => 0.0],
            'isbd'       => null,
            'isbd_bruto' => null,
            'banda'      => null,
            'tope'       => null,

            /*
             * El último ISBD del que hay evidencia, con su antigüedad pegada.
             * No es el estado actual y la pantalla no lo presenta como tal: una
             * muestra fallida se persiste igual, porque «la instancia no
             * respondió a esta hora» también es un dato (principio 3 del
             * contrato de la muestra). Saltarla dejaría un hueco que después
             * parece un periodo sano.
             */
            'ultimo_conocido' => ['isbd' => 88.2, 'banda' => 'SALUDABLE', 'hace_min' => 134],

            'componentes' => [],
            'mediciones'  => [],
            'tendencia'   => null,
            // Sin conexión no hay lecturas de memoria. Null y no una serie a
            // cero: cero sería una medida, y aquí no se midió nada.
            'memoria'     => null,
        ],
    ],
];
