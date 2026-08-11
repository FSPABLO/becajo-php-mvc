<?php

declare(strict_types=1);

/**
 * English version of config/contenido.php. Same keys, same shape — only the
 * text changes. Loaded instead of the Spanish file when the visitor picks
 * English from the language selector.
 */

return [

    'empresa' => [
        'nombre'   => 'Rivendel',
        'eslogan'  => 'Database Administration Consulting',
        'correo'   => 'contacto@rivendel.cr',
        'telefono' => '+506 0000 0000',
        'ciudad'   => 'San José, Costa Rica',
        'anio'     => 2026,
    ],

    'meta' => [
        'titulo'      => 'Rivendel | Database Administration Consulting',
        'descripcion' => 'Migration, optimization, security and monitoring for enterprise '
                       . 'databases. We reduce the operational risk of your critical information.',
    ],

    'navegacion' => [
        ['etiqueta' => 'Challenges', 'destino' => '#retos'],
        ['etiqueta' => 'Services',   'destino' => '#servicios'],
        ['etiqueta' => 'Stack',      'destino' => '#stack'],
        ['etiqueta' => 'Results',    'destino' => '#resultados'],
        ['etiqueta' => 'Team',       'destino' => '#equipo'],
        ['etiqueta' => 'Pricing',    'destino' => '#planes'],
    ],

    'herramientas' => [
        [
            'etiqueta'    => 'Database Assessment Instrument',
            'descripcion' => 'Public reference document: the 75 database administration '
                           . 'controls aligned with the ISO/IEC 27000 family.',
            'destino'     => '/herramientas/instrumento-bd',
            'icono'       => 'documento',
        ],
        [
            'etiqueta'    => 'Health Diagnosis',
            'descripcion' => 'Open an audit, answer the controls and get the risk index '
                           . 'with its remediation plan. Account required.',
            'destino'     => '/evaluacion',
            'icono'       => 'tablero',
        ],
    ],

    'hero' => [
        'norma'  => 'ISO/IEC 27002 · 27007 · COBIT 4.1',
        'titulo' => 'Your database holds what the organization cannot afford to lose.',
        'texto'  => 'Database administration consulting with a measurable information security '
                  . 'audit: 75 controls, 25 processes, 7 domains and a risk index you can track '
                  . 'over time.',

        'cta_primario'   => ['etiqueta' => 'Request an audit',   'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'See the instrument', 'destino' => '/herramientas/instrumento-bd'],

        'cifras' => [
            ['valor' => '75', 'etiqueta' => 'Controls'],
            ['valor' => '25', 'etiqueta' => 'Processes'],
            ['valor' => '7',  'etiqueta' => 'Domains'],
        ],

        /** Risk index card illustrating the hero. See the Spanish file for the notes. */
        'panel' => [
            'titulo'          => 'Overall risk index',
            'referencia'      => 'AUD-0042',
            'indice'          => '3.4',
            'indice_maximo'   => 'of 5.0',
            'estado'          => 'bad',
            'estado_etiqueta' => 'Needs attention',
            'barras' => [
                ['etiqueta' => 'Overall compliance', 'valor' => '68.0 %',    'porcentaje' => 68],
                ['etiqueta' => 'Average maturity',   'valor' => '2.6 / 5.0', 'porcentaje' => 52],
                ['etiqueta' => 'Controls answered',  'valor' => '61 / 75',   'porcentaje' => 81],
            ],
            'conteos' => [
                ['estado' => 'ok',  'etiqueta' => 'Compliant 41'],
                ['estado' => 'bad', 'etiqueta' => 'Non-compliant 14'],
                ['estado' => 'na',  'etiqueta' => 'Not applicable 6'],
            ],
        ],
    ],

    /** See the Spanish file for the notes on 'eyebrow' and the numbering. */
    'retos' => [
        'eyebrow' => 'Challenges',
        'titulo'  => 'What we usually find broken',
        'lista'   => [
            [
                'titulo' => 'Nobody knows who holds privileges',
                'texto'  => 'Inherited accounts, privileges piled up over the years, and no '
                          . 'record of why they were granted.',
            ],
            [
                'titulo' => 'The backup exists, the restore was never tested',
                'texto'  => 'A backup that has never been restored is a hypothesis, not a control.',
            ],
            [
                'titulo' => 'Compliance declared without evidence',
                'texto'  => 'ISO/IEC 27007 determines conformity against verified evidence, '
                          . 'not against the auditee\'s statement.',
            ],
            [
                'titulo' => 'Findings with no deadline and no owner',
                'texto'  => 'With no due date and no owner, the finding comes back unchanged '
                          . 'in the next audit.',
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
            ['nombre' => 'Oracle Database', 'imagen' => 'assets/images/oracle.png', 'ancho' => 221, 'alto' => 240],
            ['nombre' => 'MySQL',           'imagen' => 'assets/images/mysql.png', 'ancho' => 427, 'alto' => 240],
            ['nombre' => 'PostgreSQL',      'imagen' => 'assets/images/postgresql.png', 'ancho' => 610, 'alto' => 280],
            ['nombre' => 'SQL Server',      'imagen' => 'assets/images/sql-server.png', 'ancho' => 295, 'alto' => 240],
            ['nombre' => 'MariaDB',         'imagen' => 'assets/images/mariadb.png', 'ancho' => 480, 'alto' => 240],
            ['nombre' => 'MongoDB',         'imagen' => 'assets/images/mongodb.png', 'ancho' => 399, 'alto' => 240],
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
        'titulo'   => 'Those who have already been audited',
        'texto'    => 'Feedback from technology teams that trusted their database '
                    . 'operations to our consultancy.',
        'lista'    => [
            [
                'nombre'      => 'Marcela Vindas Chaves',
                'foto'        => 'assets/images/testimonios/marcela-vindas.jpg',
                'cargo'       => 'Head of Technology',
                'iniciales'   => 'MV',
                'puntaje'     => 5,
                'referencia'  => 'Audit AUD-0031 · 75 controls',
                'descripcion' => 'We migrated a fifteen-year-old Oracle 11g without losing a '
                               . 'single transaction. The rollback plan was written before anything '
                               . 'was touched, and that is what gave us peace of mind.',
            ],
            [
                'nombre'      => 'Andrés Quesada Mora',
                'foto'        => 'assets/images/testimonios/andres-quesada.jpg',
                'cargo'       => 'Internal Audit Manager',
                'iniciales'   => 'AQ',
                'puntaje'     => 4.5,
                'referencia'  => 'Audit AUD-0044 · 75 controls',
                'descripcion' => 'The audit report got straight to the point: prioritized findings, '
                               . 'each with an owner. We would only have liked the tracking '
                               . 'dashboard sooner.',
            ],
            [
                'nombre'      => 'Laura Céspedes Rojas',
                'foto'        => 'assets/images/testimonios/laura-cespedes.jpg',
                'cargo'       => 'Analytics Coordinator',
                'iniciales'   => 'LC',
                'puntaje'     => 5,
                'referencia'  => 'Audit AUD-0052 · 75 controls',
                // Con este abre el carrusel.
                'destacado'   => true,
                'descripcion' => 'Monthly reports went from forty minutes to under two. It was '
                               . 'indexing work and rewriting three queries, not buying hardware.',
            ],
            [
                'nombre'      => 'Diego Hernández Alfaro',
                'foto'        => 'assets/images/testimonios/diego-hernandez.jpg',
                'cargo'       => 'Head of Infrastructure',
                'iniciales'   => 'DH',
                'puntaje'     => 4,
                'referencia'  => 'Audit AUD-0058 · 75 controls',
                'descripcion' => 'They put our backups in order and restores are now tested every '
                               . 'quarter. The support was good; coordinating the maintenance '
                               . 'windows took longer than expected.',
            ],
            [
                'nombre'      => 'Sofía Ramírez Delgado',
                'foto'        => 'assets/images/testimonios/sofia-ramirez.jpg',
                'cargo'       => 'Information Security Officer',
                'iniciales'   => 'SR',
                'puntaje'     => 4.5,
                'referencia'  => 'Audit AUD-0063 · 75 controls',
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

    /** Ver config/contenido.php para la forma de este bloque. */
    'planes' => [
        'etiqueta' => 'Pricing',
        'titulo'   => 'Plans and pricing',
        'texto'    => 'Measuring the risk of your databases costs nothing. '
                    . 'You only pay once the assessment becomes continuous monitoring.',
        'lista'    => [
            [
                'nombre'    => 'Free',
                'precio'    => '$0',
                'periodo'   => '/month',
                'resumen'   => 'For the team measuring itself for the first time',
                'insignia'  => '',
                'destacado' => false,
                'accion'    => [
                    'etiqueta' => 'Start the assessment',
                    'destino'  => '/herramientas/instrumento-bd',
                ],
                'incluye'   => [
                    'Full access to the assessment instrument: all 75 controls',
                    'Unlimited report downloads in PDF, CSV and JSON',
                    'Framework and references ISO/IEC 27002 · 27007',
                ],
            ],
            [
                'nombre'    => 'Deluxe',
                'precio'    => '$20',
                'periodo'   => '/month',
                'resumen'   => 'For operations that already depend on their databases',
                'insignia'  => 'Recommended',
                'destacado' => true,
                'accion'    => [
                    'etiqueta' => 'Request this plan',
                    'destino'  => '#contacto',
                ],
                'incluye'   => [
                    'Everything in the Free plan',
                    'Health monitor for up to 5 databases',
                    'Storage of previous assessment runs',
                    'Detailed statistics by domain, process and control',
                    'Report with the mapping across all three frameworks',
                ],
                'normas'    => [
                    'etiqueta' => 'Frameworks',
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
        'titulo' => 'Let\'s talk about your databases',
        'texto'  => 'We run a free initial assessment: we review configuration, '
                  . 'backups and access, and deliver a report with prioritized findings.',
    ],
];
