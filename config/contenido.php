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

    'navegacion' => [
        ['etiqueta' => 'Retos',      'destino' => '#retos'],
        ['etiqueta' => 'Servicios',  'destino' => '#servicios'],
        ['etiqueta' => 'Stack',      'destino' => '#stack'],
        ['etiqueta' => 'Resultados', 'destino' => '#resultados'],
        ['etiqueta' => 'Equipo',     'destino' => '#equipo'],
        ['etiqueta' => 'Planes',     'destino' => '#planes'],
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

    'hero' => [
        // Badge normativo. Es referencia a norma, así que la vista lo pinta en
        // oro y en mono: el único uso que el sistema visual permite del oro.
        'norma'  => 'ISO/IEC 27002 · 27007 · COBIT 4.1',
        'titulo' => 'Su base de datos guarda lo que la organización no puede perder.',
        'texto'  => 'Consultoría en administración de bases de datos con una auditoría de '
                  . 'seguridad de la información medible: 75 controles, 25 procesos, 7 dominios '
                  . 'y un índice de riesgo que se puede seguir en el tiempo.',

        'cta_primario'   => ['etiqueta' => 'Solicitar una auditoría', 'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'Ver el instrumento',      'destino' => '/herramientas/instrumento-bd'],

        // Tamaño del instrumento, en cifras tabulares.
        'cifras' => [
            ['valor' => '75', 'etiqueta' => 'Controles'],
            ['valor' => '25', 'etiqueta' => 'Procesos'],
            ['valor' => '7',  'etiqueta' => 'Dominios'],
        ],

        /**
         * Tarjeta de índice de riesgo: enseña el producto real en vez de
         * describirlo.
         *
         * Los campos 'estado' solo admiten los tonos de la escala semántica
         * (ok, warn, bad, crit, na): la vista los pasa por pill(), que exige
         * ícono y etiqueta, de modo que un valor inventado aquí degrada a un
         * pill neutro en vez de colar clases arbitrarias en el HTML.
         *
         * Los porcentajes van aparte del texto porque uno dibuja la barra y el
         * otro se lee: 'Madurez promedio' se muestra como «2,6 / 5,0» pero la
         * barra ocupa el 52 %.
         */
        'panel' => [
            'titulo'          => 'Índice general de riesgo',
            'referencia'      => 'AUD-0042',
            'indice'          => '3,4',
            'indice_maximo'   => 'de 5,0',
            'estado'          => 'bad',
            'estado_etiqueta' => 'Requiere atención',
            'barras' => [
                ['etiqueta' => 'Cumplimiento general',  'valor' => '68,0 %',    'porcentaje' => 68],
                ['etiqueta' => 'Madurez promedio',      'valor' => '2,6 / 5,0', 'porcentaje' => 52],
                ['etiqueta' => 'Controles respondidos', 'valor' => '61 / 75',   'porcentaje' => 81],
            ],
            'conteos' => [
                ['estado' => 'ok',  'etiqueta' => 'Cumple 41'],
                ['estado' => 'bad', 'etiqueta' => 'No cumple 14'],
                ['estado' => 'na',  'etiqueta' => 'No aplica 6'],
            ],
        ],
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
];
