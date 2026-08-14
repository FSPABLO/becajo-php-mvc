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
        ['etiqueta' => 'Stack',   'destino' => '#stack'],
        ['etiqueta' => 'Pricing', 'destino' => '#planes'],
    ],

    'nosotros' => [
        [
            'etiqueta'    => 'Team',
            'descripcion' => 'Who audits: the consultants behind the instrument and the '
                           . 'audits themselves.',
            'destino'     => '#equipo',
            'icono'       => 'usuarios',
        ],
        [
            'etiqueta'    => 'Frequently asked questions',
            'descripcion' => 'How the system works, who it is for, and where the product '
                           . 'is heading.',
            'destino'     => '/preguntas-frecuentes',
            'icono'       => 'pregunta',
        ],
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

    /**
     * Typographic banner. See the Spanish file for the notes — including the
     * rule that inherits with this block: NO fabricated figures and no client
     * data in the banner, ever. The only numbers shown are the size of the
     * instrument, and the view counts them off the repository rather than
     * reading them from here.
     */
    'hero' => [
        'norma'  => 'ISO/IEC 27002 · 27007 · COBIT 4.1',
        'titulo' => 'Your database holds what the organization cannot afford to lose.',
        'texto'  => 'Database administration consulting with a measurable information '
                  . 'security audit, built on ISO/IEC 27002, 27007 and COBIT 4.1.',

        'cta_primario'   => ['etiqueta' => 'Request an audit',   'destino' => '#contacto'],
        'cta_secundario' => ['etiqueta' => 'See the instrument', 'destino' => '/herramientas/instrumento-bd'],

        /** No 'valor' on purpose — the view counts these off the repository. */
        'cifras' => [
            ['clave' => 'controles', 'etiqueta' => 'Controls',  'icono' => 'auditoria.png'],
            ['clave' => 'procesos',  'etiqueta' => 'Processes', 'icono' => 'monitoreo.png'],
            ['clave' => 'dominios',  'etiqueta' => 'Domains',   'icono' => 'base-datos.png'],
        ],

        'dominios_rotulo' => 'What gets audited',
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

    /** Frequently asked questions (/preguntas-frecuentes). See the Spanish file for the notes. */
    'preguntas' => [
        'eyebrow' => 'Frequently asked questions',
        'titulo'  => 'How it works, who it is for, and where it is heading',
        'texto'   => 'Fifteen answers about the 75-control instrument, the risk assessment '
                   . 'module and the road ahead for the product. If yours is not here, the '
                   . 'contact form reaches the same team that runs the audits.',

        'indice' => 'On this page',

        'cierre' => [
            'titulo'    => 'Is your question missing from the list?',
            'texto'     => 'Write to us and the person who audits will answer, not a form. If you '
                         . 'would rather start on your own, the 75-control instrument is open and '
                         . 'asks nobody for their details.',
            'principal' => ['etiqueta' => 'Write to the team',   'destino' => '#contacto'],
            'secundaria' => ['etiqueta' => 'See the instrument', 'destino' => '/herramientas/instrumento-bd'],
        ],

        'grupos' => [
            [
                'clave'  => 'funcionamiento',
                'titulo' => 'How the system works',
                'texto'  => 'From the questionnaire to the risk index, and from there to the '
                          . 'remediation plan.',
                'lista'  => [
                    [
                        'pregunta'  => 'What exactly is Rivendel?',
                        'respuesta' => 'It is a database administration consultancy and, at the '
                                     . 'same time, the system your audit runs on. We do not hand '
                                     . 'over a hand-written opinion: we apply an instrument of 75 '
                                     . 'controls grouped into 25 processes and 7 domains, and those '
                                     . 'answers produce a risk index that can be recalculated '
                                     . 'months later with the very same method.',
                    ],
                    [
                        'pregunta'  => 'What is the instrument of 75 controls based on?',
                        'respuesta' => 'On the ISO/IEC 27000 family — 27002 for the controls and '
                                     . '27007 for how they are audited — with COBIT 4.1 covering '
                                     . 'governance. Every control states which standard it comes '
                                     . 'from, and that reference is printed in the report: whoever '
                                     . 'reads a finding can go to the source without asking us.',
                    ],
                    [
                        'pregunta'  => 'How is an audit answered?',
                        'respuesta' => 'The auditor opens an audit for a specific organization and '
                                     . 'scope, then walks the controls one by one. Each control is '
                                     . 'answered with complies, does not comply or not applicable, '
                                     . 'a maturity level from 1 to 5, and the finding that backs '
                                     . 'that answer. Nothing forces you to finish in one sitting: '
                                     . 'progress is saved control by control.',
                    ],
                    [
                        'pregunta'  => 'How is the risk index calculated?',
                        'respuesta' => 'With stored procedures in the database, not with a formula '
                                     . 'hidden in the application. Compliance is the ratio of '
                                     . 'complying controls to applicable controls — those marked '
                                     . '«not applicable» leave the divisor, they do not count as '
                                     . 'failures — and the index combines that compliance with '
                                     . 'average maturity on a 1 to 5 scale.',
                    ],
                    [
                        'pregunta'  => 'What happens after an audit is closed?',
                        'respuesta' => 'The part that actually reduces risk begins. Every failed '
                                     . 'control can open a remediation with an owner and a '
                                     . 'committed date; the system flags the ones that fall due, '
                                     . 'and scheduling the re-audit re-evaluates only what was '
                                     . 'remediated. Comparing two audits of the same organization '
                                     . 'shows what moved and what stayed put.',
                    ],
                    [
                        'pregunta'  => 'What does the system deliver in the end?',
                        'respuesta' => 'An executive report written for two readers at once: it '
                                     . 'opens with the overall index, the domain matrix and the '
                                     . 'critical findings — for whoever signs the budget — and '
                                     . 'continues with the remediation plan control by control, '
                                     . 'each with its source standard, for whoever has to fix it. '
                                     . 'It prints or saves as PDF without the navigation chrome, '
                                     . 'and the public instrument also exports progress as CSV and '
                                     . 'JSON.',
                    ],
                ],
            ],
            [
                'clave'  => 'publico',
                'titulo' => 'Who it is for',
                'texto'  => 'Who uses it, who reads it, and what it takes to start.',
                'lista'  => [
                    [
                        'pregunta'  => 'What kind of organization does it serve?',
                        'respuesta' => 'The one that already depends on its databases and cannot '
                                     . 'say how much risk it carries: credit unions, public '
                                     . 'institutions, mid-sized companies running an ERP on an '
                                     . 'engine nobody has audited in years. You do not need a '
                                     . 'security department; you need someone who can answer how '
                                     . 'backups are taken and who holds access.',
                    ],
                    [
                        'pregunta'  => 'Who uses the system, and in which role?',
                        'respuesta' => 'There are two roles. The auditor opens audits, answers '
                                     . 'controls and follows up remediations in their own '
                                     . 'portfolio: they cannot see another consultant\'s work. The '
                                     . 'database administrator additionally maintains the master '
                                     . 'catalog — domains, processes and controls — which is what '
                                     . 'everyone else is evaluated against.',
                    ],
                    [
                        'pregunta'  => 'Is it useful if I am the in-house DBA rather than a consultant?',
                        'respuesta' => 'Yes, and that is one of the intended uses. The public '
                                     . 'instrument is answered without an account and without '
                                     . 'sending anything to any server: it works as a self-diagnosis '
                                     . 'before hiring anyone. What the internal module adds is '
                                     . 'memory — history, comparison and remediations — not '
                                     . 'different questions.',
                    ],
                    [
                        'pregunta'  => 'What do I need to get started?',
                        'respuesta' => 'For the public instrument, a browser. For the assessment '
                                     . 'module, an auditor account and the details of the '
                                     . 'organization to be audited. We do not ask for access to '
                                     . 'your databases: the audit is built from interviews and '
                                     . 'evidence, and production credentials never leave your '
                                     . 'organization.',
                    ],
                ],
            ],
            [
                'clave'  => 'futuro',
                'titulo' => 'Proposals for future projects',
                'texto'  => 'What is under study for the coming stages. These are proposals, '
                          . 'not features available today.',
                'lista'  => [
                    [
                        'pregunta'  => 'Will there be continuous database monitoring?',
                        'respuesta' => 'That is the main proposal. The panel already carries the '
                                     . 'connected databases card, which is the preview of that '
                                     . 'module: the very list of instances monitoring would feed '
                                     . 'on. What is missing is reading latency, space, locks and '
                                     . 'last backup from each one, so the diagnosis stops being a '
                                     . 'quarterly snapshot and becomes a continuous signal.',
                    ],
                    [
                        'pregunta'  => 'Will it extend to engines other than Oracle?',
                        'respuesta' => 'The instrument is already engine-independent: the 75 '
                                     . 'controls ask about backups, access and encryption, not '
                                     . 'about one vendor\'s syntax. What is under study is whether '
                                     . 'the system itself can run on PostgreSQL or SQL Server '
                                     . 'besides Oracle, swapping the data access layer without '
                                     . 'touching the views.',
                    ],
                    [
                        'pregunta'  => 'Will evidence be attachable to each finding?',
                        'respuesta' => 'It is proposed. Today a finding is text, which forces you '
                                     . 'to describe a screenshot or a query output in words. '
                                     . 'Attaching the file to the control would close that gap, on '
                                     . 'the condition that the evidence inherits the same access '
                                     . 'control as the audit it belongs to.',
                    ],
                    [
                        'pregunta'  => 'Will there be automatic alerts and notices?',
                        'respuesta' => 'The system already knows which remediations are overdue and '
                                     . 'shows them on entry. The proposed next step is for that '
                                     . 'notice to leave the system — an email to the owner before '
                                     . 'the due date, not after — and for the re-audit to propose '
                                     . 'itself once every remediation in an audit is closed.',
                    ],
                    [
                        'pregunta'  => 'Will an interface open up for integration with other systems?',
                        'respuesta' => 'That is the longest-term proposal, and the one least worth '
                                     . 'rushing. Publishing the risk index and remediation status '
                                     . 'to a corporate dashboard or a ticketing system only pays off '
                                     . 'once the data model has settled; doing it earlier means '
                                     . 'maintaining a public interface on top of a schema that is '
                                     . 'still moving.',
                    ],
                ],
            ],
        ],
    ],
];
