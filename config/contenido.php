<?php

declare(strict_types=1);

/**
 * Fuente de contenido del sitio.
 *
 * Este archivo es la "base de datos" temporal del proyecto. El repositorio
 * App\Models\RepositorioArreglo lo lee y lo entrega al controlador.
 *
 * Cuando el sitio se conecte a Oracle, se crea un RepositorioPdo que implemente
 * la misma interfaz y se cambia una sola línea en public/index.php. Ni el
 * controlador ni las vistas se enteran del cambio: ese es el propósito de
 * separar el modelo del resto.
 */

return [

    'empresa' => [
        'nombre'   => 'Rivendel',
        'eslogan'  => 'Consultoría en Administración de Bases de Datos',
        'correo'   => 'contacto@rivendel.cr',
        'telefono' => '+506 0000 0000',
        'ciudad'   => 'San José, Costa Rica',
        'anio'     => 2026,
    ],

    'meta' => [
        'titulo'      => 'Rivendel | Consultoría en Administración de Bases de Datos',
        'descripcion' => 'Migración, optimización, seguridad y monitoreo de bases de datos '
                       . 'empresariales. Reducimos el riesgo operativo de su información crítica.',
    ],

    /**
     * Enlaces sueltos del encabezado.
     *
     * Solo las secciones que se visitan directamente. Retos, Servicios y
     * Resultados se leen de corrido al bajar por la portada y no necesitaban
     * entrada propia: una barra con seis anclas obliga a elegir antes de saber
     * qué hay. Lo institucional pasó al desplegable «Nosotros».
     */
    'navegacion' => [
        ['etiqueta' => 'Stack',  'destino' => '#stack'],
        ['etiqueta' => 'Planes', 'destino' => '#planes'],
    ],

    /**
     * Menú desplegable "Nosotros" del encabezado.
     *
     * Misma forma que 'herramientas' —etiqueta, descripción, destino, icono—
     * porque el encabezado pinta los dos con el mismo bloque. Aquí va quién
     * hace el trabajo y cómo funciona lo que se ofrece; en 'herramientas', lo
     * que el visitante puede usar.
     */
    'nosotros' => [
        [
            'etiqueta'    => 'Equipo',
            'descripcion' => 'Quién audita: los consultores a cargo del instrumento y de '
                           . 'las auditorías.',
            'destino'     => '#equipo',
            'icono'       => 'usuarios',
        ],
        [
            'etiqueta'    => 'Preguntas frecuentes',
            'descripcion' => 'Cómo funciona el sistema, a quién está dirigido y hacia dónde '
                           . 'va el producto.',
            'destino'     => '/preguntas-frecuentes',
            'icono'       => 'pregunta',
        ],
    ],

    /**
     * Herramientas internas del sitio.
     *
     * Alimentan el menú desplegable "Herramientas" del encabezado. Agregar una
     * herramienta nueva es añadir una línea aquí y su ruta en config/rutas.php.
     */
    'herramientas' => [
        [
            'etiqueta'    => 'Instrumento de evaluación de BD',
            'descripcion' => 'Documento público de consulta: los 75 controles de administración '
                           . 'de bases de datos conforme a la familia ISO/IEC 27000.',
            'destino'     => '/herramientas/instrumento-bd',
            'icono'       => 'documento',
        ],
        [
            'etiqueta'    => 'Diagnóstico de Salud',
            'descripcion' => 'Levante una auditoría, responda los controles y obtenga el índice '
                           . 'de riesgo con su plan de remediación. Requiere cuenta.',
            'destino'     => '/evaluacion',
            'icono'       => 'tablero',
        ],
    ],

    /**
     * Banner de la portada: hero tipográfico.
     *
     * Aquí hubo antes una tarjeta de índice de riesgo que abría el sitio con un
     * 3,4 sobre 5,0 en rojo y el expediente inventado AUD-0042. No vuelve, y la
     * razón se hereda con este bloque: aquella cifra no tenía sujeto, así que el
     * mal resultado se leía del emisor, y además contradecía a «Retos», que
     * reprocha el cumplimiento declarado sin evidencia.
     *
     * NO SE PUBLICA NINGÚN DATO FABRICADO EN EL BANNER. Las únicas cifras que
     * aparecen son el tamaño del instrumento, y NO se escriben aquí: la vista
     * las cuenta sobre lo que devuelve el repositorio. Si el catálogo cambia,
     * el banner cambia solo. Por eso 'cifras' lleva rótulo e icono pero no
     * valor — el valor sería justo la clase de dato que se desincroniza.
     *
     * Los iconos viven en public/assets/images/icons y se pintan como MÁSCARA,
     * de modo que toman su color de un token; aquí solo va el nombre del
     * archivo. Los originales, con su cuadriculado de falsa transparencia,
     * quedaron en documentacion/design/iconos-origen.
     */
    'hero' => [
        // Badge normativo. Es referencia a norma, así que la vista lo pinta en
        // oro y en mono: el único uso que el sistema visual permite del oro.
        'norma'  => 'ISO/IEC 27002 · 27007 · COBIT 4.1',
        'titulo' => 'Su base de datos guarda lo que la organización no puede perder.',
        'texto'  => 'Consultoría en administración de bases de datos con una auditoría de '
                  . 'seguridad de la información medible, sobre ISO/IEC 27002, 27007 y '
                  . 'COBIT 4.1.',

        'cta_primario'   => ['etiqueta' => 'Solicitar una auditoría', 'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'Ver el instrumento',      'destino' => '/herramientas/instrumento-bd'],

        /**
         * Tamaño del instrumento. Sin 'valor' A PROPÓSITO: lo cuenta la vista
         * sobre el repositorio, que es la única forma de que estas tres cifras
         * no mientan el día que alguien añada un control.
         *
         * El icono va en oro porque lo que rotula ES referencia normativa —los
         * controles, procesos y dominios del instrumento ISO/IEC 27002—, que es
         * el único significado que el sistema visual le concede al oro (§5.2).
         */
        'cifras' => [
            ['clave' => 'controles', 'etiqueta' => 'Controles', 'icono' => 'auditoria.png'],
            ['clave' => 'procesos',  'etiqueta' => 'Procesos',  'icono' => 'monitoreo.png'],
            ['clave' => 'dominios',  'etiqueta' => 'Dominios',  'icono' => 'base-datos.png'],
        ],

        /**
         * Pie del banner: los dominios del instrumento con su número de
         * controles. Es la estructura del método, no el resultado de nadie —
         * enseña QUÉ se mide en vez de afirmar cómo salió una auditoría.
         *
         * La lista la arma la vista desde el repositorio; aquí solo va el
         * rótulo que la encabeza.
         */
        'dominios_rotulo' => 'Lo que se audita',
    ],

    /**
     * Retos.
     *
     * 'eyebrow' es el rótulo en versalitas sobre el título; el encabezado va
     * centrado. La lista se numera sola en la vista (01, 02...), así que el
     * orden de estos elementos ES la numeración.
     */
    'retos' => [
        'eyebrow' => 'Retos',
        'titulo'  => 'Lo que suele estar roto cuando llegamos',
        'lista'   => [
            [
                'titulo' => 'Nadie sabe quién tiene privilegios',
                'texto'  => 'Cuentas heredadas, permisos acumulados y ningún registro de '
                          . 'por qué se otorgaron.',
            ],
            [
                'titulo' => 'El respaldo existe, la restauración no se probó',
                'texto'  => 'Un respaldo que nunca se restauró es una hipótesis, no un control.',
            ],
            [
                'titulo' => 'Cumplimiento declarado sin evidencia',
                'texto'  => 'ISO/IEC 27007 determina la conformidad contra evidencia '
                          . 'verificada, no contra la afirmación del auditado.',
            ],
            [
                'titulo' => 'Hallazgos sin plazo ni responsable',
                'texto'  => 'Sin fecha límite y sin dueño, el hallazgo vuelve idéntico a la '
                          . 'siguiente auditoría.',
            ],
        ],
    ],

    'servicios' => [
        'titulo' => 'Nuestros servicios',
        'texto'  => 'Cobertura completa del ciclo de vida de sus bases de datos, '
                  . 'desde el diseño hasta la operación diaria.',
        'lista'  => [
            [
                'icono'  => 'servidor',
                'titulo' => 'Migración y modernización',
                'texto'  => 'Movemos sus bases de datos a versiones vigentes o a la nube con '
                          . 'un plan de reversión probado y ventanas de indisponibilidad mínimas.',
            ],
            [
                'icono'  => 'rayo',
                'titulo' => 'Optimización de rendimiento',
                'texto'  => 'Análisis de planes de ejecución, diseño de índices y ajuste de '
                          . 'parámetros del motor para recuperar tiempos de respuesta.',
            ],
            [
                'icono'  => 'escudo',
                'titulo' => 'Seguridad y cumplimiento',
                'texto'  => 'Endurecimiento del motor, cifrado, control de accesos y auditoría '
                          . 'alineados a la norma ISO/IEC 27001.',
            ],
            [
                'icono'  => 'respaldo',
                'titulo' => 'Respaldo y recuperación',
                'texto'  => 'Estrategias de respaldo con objetivos RTO y RPO definidos, '
                          . 'y pruebas de restauración documentadas cada trimestre.',
            ],
            [
                'icono'  => 'grafica',
                'titulo' => 'Monitoreo y alertamiento',
                'texto'  => 'Vigilancia continua de disponibilidad, espacio, bloqueos y '
                          . 'procesos críticos, con notificación antes de que el usuario lo note.',
            ],
            [
                'icono'  => 'usuarios',
                'titulo' => 'DBA como servicio',
                'texto'  => 'Un administrador de bases de datos certificado a cargo de su '
                          . 'operación, sin el costo de una plaza de tiempo completo.',
            ],
        ],
    ],

    'metricas' => [
        ['valor' => '99.9%', 'etiqueta' => 'Disponibilidad comprometida'],
        ['valor' => '<15m',  'etiqueta' => 'Tiempo de respuesta a incidentes críticos'],
        ['valor' => '40+',   'etiqueta' => 'Instancias bajo administración'],
        ['valor' => '24/7',  'etiqueta' => 'Cobertura de monitoreo'],
    ],

    'motores' => ['Oracle', 'MySQL', 'PostgreSQL', 'SQL Server', 'MongoDB', 'MariaDB'],

    /**
     * Stack tecnológico: los motores sobre los que se presta el servicio.
     *
     * 'logos' alimenta el carrusel de la sección. Las medidas 'ancho' y 'alto'
     * son las reales del archivo: se imprimen en el <img> para que el navegador
     * reserve el espacio y la fila no salte mientras cargan las imágenes.
     *
     * El orden de la lista es el orden en que desfilan los logotipos.
     */
    'stack' => [
        'etiqueta' => 'Stack tecnológico',
        'titulo'   => 'Motores que revisamos, mantenemos y soportamos',
        'logos'    => [
            ['nombre' => 'Oracle Database', 'imagen' => 'assets/images/oracle.png', 'ancho' => 221, 'alto' => 240],
            ['nombre' => 'MySQL',           'imagen' => 'assets/images/mysql.png', 'ancho' => 427, 'alto' => 240],
            ['nombre' => 'PostgreSQL',      'imagen' => 'assets/images/postgresql.png', 'ancho' => 610, 'alto' => 280],
            ['nombre' => 'SQL Server',      'imagen' => 'assets/images/sql-server.png', 'ancho' => 295, 'alto' => 240],
            ['nombre' => 'MariaDB',         'imagen' => 'assets/images/mariadb.png', 'ancho' => 480, 'alto' => 240],
            ['nombre' => 'MongoDB',         'imagen' => 'assets/images/mongodb.png', 'ancho' => 399, 'alto' => 240],
        ],
    ],

    'caso' => [
        'sector'  => 'Sector financiero',
        'cita'    => 'Pasamos de once horas de indisponibilidad al año a menos de cuarenta '
                   . 'minutos. El cambio no fue comprar más servidores: fue ordenar la operación.',
        'autor'   => 'Dirección de Tecnología',
        'empresa' => 'Institución financiera regional',
        'logros'  => [
            'Reducción del 94 % en tiempo fuera de servicio',
            'Restauración verificada cada trimestre',
            'Cumplimiento del anexo A de ISO/IEC 27001',
        ],
    ],

    /**
     * Testimonios de clientes.
     *
     * DATOS DE PRUEBA: los cinco registros son de ejemplo mientras no exista la
     * tabla correspondiente. 'puntaje' admite medios puntos (4, 4.5, 5); la
     * entidad App\Models\Entidades\Testimonio lo acota al rango 0–5 y lo
     * redondea al medio más cercano, así que un valor fuera de rango no rompe
     * la maqueta.
     */
    'testimonios' => [
        'etiqueta' => 'Testimonios',
        'titulo'   => 'Quienes ya pasaron por la auditoría',
        'texto'    => 'Opiniones de equipos de tecnología que confiaron la operación '
                    . 'de sus bases de datos a nuestra consultoría.',
        'lista'    => [
            [
                'nombre'      => 'Marcela Vindas Chaves',
                'foto'        => 'assets/images/testimonios/marcela-vindas.jpg',
                'cargo'       => 'Directora de Tecnología',
                'iniciales'   => 'MV',
                'puntaje'     => 5,
                'referencia'  => 'Auditoría AUD-0031 · 75 controles',
                'descripcion' => 'Migramos un Oracle 11g con quince años encima y no perdimos '
                               . 'una sola transacción. El plan de reversión estaba escrito antes '
                               . 'de tocar nada, y eso fue lo que nos dio tranquilidad.',
            ],
            [
                'nombre'      => 'Andrés Quesada Mora',
                'foto'        => 'assets/images/testimonios/andres-quesada.jpg',
                'cargo'       => 'Jefe de Auditoría Interna',
                'iniciales'   => 'AQ',
                'puntaje'     => 4.5,
                'referencia'  => 'Auditoría AUD-0044 · 75 controles',
                'descripcion' => 'El informe de auditoría fue directo al grano: hallazgos '
                               . 'priorizados y con responsable. Solo nos habría gustado tener '
                               . 'antes el tablero de seguimiento.',
            ],
            [
                'nombre'      => 'Laura Céspedes Rojas',
                'foto'        => 'assets/images/testimonios/laura-cespedes.jpg',
                'cargo'       => 'Coordinadora de Analítica',
                'iniciales'   => 'LC',
                'puntaje'     => 5,
                'referencia'  => 'Auditoría AUD-0052 · 75 controles',
                // Con este abre el carrusel.
                'destacado'   => true,
                'descripcion' => 'Los reportes mensuales pasaron de tardar cuarenta minutos a '
                               . 'menos de dos. Fue trabajo de índices y de reescribir tres '
                               . 'consultas, no de comprar hardware.',
            ],
            [
                'nombre'      => 'Diego Hernández Alfaro',
                'foto'        => 'assets/images/testimonios/diego-hernandez.jpg',
                'cargo'       => 'Jefe de Infraestructura',
                'iniciales'   => 'DH',
                'puntaje'     => 4,
                'referencia'  => 'Auditoría AUD-0058 · 75 controles',
                'descripcion' => 'Nos ordenaron los respaldos y ahora la restauración se prueba '
                               . 'cada trimestre. El acompañamiento fue bueno; la coordinación '
                               . 'de las ventanas de mantenimiento tomó más de lo previsto.',
            ],
            [
                'nombre'      => 'Sofía Ramírez Delgado',
                'foto'        => 'assets/images/testimonios/sofia-ramirez.jpg',
                'cargo'       => 'Oficial de Seguridad de la Información',
                'iniciales'   => 'SR',
                'puntaje'     => 4.5,
                'referencia'  => 'Auditoría AUD-0063 · 75 controles',
                'descripcion' => 'Pasamos la revisión de ISO/IEC 27001 sin observaciones en el '
                               . 'control de accesos. Documentaron cada permiso y nos enseñaron '
                               . 'a mantenerlo.',
            ],
        ],
    ],

    'equipo' => [
        [
            'nombre'      => 'Benjamín Alexander Solano Ortega',
            'rol'         => 'Seguridad y cumplimiento',
            'iniciales'   => 'BS',
            'descripcion' => 'Control de accesos, auditoría y alineación normativa.',
        ],
        [
            'nombre'      => 'Camila Fallas Jiménez',
            'rol'         => 'Rendimiento y continuidad',
            'iniciales'   => 'CF',
            'descripcion' => 'Optimización de consultas, respaldos y recuperación.',
        ],
        [
            'nombre'      => 'José Pablo Fernández Sandoval',
            'rol'         => 'Consultor líder',
            'iniciales'   => 'JF',
            'descripcion' => 'Arquitectura de datos y relación con el cliente.',
        ],
        [
            'nombre'      => 'Minor Brenes',
            'rol'         => 'Migración y monitoreo',
            'iniciales'   => 'MB',
            'descripcion' => 'Modernización de motores y vigilancia continua de la operación.',
        ],
    ],

    /**
     * Planes del servicio.
     *
     * 'precio' y 'periodo' van separados porque la vista los compone con dos
     * tamaños distintos; la cifra se imprime con la clase tabular.
     *
     * 'destacado' debe ser verdadero en UN solo plan: es el que la sección
     * levanta con el tinte primario y la insignia. Dos destacados dejarían la
     * comparación sin punto de apoyo.
     */
    'planes' => [
        'etiqueta' => 'Planes',
        'titulo'   => 'Planes y precios',
        'texto'    => 'Medir el riesgo de sus bases de datos no cuesta nada. '
                    . 'Se paga solo cuando la medición pasa a ser vigilancia continua.',
        'lista'    => [
            [
                'nombre'    => 'Gratuito',
                'precio'    => '$0',
                'periodo'   => '/mes',
                'resumen'   => 'Para el equipo que se mide por primera vez',
                'insignia'  => '',
                'destacado' => false,
                'accion'    => [
                    'etiqueta' => 'Comenzar la medición',
                    'destino'  => '/herramientas/instrumento-bd',
                ],
                'incluye'   => [
                    'Acceso completo al instrumento de medición: los 75 controles',
                    'Descarga ilimitada del informe en PDF, CSV y JSON',
                    'Marco normativo y referencias ISO/IEC 27002 · 27007',
                ],
            ],
            [
                'nombre'    => 'Deluxe',
                'precio'    => '$20',
                'periodo'   => '/mes',
                'resumen'   => 'Para la operación que ya depende de sus bases de datos',
                // La palabra existe para que «el plan recomendado» no se
                // comunique solo con el tinte de la tarjeta.
                'insignia'  => 'Recomendado',
                'destacado' => true,
                'accion'    => [
                    'etiqueta' => 'Solicitar el plan',
                    'destino'  => '#contacto',
                ],
                'incluye'   => [
                    'Todo lo del plan Gratuito',
                    'Monitor de salud para hasta 5 bases de datos',
                    'Almacenamiento de las mediciones anteriores del instrumento',
                    'Estadísticas detalladas por dominio, proceso y control',
                    'Informe con las equivalencias entre los tres marcos',
                ],
                /*
                 * Marcos que el informe del plan pagado cruza. Van aparte de
                 * 'incluye' porque la vista los pinta como badges de norma: es
                 * referencia normativa, el ÚNICO significado que el sistema
                 * visual le permite al oro.
                 */
                'normas'    => [
                    'etiqueta' => 'Marcos normativos',
                    'lista'    => [
                        'ISO/IEC 27002 · 27007',
                        'COBIT 4.1',
                        'NIST SP 800-53',
                    ],
                ],
            ],
        ],
    ],

    'contacto' => [
        'titulo' => '¿Hablamos de sus bases de datos?',
        'texto'  => 'Realizamos un diagnóstico inicial sin costo: revisamos configuración, '
                  . 'respaldos y accesos, y entregamos un informe con hallazgos priorizados.',
    ],

    /**
     * Preguntas frecuentes (/preguntas-frecuentes).
     *
     * Quince preguntas repartidas en tres grupos, y el reparto es la respuesta
     * a tres dudas distintas que llegan juntas: qué hace el sistema, si le sirve
     * a quien pregunta, y si va a seguir existiendo el año que viene.
     *
     * Las del tercer grupo son PROPUESTAS, no compromisos con fecha. Se
     * redactan en condicional a propósito: prometer un módulo que todavía no
     * tiene esquema es la forma más barata de perder la confianza que las otras
     * catorce respuestas acaban de ganar.
     */
    'preguntas' => [
        'eyebrow' => 'Preguntas frecuentes',
        'titulo'  => 'Cómo funciona, para quién es y hacia dónde va',
        'texto'   => 'Quince respuestas sobre el instrumento de 75 controles, el módulo de '
                   . 'evaluación de riesgo y el camino que sigue el producto. Si la suya no '
                   . 'está aquí, el formulario de contacto llega al mismo equipo que audita.',

        // Rótulo del índice de la página. Es copia, no cadena de interfaz: por
        // eso vive aquí, junto al texto que encabeza, y no en config/idiomas.
        'indice' => 'En esta página',

        // Cierre: la pregunta que falta. Un listado de respuestas termina
        // siempre en la que no estaba, y dejarla sin salida obliga a volver a
        // buscar el pie con la vista.
        'cierre' => [
            'titulo'    => '¿Su pregunta no está en la lista?',
            'texto'     => 'Escríbanos y le responde quien audita, no un formulario. Si prefiere '
                         . 'empezar por su cuenta, el instrumento de 75 controles está abierto y '
                         . 'no pide datos de nadie.',
            'principal' => ['etiqueta' => 'Escribir al equipo',  'destino' => '#contacto'],
            'secundaria' => ['etiqueta' => 'Ver el instrumento', 'destino' => '/herramientas/instrumento-bd'],
        ],

        'grupos' => [
            [
                'clave'  => 'funcionamiento',
                'titulo' => 'Cómo funciona el sistema',
                'texto'  => 'Del cuestionario al índice de riesgo, y de ahí al plan de '
                          . 'remediación.',
                'lista'  => [
                    [
                        'pregunta'  => '¿Qué es exactamente Rivendel?',
                        'respuesta' => 'Es una consultoría en administración de bases de datos y, '
                                     . 'a la vez, el sistema con el que se ejecuta su auditoría. '
                                     . 'No entregamos una opinión escrita a mano: aplicamos un '
                                     . 'instrumento de 75 controles agrupados en 25 procesos y 7 '
                                     . 'dominios, y de esas respuestas sale un índice de riesgo '
                                     . 'que puede volver a calcularse meses después con el mismo '
                                     . 'método.',
                    ],
                    [
                        'pregunta'  => '¿En qué se basa el instrumento de 75 controles?',
                        'respuesta' => 'En la familia ISO/IEC 27000 —27002 para los controles y '
                                     . '27007 para cómo se auditan— con el aporte de COBIT 4.1 en '
                                     . 'la parte de gobierno. Cada control declara de qué norma '
                                     . 'viene, y esa referencia se imprime en el reporte: quien '
                                     . 'lea el hallazgo puede ir a la fuente sin preguntarnos.',
                    ],
                    [
                        'pregunta'  => '¿Cómo se responde una auditoría?',
                        'respuesta' => 'El auditor abre una auditoría sobre una organización y un '
                                     . 'alcance concretos, y recorre los controles uno por uno. '
                                     . 'Cada control se responde con cumple, no cumple o no aplica, '
                                     . 'un nivel de madurez de 1 a 5 y el hallazgo que sustenta esa '
                                     . 'respuesta. Nada obliga a terminarla de una sentada: el '
                                     . 'avance queda guardado control por control.',
                    ],
                    [
                        'pregunta'  => '¿Cómo se calcula el índice de riesgo?',
                        'respuesta' => 'Con procedimientos almacenados en la base de datos, no con '
                                     . 'una fórmula escondida en la aplicación. El cumplimiento es '
                                     . 'la razón entre controles que cumplen y controles aplicables '
                                     . '—los marcados «no aplica» salen del divisor, no cuentan como '
                                     . 'falla—, y el índice combina ese cumplimiento con la madurez '
                                     . 'promedio en una escala de 1 a 5.',
                    ],
                    [
                        'pregunta'  => '¿Qué pasa después de cerrar una auditoría?',
                        'respuesta' => 'Empieza la parte que de verdad reduce el riesgo. Cada '
                                     . 'control incumplido puede abrir una remediación con '
                                     . 'responsable y fecha comprometida; el sistema avisa cuando '
                                     . 'una vence, y al programar la re-auditoría se vuelve a '
                                     . 'evaluar solo lo remediado. La comparación entre dos '
                                     . 'auditorías de la misma organización muestra qué se movió y '
                                     . 'qué quedó igual.',
                    ],
                    [
                        'pregunta'  => '¿Qué entrega el sistema al final?',
                        'respuesta' => 'Un reporte ejecutivo escrito para dos lectores a la vez: '
                                     . 'abre con el índice general, la matriz de dominios y los '
                                     . 'hallazgos críticos —para quien firma el presupuesto— y '
                                     . 'sigue con el plan de remediación control por control, con '
                                     . 'su norma de origen, para quien tiene que arreglarlo. Se '
                                     . 'imprime o se guarda en PDF sin los controles de navegación, '
                                     . 'y el instrumento público además exporta el avance en CSV y '
                                     . 'JSON.',
                    ],
                ],
            ],
            [
                'clave'  => 'publico',
                'titulo' => 'A quién está dirigido',
                'texto'  => 'Quién lo usa, quién lo lee y qué hace falta para empezar.',
                'lista'  => [
                    [
                        'pregunta'  => '¿A qué tipo de organización le sirve?',
                        'respuesta' => 'A la que ya depende de sus bases de datos y no sabe decir '
                                     . 'cuánto riesgo carga: cooperativas, instituciones públicas, '
                                     . 'empresas medianas con un ERP encima de un motor que nadie '
                                     . 'audita desde hace años. No hace falta un departamento de '
                                     . 'seguridad; hace falta alguien que pueda responder cómo se '
                                     . 'respaldan y quién tiene acceso.',
                    ],
                    [
                        'pregunta'  => '¿Quién usa el sistema y con qué rol?',
                        'respuesta' => 'Hay dos roles. El auditor levanta auditorías, responde '
                                     . 'controles y da seguimiento a las remediaciones de su propia '
                                     . 'cartera: no ve el trabajo de otro consultor. El '
                                     . 'administrador de base de datos mantiene además el catálogo '
                                     . 'maestro —dominios, procesos y controles—, que es lo que '
                                     . 'todos los demás evalúan.',
                    ],
                    [
                        'pregunta'  => '¿Sirve si soy el DBA de la organización y no un consultor?',
                        'respuesta' => 'Sí, y es uno de los usos previstos. El instrumento público '
                                     . 'se responde sin cuenta y sin enviar nada a ningún servidor: '
                                     . 'sirve como autodiagnóstico antes de contratar a nadie. Lo '
                                     . 'que cambia con el módulo interno es la memoria —histórico, '
                                     . 'comparación y remediaciones—, no el contenido de las '
                                     . 'preguntas.',
                    ],
                    [
                        'pregunta'  => '¿Qué se necesita para empezar?',
                        'respuesta' => 'Para el instrumento público, un navegador. Para el módulo '
                                     . 'de evaluación, una cuenta de auditor y los datos de la '
                                     . 'organización que se va a auditar. No pedimos acceso a sus '
                                     . 'bases de datos: la auditoría se levanta con entrevista y '
                                     . 'evidencia, y las credenciales de producción no salen de su '
                                     . 'organización.',
                    ],
                ],
            ],
            [
                'clave'  => 'futuro',
                'titulo' => 'Propuestas de futuros proyectos',
                'texto'  => 'Lo que está en estudio para las próximas etapas. Son propuestas, '
                          . 'no funciones disponibles hoy.',
                'lista'  => [
                    [
                        'pregunta'  => '¿Habrá monitoreo continuo de las bases de datos?',
                        'respuesta' => 'Es la propuesta principal. Hoy el panel ya lleva la ficha '
                                     . 'de bases de datos conectadas, que es la previsualización de '
                                     . 'ese módulo: la misma lista de instancias que alimentaría el '
                                     . 'monitoreo. Lo que falta es leer de cada una la latencia, el '
                                     . 'espacio, los bloqueos y el último respaldo, para que el '
                                     . 'diagnóstico deje de ser una foto trimestral y pase a ser una '
                                     . 'señal continua.',
                    ],
                    [
                        'pregunta'  => '¿Se ampliará a otros motores además de Oracle?',
                        'respuesta' => 'El instrumento ya es independiente del motor: los 75 '
                                     . 'controles preguntan por respaldos, accesos y cifrado, no '
                                     . 'por la sintaxis de un fabricante. Lo que está en estudio es '
                                     . 'que el propio sistema pueda apoyarse en PostgreSQL o SQL '
                                     . 'Server además de Oracle, cambiando la capa de acceso a datos '
                                     . 'sin tocar las vistas.',
                    ],
                    [
                        'pregunta'  => '¿Se podrá adjuntar evidencia a cada hallazgo?',
                        'respuesta' => 'Está propuesto. Hoy el hallazgo es texto, y eso obliga a '
                                     . 'describir con palabras una captura de pantalla o la salida '
                                     . 'de una consulta. Adjuntar el archivo al control cerraría esa '
                                     . 'distancia, con la condición de que la evidencia herede el '
                                     . 'mismo control de acceso que la auditoría a la que pertenece.',
                    ],
                    [
                        'pregunta'  => '¿Habrá alertas y avisos automáticos?',
                        'respuesta' => 'El sistema ya sabe qué remediaciones están vencidas y las '
                                     . 'muestra al entrar. El paso siguiente propuesto es que ese '
                                     . 'aviso salga del sistema —correo al responsable antes del '
                                     . 'vencimiento, no después— y que la re-auditoría se proponga '
                                     . 'sola cuando todas las remediaciones de una auditoría queden '
                                     . 'cerradas.',
                    ],
                    [
                        'pregunta'  => '¿Se abrirá una interfaz para integrarlo con otros sistemas?',
                        'respuesta' => 'Es la propuesta de más largo plazo, y la que menos sentido '
                                     . 'tiene apresurar. Publicar el índice de riesgo y el estado de '
                                     . 'las remediaciones hacia un tablero corporativo o un sistema '
                                     . 'de tiquetes solo vale la pena cuando el modelo de datos esté '
                                     . 'asentado; hacerlo antes obliga a mantener una interfaz '
                                     . 'pública encima de un esquema que todavía se mueve.',
                    ],
                ],
            ],
        ],
    ],
];
