<?php

declare(strict_types=1);

/**
 * English version of config/contenido.php. Same keys, same shape — only the
 * text changes. Loaded instead of the Spanish file when the visitor picks
 * English from the language selector.
 */

return [

    'empresa' => [
        'nombre'   => 'Becajo',
        'eslogan'  => 'Database Administration Consulting',
        'correo'   => 'contacto@becajo.cr',
        'telefono' => '+506 0000 0000',
        'ciudad'   => 'San José, Costa Rica',
        'anio'     => 2026,
    ],

    'meta' => [
        'titulo'      => 'Becajo | Database Administration Consulting',
        'descripcion' => 'Migration, optimization, security and monitoring for enterprise '
                       . 'databases. We reduce the operational risk of your critical information.',
    ],

    'navegacion' => [
        ['etiqueta' => 'Challenges', 'destino' => '#retos'],
        ['etiqueta' => 'Services',   'destino' => '#servicios'],
        ['etiqueta' => 'Results',    'destino' => '#resultados'],
        ['etiqueta' => 'Team',       'destino' => '#equipo'],
        ['etiqueta' => 'Contact',    'destino' => '#contacto'],
    ],

    'herramientas' => [
        [
            'etiqueta'    => 'Consulting Instrument',
            'descripcion' => 'Assessment of 75 database administration controls '
                           . 'aligned with the ISO/IEC 27000 family.',
            'destino'     => '/herramientas/instrumento-bd',
            'icono'       => 'documento',
        ],
    ],

    'hero' => [
        'etiqueta'  => 'Enterprise database administration',
        'titulo'    => 'Your data, under control.',
        'resaltado' => 'Your operation, uninterrupted.',
        'texto'     => 'Becajo supports organizations in administering, protecting and '
                     . 'optimizing their databases. Less downtime, less risk, decisions backed '
                     . 'by reliable information.',
        'cta_primario'   => ['etiqueta' => 'Request a diagnosis', 'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'See our services',    'destino' => '#servicios'],
    ],

    'retos' => [
        'titulo' => 'The problems we solve',
        'texto'  => 'Most organizations don\'t lose data to a spectacular attack, but to '
                  . 'small oversights that pile up during day-to-day operation.',
        'lista'  => [
            [
                'titulo' => 'Degraded performance',
                'texto'  => 'Queries that take minutes, missing indexes, and users '
                          . 'staring at the screen waiting.',
            ],
            [
                'titulo' => 'Unverified backups',
                'texto'  => 'Copies generated every night that nobody has ever tried to '
                          . 'restore. An untested backup is not a backup.',
            ],
            [
                'titulo' => 'Uncontrolled access',
                'texto'  => 'Shared accounts, excessive privileges, and logs that nobody '
                          . 'reviews. Internal risk outweighs external risk.',
            ],
            [
                'titulo' => 'No continuity plan',
                'texto'  => 'Without a defined RTO or RPO, recovery from an incident '
                          . 'gets improvised at the worst possible moment.',
            ],
        ],
    ],

    'servicios' => [
        'titulo' => 'Our services',
        'texto'  => 'Full coverage of your database lifecycle, from design to '
                  . 'day-to-day operation.',
        'lista'  => [
            [
                'icono'  => 'servidor',
                'titulo' => 'Migration and modernization',
                'texto'  => 'We move your databases to current versions or to the cloud with '
                          . 'a tested rollback plan and minimal downtime windows.',
            ],
            [
                'icono'  => 'rayo',
                'titulo' => 'Performance optimization',
                'texto'  => 'Execution plan analysis, index design and engine parameter '
                          . 'tuning to bring response times back down.',
            ],
            [
                'icono'  => 'escudo',
                'titulo' => 'Security and compliance',
                'texto'  => 'Engine hardening, encryption, access control and auditing '
                          . 'aligned with ISO/IEC 27001.',
            ],
            [
                'icono'  => 'respaldo',
                'titulo' => 'Backup and recovery',
                'texto'  => 'Backup strategies with defined RTO and RPO targets, and '
                          . 'documented restore drills every quarter.',
            ],
            [
                'icono'  => 'grafica',
                'titulo' => 'Monitoring and alerting',
                'texto'  => 'Continuous watch over availability, storage, locks and '
                          . 'critical processes, with notifications before the user notices.',
            ],
            [
                'icono'  => 'usuarios',
                'titulo' => 'DBA as a service',
                'texto'  => 'A certified database administrator running your operation, '
                          . 'without the cost of a full-time position.',
            ],
            [
                'icono'          => 'tablero',
                'titulo'         => 'ISO/IEC 27002 risk assessment',
                'texto'          => 'We audit your database administration against 75 '
                                   . 'ISO/IEC 27002 controls and deliver compliance, maturity and '
                                   . 'risk exposure by domain.',
                'enlace'         => '/ingresar',
                'etiquetaEnlace' => 'Start assessment',
            ],
        ],
    ],

    'metricas' => [
        ['valor' => '99.9%', 'etiqueta' => 'Committed availability'],
        ['valor' => '<15m',  'etiqueta' => 'Critical incident response time'],
        ['valor' => '40+',   'etiqueta' => 'Instances under administration'],
        ['valor' => '24/7',  'etiqueta' => 'Monitoring coverage'],
    ],

    'motores' => ['Oracle', 'MySQL', 'PostgreSQL', 'SQL Server', 'MongoDB', 'MariaDB'],

    'caso' => [
        'sector'  => 'Financial sector',
        'cita'    => 'We went from eleven hours of downtime a year to under forty '
                   . 'minutes. The change wasn\'t buying more servers — it was putting the '
                   . 'operation in order.',
        'autor'   => 'Technology Management',
        'empresa' => 'Regional financial institution',
        'logros'  => [
            '94% reduction in downtime',
            'Restore verified every quarter',
            'Compliance with ISO/IEC 27001 Annex A',
        ],
    ],

    'equipo' => [
        [
            'nombre'      => 'Benjamín Alexander Solano Ortega',
            'rol'         => 'Security and compliance',
            'iniciales'   => 'BS',
            'descripcion' => 'Access control, auditing and regulatory alignment.',
        ],
        [
            'nombre'      => 'Camila Fallas Jiménez',
            'rol'         => 'Performance and continuity',
            'iniciales'   => 'CF',
            'descripcion' => 'Query optimization, backups and recovery.',
        ],
        [
            'nombre'      => 'José Pablo Fernández Sandoval',
            'rol'         => 'Lead consultant',
            'iniciales'   => 'JF',
            'descripcion' => 'Data architecture and client relationship.',
        ],
        [
            'nombre'      => 'Minor Brenes',
            'rol'         => 'Migration and monitoring',
            'iniciales'   => 'MB',
            'descripcion' => 'Engine modernization and continuous operational monitoring.',
        ],
    ],

    'contacto' => [
        'titulo' => 'Let\'s talk about your databases',
        'texto'  => 'We run a free initial assessment: we review configuration, '
                  . 'backups and access, and deliver a report with prioritized findings.',
    ],
];
