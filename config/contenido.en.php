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
        'puntos'    => [
            'Free initial diagnosis',
            'Critical incidents answered in under 15 minutes',
            'Report with prioritized findings within five business days',
        ],
        'cta_primario'   => ['etiqueta' => 'Request a diagnosis', 'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'See our services',    'destino' => '#servicios'],

        /** Sample dashboard illustrating the hero. See the Spanish file for the notes. */
        'panel' => [
            'titulo'    => 'Operational status',
            'subtitulo' => 'Last 24 hours',
            'filas'     => [
                ['etiqueta' => 'Availability',        'valor' => '99.98%',        'estado' => 'exito'],
                ['etiqueta' => 'Verified backups',    'valor' => '12 / 12',       'estado' => 'exito'],
                ['etiqueta' => 'Slow queries',        'valor' => '3 under review', 'estado' => 'aviso'],
                ['etiqueta' => 'Privileged accounts', 'valor' => 'Audited',       'estado' => 'exito'],
            ],
            'pie' => 'Sample of the dashboard delivered with the monitoring service.',
        ],
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

    /** Technology stack. Structure documented in config/contenido.php. */
    'stack' => [
        'etiqueta' => 'Technology stack',
        'titulo'   => 'Engines we review, maintain and support',
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

    /** Client testimonials. TEST DATA. Structure documented in config/contenido.php. */
    'testimonios' => [
        'etiqueta' => 'Testimonials',
        'titulo'   => 'What our clients say',
        'texto'    => 'Feedback from technology teams that trusted their database '
                    . 'operations to our consultancy.',
        'lista'    => [
            [
                'nombre'      => 'Marcela Vindas Chaves',
                'cargo'       => 'Head of Technology',
                'iniciales'   => 'MV',
                'puntaje'     => 5,
                'descripcion' => 'We migrated a fifteen-year-old Oracle 11g without losing a '
                               . 'single transaction. The rollback plan was written before anything '
                               . 'was touched, and that is what gave us peace of mind.',
            ],
            [
                'nombre'      => 'Andrés Quesada Mora',
                'cargo'       => 'Internal Audit Manager',
                'iniciales'   => 'AQ',
                'puntaje'     => 4.5,
                'descripcion' => 'The audit report got straight to the point: prioritized findings, '
                               . 'each with an owner. We would only have liked the tracking '
                               . 'dashboard sooner.',
            ],
            [
                'nombre'      => 'Laura Céspedes Rojas',
                'cargo'       => 'Analytics Coordinator',
                'iniciales'   => 'LC',
                'puntaje'     => 5,
                'descripcion' => 'Monthly reports went from forty minutes to under two. It was '
                               . 'indexing work and rewriting three queries, not buying hardware.',
            ],
            [
                'nombre'      => 'Diego Hernández Alfaro',
                'cargo'       => 'Head of Infrastructure',
                'iniciales'   => 'DH',
                'puntaje'     => 4,
                'descripcion' => 'They put our backups in order and restores are now tested every '
                               . 'quarter. The support was good; coordinating the maintenance '
                               . 'windows took longer than expected.',
            ],
            [
                'nombre'      => 'Sofía Ramírez Delgado',
                'cargo'       => 'Information Security Officer',
                'iniciales'   => 'SR',
                'puntaje'     => 4.5,
                'descripcion' => 'We passed the ISO/IEC 27001 review with no findings on access '
                               . 'control. They documented every permission and taught us how to '
                               . 'keep it that way.',
            ],
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
