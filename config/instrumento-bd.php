<?php

declare(strict_types=1);

/**
 * Instrumento de consultoría para la administración de bases de datos.
 *
 * Contenido del instrumento, separado por completo de la vista y del
 * comportamiento: 7 dominios, 25 procesos, 75 controles con su pregunta de
 * auditoría, la escala de madurez, el marco normativo y las referencias.
 *
 * Lo lee App\Models\RepositorioInstrumentoArreglo. Si mañana el catálogo de
 * controles vive en una tabla de Oracle, se escribe otro repositorio con la
 * misma interfaz y ni el controlador ni las vistas se enteran.
 *
 * Convenciones del catálogo:
 *   - Los identificadores C-001 a C-075 siguen el orden de presentación.
 *   - Cada control describe un ESTADO VERIFICABLE, no una intención.
 *   - Cada pregunta pide el cómo, el quién o el cuándo: no se responde con un
 *     sí o un no a secas.
 */

return [

    'meta' => [
        'titulo'      => 'Instrumento de consultoría para la administración de bases de datos',
        'descripcion' => 'Evaluación de 75 controles agrupados en 25 procesos y 7 dominios, '
                       . 'con anclaje en la familia de normas ISO/IEC 27000 a 27011.',
        'version'     => '1.0',
    ],

    // ── Dominios ─────────────────────────────────────────────────────────────
    'dominios' => [
        [
            'clave'       => 'gobierno',
            'corto'       => 'Gobierno',
            'nombre'      => 'Gobierno y riesgo',
            'descripcion' => 'Quién responde por los datos, cómo se valora el riesgo y con qué '
                           . 'competencia y documentación se opera.',
        ],
        [
            'clave'       => 'configuracion',
            'corto'       => 'Configuración',
            'nombre'      => 'Configuración y cambios',
            'descripcion' => 'Instalación asegurada del motor, control de cambios, parches y '
                           . 'diseño físico de las bases.',
        ],
        [
            'clave'       => 'almacenamiento',
            'corto'       => 'Almacenamiento',
            'nombre'      => 'Memoria y almacenamiento',
            'descripcion' => 'Memoria, estructuras de almacenamiento, registro de transacciones '
                           . 'y proyección de capacidad.',
        ],
        [
            'clave'       => 'accesos',
            'corto'       => 'Accesos',
            'nombre'      => 'Accesos y privilegios',
            'descripcion' => 'Ciclo de vida de las cuentas, roles, accesos privilegiados y '
                           . 'autenticación.',
        ],
        [
            'clave'       => 'dato',
            'corto'       => 'Protección',
            'nombre'      => 'Protección del dato',
            'descripcion' => 'Cifrado, enmascaramiento en ambientes no productivos, retención y '
                           . 'eliminación segura.',
        ],
        [
            'clave'       => 'continuidad',
            'corto'       => 'Continuidad',
            'nombre'      => 'Continuidad',
            'descripcion' => 'Respaldo, recuperación probada, alta disponibilidad y replicación.',
        ],
        [
            'clave'       => 'vigilancia',
            'corto'       => 'Vigilancia',
            'nombre'      => 'Vigilancia y terceros',
            'descripcion' => 'Monitoreo, auditoría de registros, incidentes y servicios de base '
                           . 'de datos contratados o en la nube.',
        ],
    ],

    // ── Procesos ─────────────────────────────────────────────────────────────
    'procesos' => [
        ['numero' => 1,  'dominio' => 'gobierno',       'nombre' => 'Gobierno y control interno del SGBD',                 'ancla' => 'ISO/IEC 27001 cl. 5; A.5.1, A.5.2, A.5.3'],
        ['numero' => 2,  'dominio' => 'gobierno',       'nombre' => 'Gestión de riesgos inherentes al SGBD',                'ancla' => 'ISO/IEC 27005; ISO/IEC 27001 cl. 6.1'],
        ['numero' => 25, 'dominio' => 'gobierno',       'nombre' => 'Competencia, documentación e intercambio de información', 'ancla' => 'ISO/IEC 27001 cl. 7.2; A.5.37, A.6.3, A.5.14; ISO/IEC 27010'],

        ['numero' => 3,  'dominio' => 'configuracion',  'nombre' => 'Instalación y aseguramiento (hardening) del SGBD',     'ancla' => 'A.8.9, A.8.19, A.5.21'],
        ['numero' => 4,  'dominio' => 'configuracion',  'nombre' => 'Gestión de cambios y de la configuración',             'ancla' => 'A.8.32, A.8.31'],
        ['numero' => 5,  'dominio' => 'configuracion',  'nombre' => 'Gestión de vulnerabilidades técnicas y parches',       'ancla' => 'A.8.8; ISO/IEC 27008'],
        ['numero' => 6,  'dominio' => 'configuracion',  'nombre' => 'Creación de bases de datos y diseño físico',           'ancla' => 'A.8.27, A.8.9'],

        ['numero' => 7,  'dominio' => 'almacenamiento', 'nombre' => 'Administración de memoria y procesos de soporte',      'ancla' => 'A.8.6, A.8.16'],
        ['numero' => 8,  'dominio' => 'almacenamiento', 'nombre' => 'Gestión de estructuras de almacenamiento',             'ancla' => 'A.8.6, A.8.13'],
        ['numero' => 9,  'dominio' => 'almacenamiento', 'nombre' => 'Administración del registro de transacciones',         'ancla' => 'A.8.13, A.8.15'],
        ['numero' => 10, 'dominio' => 'almacenamiento', 'nombre' => 'Gestión de capacidad',                                 'ancla' => 'A.8.6'],

        ['numero' => 11, 'dominio' => 'accesos',        'nombre' => 'Gestión de cuentas de usuario',                        'ancla' => 'A.5.16, A.5.18'],
        ['numero' => 12, 'dominio' => 'accesos',        'nombre' => 'Gestión de privilegios y roles',                       'ancla' => 'A.8.2, A.5.15'],
        ['numero' => 13, 'dominio' => 'accesos',        'nombre' => 'Cuentas administrativas y accesos privilegiados',      'ancla' => 'A.8.2, A.5.17, A.8.15'],
        ['numero' => 14, 'dominio' => 'accesos',        'nombre' => 'Autenticación y política de contraseñas',              'ancla' => 'A.5.17, A.8.5, A.8.28'],

        ['numero' => 15, 'dominio' => 'dato',           'nombre' => 'Criptografía y gestión de llaves',                     'ancla' => 'A.8.24, A.8.20'],
        ['numero' => 16, 'dominio' => 'dato',           'nombre' => 'Enmascaramiento y ambientes no productivos',           'ancla' => 'A.8.11, A.8.33, A.8.31'],
        ['numero' => 23, 'dominio' => 'dato',           'nombre' => 'Retención, archivado y eliminación segura',            'ancla' => 'A.5.33, A.5.34, A.8.10, A.7.14'],

        ['numero' => 17, 'dominio' => 'continuidad',    'nombre' => 'Respaldo de bases de datos',                           'ancla' => 'A.8.13, A.8.24'],
        ['numero' => 18, 'dominio' => 'continuidad',    'nombre' => 'Recuperación y continuidad del servicio',              'ancla' => 'A.8.13, A.5.29, A.5.30'],
        ['numero' => 19, 'dominio' => 'continuidad',    'nombre' => 'Alta disponibilidad y replicación',                    'ancla' => 'A.8.14, A.8.16'],

        ['numero' => 20, 'dominio' => 'vigilancia',     'nombre' => 'Monitoreo y gestión del desempeño',                    'ancla' => 'A.8.16, A.8.6; ISO/IEC 27004'],
        ['numero' => 21, 'dominio' => 'vigilancia',     'nombre' => 'Auditoría de la base de datos y registros',            'ancla' => 'A.8.15, A.8.17; ISO/IEC 27007'],
        ['numero' => 22, 'dominio' => 'vigilancia',     'nombre' => 'Gestión de incidentes de seguridad en la base de datos', 'ancla' => 'A.5.24 a A.5.28'],
        ['numero' => 24, 'dominio' => 'vigilancia',     'nombre' => 'Proveedores y bases de datos gestionadas o en la nube', 'ancla' => 'A.5.19 a A.5.23; ISO/IEC 27011'],
    ],

    // ── Controles y preguntas de auditoría ───────────────────────────────────
    'controles' => [

        // Proceso 1 — Gobierno y control interno del SGBD
        [
            'id'        => 'C-001',
            'proceso'   => 1,
            'iso'       => 'ISO/IEC 27002:2022 A.5.1',
            'enunciado' => 'Existe una política de seguridad de la información aprobada por la dirección que trata de manera explícita la administración de bases de datos y se revisa al menos una vez al año.',
            'evidencia' => 'Política vigente con firma de aprobación, acta de la dirección y registro de la última revisión.',
            'pregunta'  => '¿Quién aprobó la política vigente de seguridad de la información, en qué fecha se revisó por última vez y qué apartados tratan específicamente las bases de datos?',
        ],
        [
            'id'        => 'C-002',
            'proceso'   => 1,
            'iso'       => 'ISO/IEC 27002:2022 A.5.2',
            'enunciado' => 'Las responsabilidades de propiedad, custodia y administración de cada base de datos están asignadas nominalmente y documentadas.',
            'evidencia' => 'Matriz de responsabilidades o descripciones de puesto donde consten los nombres y las bases asignadas.',
            'pregunta'  => '¿Quién es el propietario y quién el custodio de cada base de datos en producción, y en qué documento consta esa asignación?',
        ],
        [
            'id'        => 'C-003',
            'proceso'   => 1,
            'iso'       => 'ISO/IEC 27002:2022 A.5.3',
            'enunciado' => 'Las funciones de desarrollo, administración de la base de datos y revisión de sus registros de auditoría están segregadas entre personas distintas.',
            'evidencia' => 'Organigrama del área y comparación de las cuentas asignadas a cada función.',
            'pregunta'  => '¿Cómo se separan las funciones de quien desarrolla, quien administra y quien revisa las bitácoras, y qué control compensatorio aplica cuando una sola persona cubre varias?',
        ],

        // Proceso 2 — Gestión de riesgos inherentes al SGBD
        [
            'id'        => 'C-004',
            'proceso'   => 2,
            'iso'       => 'ISO/IEC 27005:2022 cl. 7.2; A.5.9',
            'enunciado' => 'Existe un inventario actualizado de las bases de datos en operación que registra motor, versión, responsable y clasificación de la información contenida.',
            'evidencia' => 'Inventario con fecha de última actualización y criterio de clasificación aplicado.',
            'pregunta'  => '¿Cuántas bases de datos están en operación hoy, cómo se mantiene actualizado ese inventario y quién clasifica la información que contienen?',
        ],
        [
            'id'        => 'C-005',
            'proceso'   => 2,
            'iso'       => 'ISO/IEC 27005:2022 cl. 7.3; ISO/IEC 27001 cl. 6.1.2',
            'enunciado' => 'Se ha realizado una evaluación de riesgos de las bases de datos que identifica amenazas, probabilidad e impacto sobre la integridad, la confidencialidad y la disponibilidad.',
            'evidencia' => 'Matriz de riesgos fechada, con la metodología y las escalas de valoración declaradas.',
            'pregunta'  => '¿Con qué metodología se evaluaron los riesgos de las bases de datos, cuándo se hizo la última valoración y quiénes participaron en ella?',
        ],
        [
            'id'        => 'C-006',
            'proceso'   => 2,
            'iso'       => 'ISO/IEC 27001 cl. 6.1.3 y cl. 8.3',
            'enunciado' => 'Cada riesgo tratado o aceptado cuenta con un plan con responsable asignado, fecha compromiso y seguimiento registrado.',
            'evidencia' => 'Plan de tratamiento con el estado de cada riesgo y acta de aceptación del riesgo residual.',
            'pregunta'  => '¿Qué riesgos de base de datos siguen abiertos, quién responde por cada plan de tratamiento y en qué fecha vencen?',
        ],

        // Proceso 25 — Competencia, documentación e intercambio de información
        [
            'id'        => 'C-007',
            'proceso'   => 25,
            'iso'       => 'ISO/IEC 27001 cl. 7.2; A.6.3',
            'enunciado' => 'El personal a cargo de las bases de datos tiene competencias definidas por puesto y evidencia de formación o certificación vigente.',
            'evidencia' => 'Perfil de puesto, plan de capacitación y constancias del último año.',
            'pregunta'  => '¿Qué formación específica en el motor y en seguridad de la información recibió el equipo de base de datos en el último año y cómo se acredita?',
        ],
        [
            'id'        => 'C-008',
            'proceso'   => 25,
            'iso'       => 'ISO/IEC 27002:2022 A.5.37',
            'enunciado' => 'Los procedimientos operativos de la base de datos están documentados, versionados y disponibles para quien debe ejecutarlos.',
            'evidencia' => 'Repositorio de procedimientos con control de versiones y fecha de última revisión.',
            'pregunta'  => '¿Dónde está el procedimiento operativo de la base de datos, qué versión está vigente y cuándo se actualizó por última vez?',
        ],
        [
            'id'        => 'C-009',
            'proceso'   => 25,
            'iso'       => 'ISO/IEC 27002:2022 A.5.14; ISO/IEC 27010:2015',
            'enunciado' => 'El intercambio de información de la base de datos con terceros está regulado por acuerdos que definen el medio, el cifrado y las responsabilidades de cada parte.',
            'evidencia' => 'Acuerdos de intercambio firmados y registro de las transferencias realizadas.',
            'pregunta'  => '¿Qué información de la base de datos se envía a terceros, por qué medio viaja y qué acuerdo respalda cada intercambio?',
        ],

        // Proceso 3 — Instalación y aseguramiento (hardening) del SGBD
        [
            'id'        => 'C-010',
            'proceso'   => 3,
            'iso'       => 'ISO/IEC 27002:2022 A.8.9',
            'enunciado' => 'Existe una línea base de configuración segura del motor, aprobada formalmente y aplicada a toda instancia nueva.',
            'evidencia' => 'Documento de línea base aprobado y reporte de conformidad de las instancias en operación.',
            'pregunta'  => '¿Qué línea base de configuración se aplica al instalar una instancia nueva y quién verifica que quedó conforme a ella?',
        ],
        [
            'id'        => 'C-011',
            'proceso'   => 3,
            'iso'       => 'ISO/IEC 27002:2022 A.8.9, A.8.19',
            'enunciado' => 'Las cuentas por omisión, los esquemas de ejemplo y los servicios no requeridos se deshabilitan o eliminan antes de poner la instancia en producción.',
            'evidencia' => 'Lista de verificación del aseguramiento firmada y consulta de cuentas y servicios activos.',
            'pregunta'  => '¿Qué cuentas y componentes por omisión se retiraron de la última instancia instalada y cómo se comprobó que ya no están activos?',
        ],
        [
            'id'        => 'C-012',
            'proceso'   => 3,
            'iso'       => 'ISO/IEC 27002:2022 A.5.21, A.8.19',
            'enunciado' => 'El software del motor y sus actualizaciones se obtienen de fuentes autorizadas y se verifica su integridad antes de instalarlos, con registro del responsable.',
            'evidencia' => 'Procedimiento de instalación, lista de personal autorizado y bitácora de instalaciones con verificación de firma o suma de comprobación.',
            'pregunta'  => '¿De dónde se descarga el instalador del motor, cómo se comprueba su integridad y quién puede ejecutar la instalación en el servidor?',
        ],

        // Proceso 4 — Gestión de cambios y de la configuración
        [
            'id'        => 'C-013',
            'proceso'   => 4,
            'iso'       => 'ISO/IEC 27002:2022 A.8.32',
            'enunciado' => 'Todo cambio en el esquema o en la configuración de la base de datos se solicita, autoriza y registra antes de aplicarse en producción.',
            'evidencia' => 'Registro de cambios del último trimestre con solicitante, autorizador, fecha y resultado.',
            'pregunta'  => '¿Cómo se solicita y se autoriza un cambio en producción, y quién puede aplicarlo fuera de ese flujo cuando hay una urgencia?',
        ],
        [
            'id'        => 'C-014',
            'proceso'   => 4,
            'iso'       => 'ISO/IEC 27002:2022 A.8.32',
            'enunciado' => 'Cada cambio cuenta con un plan de reversión documentado y probado antes de su aplicación en producción.',
            'evidencia' => 'Planes de reversión de los últimos cambios y evidencia de su prueba en un ambiente previo.',
            'pregunta'  => '¿Cuál fue el último cambio que hubo que revertir, cuánto tardó la reversión y con qué plan se ejecutó?',
        ],
        [
            'id'        => 'C-015',
            'proceso'   => 4,
            'iso'       => 'ISO/IEC 27002:2022 A.8.31',
            'enunciado' => 'Los ambientes de desarrollo, pruebas y producción están separados y no comparten instancias, credenciales ni cuentas de servicio.',
            'evidencia' => 'Inventario de instancias por ambiente y comparación de las cuentas existentes entre ellos.',
            'pregunta'  => '¿En qué servidores viven desarrollo, pruebas y producción, y qué credenciales o servicios comparten hoy entre sí?',
        ],

        // Proceso 5 — Gestión de vulnerabilidades técnicas y parches
        [
            'id'        => 'C-016',
            'proceso'   => 5,
            'iso'       => 'ISO/IEC 27002:2022 A.8.8',
            'enunciado' => 'Existe un proceso definido de identificación de vulnerabilidades del motor, con fuente de información suscrita y periodicidad de revisión establecida.',
            'evidencia' => 'Procedimiento, constancia de la suscripción al boletín del fabricante y último reporte de vulnerabilidades.',
            'pregunta'  => '¿Por qué medio se entera el equipo de una vulnerabilidad nueva del motor y con qué frecuencia se revisa esa fuente?',
        ],
        [
            'id'        => 'C-017',
            'proceso'   => 5,
            'iso'       => 'ISO/IEC 27002:2022 A.8.8',
            'enunciado' => 'Los parches de seguridad se clasifican por criticidad y se aplican dentro de plazos máximos definidos y aprobados.',
            'evidencia' => 'Política de plazos por criticidad y bitácora de parches con fecha de publicación y fecha de aplicación.',
            'pregunta'  => '¿Cuál es el plazo máximo comprometido para aplicar un parche crítico y cuánto se tardó realmente con el último que se aplicó?',
        ],
        [
            'id'        => 'C-018',
            'proceso'   => 5,
            'iso'       => 'ISO/IEC 27008:2019; A.8.8',
            'enunciado' => 'Se ejecutan revisiones técnicas de cumplimiento sobre la configuración de la base de datos y sus hallazgos se remedian con seguimiento documentado.',
            'evidencia' => 'Último informe de revisión técnica y estado de remediación de cada hallazgo.',
            'pregunta'  => '¿Cuándo se hizo la última revisión técnica de la configuración, quién la ejecutó y cuántos hallazgos siguen abiertos hoy?',
        ],

        // Proceso 6 — Creación de bases de datos y diseño físico
        [
            'id'        => 'C-019',
            'proceso'   => 6,
            'iso'       => 'ISO/IEC 27002:2022 A.8.27',
            'enunciado' => 'El diseño físico de cada base de datos sigue principios de arquitectura segura documentados, incluidas la ubicación de los archivos y la separación de datos e índices.',
            'evidencia' => 'Documento de diseño físico aprobado y esquema de distribución de archivos.',
            'pregunta'  => '¿Qué criterios de diseño físico se aplicaron a la última base de datos creada y quién los aprobó?',
        ],
        [
            'id'        => 'C-020',
            'proceso'   => 6,
            'iso'       => 'ISO/IEC 27002:2022 A.8.27, A.8.9',
            'enunciado' => 'La creación de bases de datos nuevas sigue un procedimiento formal con autorización previa y registro del resultado de la verificación contra la línea base.',
            'evidencia' => 'Solicitudes de creación autorizadas y bitácora de bases creadas en el último año.',
            'pregunta'  => '¿Quién autoriza la creación de una base de datos nueva y cómo se registra que quedó conforme a la línea base?',
        ],
        [
            'id'        => 'C-021',
            'proceso'   => 6,
            'iso'       => 'ISO/IEC 27002:2022 A.8.9',
            'enunciado' => 'Los parámetros de inicialización de cada instancia están documentados y sus desviaciones respecto a la línea base están justificadas y autorizadas.',
            'evidencia' => 'Exportación de los parámetros vigentes y registro de desviaciones con su justificación.',
            'pregunta'  => '¿Qué parámetros de la instancia se apartan de la línea base, quién autorizó cada desviación y por qué razón técnica?',
        ],

        // Proceso 7 — Administración de memoria y procesos de soporte
        [
            'id'        => 'C-022',
            'proceso'   => 7,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6',
            'enunciado' => 'Las áreas de memoria de la instancia están dimensionadas conforme a un criterio documentado que se revisa con periodicidad definida.',
            'evidencia' => 'Documento de dimensionamiento y última revisión con los datos de uso que la sustentan.',
            'pregunta'  => '¿Con qué criterio se dimensionó la memoria de la instancia y cuándo se revisó ese cálculo por última vez?',
        ],
        [
            'id'        => 'C-023',
            'proceso'   => 7,
            'iso'       => 'ISO/IEC 27002:2022 A.8.16',
            'enunciado' => 'Los procesos de soporte de la instancia están vigilados y su detención inesperada genera una alerta con destinatario definido.',
            'evidencia' => 'Configuración de las alertas y registro de las alertas emitidas en el último trimestre.',
            'pregunta'  => '¿Qué ocurre y a quién se avisa cuando se detiene un proceso de soporte de la instancia fuera del horario laboral?',
        ],
        [
            'id'        => 'C-024',
            'proceso'   => 7,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6, A.8.16',
            'enunciado' => 'Los indicadores de uso de memoria y de contención se registran y se conservan durante un periodo definido para el análisis de tendencia.',
            'evidencia' => 'Histórico de métricas de memoria de al menos tres meses y política de retención de esas métricas.',
            'pregunta'  => '¿Dónde se guardan las métricas de memoria, cuánto tiempo se conservan y quién las revisa?',
        ],

        // Proceso 8 — Gestión de estructuras de almacenamiento
        [
            'id'        => 'C-025',
            'proceso'   => 8,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6',
            'enunciado' => 'El crecimiento de los espacios de almacenamiento se monitorea con umbrales definidos que disparan una alerta antes de la saturación.',
            'evidencia' => 'Configuración de los umbrales y registro del último aviso generado con su atención.',
            'pregunta'  => '¿A qué porcentaje de ocupación avisa el sistema, a quién le llega ese aviso y cuándo se activó por última vez?',
        ],
        [
            'id'        => 'C-026',
            'proceso'   => 8,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6',
            'enunciado' => 'La distribución de los archivos de datos entre los volúmenes está documentada y responde a un criterio explícito de desempeño y aislamiento.',
            'evidencia' => 'Mapa de archivos por volumen y documento con el criterio de asignación.',
            'pregunta'  => '¿Cómo están repartidos los archivos de datos entre los volúmenes y qué criterio se siguió para ese reparto?',
        ],
        [
            'id'        => 'C-027',
            'proceso'   => 8,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13, A.8.6',
            'enunciado' => 'Existe un procedimiento documentado para ampliar o reorganizar estructuras de almacenamiento sin interrumpir el servicio.',
            'evidencia' => 'Procedimiento vigente y registro de la última ampliación ejecutada con su tiempo de afectación.',
            'pregunta'  => '¿Cómo se amplía un espacio de almacenamiento saturado y cuánto tiempo de servicio se perdió la última vez que ocurrió?',
        ],

        // Proceso 9 — Administración del registro de transacciones
        [
            'id'        => 'C-028',
            'proceso'   => 9,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13',
            'enunciado' => 'El registro de transacciones opera en un modo que permite la recuperación al punto en el tiempo exigido por el RPO comprometido para cada base.',
            'evidencia' => 'Configuración del modo de registro por instancia y tabla de RPO comprometidos.',
            'pregunta'  => '¿En qué modo de registro opera cada base de datos y qué punto de recuperación garantiza ese modo?',
        ],
        [
            'id'        => 'C-029',
            'proceso'   => 9,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13',
            'enunciado' => 'Los archivos del registro de transacciones se escriben en una ubicación distinta de la de los archivos de datos.',
            'evidencia' => 'Rutas configuradas en la instancia y evidencia de la separación física o lógica de los volúmenes.',
            'pregunta'  => '¿Dónde se escriben los archivos de registro y qué se perdería si desapareciera el volumen de los archivos de datos?',
        ],
        [
            'id'        => 'C-030',
            'proceso'   => 9,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13, A.8.15',
            'enunciado' => 'La depuración de los registros de transacciones se rige por una política de retención autorizada y solo se ejecuta sobre registros ya respaldados.',
            'evidencia' => 'Política de retención del registro y bitácora de depuraciones con la verificación previa de respaldo.',
            'pregunta'  => '¿Cuánto tiempo se conservan los registros de transacciones, quién autoriza su depuración y cómo se comprueba que ya fueron respaldados?',
        ],

        // Proceso 10 — Gestión de capacidad
        [
            'id'        => 'C-031',
            'proceso'   => 10,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6',
            'enunciado' => 'Existe una proyección de capacidad de almacenamiento, memoria y procesamiento con un horizonte de al menos doce meses.',
            'evidencia' => 'Documento de proyección fechado, con los supuestos de crecimiento declarados.',
            'pregunta'  => '¿Hasta cuándo alcanza la capacidad actual según la última proyección y en qué supuestos de crecimiento se basa?',
        ],
        [
            'id'        => 'C-032',
            'proceso'   => 10,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6',
            'enunciado' => 'Cada umbral de capacidad tiene un responsable asignado y una ruta de escalamiento definida para cuando se supera.',
            'evidencia' => 'Matriz de umbrales con responsables y registro de los escalamientos ocurridos.',
            'pregunta'  => '¿Quién recibe la alerta cuando se supera un umbral de capacidad y qué debe hacer en las primeras horas?',
        ],
        [
            'id'        => 'C-033',
            'proceso'   => 10,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6',
            'enunciado' => 'Las decisiones de ampliación de capacidad se sustentan en datos históricos de uso y quedan registradas con su autorización.',
            'evidencia' => 'Expediente de la última ampliación con los datos de uso que la justificaron.',
            'pregunta'  => '¿Cuál fue la última ampliación de capacidad, qué datos la justificaron y quién la autorizó?',
        ],

        // Proceso 11 — Gestión de cuentas de usuario
        [
            'id'        => 'C-034',
            'proceso'   => 11,
            'iso'       => 'ISO/IEC 27002:2022 A.5.16',
            'enunciado' => 'El alta de una cuenta de base de datos requiere una solicitud autorizada por el propietario de la información y queda registrada.',
            'evidencia' => 'Solicitudes de alta autorizadas de los últimos seis meses.',
            'pregunta'  => '¿Quién autoriza el alta de una cuenta nueva en la base de datos y dónde queda constancia de esa autorización?',
        ],
        [
            'id'        => 'C-035',
            'proceso'   => 11,
            'iso'       => 'ISO/IEC 27002:2022 A.5.16, A.5.18',
            'enunciado' => 'Las cuentas del personal que causa baja o cambia de función se deshabilitan dentro de un plazo máximo definido.',
            'evidencia' => 'Procedimiento de baja, últimas bajas de personal y fecha real de deshabilitación de sus cuentas.',
            'pregunta'  => '¿Cuánto tiempo transcurrió entre la última baja de personal y la deshabilitación efectiva de su cuenta en la base de datos?',
        ],
        [
            'id'        => 'C-036',
            'proceso'   => 11,
            'iso'       => 'ISO/IEC 27002:2022 A.5.18',
            'enunciado' => 'Se realiza una revisión periódica de las cuentas activas que confirma que cada una sigue siendo necesaria y tiene un dueño identificado.',
            'evidencia' => 'Acta de la última revisión de cuentas con la lista de cuentas revocadas.',
            'pregunta'  => '¿Cuándo se revisaron por última vez las cuentas activas de la base de datos y cuántas se dieron de baja como resultado?',
        ],

        // Proceso 12 — Gestión de privilegios y roles
        [
            'id'        => 'C-037',
            'proceso'   => 12,
            'iso'       => 'ISO/IEC 27002:2022 A.5.15, A.8.2',
            'enunciado' => 'Los privilegios se otorgan mediante roles definidos por función y no de manera directa al usuario.',
            'evidencia' => 'Catálogo de roles con sus privilegios y consulta de privilegios otorgados directamente a usuarios.',
            'pregunta'  => '¿Qué roles existen, qué privilegios agrupa cada uno y cuántos usuarios tienen privilegios asignados fuera de un rol?',
        ],
        [
            'id'        => 'C-038',
            'proceso'   => 12,
            'iso'       => 'ISO/IEC 27002:2022 A.8.2',
            'enunciado' => 'Las cuentas de aplicación operan con el privilegio mínimo necesario y no poseen privilegios de administración del motor.',
            'evidencia' => 'Consulta de los privilegios efectivos de cada cuenta de aplicación.',
            'pregunta'  => '¿Con qué cuenta se conecta la aplicación a la base de datos y qué privilegios exactos tiene esa cuenta hoy?',
        ],
        [
            'id'        => 'C-039',
            'proceso'   => 12,
            'iso'       => 'ISO/IEC 27002:2022 A.5.15, A.8.2',
            'enunciado' => 'Los cambios de privilegios quedan registrados con solicitante, autorizador y fecha, y se revisan con periodicidad definida.',
            'evidencia' => 'Bitácora de otorgamiento y revocación de privilegios del último año y acta de su revisión.',
            'pregunta'  => '¿Cómo se registra el otorgamiento de un privilegio y quién verifica después que ese otorgamiento siga siendo necesario?',
        ],

        // Proceso 13 — Cuentas administrativas y accesos privilegiados
        [
            'id'        => 'C-040',
            'proceso'   => 13,
            'iso'       => 'ISO/IEC 27002:2022 A.8.2, A.5.17',
            'enunciado' => 'Las cuentas administrativas del motor son nominativas o su uso queda trazado hasta una persona identificable.',
            'evidencia' => 'Lista de cuentas administrativas y descripción del mecanismo de trazabilidad de su uso.',
            'pregunta'  => '¿Quiénes conocen la contraseña de la cuenta administrativa del motor y cómo se determina quién la usó en una fecha dada?',
        ],
        [
            'id'        => 'C-041',
            'proceso'   => 13,
            'iso'       => 'ISO/IEC 27002:2022 A.8.15, A.8.2',
            'enunciado' => 'El uso de privilegios administrativos se registra en una bitácora que el propio administrador no puede alterar ni borrar.',
            'evidencia' => 'Configuración de la auditoría de sesiones privilegiadas y permisos sobre el destino de la bitácora.',
            'pregunta'  => '¿Dónde se escribe la bitácora de las sesiones administrativas y qué impide que un administrador la modifique?',
        ],
        [
            'id'        => 'C-042',
            'proceso'   => 13,
            'iso'       => 'ISO/IEC 27002:2022 A.8.2',
            'enunciado' => 'El acceso administrativo se concede de forma temporal y acotada a una tarea específica, con revocación verificada al concluirla.',
            'evidencia' => 'Registro de accesos elevados con vigencia declarada y fecha de revocación confirmada.',
            'pregunta'  => '¿Cómo se otorga un acceso administrativo temporal, por cuánto tiempo se concede y quién confirma que se revocó?',
        ],

        // Proceso 14 — Autenticación y política de contraseñas
        [
            'id'        => 'C-043',
            'proceso'   => 14,
            'iso'       => 'ISO/IEC 27002:2022 A.5.17',
            'enunciado' => 'El perfil de contraseñas del motor impone longitud mínima, complejidad, vigencia y bloqueo por intentos fallidos.',
            'evidencia' => 'Exportación del perfil de contraseñas configurado y cuentas a las que se aplica.',
            'pregunta'  => '¿Qué longitud mínima, qué vigencia y cuántos intentos fallidos tiene configurado el perfil de contraseñas del motor?',
        ],
        [
            'id'        => 'C-044',
            'proceso'   => 14,
            'iso'       => 'ISO/IEC 27002:2022 A.8.5',
            'enunciado' => 'El acceso administrativo remoto a la base de datos exige autenticación multifactor o un mecanismo de fortaleza equivalente.',
            'evidencia' => 'Configuración del mecanismo de autenticación reforzada y lista de cuentas cubiertas.',
            'pregunta'  => '¿Qué segundo factor se solicita para administrar la base de datos desde fuera de la red interna y a qué cuentas cubre?',
        ],
        [
            'id'        => 'C-045',
            'proceso'   => 14,
            'iso'       => 'ISO/IEC 27002:2022 A.8.28, A.5.17',
            'enunciado' => 'Las credenciales de conexión no aparecen en texto claro en el código fuente, los archivos de configuración ni las tareas programadas.',
            'evidencia' => 'Revisión de archivos de configuración y de tareas programadas, y descripción del gestor de secretos utilizado.',
            'pregunta'  => '¿Dónde está guardada la contraseña con la que la aplicación se conecta a la base de datos y quién puede leerla?',
        ],

        // Proceso 15 — Criptografía y gestión de llaves
        [
            'id'        => 'C-046',
            'proceso'   => 15,
            'iso'       => 'ISO/IEC 27002:2022 A.8.24',
            'enunciado' => 'Los datos clasificados como sensibles están cifrados en reposo con un algoritmo y una longitud de llave vigentes.',
            'evidencia' => 'Inventario de objetos cifrados y configuración del algoritmo empleado.',
            'pregunta'  => '¿Qué columnas o espacios de datos están cifrados en reposo, con qué algoritmo y desde cuándo?',
        ],
        [
            'id'        => 'C-047',
            'proceso'   => 15,
            'iso'       => 'ISO/IEC 27002:2022 A.8.24, A.8.20',
            'enunciado' => 'Las conexiones a la base de datos viajan cifradas en la red, incluidas las de administración y las de respaldo.',
            'evidencia' => 'Configuración del cifrado en tránsito y captura de verificación del canal.',
            'pregunta'  => '¿Cómo se verifica que ninguna conexión a la base de datos viaja sin cifrar, incluidas las tareas automáticas nocturnas?',
        ],
        [
            'id'        => 'C-048',
            'proceso'   => 15,
            'iso'       => 'ISO/IEC 27002:2022 A.8.24',
            'enunciado' => 'Las llaves criptográficas tienen un ciclo de vida definido con custodio designado, resguardo separado del dato cifrado y periodicidad de rotación.',
            'evidencia' => 'Procedimiento de gestión de llaves y registro de la última rotación ejecutada.',
            'pregunta'  => '¿Quién custodia las llaves de cifrado, dónde se resguardan y cuándo se rotaron por última vez?',
        ],

        // Proceso 16 — Enmascaramiento y ambientes no productivos
        [
            'id'        => 'C-049',
            'proceso'   => 16,
            'iso'       => 'ISO/IEC 27002:2022 A.8.11, A.8.33',
            'enunciado' => 'Los datos productivos que se copian a ambientes de prueba se enmascaran o se sustituyen antes de ponerse a disposición de los usuarios de ese ambiente.',
            'evidencia' => 'Procedimiento de enmascaramiento y muestra del resultado obtenido en el ambiente de prueba.',
            'pregunta'  => '¿Qué campos se enmascaran al copiar datos a pruebas, con qué herramienta y quién valida el resultado?',
        ],
        [
            'id'        => 'C-050',
            'proceso'   => 16,
            'iso'       => 'ISO/IEC 27002:2022 A.8.33',
            'enunciado' => 'El uso de datos productivos para pruebas requiere autorización del propietario de la información y queda registrado con su vigencia.',
            'evidencia' => 'Autorizaciones de las últimas copias hacia ambientes no productivos.',
            'pregunta'  => '¿Quién autorizó la última copia de datos productivos a pruebas y por cuánto tiempo se autorizó conservarla?',
        ],
        [
            'id'        => 'C-051',
            'proceso'   => 16,
            'iso'       => 'ISO/IEC 27002:2022 A.8.31, A.8.33',
            'enunciado' => 'Los ambientes no productivos aplican controles de acceso equivalentes a los de producción sobre los datos que conservan.',
            'evidencia' => 'Comparación de cuentas y privilegios entre el ambiente de producción y el de pruebas.',
            'pregunta'  => '¿Quién tiene acceso al ambiente de pruebas y en qué se diferencia esa lista de la de acceso a producción?',
        ],

        // Proceso 23 — Retención, archivado y eliminación segura
        [
            'id'        => 'C-052',
            'proceso'   => 23,
            'iso'       => 'ISO/IEC 27002:2022 A.5.33',
            'enunciado' => 'Existe una política de retención por tipo de dato que define el plazo de conservación y el momento de la eliminación o el archivado.',
            'evidencia' => 'Política de retención autorizada con su tabla de plazos por tipo de registro.',
            'pregunta'  => '¿Cuánto tiempo se conserva cada tipo de registro en la base de datos y en qué documento están definidos esos plazos?',
        ],
        [
            'id'        => 'C-053',
            'proceso'   => 23,
            'iso'       => 'ISO/IEC 27002:2022 A.5.34',
            'enunciado' => 'Los datos personales contenidos en la base están identificados y su tratamiento cumple los requisitos legales aplicables.',
            'evidencia' => 'Inventario de tablas y columnas con datos personales y aviso de privacidad vinculado.',
            'pregunta'  => '¿Qué tablas o columnas contienen datos personales y qué requisito legal aplica a su conservación?',
        ],
        [
            'id'        => 'C-054',
            'proceso'   => 23,
            'iso'       => 'ISO/IEC 27002:2022 A.8.10, A.7.14',
            'enunciado' => 'La eliminación de datos y el retiro de medios de almacenamiento se ejecutan por un método que impide su recuperación y dejan constancia registrada.',
            'evidencia' => 'Procedimiento de borrado seguro y constancias de destrucción de los medios retirados.',
            'pregunta'  => '¿Con qué método se borran los datos que ya cumplieron su plazo y qué constancia queda de la destrucción de un disco retirado?',
        ],

        // Proceso 17 — Respaldo de bases de datos
        [
            'id'        => 'C-055',
            'proceso'   => 17,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13',
            'enunciado' => 'Existe una política de respaldo que define alcance, frecuencia, retención, RPO y RTO por base de datos.',
            'evidencia' => 'Política de respaldo autorizada con la tabla de parámetros por base de datos.',
            'pregunta'  => '¿Qué RPO y qué RTO están comprometidos para cada base de datos y quién los aprobó?',
        ],
        [
            'id'        => 'C-056',
            'proceso'   => 17,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13',
            'enunciado' => 'La ejecución de cada respaldo se verifica de forma automática y las fallas generan una alerta con atención registrada.',
            'evidencia' => 'Bitácora de respaldos del último mes con resultado y evidencia de la atención de las fallas.',
            'pregunta'  => '¿Cómo se entera el equipo de que un respaldo falló durante la noche y qué se hizo con la última falla registrada?',
        ],
        [
            'id'        => 'C-057',
            'proceso'   => 17,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13, A.8.24',
            'enunciado' => 'Existe al menos una copia de respaldo cifrada y resguardada en una ubicación distinta de la de la base de datos original.',
            'evidencia' => 'Ubicación del resguardo externo, contrato o convenio que lo respalda y configuración de cifrado de los respaldos.',
            'pregunta'  => '¿Dónde está físicamente la copia de respaldo más reciente fuera del sitio principal y cómo está protegida?',
        ],

        // Proceso 18 — Recuperación y continuidad del servicio
        [
            'id'        => 'C-058',
            'proceso'   => 18,
            'iso'       => 'ISO/IEC 27002:2022 A.8.13',
            'enunciado' => 'Se realizan pruebas de restauración completas con periodicidad definida y su resultado queda documentado.',
            'evidencia' => 'Informe de la última prueba de restauración con los tiempos obtenidos y el responsable de la ejecución.',
            'pregunta'  => '¿Cuándo fue la última prueba real de restauración y cuál fue el tiempo de recuperación obtenido?',
        ],
        [
            'id'        => 'C-059',
            'proceso'   => 18,
            'iso'       => 'ISO/IEC 27002:2022 A.5.29, A.5.30',
            'enunciado' => 'Existe un plan de continuidad para las bases de datos con roles asignados, secuencia de recuperación y criterios de activación.',
            'evidencia' => 'Plan de continuidad vigente y acta del último ejercicio realizado.',
            'pregunta'  => '¿Quién decide activar el plan de continuidad, con qué criterio y en qué orden se recuperan las bases de datos?',
        ],
        [
            'id'        => 'C-060',
            'proceso'   => 18,
            'iso'       => 'ISO/IEC 27002:2022 A.5.30, A.8.13',
            'enunciado' => 'Los tiempos de recuperación obtenidos en las pruebas se comparan contra el RTO comprometido y las desviaciones generan acciones con seguimiento.',
            'evidencia' => 'Comparativo entre RTO comprometido y RTO real, con las acciones derivadas y su estado.',
            'pregunta'  => '¿Qué diferencia hubo entre el RTO comprometido y el tiempo real de la última prueba, y qué se hizo con esa diferencia?',
        ],

        // Proceso 19 — Alta disponibilidad y replicación
        [
            'id'        => 'C-061',
            'proceso'   => 19,
            'iso'       => 'ISO/IEC 27002:2022 A.8.14',
            'enunciado' => 'La arquitectura de alta disponibilidad está documentada y su nivel de redundancia corresponde a la criticidad declarada de cada base de datos.',
            'evidencia' => 'Diagrama de arquitectura y matriz que cruza criticidad con nivel de redundancia.',
            'pregunta'  => '¿Qué bases operan con redundancia, de qué tipo, y qué criterio definió cuáles la tienen y cuáles no?',
        ],
        [
            'id'        => 'C-062',
            'proceso'   => 19,
            'iso'       => 'ISO/IEC 27002:2022 A.8.16, A.8.14',
            'enunciado' => 'El estado y el retraso de la replicación se monitorean con umbrales que generan alerta ante una desincronización.',
            'evidencia' => 'Tablero o configuración de alertas de retraso de replicación y registro del último incidente detectado.',
            'pregunta'  => '¿Cuánto retraso de replicación se considera aceptable, quién lo vigila y cuál fue el máximo observado en el último mes?',
        ],
        [
            'id'        => 'C-063',
            'proceso'   => 19,
            'iso'       => 'ISO/IEC 27002:2022 A.8.14',
            'enunciado' => 'La conmutación al nodo secundario se prueba con periodicidad definida y su resultado queda documentado.',
            'evidencia' => 'Bitácora de pruebas de conmutación con fecha y tiempo de indisponibilidad observado.',
            'pregunta'  => '¿Cuándo se probó por última vez la conmutación al nodo secundario y cuánto tiempo estuvo el servicio fuera?',
        ],

        // Proceso 20 — Monitoreo y gestión del desempeño
        [
            'id'        => 'C-064',
            'proceso'   => 20,
            'iso'       => 'ISO/IEC 27002:2022 A.8.16',
            'enunciado' => 'Existe un monitoreo continuo de la disponibilidad y el desempeño de la base de datos, con alertas configuradas y destinatario definido.',
            'evidencia' => 'Herramienta de monitoreo, catálogo de alertas y cobertura de instancias vigiladas.',
            'pregunta'  => '¿Qué herramienta vigila la base de datos, qué alertas tiene configuradas y a cuántas instancias cubre?',
        ],
        [
            'id'        => 'C-065',
            'proceso'   => 20,
            'iso'       => 'ISO/IEC 27004:2016; A.8.16',
            'enunciado' => 'Se han definido indicadores de desempeño con meta, responsable y periodicidad de medición, y se reportan a la dirección.',
            'evidencia' => 'Tablero de indicadores con meta, valor actual y último reporte entregado.',
            'pregunta'  => '¿Qué indicadores de desempeño se reportan a la dirección, con qué meta y cada cuánto se miden?',
        ],
        [
            'id'        => 'C-066',
            'proceso'   => 20,
            'iso'       => 'ISO/IEC 27002:2022 A.8.6, A.8.16',
            'enunciado' => 'Las consultas de mayor consumo se identifican periódicamente y se optimizan con seguimiento de la mejora obtenida.',
            'evidencia' => 'Informe de consultas críticas y comparativo de tiempos antes y después de la optimización.',
            'pregunta'  => '¿Cuál fue la última consulta que se optimizó, cuánto tardaba antes y cuánto tarda ahora?',
        ],

        // Proceso 21 — Auditoría de la base de datos y registros
        [
            'id'        => 'C-067',
            'proceso'   => 21,
            'iso'       => 'ISO/IEC 27002:2022 A.8.15',
            'enunciado' => 'La auditoría del motor está habilitada y registra al menos los accesos a datos sensibles, los cambios de privilegios y los intentos de conexión fallidos.',
            'evidencia' => 'Configuración de la auditoría y muestra de los eventos efectivamente registrados.',
            'pregunta'  => '¿Qué eventos quedan registrados hoy por la auditoría del motor y qué eventos se decidió no registrar y por qué?',
        ],
        [
            'id'        => 'C-068',
            'proceso'   => 21,
            'iso'       => 'ISO/IEC 27002:2022 A.8.15; ISO/IEC 27007:2020',
            'enunciado' => 'Los registros de auditoría se revisan con periodicidad definida y cada revisión deja constancia escrita con sus hallazgos.',
            'evidencia' => 'Actas de revisión de bitácoras del último semestre con los hallazgos y su seguimiento.',
            'pregunta'  => '¿Quién revisa las bitácoras de la base de datos, cada cuánto lo hace y qué encontró en la última revisión?',
        ],
        [
            'id'        => 'C-069',
            'proceso'   => 21,
            'iso'       => 'ISO/IEC 27002:2022 A.8.17',
            'enunciado' => 'Los relojes de los servidores de base de datos están sincronizados contra una fuente de tiempo única y verificada.',
            'evidencia' => 'Configuración del servicio de tiempo y verificación del desfase observado.',
            'pregunta'  => '¿Contra qué fuente de tiempo se sincronizan los servidores de base de datos y qué desfase máximo se ha observado?',
        ],

        // Proceso 22 — Gestión de incidentes de seguridad en la base de datos
        [
            'id'        => 'C-070',
            'proceso'   => 22,
            'iso'       => 'ISO/IEC 27002:2022 A.5.24, A.5.26',
            'enunciado' => 'Existe un procedimiento de gestión de incidentes que define roles, clasificación por severidad y tiempos de respuesta para los eventos de base de datos.',
            'evidencia' => 'Procedimiento vigente y matriz de clasificación con los tiempos comprometidos.',
            'pregunta'  => '¿Quién coordina la atención de un incidente en la base de datos y qué tiempo de respuesta se compromete según su severidad?',
        ],
        [
            'id'        => 'C-071',
            'proceso'   => 22,
            'iso'       => 'ISO/IEC 27002:2022 A.5.25, A.5.26',
            'enunciado' => 'El personal conoce el canal por el que debe reportar un evento sospechoso en la base de datos y los reportes quedan registrados.',
            'evidencia' => 'Constancia de difusión del canal de reporte y bitácora de eventos reportados.',
            'pregunta'  => '¿Por qué canal reporta un operador un comportamiento extraño en la base de datos y cuántos reportes se recibieron en el último año?',
        ],
        [
            'id'        => 'C-072',
            'proceso'   => 22,
            'iso'       => 'ISO/IEC 27002:2022 A.5.27, A.5.28',
            'enunciado' => 'Los incidentes cierran con análisis de causa raíz, lecciones aprendidas y evidencia preservada bajo cadena de custodia.',
            'evidencia' => 'Expediente del último incidente con la causa raíz determinada y el registro de custodia de la evidencia.',
            'pregunta'  => '¿Cuál fue el último incidente de base de datos, qué causa raíz se determinó y cómo se preservó la evidencia?',
        ],

        // Proceso 24 — Proveedores y bases de datos gestionadas o en la nube
        [
            'id'        => 'C-073',
            'proceso'   => 24,
            'iso'       => 'ISO/IEC 27002:2022 A.5.19, A.5.20',
            'enunciado' => 'Los contratos con proveedores de servicios de base de datos incluyen requisitos de seguridad, niveles de servicio y condiciones de terminación.',
            'evidencia' => 'Contrato vigente con su anexo de seguridad y de niveles de servicio.',
            'pregunta'  => '¿Qué nivel de servicio y qué obligaciones de seguridad están escritos en el contrato del proveedor de base de datos?',
        ],
        [
            'id'        => 'C-074',
            'proceso'   => 24,
            'iso'       => 'ISO/IEC 27002:2022 A.5.22; ISO/IEC 27011:2016',
            'enunciado' => 'El desempeño y el cumplimiento de seguridad del proveedor se supervisan con evidencia periódica y con validación por parte de la organización.',
            'evidencia' => 'Informes de servicio recibidos y minutas de seguimiento del último año.',
            'pregunta'  => '¿Con qué frecuencia se revisa el cumplimiento del proveedor, qué evidencia entrega y quién la valida dentro de la organización?',
        ],
        [
            'id'        => 'C-075',
            'proceso'   => 24,
            'iso'       => 'ISO/IEC 27002:2022 A.5.23, A.5.21',
            'enunciado' => 'Para los servicios de base de datos en la nube están definidas la matriz de responsabilidad compartida, la ubicación de los datos y el mecanismo de recuperación al terminar el servicio.',
            'evidencia' => 'Matriz de responsabilidad compartida firmada y cláusula contractual de portabilidad o salida.',
            'pregunta'  => '¿En qué país residen los datos alojados en la nube, qué tareas de seguridad ejecuta el proveedor y cómo se recuperan los datos si termina el contrato?',
        ],
    ],

    // ── Escala de madurez ────────────────────────────────────────────────────
    'escala' => [
        ['nivel' => 0, 'nombre' => 'Inexistente',           'descripcion' => 'El control no existe ni se ha considerado. La organización no reconoce la necesidad.'],
        ['nivel' => 1, 'nombre' => 'Inicial',               'descripcion' => 'La actividad se ejecuta de forma reactiva y depende del criterio individual de quien la atiende.'],
        ['nivel' => 2, 'nombre' => 'Repetible',             'descripcion' => 'La práctica se repite con regularidad, pero no está documentada ni es uniforme entre personas.'],
        ['nivel' => 3, 'nombre' => 'Documentado',           'descripcion' => 'Existe un procedimiento escrito, comunicado y aplicado de manera consistente.'],
        ['nivel' => 4, 'nombre' => 'Gestionado y medido',   'descripcion' => 'El control se mide con indicadores y sus desviaciones generan acciones correctivas.'],
        ['nivel' => 5, 'nombre' => 'Optimizado',            'descripcion' => 'El control se mejora de forma continua con base en la medición y en el aprendizaje de incidentes.'],
    ],

    // ── Marco normativo ──────────────────────────────────────────────────────
    'marco' => [
        ['norma' => 'ISO/IEC 27000:2018', 'titulo' => 'Visión general y vocabulario',                              'aporte' => 'Fija el significado de los términos que usa el instrumento: activo, control, riesgo, evento y no conformidad. Evita que consultor y DBA discutan sobre palabras distintas.'],
        ['norma' => 'ISO/IEC 27001:2022', 'titulo' => 'Requisitos del sistema de gestión',                         'aporte' => 'Aporta la estructura de gobierno: liderazgo, planificación del riesgo, competencia y mejora. De su cláusula 6.1.3 proviene la regla de excluir del cálculo los controles marcados como "no aplica".'],
        ['norma' => 'ISO/IEC 27002:2022', 'titulo' => 'Controles de seguridad de la información',                  'aporte' => 'Es la fuente directa de los 93 controles del anexo A. Cada uno de los 75 controles del instrumento cita el control de esta norma del que se deriva.'],
        ['norma' => 'ISO/IEC 27003:2017', 'titulo' => 'Guía de implementación',                                    'aporte' => 'Orienta el orden de implantación cuando la evaluación revela muchas brechas a la vez: qué atender primero y con qué alcance.'],
        ['norma' => 'ISO/IEC 27004:2016', 'titulo' => 'Seguimiento, medición, análisis y evaluación',              'aporte' => 'Sustenta el tablero: define qué es un indicador válido, cómo se establece su meta y con qué periodicidad se mide.'],
        ['norma' => 'ISO/IEC 27005:2022', 'titulo' => 'Gestión de riesgos de seguridad de la información',        'aporte' => 'Da el método detrás de las tres casillas de riesgo del instrumento: identificar, analizar y valorar el efecto sobre integridad, confidencialidad y disponibilidad.'],
        ['norma' => 'ISO/IEC 27006:2021', 'titulo' => 'Requisitos para organismos de certificación',              'aporte' => 'Define el nivel de evidencia que un auditor externo consideraría suficiente. Sirve como referencia para calibrar la exigencia de la columna de evidencia.'],
        ['norma' => 'ISO/IEC 27007:2020', 'titulo' => 'Directrices para la auditoría del SGSI',                    'aporte' => 'Rige la redacción de las 75 preguntas del cuestionario: entrevista abierta, verificable y orientada al hecho, no a la intención declarada.'],
        ['norma' => 'ISO/IEC 27008:2019', 'titulo' => 'Evaluación técnica de los controles',                       'aporte' => 'Respalda la parte técnica de la revisión: comprobación de parámetros, privilegios efectivos y configuración real frente a la documentada.'],
        ['norma' => 'ISO/IEC 27009:2020', 'titulo' => 'Aplicación sectorial de la norma 27001',                    'aporte' => 'Autoriza y encuadra la adaptación de los controles genéricos al dominio concreto de la administración de bases de datos.'],
        ['norma' => 'ISO/IEC 27010:2015', 'titulo' => 'Comunicación entre organizaciones y sectores',              'aporte' => 'Cubre el intercambio de información de la base de datos con terceros: qué se comparte, por qué medio y bajo qué acuerdo.'],
        ['norma' => 'ISO/IEC 27011:2016', 'titulo' => 'Controles para organizaciones de telecomunicaciones',       'aporte' => 'Aporta el tratamiento de servicios operados por un proveedor, incluidas las bases de datos gestionadas y las alojadas en la nube.'],
    ],

    // ── Referencias ──────────────────────────────────────────────────────────
    'referencias' => [
        ['titulo' => 'ISO/IEC 27000:2018 — Overview and vocabulary',                        'fuente' => 'ISO (texto de acceso público)', 'enlace' => 'https://standards.iso.org/ittf/PubliclyAvailableStandards/'],
        ['titulo' => 'ISO/IEC 27001:2022 — Information security management systems',        'fuente' => 'ISO',                            'enlace' => 'https://www.iso.org/standard/27001'],
        ['titulo' => 'ISO/IEC 27002:2022 — Information security controls',                  'fuente' => 'ISO',                            'enlace' => 'https://www.iso.org/standard/75652.html'],
        ['titulo' => 'ISO/IEC 27003:2017 — Guidance',                                       'fuente' => 'ISO',                            'enlace' => 'https://www.iso.org/standard/63417.html'],
        ['titulo' => 'ISO/IEC 27004:2016 — Monitoring, measurement, analysis and evaluation', 'fuente' => 'ISO',                          'enlace' => 'https://www.iso.org/standard/64120.html'],
        ['titulo' => 'ISO/IEC 27005:2022 — Guidance on managing information security risks', 'fuente' => 'ISO',                           'enlace' => 'https://www.iso.org/standard/80585.html'],
        ['titulo' => 'ISO/IEC 27006:2021 — Requirements for certification bodies',           'fuente' => 'ISO',                            'enlace' => 'https://www.iso.org/standard/82908.html'],
        ['titulo' => 'ISO/IEC 27007:2020 — Guidelines for information security management systems auditing', 'fuente' => 'ISO',           'enlace' => 'https://www.iso.org/standard/77802.html'],
        ['titulo' => 'ISO/IEC TS 27008:2019 — Guidelines for the assessment of information security controls', 'fuente' => 'ISO',         'enlace' => 'https://www.iso.org/standard/67397.html'],
        ['titulo' => 'ISO/IEC 27009:2020 — Sector-specific application of ISO/IEC 27001',    'fuente' => 'ISO',                            'enlace' => 'https://www.iso.org/standard/73907.html'],
        ['titulo' => 'ISO/IEC 27010:2015 — Inter-sector and inter-organizational communications', 'fuente' => 'ISO',                       'enlace' => 'https://www.iso.org/standard/68427.html'],
        ['titulo' => 'ISO/IEC 27011:2016 — Guidelines for telecommunications organizations', 'fuente' => 'ISO',                            'enlace' => 'https://www.iso.org/standard/64143.html'],
        ['titulo' => 'Catálogo de normas ISO/IEC JTC 1/SC 27',                               'fuente' => 'ISO — Comité técnico',           'enlace' => 'https://www.iso.org/committee/45306/x/catalogue/'],
        ['titulo' => 'Catálogo de normas INTE/ISO/IEC y su equivalencia internacional',      'fuente' => 'INTECO — organismo nacional de normalización de Costa Rica', 'enlace' => 'https://www.inteco.org/'],
    ],
];
