<?php

declare(strict_types=1);

/** Diccionario en inglés. Mismas claves que config/idiomas/es.php. */
return [
    // Navegación / encabezado
    'nav.nosotros'        => 'About us',
    'nav.herramientas'    => 'Tools',
    'nav.mis_auditorias'  => 'My Audits',
    'nav.catalogo'        => 'Catalog',
    'nav.perfil'          => 'Profile',
    'nav.ir_tablero'      => 'Go to dashboard',
    'nav.salir'           => 'Log out',
    'nav.ingresar'        => 'Log in',
    'nav.contactar'       => 'Contact',
    'nav.abrir_menu'      => 'Open navigation menu',
    'nav.idioma'          => 'Language',
    'nav.principal'       => 'Main navigation',
    'nav.movil'           => 'Mobile navigation',
    'nav.saltar_contenido' => 'Skip to content',

    // Marco del módulo interno: barra lateral y barra superior.
    'panel.navegacion'            => 'Module navigation',
    'panel.ubicacion'             => 'Current location',
    'panel.mostrar_navegacion'    => 'Show navigation',
    'panel.ocultar_navegacion'    => 'Hide navigation',
    'panel.grupo_auditorias'      => 'Audits',
    'panel.grupo_administracion'  => 'Administration',
    'panel.grupo_referencia'      => 'Reference',
    'panel.grupo_monitoreo'       => 'Monitoring',
    'panel.catalogo_controles'    => 'Control catalog',
    'panel.matriz_cid'            => 'Process vs C-I-A map',
    'panel.remediaciones_vencidas' => 'Overdue remediations',
    'panel.volver_sitio'          => 'Back to the site',
    'panel.rol_auditor'           => 'Auditor',
    'panel.rol_admin'             => 'DB administrator',

    // Module assistant: corner launcher and right-hand panel.
    // The NAME lives in a single key and the rest receive it as %s.
    'asistente.nombre'              => 'Lembas',
    'asistente.funcion'             => 'Audit assistant',
    'asistente.abrir'               => 'Open %s',
    'asistente.cerrar'              => 'Close %s',
    'asistente.responde_como'       => 'With your account permissions · %s',
    'asistente.contexto'            => 'Current screen',
    'asistente.conversacion'        => 'Conversation with %s',
    'asistente.bienvenida'          => 'Hi, %s. I am %s, what would you like to look up?',
    'asistente.alcance_auditor'     => 'I can show you your audits, the summary and risks of each one, open a control for you to fill in, and explain the control catalog. I only work with the audits you conducted.',
    'asistente.alcance_admin'       => 'I can show you your audits, the summary and risks of each one, open a control for you to fill in, explain the control catalog, and show overdue remediations across all audits.',
    'asistente.privacidad'          => 'Your audit data is shown here but never sent to %s. Do not type findings or evidence in the chat: they belong in the control form.',
    'asistente.para_empezar'        => 'To get started',
    'asistente.sugerencia_auditorias' => 'Show me my audits',
    'asistente.sugerencia_resumen'  => 'Summary of this audit',
    'asistente.sugerencia_riesgo'   => 'Which controls have the highest risk?',
    'asistente.sugerencia_llenar'   => 'I want to fill in the backup control',
    'asistente.sugerencia_catalogo' => 'What evidence does control C-014 require?',
    'asistente.sugerencia_vencidas' => 'Which remediations are overdue?',
    'asistente.mensaje'             => 'Message for %s',
    'asistente.marcador'            => 'Ask %s…',
    'asistente.enviar'              => 'Send',
    'asistente.ayuda_teclado'       => 'Enter sends; Shift + Enter, new line.',
    'asistente.aviso_ia'            => '%s is an AI: check its answers against the evidence.',
    'asistente.usted'               => 'You',
    'asistente.nueva_conversacion'  => 'New conversation',
    'asistente.pensando'            => '%s is looking it up…',

    // Cards: data rendered by the server.
    'asistente.ficha_auditorias'    => 'Your audits',
    'asistente.ficha_sin_auditorias' => 'You have no audits yet.',
    'asistente.ficha_resumen'       => 'Summary · Audit %s',
    'asistente.ficha_riesgo'        => 'Highest risk · Audit %s',
    'asistente.ficha_sin_riesgo'    => 'No control in this audit has a calculated risk level yet.',
    'asistente.ficha_sin_vencidas'  => 'There are no overdue remediations.',
    'asistente.auditoria_n'         => 'Audit %s',
    'asistente.ver_todas'           => 'See all',
    'asistente.mostrando_de'        => 'Showing %s of %s.',
    'asistente.respuestas_si_no_na' => 'Answers: %s Yes · %s No · %s Not applicable',
    'asistente.respuesta_registrada' => 'Recorded answer: %s',
    'asistente.pregunta_auditoria'  => 'Audit question:',
    'asistente.evidencia_esperada'  => 'Expected evidence:',
    'asistente.auditoria_finalizada' => 'This audit is finalized: reopen it to change this control.',
    'asistente.abrir_formulario'    => 'Fill in this control',
    'asistente.revisar_formulario'  => 'Review the answer',
    'asistente.vencio'              => 'Due: %s',
    'asistente.responsable'         => 'Owner: %s',

    // Notices inside a turn.
    'asistente.aviso_auditoria_no_encontrada' => 'I could not find audit %s among yours.',
    'asistente.aviso_control_no_encontrado'   => 'Code %s does not match any control in the catalog.',
    'asistente.aviso_rechazo'       => 'I cannot answer that request. Try phrasing it differently.',
    'asistente.aviso_cortada'       => 'The answer was cut off for being too long.',
    'asistente.aviso_incompleta'    => 'I could not finish the request in the available steps. Try a more specific question.',

    // Errors: the type decides the text; technical detail goes to the log.
    'asistente.error_vacia'         => 'Type a question before sending.',
    'asistente.error_larga'         => 'The question is too long. Keep it under 2000 characters.',
    'asistente.error_codificacion'  => 'The question contains characters that could not be read. Please type it again.',
    'asistente.error_limite_usuario' => 'You reached the limit of %s daily requests. You can ask again tomorrow.',
    'asistente.error_limite_total'  => 'The system reached its daily request limit. Try again tomorrow.',
    'asistente.error_sesion'        => 'Your session expired. Reload the page and log in again.',
    'asistente.error_apagado'       => 'The assistant is not configured on this server.',
    'asistente.error_red'           => 'I could not reach the service. Check the connection and try again.',
    'asistente.error_saturado'      => 'The service is overloaded right now. Try again in a few minutes.',
    'asistente.error_credencial'    => 'The API key is invalid or was revoked. Let the system administrator know.',
    'asistente.error_saldo'         => 'The API account ran out of credit or reached its spend limit.',
    'asistente.error_peticion'      => 'Something failed while preparing the request. It was logged; try again.',
    'asistente.error_interno'       => 'Something failed while processing the request. Try again.',

    // Testimonios de la portada
    'testimonios.calificacion' => 'Rating: %s out of 5 stars',
    'testimonios.lista'        => 'Client testimonials',
    'testimonios.anterior'     => 'See the previous testimonial',
    'testimonios.siguiente'    => 'See the next testimonial',
    'testimonios.ir_a'         => 'See the testimonial from %s',
    'testimonios.calificacion_corta' => '%s of 5.0',
    'testimonios.anterior_corto'   => 'previous',
    'testimonios.siguiente_corto'  => 'next',

    // Pie de página
    'pie.contacto'        => 'Contact',
    'pie.proyecto'        => 'Academic project — Database Administration.',
    'pie.eslogan_extra'   => "We reduce the operational risk of your organization's critical information.",
    'pie.arquitectura'    => 'MVC architecture in PHP',

    // Botones y palabras comunes
    'comun.guardar'       => 'Save',
    'comun.cancelar'      => 'Cancel',
    'comun.editar'        => 'Edit',
    'comun.eliminar'      => 'Delete',
    'comun.crear'         => 'Create',
    'comun.volver'        => 'Back',
    'comun.si'            => 'Yes',
    'comun.no'            => 'No',
    'comun.no_aplica'     => 'N/A',
    'comun.buscar'        => 'Search',

    // Authentication
    'auth.eyebrow'            => 'ISO/IEC 27002 risk assessment',
    'auth.ingresar_titulo'    => 'Log in',
    'auth.ingresar_texto'     => 'Access for %s auditors.',
    'auth.correo'             => 'Email address',
    'auth.clave'              => 'Password',
    'auth.entrar'             => 'Log in',
    'auth.sin_cuenta'         => "Don't have an account?",
    'auth.registrarse'        => 'Sign up',
    'auth.registrarse_titulo' => 'Create account',
    'auth.registrarse_texto'  => 'The account is created with an auditor profile.',
    'auth.nombre_completo'    => 'Full name',
    'auth.organizacion'       => 'Organization',
    'auth.confirmar_clave'    => 'Repeat password',
    'auth.crear_cuenta'       => 'Create account',
    'auth.ya_tiene_cuenta'    => 'Already have an account?',
    'auth.iniciar_sesion'     => 'Log in',
    'auth.minimo_caracteres'  => 'Minimum %s characters.',

    // Log-in welcome panel
    'auth.panel_titulo'       => 'Start your audit',
    'auth.panel_texto'        => 'Three steps to assess the 75 controls of the instrument.',
    'auth.paso_uno'           => 'Log in',
    // The first step names what is being done on each screen.
    'auth.paso_uno_registro'  => 'Create your account',
    'auth.paso_dos'           => 'Open an audit',
    'auth.paso_tres'          => 'Assess and report',

    // Assessment module — dashboard and new audit
    'eval.comparar_historico'  => 'Compare history',
    'eval.nueva_auditoria'     => 'New audit',
    'eval.mis_auditorias'      => 'My audits',
    'eval.sin_auditorias'      => 'No audits yet.',

    // Resumen de la cartera, en la cabecera de "Mis auditorías".
    'eval.kpi_auditorias'      => 'Audits',
    'eval.kpi_organizaciones'  => 'Distinct organizations: %s',
    'eval.kpi_pendientes'      => 'Awaiting closure',
    'eval.kpi_finalizadas'     => 'Completed',
    'eval.kpi_congeladas'      => 'Frozen results',
    'eval.kpi_indice'          => 'Average risk index',
    'eval.kpi_calculadas'      => 'Audits with a calculated index: %s',
    'eval.kpi_sin_calculo'     => 'Not calculated yet',

    // Tablero del panel: matriz de la última auditoría y evolución mensual.
    'eval.evolucion_titulo'    => 'Monthly progress',
    'eval.tablero_titulo'       => 'Status and progress',
    'eval.empresa_coincidencias' => 'Matching companies',
    'eval.empresa_auditada'    => 'Audited organization',
    'eval.evolucion_texto'     => 'How much of the instrument was applied each month, and what share came out compliant.',
    'eval.cobertura_instrumento' => 'Instrument coverage',
    'eval.sin_evolucion'       => 'No months with audits to plot yet.',
    'eval.evolucion_un_mes'    => 'There are audits from a single month (%s). The trend line appears with two or more.',
    'eval.evolucion_resumen'   => 'Progress across %s months with audits. Latest compliance: %s. Latest coverage: %s.',
    'eval.punto_cobertura'     => 'Month %s · coverage %s · audits: %s',
    'eval.punto_cumplimiento'  => 'Month %s · compliance %s',
    'eval.eje_meses'           => 'Months with recorded audits, not consecutive calendar months.',
    'eval.eje_matriz_corto'    => 'Probability ↑ · Impact →',
    'eval.empresa_marcador'    => 'Type the organization name',
    'eval.ver_progreso'        => 'View progress',
    'eval.empresa_sin_coincidencia' => 'No audits for "%s". Showing %s.',

    // Bases de datos conectadas (previsualización del monitoreo).
    'bd.titulo'                => 'Connected databases',
    'bd.texto'                 => 'Engines under watch, awaiting a health check.',
    'bd.conexion'              => 'connection',
    'bd.conexiones'            => 'connections',
    'bd.enlace_activo'         => 'link up',
    'bd.total'                 => '%s connections across %s engines.',
    'bd.previsualizacion'      => 'Preview: per-instance diagnostics arrive with the monitoring module.',
    'bd.sin_conexiones'        => 'No databases registered yet.',

    // Filtros y paginación de la tabla de auditorías.
    'eval.buscar_etiqueta'     => 'Search by organization or area',
    'eval.buscar_marcador'     => 'e.g. cooperative, backups…',
    'eval.limpiar'             => 'Clear',
    'eval.ordenar_por'         => 'Sort by',
    'eval.orden_recientes'     => 'Most recent',
    'eval.orden_indice'        => 'Highest index',
    'eval.sin_coincidencias'   => 'No audit matches.',
    'eval.sin_coincidencias_texto' => '"%s" was not found in the organization or the area assessed.',
    'eval.paginacion'          => 'Audit pagination',
    'eval.rango'               => 'Showing %s–%s of %s',
    'eval.anterior'            => 'Previous',
    'eval.siguiente'           => 'Next',
    'eval.celda_matriz'        => 'Impact %s · Probability %s · controls: %s · %s',

    'eval.crear_primera'       => 'Create the first one to start assessing the %s controls in the instrument.',
    'eval.col_organizacion'    => 'Organization',
    'eval.col_area'            => 'Area assessed',
    'eval.col_fecha'           => 'Date',
    'eval.col_estado'          => 'Status',
    'eval.col_indice'          => 'Index',
    'eval.finalizada'          => 'Completed',
    'eval.en_progreso'         => 'In progress',
    'eval.sin_calcular'        => 'not calculated',
    'eval.volver'              => 'Back',
    'eval.crear_auditoria'     => 'Create audit',

    // Assessment module — audit detail (mostrar.php)
    'eval.auditoria_n'         => 'Audit %s',
    'eval.entrevistado'        => 'interviewee:',
    'eval.ver_resultados'      => 'View results',
    'eval.finalizar'           => 'Finish',
    'eval.reabrir'             => 'Reopen',
    'eval.aviso_finalizada'    => 'This audit is finished and no longer accepts changes. Reopen it to keep assessing.',
    'eval.avance'              => 'Assessment progress',
    'eval.de_controles'        => 'of',
    'eval.controles_palabra'   => 'controls',
    'eval.editar_encabezado'   => 'Edit header',
    'eval.guardar_encabezado'  => 'Save header',
    'eval.controles_instrumento' => 'Instrument controls',
    'eval.dominios_lista'       => 'Audit domains',
    'eval.col_codigo'          => 'Code',
    'eval.col_proceso'         => 'Process',
    'eval.col_enunciado'       => 'Statement',
    'eval.col_resp'            => 'Ans.',
    'eval.col_madurez'         => 'Maturity',

    // Assessment module — rate control (control.php)
    'eval.sin_proceso'         => 'No process',
    'eval.pregunta_auditoria'  => 'Audit question',
    'eval.evidencia_esperada'  => 'Expected evidence',
    'eval.respuesta'           => 'Answer',
    'eval.nivel_madurez'       => 'Observed maturity level',
    'eval.sin_calificar'       => '— Not rated —',
    'eval.criterio_comprobacion' => 'Verification criterion',
    'eval.ninguno'             => '— None —',
    'eval.que_compromete'      => 'What is at risk if this control fails?',
    'eval.confidencialidad'    => 'Confidentiality',
    'eval.integridad'          => 'Integrity',
    'eval.disponibilidad'      => 'Availability',
    'eval.impacto'             => 'Impact',
    'eval.probabilidad'        => 'Probability',
    'eval.nivel_riesgo_reg'    => 'Recorded risk level:',
    'eval.promedio_impacto'    => '(average of impact and probability)',
    'eval.hallazgo'            => 'Finding',
    'eval.recomendacion'       => 'Recommendation',
    'eval.guardar'             => 'Save',
    'eval.guardar_siguiente'   => 'Save and next',
    'eval.estado_si'           => 'Yes',
    'eval.estado_no'           => 'No',
    'eval.estado_na'           => 'N/A',
    'eval.grado_logro'         => 'Practice achievement',
    'eval.grado_ayuda'         => 'N: not (0-15%) · P: partially (15-50%) · L: largely (50-85%) · F: fully (85-100%). L and F count as achieved.',
    'eval.grado_n'             => 'N — Not achieved',
    'eval.grado_p'             => 'P — Partially',
    'eval.grado_l'             => 'L — Largely',
    'eval.grado_f'             => 'F — Fully',
    'eval.capacidad'           => 'Capability',
    'eval.capacidad_objetivo'  => 'Objective capability',
    'eval.capacidad_sin_declarar' => 'Capability not set',
    'eval.justificacion_capacidad' => 'Rationale (which practices and evidence support it)',
    'eval.guardar_capacidad'   => 'Save capability',
    'eval.criterio_documentado' => 'Documented',
    'eval.criterio_repetible'   => 'Repeatable',
    'eval.criterio_evidencia'   => 'With evidence',
    'eval.evidencia_verificada' => 'Evidence reviewed',
    'eval.evidencia_ayuda'      => 'Required if the answer is "Yes": describe the document, log, screenshot, or configuration that supports the answer (ISO/IEC 27007 — conformity is proven with evidence, not with the auditee\'s statement).',
    'eval.calidad_evidencia'    => 'Evidence quality',
    'eval.calidad_bien'         => 'Well implemented',
    'eval.calidad_mejora'       => 'Needs improvement',
    'eval.calidad_declarativo'  => 'Declarative (no real evidence)',

    // Control card inside an audit
    // (components/tarjeta-control-auditoria.php)
    'eval.sin_responder'        => 'Not answered',
    'eval.nivel_riesgo'         => 'Risk level',
    'eval.dimensiones'          => 'Compromises',
    'eval.no_marcada'           => 'not marked',
    'eval.aviso_na_sin_justificar' => 'Marked as "not applicable" with no finding written: it leaves the compliance calculation without any record of why (ISO/IEC 27001, cl. 6.1.3).',
    'eval.dominio_anterior'     => 'Previous domain',
    'eval.dominio_siguiente'    => 'Next domain',
    'eval.guardado'             => 'Saved',
    'eval.remediaciones'        => 'Remediation',
    'eval.respondidos'          => 'Answered',
    'eval.dominios_palabra'     => 'Domains',
    'eval.evidencia_marcador'   => 'Document, log, screenshot or setting reviewed',

    // The evidence attachment. It accompanies the written description, it does
    // not replace it. The status line always prints, with or without a file.
    'eval.evidencia_archivo'          => 'Attached file',
    'eval.evidencia_archivo_sin'      => 'No file attached.',
    'eval.evidencia_archivo_quitar'   => 'Remove this file when saving',
    'eval.evidencia_archivo_soltar'   => 'Drag the file here',
    'eval.evidencia_archivo_o'        => 'or',
    'eval.evidencia_archivo_examinar' => 'Browse your computer',
    'eval.evidencia_archivo_ayuda'    => 'Optional. Image (PNG, JPG, WEBP or GIF) or PDF, up to %s MB.',
    'eval.evidencia_archivo_sustituir' => 'Choose another file to replace the current one. Image or PDF, up to %s MB.',
    'eval.hallazgo_marcador'    => 'What was observed during verification',
    'eval.recomendacion_marcador' => 'Suggested action and its priority',

    // Results / report / comparison
    'eval.resultados'           => 'Results',
    'eval.reporte_pdf'          => 'Executive report (PDF)',
    'eval.aviso_riesgo_critico' => 'This audit has at least one dimension (C, I or D) in the red zone. Review the risk exposure before delivering the report.',
    'eval.cumplimiento_general' => 'Overall compliance',
    'eval.madurez_promedio'     => 'Average maturity',
    'eval.indice_general_riesgo' => 'Overall risk index',
    'eval.controles_respondidos' => 'Controls answered:',
    'eval.si_minuscula'         => 'yes',
    'eval.no_minuscula'         => 'no',
    'eval.na_minuscula'         => 'n/a',
    'eval.exposicion_riesgo'    => 'Risk exposure',
    'eval.sin_dimensiones'      => 'No controls with marked dimensions and rated maturity yet.',
    'eval.matriz_riesgo'        => 'Risk matrix',
    'eval.sin_impacto_prob'     => 'No controls with rated impact and probability yet.',
    'eval.eje_matriz'           => 'Probability (vertical axis) × Impact (horizontal axis)',
    'eval.eje_matriz_reporte'   => 'Rows: probability 5→1 · Columns: impact 1→5',
    // Compliance-by-domain column chart.
    'eval.gr_dominios_resumen'  => 'Compliance by domain, %s in total. The lowest is %s, at %s.',
    'eval.detalle_dominio'      => 'Domain breakdown',
    'eval.detalle_dominio_ayuda' => 'Open a domain to see its flagged controls.',
    'eval.gr_dominios_pie'      => 'The 50 % and 80 % lines are the zone thresholds: below 50 % the risk is high, above 80 % it is low.',
    'eval.de_cinco'             => 'of 5',
    'eval.cumplimiento_dominio' => 'Compliance by domain',
    'eval.sin_controles_eval'   => 'No controls assessed yet.',
    'eval.col_dominio'          => 'Domain',
    'eval.col_cumplimiento'     => 'Compliance',
    'eval.zona_alta'            => 'High risk',
    'eval.zona_media'           => 'Medium risk',
    'eval.zona_baja'            => 'Low risk',
    'eval.sin_datos'            => 'no data',
    'eval.menor_madurez'        => 'Controls with lowest maturity',
    'eval.mayor_riesgo'         => 'Controls with highest risk',
    'eval.sin_datos_suficientes' => 'Not enough data.',
    'eval.reporte_ejecutivo'    => 'Executive report',
    'eval.generado_el'          => 'Generated on %s by %s',
    'eval.auditor'               => 'Auditor',
    'eval.admin_entrevistado'    => 'DB administrator interviewed',
    'eval.comparacion_historica' => 'Historical comparison',
    'eval.evolucion_indice'      => 'Trend of the overall risk index by organization.',
    'eval.sin_auditorias_comparar' => 'No audits to compare yet.',
    'eval.solo_una_auditoria'    => 'There is only one audit so far. At least two are needed to see a trend.',
    'eval.riesgo'                 => 'risk',

    // Historical comparison — overall index columns, one per audit.
    'eval.auditorias_de_organizacion' => 'Audits: %s · latest on %s',
    'eval.indice_por_auditoria'  => 'Overall index by audit',
    'eval.indice_por_auditoria_texto' => 'Oldest to most recent. The latest one stands out; each column opens its audit.',
    // Decimal comma even here: the figures the chart draws use the Spanish
    // format across the whole product, and a threshold written «0.80» next to
    // an axis that reads «0,80» looks like a different number.
    'eval.umbral_verde'          => 'Green-zone threshold (0,80)',
    'eval.columna_indice'        => 'Audit of %s · index %s',
    'eval.indice_resumen'        => 'Overall index by audit. Audits with a calculated index: %s. Latest value: %s.',
    'eval.sin_indice_calculado'  => 'No audit of this organization has its index calculated yet.',
    'eval.auditorias_sin_indice' => 'Audits with no calculated index, and therefore no column: %s.',

    // Historical comparison — maturity profile by domain (radar chart).
    'eval.perfil_dominios'       => 'Maturity profile by domain',
    'eval.perfil_dominios_texto' => 'Latest audit with a breakdown (%s).',
    'eval.sin_desglose_dominio'  => 'No controls with a maturity level have been assessed for this organization yet.',
    'eval.radar_vertice'         => '%s · maturity %s of %s',
    'eval.radar_resumen'         => 'Maturity profile by domain for the audit of %s: %s.',
    'eval.radar_insuficiente'    => 'At least three assessed domains are needed to draw the profile. This audit has %s.',
    'eval.radar_pie'             => '%s domains assessed · maturity scale from 0 to %s, one ring per level.',

    // Historical comparison — breakdown table.
    'eval.dominio'               => 'Domain',
    'eval.madurez_por_dominio'   => 'Weighted maturity by domain',
    'eval.madurez_por_dominio_pie' => 'Average weighted by each control\'s weight. A dash marks a domain that audit did not assess.',
    // Compare history — the antechamber: picking the company.
    //
    // The filter VALUES («VERDE», «TENDENCIA») travel in the URL and are not
    // translated: a shared link has to keep working when whoever opens it
    // works in the other language. What gets translated is the label.
    'eval.filtros'               => 'Filters',
    'eval.aplicar_filtros'       => 'Apply filters',
    'eval.limpiar_filtros'       => 'Clear all',
    'eval.filtros_activos'       => 'Applied filters',
    // The verb lives in the accessible label: «Low risk ×» does not announce
    // what pressing it does.
    'eval.quitar_filtro'         => 'Remove the «%s» filter',
    'eval.filtro_zona'           => 'Risk of the latest audit',
    'eval.filtro_sin_indice'     => 'No index',
    'eval.filtro_estado'         => 'Portfolio status',
    'eval.filtro_con_progreso'   => 'With work in progress',
    'eval.filtro_finalizadas'    => 'All finished',
    'eval.filtro_historico'      => 'History',
    'eval.filtro_con_tendencia'  => 'Two audits or more',
    'eval.filtro_una_sola'       => 'A single audit',
    // «Companies: 3 of 7» and not «3 companies»: the figure after the colon
    // reads the same in the singular and in the plural.
    'eval.empresas_rango'        => 'Companies: %s of %s',
    'eval.orden_empresa_reciente'   => 'Most recently audited',
    'eval.orden_empresa_auditorias' => 'Most audits',
    'eval.orden_empresa_indice'     => 'Best index',
    'eval.orden_empresa_nombre'     => 'Name (A-Z)',
    'eval.sin_empresas_filtro'      => 'No company matches the filter.',
    'eval.sin_empresas_filtro_texto' => 'Remove a condition to widen the result.',
    'eval.ultimo_indice'         => 'Latest index',
    'eval.variacion_anterior'    => '%s since the previous one',
    'eval.primera_lectura'       => 'First reading with an index',
    'eval.en_progreso_n'         => '%s in progress',
    'eval.periodo_auditado'      => 'Audited period',

    // Header form (audit create/edit)
    'eval.admin_entrevistado_label' => 'Database administrator interviewed',
    'eval.seleccione'               => '— Select —',
    'eval.organizacion_registrada'  => 'Their organization is the one recorded as the audited entity.',
    'eval.origen_registrado'        => 'From the list',
    'eval.origen_manual'            => 'Edit details',
    'eval.admin_nombre'             => 'Name of the person interviewed',
    'eval.admin_nombre_marcador'    => 'E.g. Marta Jimenez',
    'eval.admin_empresa'            => 'Company they belong to',
    'eval.admin_empresa_marcador'   => 'E.g. Cooperativa de Ejemplo R.L.',
    'eval.admin_manual_ayuda'       => 'The company you type here is the one recorded as the audited entity. No account is created: the data lives in this audit.',
    'eval.area_evaluada'            => 'Area assessed',
    'eval.fecha_auditoria'          => 'Audit date',

    // Audit type (assessment model) and legend on the creation screen
    'eval.tipo_auditoria'        => 'Audit standard',
    'eval.tipo_proximamente'     => 'coming soon',
    'eval.tipo_ayuda'            => 'Sets the control catalogue and maturity scale. It cannot be changed later.',
    'eval.nueva_subtitulo'       => 'Open a dated assessment for the organization you are about to audit.',
    'eval.nueva_leyenda_titulo'  => 'What is this for?',
    'eval.nueva_leyenda_texto'   => 'An audit is the dated assessment of one organization: you go through the '
                                  . 'instrument control by control, and the answers produce the risk index, '
                                  . 'the C-I-A matrix and the remediations. Repeating it is what makes comparison possible.',
    'eval.nueva_modelos_proximos' => 'Coming in future updates',
    'eval.nueva_modelos_texto'   => 'Assessment models other than ISO 27000. They ship with the Deluxe plan, '
                                  . 'which cross-references the three frameworks.',
    'eval.nueva_ver_plan'        => 'See the Deluxe plan →',

    // ── Database health monitor (part 2) ─────────────────────────────────────
    //
    // A SINGLE five-band vocabulary: the same keys serve a metric, a component
    // and the index. A second set of names anywhere would be exactly the
    // translation table that §5.2 of the plan exists to avoid.
    'mon.banda_optimo'       => 'Optimal',
    'mon.banda_saludable'    => 'Healthy',
    'mon.banda_advertencia'  => 'Warning',
    'mon.banda_degradado'    => 'Degraded',
    'mon.banda_critico'      => 'Critical',
    'mon.banda_sin_dato'     => 'No data',

    'mon.monitor'            => 'Monitor',
    'mon.titulo'             => 'Database health monitor',

    'mon.aviso_maqueta'      => 'Mock-up: the figures on this screen are synthetic. They do not come from '
        . 'any real database or from an actual collection agent sample — do not use them as evidence.',

    'mon.instancia_no_encontrada' => 'There is no monitored database with that key.',

    // Lobby: one card per monitored database
    'mon.buscar_etiqueta'    => 'Search by key, engine or environment',
    'mon.buscar_marcador'    => 'e.g. prodcore, 19c…',
    'mon.filtro_banda'       => 'Health status',
    'mon.filtro_conexion'    => 'Connection',
    'mon.filtro_entorno'     => 'Environment',
    'mon.filtro_motor'       => 'Engine',
    'mon.filtro_sin_indice'  => 'No index',
    'mon.instancias_rango'   => 'Databases: %s of %s',
    'mon.orden_atencion'     => 'Most urgent',
    'mon.orden_indice'       => 'Best index',
    'mon.orden_reciente'     => 'Most recent sample',
    'mon.orden_clave'        => 'Key (A-Z)',
    'mon.sin_instancias_filtro' => 'No database matches the filter.',
    'mon.ultima_muestra'     => 'Last sample %s',

    'mon.duracion'           => 'Collection time',
    'mon.hace_min'           => '%s min ago',
    'mon.hace_horas'         => '%s h %s min ago',

    'mon.contexto_raiz'       => 'Root connection did not respond',
    'mon.contexto_contenedor' => 'Container connection did not respond',

    'mon.isbd'               => 'DBHI',
    'mon.formula'            => 'DBHI = 0.30·Processes + 0.35·Memory + 0.35·Files, over each component\'s '
                              . 'already published value and capped by the worst state observed.',

    'mon.sin_respuesta'      => 'No response',
    'mon.caida_explicacion'  => 'The instance did not answer this collection. The sample is stored anyway, '
                              . 'because "it did not answer at this hour" is also data: skipping it would '
                              . 'leave a gap that later looks like a healthy period.',
    'mon.ultimo_conocido'    => 'Last known value',

    'mon.muestra_incompleta' => 'Incomplete sample',
    'mon.incompleta_explicacion' => 'Coverage for this sample is %s and the floor is %s, so no index is '
                              . 'published. An index computed over half the evidence is worse than no '
                              . 'index at all, because it looks like a good one.',

    // "Why it's worth that" card: only shown when the weakest link capped the ISBD.
    'mon.causa'              => 'Why it is worth that',
    'mon.tope_explicacion'   => 'The weighted average of the three components would be %s, but %s is in '
                              . 'band %s and caps the ISBD at %s: the published index never exceeds what '
                              . 'its weakest component allows.',

    'mon.componentes'        => 'Components',
    'mon.componentes_vacio'  => 'There is nothing to compute: the instance did not answer this collection, so no metric was ever measured.',
    'mon.comp_procesos'      => 'Processes',
    'mon.comp_memoria'       => 'Memory',
    'mon.comp_archivos'      => 'Files',
    'mon.peso'               => 'weight %s %%',
    'mon.comp_topado'        => 'Average %s, capped at %s by the component\'s worst state.',
    'mon.comp_sin_metricas'  => 'None of its metrics could be collected in this sample.',

    // CONSULTAS card: it is collected and shown, but is not a summand of the ISBD (§3.1).
    'mon.comp_consultas'       => 'Queries',
    'mon.consultas_fuera_isbd' => 'It is collected and shown, but does not count toward the ISBD formula: it '
                                . "measures the work asked of the database, not its health.",

    // Monitored database selector
    'mon.base_vigilada'      => 'Monitored database',
    'mon.conectada'          => 'Connected',
    'mon.sin_conexion'       => 'No connection',

    // Operations console
    'mon.consola'            => 'Operations panel',
    'mon.medidor_lectura'    => 'Database health index: %s, band %s.',
    'mon.desc_procesos'      => 'Session and process quota, background process vitality, and redo write wait.',
    'mon.desc_memoria'       => 'PGA against its target, PGA cache hit ratio, and shared pool free memory.',
    'mon.desc_archivos'      => 'Tablespace usage, datafiles online, and redo groups with no invalid members.',

    // DBHI trend
    'mon.tendencia'          => 'DBHI trend',
    'mon.isbd_publicado'     => 'Published DBHI',
    'mon.leyenda_topada'     => 'Reading capped by the weakest link',
    'mon.leyenda_hueco'      => 'No index published',
    'mon.sin_tendencia'      => 'There are not enough readings yet to draw a trend.',
    'mon.tendencia_resumen'  => 'Health index trend over %s readings; the latest published one is %s and %s were capped by the weakest link.',
    'mon.tendencia_pie'      => '%s readings, one every %s minutes: the last %s minutes. The flat plateaus on 40, 60, '
                              . '75 and 90 are not a drawing glitch: they are the readings where the average came to '
                              . 'more and the weakest link pulled it down to the band boundary.',
    'mon.punto_lectura'      => 'Reading %s: %s',
    'mon.punto_topado'       => 'Reading %s: %s, capped by the weakest link',

    // Index cards and traffic light
    'mon.indices_lista'      => 'Indices that make up the DBHI',
    'mon.ver_procesos'       => 'See the %s processes assessed',
    'mon.semaforo_leyenda'   => 'The traffic light groups the instrument\'s five bands, keeping their '
                              . 'boundaries: red is Critical or Degraded (up to 60), amber is Warning '
                              . '(60 to 75) and green is Healthy or Optimal (above 75). The DBHI turns '
                              . 'red if any of the three indices is red, and only turns green when all '
                              . 'three are.',

    // Per-index process table
    'mon.procesos_de'        => 'Processes assessed · %s',
    'mon.sin_procesos'       => 'This index has no processes declared in the catalogue.',
    'mon.col_proceso'        => 'Process',
    'mon.col_metricas'       => 'Metrics',
    'mon.col_resultado'      => 'Result',
    'mon.col_descripcion'    => 'Description',
    'mon.col_recomendacion'  => 'Recommendation',
    'mon.ayuda_tabla'        => 'How to read this table',
    'mon.ayuda_consola'      => 'How this panel is computed and coloured',
    'mon.res_correcto'       => 'Pass',
    'mon.res_hallazgo'       => 'Finding',
    'mon.res_sin_dato'       => 'No data',

    'mon.procesos_nota'      => 'Every metric has its own column and is normalised to 0-1, so they '
                              . 'measure the same thing even though they observe different ones. A BLANK '
                              . 'cell means that metric does not assess that process; a dash means it '
                              . 'does but could not be collected. A process passes when none of its '
                              . 'metrics trips the traffic light, and "no data" is not a failure of its '
                              . 'own: that is why it carries no cross. The gold code next to the process '
                              . 'name is the COBIT 2019 management objective whose risk it watches.',

    /*
     * COBIT 2019 traceability for the process table (see `catalogo_cobit` in
     * config/monitor-mockup.php). Official English name of the objective.
     */
    'mon.cobit_dss01' => 'Managed Operations',
    'mon.cobit_dss04' => 'Managed Continuity',
    'mon.cobit_apo14' => 'Managed Data',

    /*
     * Metric ficha (catalogo_metricas in config/monitor-mockup.php): name and
     * what it measures, for the popover on the code in the process table's
     * header.
     */
    'mon.metrica_mpro01_nombre'      => 'Session utilization',
    'mon.metrica_mpro01_descripcion' => 'What share of the sessions the instance allows is in use. On hitting '
        . 'the ceiling, new connections are rejected with ORA-00018 even though the database is healthy '
        . 'underneath.',
    'mon.metrica_mpro02_nombre'      => 'Process utilization',
    'mon.metrica_mpro02_descripcion' => "How much of the operating system's process array is occupied. It runs "
        . 'out before the session quota on dedicated-server instances, and its failure (ORA-00020) is just as '
        . 'abrupt.',
    'mon.metrica_mpro03_nombre'      => 'Mandatory background processes present',
    'mon.metrica_mpro03_descripcion' => 'Gate: checks that the five background processes — CKPT, DBW0, LGWR, '
        . "PMON and SMON — are alive in this sample. There's no middle ground: it's 1 or 0.",
    'mon.metrica_mpro04_nombre'      => 'Average redo write wait',
    'mon.metrica_mpro04_descripcion' => "Average milliseconds a redo log write takes. It's the latency the "
        . 'application feels on commit, so it reads as user experience, not infrastructure.',
    'mon.metrica_mpro05_nombre'      => 'Background process restart detected',
    'mon.metrica_mpro05_descripcion' => "Gate: compares each background process's fingerprint against the "
        . 'previous sample. It sees what M-PRO-03 cannot — a process that died and came back up between two '
        . 'samples.',
    'mon.metrica_mpro06_nombre'      => 'Checkpoint age',
    'mon.metrica_mpro06_descripcion' => 'How far the checkpoint has fallen behind the MTTR target. It says how '
        . 'long recovery would take if the instance crashed right now.',
    'mon.metrica_mmem01_nombre'      => 'PGA cache hit ratio',
    'mon.metrica_mmem01_descripcion' => "Share of PGA work resolved entirely in memory, without touching disk. "
        . "HIGHER IS BETTER: it's the PGA's outcome measure, while M-MEM-02 is its consumption measure.",
    'mon.metrica_mmem02_nombre'      => 'PGA allocated against target',
    'mon.metrica_mmem02_descripcion' => "How much private session memory has been allocated against the "
        . "configured target. Going over doesn't fail the work: it spills to the temporary tablespace and "
        . 'everything quietly gets slower.',
    'mon.metrica_mmem03_nombre'      => 'Shared pool free memory',
    'mon.metrica_mmem03_descripcion' => 'How much free space is left in the SGA area where execution plans and '
        . 'the cached dictionary live. HIGHER IS BETTER: here, free space is the room to maneuver.',
    'mon.metrica_marc01_nombre'      => 'Worst tablespace utilization',
    'mon.metrica_marc01_descripcion' => "Usage of the worst-off permanent tablespace, never the average: a "
        . "healthy average hides the file that's about to burst. When it fills up, writing fails with "
        . 'ORA-01653.',
    'mon.metrica_marc02_nombre'      => 'Datafiles in a valid state',
    'mon.metrica_marc02_descripcion' => "Gate: checks that every data file is online. One offline leaves its "
        . 'share of the data inaccessible even if the rest of the instance responds normally.',
    'mon.metrica_marc03_nombre'      => 'Redo groups with no invalid members',
    'mon.metrica_marc03_descripcion' => "Gate over the unusable members of the redo log groups. With every "
        . "group unusable, the database halts, because it can't rotate the log.",
    'mon.metrica_marc04_nombre'      => 'Worst temporary tablespace utilization',
    'mon.metrica_marc04_descripcion' => "Usage of the worst-off temporary tablespace: the workspace for what "
        . "didn't fit in PGA. When it runs out, the running query fails with ORA-01652, but no permanent data "
        . 'is lost.',
    'mon.metrica_marc05_nombre'      => 'Worst non-autoextending tablespace utilization',
    'mon.metrica_marc05_descripcion' => "Usage of the worst tablespace that CANNOT auto-extend. It covers "
        . 'M-ARC-01\'s blind spot: with autoextend on, the percentage is measured against the reachable '
        . 'maximum and almost never alarms.',
    'mon.metrica_mcon01_nombre'      => 'Statements over the per-execution time threshold',
    'mon.metrica_mcon01_descripcion' => 'How many of the top-20 statements exceed the agreed time per '
        . "execution. It's a count, not a ratio, and it measures the work asked of the database, not its "
        . 'health.',

    /*
     * Process catalog (catalogo_procesos in config/monitor-mockup.php): name,
     * description and recommendation per process, grouped by index.
     */
    'mon.proceso_pmon_nombre'        => 'PMON',
    'mon.proceso_pmon_descripcion'   => 'Process monitor. Cleans up after sessions that end abnormally: rolls '
        . 'back their transaction, releases the locks they held, and returns their slot to the process array. '
        . "If PMON is gone, the instance is gone.",
    'mon.proceso_pmon_recomendacion' => 'Alerting on the very first sample where it is missing is recommended, '
        . 'with no confirmation wait: this process has no partial degradation.',

    'mon.proceso_smon_nombre'        => 'SMON',
    'mon.proceso_smon_descripcion'   => 'System monitor. Recovers the instance on startup after a crash, '
        . 'coalesces contiguous free extents, and cleans up orphaned temporary segments.',
    'mon.proceso_smon_recomendacion' => 'Watching it alongside M-ARC-04 is recommended: when SMON falls '
        . 'behind, the temporary tablespace is the first to notice.',

    'mon.proceso_dbw0_nombre'        => 'DBW0',
    'mon.proceso_dbw0_descripcion'   => "Database writer. Flushes dirty buffer cache blocks down to the "
        . "datafiles so there's free room to read the next ones.",
    'mon.proceso_dbw0_recomendacion' => 'Reading it in isolation is not recommended: if DBW0 is alive but the '
        . 'checkpoint falls behind (M-PRO-06), the bottleneck is disk I/O, not the process.',

    'mon.proceso_lgwr_nombre'        => 'LGWR',
    'mon.proceso_lgwr_descripcion'   => 'Redo log writer. Flushes the redo buffer to the log files on every '
        . 'COMMIT, which is why its latency is the latency the application feels on commit.',
    'mon.proceso_lgwr_recomendacion' => 'Treating M-PRO-04 as a user-experience metric, not an infrastructure '
        . 'one, is recommended: above 20 ms, COMMITs are noticeable from outside.',

    'mon.proceso_ckpt_nombre'        => 'CKPT',
    'mon.proceso_ckpt_descripcion'   => 'Checkpoint process. Marks how far the on-disk content is guaranteed '
        . 'and updates the datafile headers. The further behind it runs, the longer recovery takes after a '
        . 'crash.',
    'mon.proceso_ckpt_recomendacion' => 'Comparing M-PRO-06 against the MTTR target agreed with the business, '
        . 'not against an absolute number, is recommended.',

    'mon.proceso_cupo_sesiones_nombre'        => 'Session quota',
    'mon.proceso_cupo_sesiones_descripcion'   => "Not a background process: it's the ceiling of concurrent "
        . 'sessions the instance allows. Once exhausted, new connections are rejected with ORA-00018 even '
        . 'though the database is perfectly healthy underneath.',
    'mon.proceso_cupo_sesiones_recomendacion' => "Measuring it against V\$RESOURCE_LIMIT's effective limit, "
        . 'never against an assumed figure, is recommended.',

    'mon.proceso_cupo_procesos_nombre'        => 'Process quota',
    'mon.proceso_cupo_procesos_descripcion'   => "Ceiling of the operating system's process array. It runs out "
        . 'before the session quota on dedicated-server instances, and its failure (ORA-00020) is just as '
        . 'abrupt.',
    'mon.proceso_cupo_procesos_recomendacion' => 'Watching it alongside the session quota is recommended: they '
        . 'rise together, and which one warns first depends on configuration, not load.',

    'mon.proceso_shared_pool_nombre'        => 'Shared pool',
    'mon.proceso_shared_pool_descripcion'   => 'SGA area where execution plans and the cached data dictionary '
        . 'live. Once it runs out of free space, Oracle starts evicting plans and recompiling statements it '
        . 'had already solved, and the cost shows up as CPU, not memory.',
    'mon.proceso_shared_pool_recomendacion' => 'Chasing 100% occupancy is not recommended here: free space is '
        . 'the room to maneuver.',

    'mon.proceso_pga_nombre'        => 'PGA',
    'mon.proceso_pga_descripcion'   => "Program Global Area: each session's private memory for sorts, "
        . "aggregations and hash joins. Going over target doesn't fail the work: it spills to the temporary "
        . 'tablespace and everything quietly gets slower.',
    'mon.proceso_pga_recomendacion' => 'Reading it alongside M-ARC-04, where whatever did not fit lands, is '
        . 'recommended.',

    'mon.proceso_cache_pga_nombre'        => 'PGA cache',
    'mon.proceso_cache_pga_descripcion'   => "Share of PGA work resolved entirely in memory, without touching "
        . "disk. It's the PGA's outcome measure, while M-MEM-02 is its consumption measure.",
    'mon.proceso_cache_pga_recomendacion' => 'Acting when this drops even while the allocated figure stays '
        . 'within target is recommended: it means the target fell short of the real load.',

    'mon.proceso_tablespaces_permanentes_nombre'        => 'Permanent tablespaces',
    'mon.proceso_tablespaces_permanentes_descripcion'   => "Space used in the worst-off tablespace, never the "
        . "average: a healthy average hides the file that's about to burst. When it fills up, writing fails "
        . 'with ORA-01653 and the transaction is lost.',
    'mon.proceso_tablespaces_permanentes_recomendacion' => 'Reviewing weekly growth alongside the percentage '
        . 'is recommended: the percentage says where it stands, the slope says when it arrives.',

    'mon.proceso_datafiles_nombre'        => 'Datafiles',
    'mon.proceso_datafiles_descripcion'   => "Gate: either every data file is online, or it isn't. One offline "
        . 'datafile leaves its share of the data inaccessible even if the rest of the instance responds '
        . 'normally.',
    'mon.proceso_datafiles_recomendacion' => 'Averaging it with anything is not recommended: offline sends the '
        . 'files index to critical without discussion.',

    'mon.proceso_grupos_redo_nombre'        => 'Redo log groups',
    'mon.proceso_grupos_redo_descripcion'   => "Gate over the invalid members of the redo log groups. With "
        . "every group unusable, the database halts, because it can't rotate the log.",
    'mon.proceso_grupos_redo_recomendacion' => 'Keeping at least two members per group on separate disks is '
        . 'recommended: the metric measures validity, not redundancy.',

    'mon.proceso_tablespace_temporal_nombre'        => 'Temporary tablespace',
    'mon.proceso_tablespace_temporal_descripcion'   => "Workspace for whatever didn't fit in PGA. Once it runs "
        . 'out, the query or index being built fails with ORA-01652, but no permanent data is lost.',
    'mon.proceso_tablespace_temporal_recomendacion' => 'Sizing it from the observed peak, not the average, is '
        . 'recommended: a single large query consumes it, not everyday use.',

    'mon.proceso_tablespace_sin_autoextend_nombre'        => 'Non-autoextending tablespace',
    'mon.proceso_tablespace_sin_autoextend_descripcion'   => "Covers M-ARC-01's blind spot: with autoextend on, "
        . 'the percentage is measured against the reachable maximum and stays optimal no matter how much the '
        . "file grows. Without autoextend, that same percentage really does mean 'how full it is'.",
    'mon.proceso_tablespace_sin_autoextend_recomendacion' => 'Treating it as the early warning of the two is '
        . 'recommended.',

    // Memory chart
    'mon.memoria_titulo'     => 'Database memory',
    'mon.memoria_pie'        => 'One reading added every %s seconds · %s-minute window',
    'mon.memoria_nota'       => 'Mock-up: the reading that shows up every half minute is generated by '
                              . 'this browser, not by the collection agent. The horizontal axis is the '
                              . 'instance uptime, not wall-clock time.',
    'mon.mem_de_total'       => 'of %s allocated',
    'mon.mem_normal'         => 'Below threshold',
    'mon.mem_sobre_aceptacion' => 'Above acceptance threshold',
    'mon.mem_sobre_peligro'  => 'Above danger threshold',
    'mon.mem_resumen'        => 'Memory in use: %s of %s allocated, %s per cent.',
    'mon.umbral_aceptacion'  => 'Acceptance %s %% · %s',
    'mon.umbral_peligro'     => 'Danger %s %% · %s',
    'mon.eje_uptime'         => 'Instance uptime',
    'mon.uptime_min'         => '%s min',
    'mon.uptime_horas'       => '%s h %s min',

    'mon.vacio_titulo'       => 'No instances are being monitored yet',
    'mon.vacio_texto'        => 'Once the collection agent leaves its first sample, this screen will show '
                              . 'each instance\'s health index, its components and its alerts.',

    // ── User profile ─────────────────────────────────────────────────────────
    'perfil.titulo'             => 'My profile',
    'perfil.ir'                 => 'View my profile',
    'perfil.id'                 => 'Identifier',
    'perfil.descripcion'        => 'About me',
    'perfil.descripcion_ayuda'  => 'A short note about your role or speciality. Only you can see it.',
    'perfil.descripcion_marcador' => 'E.g.: Database auditor, focused on continuity and backup.',
    'perfil.guardar_descripcion' => 'Save description',
    'perfil.subir_foto'         => 'Upload a photo',
    'perfil.cambiar_foto'       => 'Change photo',
    'perfil.subir_ahora'        => 'Upload now',
    'perfil.quitar_foto'        => 'Remove the photo',
    'perfil.foto_ayuda'         => 'PNG, JPG, WEBP or GIF, up to %s MB.',
    'perfil.calendario'         => 'Audits this month',
    'perfil.mis_auditorias'     => 'Audits carried out',
    'perfil.total_auditorias'   => 'Audits',
    'perfil.finalizadas'        => 'Finished',
    'perfil.en_progreso'        => 'In progress',
    'perfil.mes_resumen'        => 'Audits this month: %s · Days worked: %s',
    'perfil.mes_sin_auditorias' => 'No audits this month.',
    'perfil.y_mas'              => 'and %s more',
    'perfil.pagina_de'          => 'Page %s of %s',

    // ── Calendar ─────────────────────────────────────────────────────────────
    'cal.mes_anterior'  => 'Previous month',
    'cal.mes_siguiente' => 'Next month',
    'cal.mes_1'  => 'January',
    'cal.mes_2'  => 'February',
    'cal.mes_3'  => 'March',
    'cal.mes_4'  => 'April',
    'cal.mes_5'  => 'May',
    'cal.mes_6'  => 'June',
    'cal.mes_7'  => 'July',
    'cal.mes_8'  => 'August',
    'cal.mes_9'  => 'September',
    'cal.mes_10' => 'October',
    'cal.mes_11' => 'November',
    'cal.mes_12' => 'December',
    'cal.dia_1' => 'Mo',
    'cal.dia_2' => 'Tu',
    'cal.dia_3' => 'We',
    'cal.dia_4' => 'Th',
    'cal.dia_5' => 'Fr',
    'cal.dia_6' => 'Sa',
    'cal.dia_7' => 'Su',
];
