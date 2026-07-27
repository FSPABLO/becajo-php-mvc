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
        'correo'   => 'contacto@becajo.mx',
        'telefono' => '+52 55 0000 0000',
        'ciudad'   => 'México',
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

    'hero' => [
        'etiqueta'  => 'Administración de bases de datos empresariales',
        'titulo'    => 'Sus datos, bajo control.',
        'resaltado' => 'Su operación, sin interrupciones.',
        'texto'     => 'Becajo acompaña a las organizaciones en la administración, protección y '
                     . 'optimización de sus bases de datos. Menos tiempo fuera de servicio, '
                     . 'menos riesgo, decisiones respaldadas por información confiable.',
        'cta_primario'   => ['etiqueta' => 'Solicitar diagnóstico', 'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'Conocer servicios',     'destino' => '#servicios'],
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
        ],
    ],

    'metricas' => [
        ['valor' => '99.9%', 'etiqueta' => 'Disponibilidad comprometida'],
        ['valor' => '<15m',  'etiqueta' => 'Tiempo de respuesta a incidentes críticos'],
        ['valor' => '40+',   'etiqueta' => 'Instancias bajo administración'],
        ['valor' => '24/7',  'etiqueta' => 'Cobertura de monitoreo'],
    ],

    'motores' => ['Oracle', 'MySQL', 'PostgreSQL', 'SQL Server', 'MongoDB', 'MariaDB'],

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
    ],

    'contacto' => [
        'titulo' => '¿Hablamos de sus bases de datos?',
        'texto'  => 'Realizamos un diagnóstico inicial sin costo: revisamos configuración, '
                  . 'respaldos y accesos, y entregamos un informe con hallazgos priorizados.',
    ],
];
