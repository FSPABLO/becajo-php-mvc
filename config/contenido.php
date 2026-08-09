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
        'nombre'   => 'Becajo',
        'eslogan'  => 'Consultoría en Administración de Bases de Datos',
        'correo'   => 'contacto@becajo.cr',
        'telefono' => '+506 0000 0000',
        'ciudad'   => 'San José, Costa Rica',
        'anio'     => 2026,
    ],

    'meta' => [
        'titulo'      => 'Becajo | Consultoría en Administración de Bases de Datos',
        'descripcion' => 'Migración, optimización, seguridad y monitoreo de bases de datos '
                       . 'empresariales. Reducimos el riesgo operativo de su información crítica.',
    ],

    'navegacion' => [
        ['etiqueta' => 'Retos',      'destino' => '#retos'],
        ['etiqueta' => 'Servicios',  'destino' => '#servicios'],
        ['etiqueta' => 'Resultados', 'destino' => '#resultados'],
        ['etiqueta' => 'Equipo',     'destino' => '#equipo'],
        ['etiqueta' => 'Contacto',   'destino' => '#contacto'],
    ],

    /**
     * Herramientas internas del sitio.
     *
     * Alimentan el menú desplegable "Herramientas" del encabezado. Agregar una
     * herramienta nueva es añadir una línea aquí y su ruta en config/rutas.php.
     */
    'herramientas' => [
        [
            'etiqueta'    => 'Instrumento de Consultoría',
            'descripcion' => 'Evaluación de 75 controles de administración de bases de datos '
                           . 'conforme a la familia ISO/IEC 27000.',
            'destino'     => '/herramientas/instrumento-bd',
            'icono'       => 'documento',
        ],
    ],

    'hero' => [
        'etiqueta'  => 'Administración de bases de datos empresariales',
        'titulo'    => 'Sus datos, bajo control.',
        'resaltado' => 'Su operación, sin interrupciones.',
        'texto'     => 'Becajo acompaña a las organizaciones en la administración, protección y '
                     . 'optimización de sus bases de datos. Menos tiempo fuera de servicio, '
                     . 'menos riesgo, decisiones respaldadas por información confiable.',
        'puntos'    => [
            'Diagnóstico inicial sin costo',
            'Respuesta a incidentes críticos en menos de 15 minutos',
            'Informe con hallazgos priorizados en cinco días hábiles',
        ],
        'cta_primario'   => ['etiqueta' => 'Solicitar diagnóstico', 'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'Conocer servicios',     'destino' => '#servicios'],

        /**
         * Tablero de ejemplo que ilustra el hero.
         *
         * El campo 'estado' solo admite los valores exito, aviso y alerta: la
         * vista los traduce a clases de color con una lista blanca, para que un
         * valor inventado aquí no inyecte clases arbitrarias en el HTML.
         */
        'panel' => [
            'titulo'    => 'Estado de la operación',
            'subtitulo' => 'Últimas 24 horas',
            'filas'     => [
                ['etiqueta' => 'Disponibilidad',        'valor' => '99.98 %',      'estado' => 'exito'],
                ['etiqueta' => 'Respaldos verificados', 'valor' => '12 / 12',      'estado' => 'exito'],
                ['etiqueta' => 'Consultas lentas',      'valor' => '3 en revisión', 'estado' => 'aviso'],
                ['etiqueta' => 'Accesos privilegiados', 'valor' => 'Auditados',    'estado' => 'exito'],
            ],
            'pie' => 'Ejemplo del tablero que entregamos con el servicio de monitoreo.',
        ],
    ],

    'retos' => [
        'titulo' => 'Los problemas que resolvemos',
        'texto'  => 'La mayoría de las organizaciones no pierde información por un ataque '
                  . 'espectacular, sino por descuidos acumulados en la operación diaria.',
        'lista'  => [
            [
                'titulo' => 'Rendimiento degradado',
                'texto'  => 'Consultas que tardan minutos, índices ausentes y usuarios '
                          . 'esperando frente a la pantalla.',
            ],
            [
                'titulo' => 'Respaldos sin verificar',
                'texto'  => 'Copias que se generan cada noche pero que nadie ha intentado '
                          . 'restaurar. Un respaldo no probado no es un respaldo.',
            ],
            [
                'titulo' => 'Accesos sin control',
                'texto'  => 'Cuentas compartidas, privilegios excesivos y bitácoras que '
                          . 'nadie revisa. El riesgo interno supera al externo.',
            ],
            [
                'titulo' => 'Ausencia de plan de continuidad',
                'texto'  => 'Sin RTO ni RPO definidos, la recuperación ante un incidente '
                          . 'se improvisa en el peor momento posible.',
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
            [
                'icono'          => 'tablero',
                'titulo'         => 'Evaluación de riesgo ISO/IEC 27002',
                'texto'          => 'Auditamos la administración de sus bases de datos contra 75 '
                                   . 'controles ISO/IEC 27002 y entregamos cumplimiento, madurez y '
                                   . 'exposición al riesgo por dominio.',
                'enlace'         => '/ingresar',
                'etiquetaEnlace' => 'Iniciar evaluación',
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
            ['nombre' => 'Oracle Database', 'imagen' => 'assets/images/oracle.jpg',     'ancho' => 663,  'alto' => 720],
            ['nombre' => 'MySQL',           'imagen' => 'assets/images/mysql.png',      'ancho' => 1280, 'alto' => 720],
            ['nombre' => 'PostgreSQL',      'imagen' => 'assets/images/postgresql.png', 'ancho' => 610,  'alto' => 280],
            ['nombre' => 'SQL Server',      'imagen' => 'assets/images/sql-server.png', 'ancho' => 614,  'alto' => 499],
            ['nombre' => 'MariaDB',         'imagen' => 'assets/images/mariadb.jpg',    'ancho' => 1024, 'alto' => 512],
            ['nombre' => 'MongoDB',         'imagen' => 'assets/images/mongodb.jpg',    'ancho' => 714,  'alto' => 430],
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
        'titulo'   => 'Lo que dicen nuestros clientes',
        'texto'    => 'Opiniones de equipos de tecnología que confiaron la operación '
                    . 'de sus bases de datos a nuestra consultoría.',
        'lista'    => [
            [
                'nombre'      => 'Marcela Vindas Chaves',
                'cargo'       => 'Directora de Tecnología',
                'iniciales'   => 'MV',
                'puntaje'     => 5,
                'descripcion' => 'Migramos un Oracle 11g con quince años encima y no perdimos '
                               . 'una sola transacción. El plan de reversión estaba escrito antes '
                               . 'de tocar nada, y eso fue lo que nos dio tranquilidad.',
            ],
            [
                'nombre'      => 'Andrés Quesada Mora',
                'cargo'       => 'Jefe de Auditoría Interna',
                'iniciales'   => 'AQ',
                'puntaje'     => 4.5,
                'descripcion' => 'El informe de auditoría fue directo al grano: hallazgos '
                               . 'priorizados y con responsable. Solo nos habría gustado tener '
                               . 'antes el tablero de seguimiento.',
            ],
            [
                'nombre'      => 'Laura Céspedes Rojas',
                'cargo'       => 'Coordinadora de Analítica',
                'iniciales'   => 'LC',
                'puntaje'     => 5,
                'descripcion' => 'Los reportes mensuales pasaron de tardar cuarenta minutos a '
                               . 'menos de dos. Fue trabajo de índices y de reescribir tres '
                               . 'consultas, no de comprar hardware.',
            ],
            [
                'nombre'      => 'Diego Hernández Alfaro',
                'cargo'       => 'Jefe de Infraestructura',
                'iniciales'   => 'DH',
                'puntaje'     => 4,
                'descripcion' => 'Nos ordenaron los respaldos y ahora la restauración se prueba '
                               . 'cada trimestre. El acompañamiento fue bueno; la coordinación '
                               . 'de las ventanas de mantenimiento tomó más de lo previsto.',
            ],
            [
                'nombre'      => 'Sofía Ramírez Delgado',
                'cargo'       => 'Oficial de Seguridad de la Información',
                'iniciales'   => 'SR',
                'puntaje'     => 4.5,
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

    'contacto' => [
        'titulo' => '¿Hablamos de sus bases de datos?',
        'texto'  => 'Realizamos un diagnóstico inicial sin costo: revisamos configuración, '
                  . 'respaldos y accesos, y entregamos un informe con hallazgos priorizados.',
    ],
];
