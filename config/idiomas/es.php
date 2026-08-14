<?php

declare(strict_types=1);

/**
 * Diccionario en español. Es el idioma por defecto del sitio, así que en
 * rigor casi ninguna vista necesitaría pasar por aquí — pero mantener las
 * mismas claves en los dos archivos es lo que evita que se desincronicen.
 */
return [
    // Navegación / encabezado
    'nav.nosotros'        => 'Nosotros',
    'nav.herramientas'    => 'Herramientas',
    'nav.mis_auditorias'  => 'Mis auditorías',
    'nav.catalogo'        => 'Catálogo',
    'nav.perfil'          => 'Perfil',
    'nav.salir'           => 'Salir',
    'nav.ingresar'        => 'Ingresar',
    'nav.contactar'       => 'Contactar',
    'nav.abrir_menu'      => 'Abrir menú de navegación',
    'nav.idioma'          => 'Idioma',
    'nav.principal'       => 'Navegación principal',
    'nav.movil'           => 'Navegación móvil',
    'nav.saltar_contenido' => 'Saltar al contenido',

    // Marco del módulo interno: barra lateral y barra superior.
    'panel.navegacion'            => 'Navegación del módulo',
    'panel.ubicacion'             => 'Ubicación actual',
    'panel.mostrar_navegacion'    => 'Mostrar la navegación',
    'panel.ocultar_navegacion'    => 'Ocultar la navegación',
    'panel.grupo_auditorias'      => 'Auditorías',
    'panel.grupo_administracion'  => 'Administración',
    'panel.grupo_referencia'      => 'Referencia',
    'panel.catalogo_controles'    => 'Catálogo de controles',
    'panel.matriz_cid'            => 'Mapa de procesos vs C-I-D',
    'panel.remediaciones_vencidas' => 'Remediaciones vencidas',
    'panel.volver_sitio'          => 'Volver al sitio',
    'panel.rol_auditor'           => 'Auditor',
    'panel.rol_admin'             => 'Administrador de BD',

    // Testimonios de la portada
    'testimonios.calificacion' => 'Calificación: %s de 5 estrellas',
    'testimonios.lista'        => 'Testimonios de clientes',
    'testimonios.anterior'     => 'Ver el testimonio anterior',
    'testimonios.siguiente'    => 'Ver el testimonio siguiente',
    'testimonios.ir_a'         => 'Ver el testimonio de %s',
    'testimonios.calificacion_corta' => '%s de 5,0',
    'testimonios.anterior_corto'   => 'anterior',
    'testimonios.siguiente_corto'  => 'siguiente',

    // Pie de página
    'pie.contacto'        => 'Contacto',
    'pie.proyecto'        => 'Proyecto académico — Administración de Bases de Datos.',
    'pie.eslogan_extra'   => 'Reducimos el riesgo operativo de la información crítica de su organización.',
    'pie.arquitectura'    => 'Arquitectura MVC en PHP',

    // Botones y palabras comunes
    'comun.guardar'       => 'Guardar',
    'comun.cancelar'      => 'Cancelar',
    'comun.editar'        => 'Editar',
    'comun.eliminar'      => 'Eliminar',
    'comun.crear'         => 'Crear',
    'comun.volver'        => 'Volver',
    'comun.si'            => 'Sí',
    'comun.no'            => 'No',
    'comun.no_aplica'     => 'No aplica',
    'comun.buscar'        => 'Buscar',

    // Autenticación
    'auth.eyebrow'            => 'Evaluación de riesgo ISO/IEC 27002',
    'auth.ingresar_titulo'    => 'Iniciar sesión',
    'auth.ingresar_texto'     => 'Acceso para auditores de %s.',
    'auth.correo'             => 'Correo electrónico',
    'auth.clave'              => 'Contraseña',
    'auth.entrar'             => 'Entrar',
    'auth.sin_cuenta'         => '¿No tiene cuenta?',
    'auth.registrarse'        => 'Registrarse',
    'auth.registrarse_titulo' => 'Crear cuenta',
    'auth.registrarse_texto'  => 'La cuenta se crea con perfil de auditor.',
    'auth.nombre_completo'    => 'Nombre completo',
    'auth.organizacion'       => 'Organización',
    'auth.confirmar_clave'    => 'Repetir contraseña',
    'auth.crear_cuenta'       => 'Crear cuenta',
    'auth.ya_tiene_cuenta'    => '¿Ya tiene cuenta?',
    'auth.iniciar_sesion'     => 'Iniciar sesión',
    'auth.minimo_caracteres'  => 'Mínimo %s caracteres.',

    // Panel de bienvenida del inicio de sesión
    'auth.panel_titulo'       => 'Comience su auditoría',
    'auth.panel_texto'        => 'Tres pasos para evaluar los 75 controles del instrumento.',
    'auth.paso_uno'           => 'Inicie sesión',
    // El primer paso nombra lo que se está haciendo en cada pantalla.
    'auth.paso_uno_registro'  => 'Cree su cuenta',
    'auth.paso_dos'           => 'Levante una auditoría',
    'auth.paso_tres'          => 'Evalúe y reporte',

    // Módulo de evaluación — panel y alta de auditoría
    'eval.comparar_historico'  => 'Comparar histórico',
    'eval.nueva_auditoria'     => 'Nueva auditoría',
    'eval.mis_auditorias'      => 'Mis auditorías',
    'eval.sin_auditorias'      => 'Todavía no hay auditorías.',

    // Resumen de la cartera, en la cabecera de "Mis auditorías". Las notas se
    // redactan con la cifra AL FINAL para que valgan igual en singular y en
    // plural: "Organizaciones distintas: 1" se lee bien; "1 organizaciones", no.
    'eval.kpi_auditorias'      => 'Auditorías',
    'eval.kpi_organizaciones'  => 'Organizaciones distintas: %s',
    'eval.kpi_pendientes'      => 'Pendientes de cierre',
    'eval.kpi_finalizadas'     => 'Finalizadas',
    'eval.kpi_congeladas'      => 'Resultados congelados',
    'eval.kpi_indice'          => 'Índice de riesgo promedio',
    'eval.kpi_calculadas'      => 'Auditorías con índice calculado: %s',
    'eval.kpi_sin_calculo'     => 'Todavía sin cálculo',

    // Tablero del panel: matriz de la última auditoría y evolución mensual.
    'eval.evolucion_titulo'    => 'Evolución mensual',
    'eval.empresa_auditada'    => 'Empresa auditada',
    'eval.evolucion_texto'     => 'Cuánto del instrumento se aplicó cada mes y qué proporción resultó conforme.',
    'eval.cobertura_instrumento' => 'Cobertura del instrumento',
    'eval.sin_evolucion'       => 'Todavía no hay meses con auditorías que graficar.',
    'eval.evolucion_un_mes'    => 'Solo hay auditorías de un mes (%s). Con dos o más aparece la curva de evolución.',
    'eval.evolucion_resumen'   => 'Evolución a lo largo de %s meses con auditorías. Último cumplimiento: %s. Última cobertura: %s.',
    'eval.punto_cobertura'     => 'Mes %s · cobertura %s · auditorías: %s',
    'eval.punto_cumplimiento'  => 'Mes %s · cumplimiento %s',
    // Se dice explícitamente porque la pendiente entre dos columnas contiguas
    // se leería como un mes de diferencia cuando pueden ser varios.
    'eval.eje_meses'           => 'Meses con auditorías registradas, no meses consecutivos del calendario.',
    'eval.eje_matriz_corto'    => 'Probabilidad ↑ · Impacto →',
    'eval.empresa_marcador'    => 'Escriba el nombre de la empresa',
    'eval.ver_progreso'        => 'Ver progreso',
    'eval.empresa_sin_coincidencia' => 'No hay auditorías de «%s». Se muestra %s.',

    // Bases de datos conectadas (previsualización del monitoreo).
    'bd.titulo'                => 'Bases de datos conectadas',
    'bd.texto'                 => 'Motores bajo vigilancia, a la espera de diagnóstico de salud.',
    'bd.conexion'              => 'conexión',
    'bd.conexiones'            => 'conexiones',
    'bd.enlace_activo'         => 'enlace activo',
    'bd.total'                 => '%s conexiones en %s motores.',
    'bd.previsualizacion'      => 'Previsualización: el diagnóstico por instancia llega con el módulo de monitoreo.',
    'bd.sin_conexiones'        => 'Todavía no hay bases de datos registradas.',

    // Filtros y paginación de la tabla de auditorías.
    'eval.buscar_etiqueta'     => 'Buscar por organización o área',
    'eval.buscar_marcador'     => 'Ej.: cooperativa, respaldos…',
    'eval.limpiar'             => 'Limpiar',
    'eval.ordenar_por'         => 'Ordenar por',
    'eval.orden_recientes'     => 'Recientes',
    'eval.orden_indice'        => 'Mayor índice',
    'eval.sin_coincidencias'   => 'Ninguna auditoría coincide.',
    'eval.sin_coincidencias_texto' => 'No se encontró «%s» en la organización ni en el área evaluada.',
    'eval.paginacion'          => 'Paginación de auditorías',
    'eval.rango'               => 'Mostrando %s–%s de %s',
    'eval.anterior'            => 'Anterior',
    'eval.siguiente'           => 'Siguiente',
    'eval.celda_matriz'        => 'Impacto %s · Probabilidad %s · controles: %s · %s',

    'eval.crear_primera'       => 'Cree la primera para empezar a evaluar los %s controles del instrumento.',
    'eval.col_organizacion'    => 'Organización',
    'eval.col_area'            => 'Área evaluada',
    'eval.col_fecha'           => 'Fecha',
    'eval.col_estado'          => 'Estado',
    'eval.col_indice'          => 'Índice',
    'eval.finalizada'          => 'Finalizada',
    'eval.en_progreso'         => 'En progreso',
    'eval.sin_calcular'        => 'sin calcular',
    'eval.volver'              => 'Volver',
    'eval.volver_auditorias'   => '← Mis auditorías',
    'eval.crear_auditoria'     => 'Crear auditoría',

    // Módulo de evaluación — detalle de auditoría (mostrar.php)
    'eval.auditoria_n'         => 'Auditoría %s',
    'eval.entrevistado'        => 'entrevistado:',
    'eval.ver_resultados'      => 'Ver resultados',
    'eval.finalizar'           => 'Finalizar',
    'eval.reabrir'             => 'Reabrir',
    'eval.aviso_finalizada'    => 'Esta auditoría está finalizada y no admite cambios. Reábrala para seguir evaluando.',
    'eval.avance'              => 'Avance de la evaluación',
    'eval.de_controles'        => 'de',
    'eval.controles_palabra'   => 'controles',
    'eval.editar_encabezado'   => 'Editar encabezado',
    'eval.guardar_encabezado'  => 'Guardar encabezado',
    'eval.controles_instrumento' => 'Controles del instrumento',
    'eval.col_codigo'          => 'Código',
    'eval.col_proceso'         => 'Proceso',
    'eval.col_enunciado'       => 'Enunciado',
    'eval.col_resp'            => 'Resp.',
    'eval.col_madurez'         => 'Madurez',

    // Módulo de evaluación — calificar control (control.php)
    'eval.sin_proceso'         => 'Sin proceso',
    'eval.pregunta_auditoria'  => 'Pregunta de auditoría',
    'eval.evidencia_esperada'  => 'Evidencia esperada',
    'eval.respuesta'           => 'Respuesta',
    'eval.nivel_madurez'       => 'Nivel de madurez observado',
    'eval.sin_calificar'       => '— Sin calificar —',
    'eval.criterio_comprobacion' => 'Criterio de comprobación',
    'eval.ninguno'             => '— Ninguno —',
    'eval.que_compromete'      => '¿Qué compromete si este control falla?',
    'eval.confidencialidad'    => 'Confidencialidad',
    'eval.integridad'          => 'Integridad',
    'eval.disponibilidad'      => 'Disponibilidad',
    'eval.impacto'             => 'Impacto',
    'eval.probabilidad'        => 'Probabilidad',
    'eval.nivel_riesgo_reg'    => 'Nivel de riesgo registrado:',
    'eval.promedio_impacto'    => '(promedio de impacto y probabilidad)',
    'eval.hallazgo'            => 'Hallazgo',
    'eval.recomendacion'       => 'Recomendación',
    'eval.guardar'             => 'Guardar',
    'eval.guardar_siguiente'   => 'Guardar y siguiente',
    'eval.estado_si'           => 'Sí',
    'eval.estado_no'           => 'No',
    'eval.estado_na'           => 'No aplica',
    'eval.criterio_documentado' => 'Documentado',
    'eval.criterio_repetible'   => 'Repetible',
    'eval.criterio_evidencia'   => 'Con evidencia',
    'eval.evidencia_verificada' => 'Evidencia revisada',
    'eval.evidencia_ayuda'      => 'Obligatorio si la respuesta es "Sí": describa el documento, log, captura o configuración que sustenta la respuesta (ISO/IEC 27007 — la conformidad se prueba con evidencia, no con la afirmación del auditado).',
    'eval.calidad_evidencia'    => 'Calidad de la evidencia',
    'eval.calidad_bien'         => 'Bien implementado',
    'eval.calidad_mejora'       => 'Requiere mejora',
    'eval.calidad_declarativo'  => 'Declarativo (sin evidencia real)',

    // Resultados / reporte / comparación
    'eval.resultados'           => 'Resultados',
    'eval.reporte_pdf'          => 'Reporte ejecutivo (PDF)',
    'eval.aviso_riesgo_critico' => 'Esta auditoría tiene al menos una dimensión (C, I o D) en zona roja. Revise la exposición al riesgo antes de entregar el informe.',
    'eval.cumplimiento_general' => 'Cumplimiento general',
    'eval.madurez_promedio'     => 'Madurez promedio',
    'eval.indice_general_riesgo' => 'Índice general de riesgo',
    'eval.controles_respondidos' => 'Controles respondidos:',
    'eval.si_minuscula'         => 'sí',
    'eval.no_minuscula'         => 'no',
    'eval.na_minuscula'         => 'no aplica',
    'eval.exposicion_riesgo'    => 'Exposición al riesgo',
    'eval.sin_dimensiones'      => 'Todavía no hay controles con dimensiones marcadas y madurez calificada.',
    'eval.matriz_riesgo'        => 'Matriz de riesgo',
    'eval.sin_impacto_prob'     => 'Todavía no hay controles con impacto y probabilidad calificados.',
    'eval.eje_matriz'           => 'Probabilidad (eje vertical) × Impacto (eje horizontal)',
    'eval.eje_matriz_reporte'   => 'Filas: probabilidad 5→1 · Columnas: impacto 1→5',
    'eval.cumplimiento_dominio' => 'Cumplimiento por dominio',
    'eval.sin_controles_eval'   => 'Sin controles evaluados todavía.',
    'eval.col_dominio'          => 'Dominio',
    'eval.col_cumplimiento'     => 'Cumplimiento',
    'eval.zona_alta'            => 'Riesgo alto',
    'eval.zona_media'           => 'Riesgo medio',
    'eval.zona_baja'            => 'Riesgo bajo',
    'eval.sin_datos'            => 'sin datos',
    'eval.menor_madurez'        => 'Controles con menor madurez',
    'eval.mayor_riesgo'         => 'Controles con mayor riesgo',
    'eval.sin_datos_suficientes' => 'Sin datos suficientes.',
    'eval.reporte_ejecutivo'    => 'Reporte ejecutivo',
    'eval.generado_el'          => 'Generado el %s por %s',
    'eval.auditor'               => 'Auditor',
    'eval.admin_entrevistado'    => 'Administrador de BD entrevistado',
    'eval.comparacion_historica' => 'Comparación histórica',
    'eval.evolucion_indice'      => 'Evolución del índice general de riesgo por organización.',
    'eval.sin_auditorias_comparar' => 'Todavía no hay auditorías para comparar.',
    'eval.solo_una_auditoria'    => 'Solo hay una auditoría todavía. Se necesitan al menos dos para ver una tendencia.',
    'eval.riesgo'                 => 'riesgo',

    // Comparación histórica — columnas del índice general por auditoría.
    // «Auditorías: 1» y no «1 auditorías»: la cifra va detrás de los dos
    // puntos para que la frase valga igual en singular y en plural.
    'eval.auditorias_de_organizacion' => 'Auditorías: %s · última el %s',
    'eval.indice_por_auditoria'  => 'Índice general por auditoría',
    'eval.indice_por_auditoria_texto' => 'De la más antigua a la más reciente. La última va destacada; cada columna abre su auditoría.',
    // El corte de zona verde de fn_zona (pkg_indicadores). En oro porque es
    // referencia normativa, y el oro no significa otra cosa en Rivendel.
    'eval.umbral_verde'          => 'Umbral de zona verde (0,80)',
    'eval.columna_indice'        => 'Auditoría del %s · índice %s',
    'eval.indice_resumen'        => 'Índice general por auditoría. Auditorías con índice calculado: %s. Último valor: %s.',
    'eval.sin_indice_calculado'  => 'Ninguna auditoría de esta organización tiene todavía el índice calculado.',
    'eval.auditorias_sin_indice' => 'Auditorías sin índice calculado, sin columna en el gráfico: %s.',

    // Comparación histórica — perfil de madurez por dominio (gráfico de araña).
    'eval.perfil_dominios'       => 'Perfil de madurez por dominio',
    'eval.perfil_dominios_texto' => 'Última auditoría con desglose (%s).',
    'eval.sin_desglose_dominio'  => 'Todavía no hay controles evaluados con madurez en esta organización.',
    'eval.radar_vertice'         => '%s · madurez %s de %s',
    'eval.radar_resumen'         => 'Perfil de madurez por dominio de la auditoría del %s: %s.',
    'eval.radar_insuficiente'    => 'Se necesitan al menos tres dominios evaluados para dibujar el perfil. Esta auditoría tiene %s.',
    'eval.radar_pie'             => '%s dominios evaluados · escala de madurez de 0 a %s, un anillo por nivel.',

    // Comparación histórica — tabla del desglose.
    'eval.dominio'               => 'Dominio',
    'eval.madurez_por_dominio'   => 'Madurez ponderada por dominio',
    'eval.madurez_por_dominio_pie' => 'Promedio ponderado por el peso de cada control. Un guion marca el dominio que esa auditoría no evaluó.',

    // Formulario de encabezado (alta/edición de auditoría)
    'eval.admin_entrevistado_label' => 'Administrador de base de datos entrevistado',
    'eval.seleccione'               => '— Seleccione —',
    'eval.organizacion_registrada'  => 'Su organización es la que queda registrada como entidad auditada.',
    'eval.area_evaluada'            => 'Área evaluada',
    'eval.fecha_auditoria'          => 'Fecha de la auditoría',

    // Tipo de auditoría (modelo de medición) y leyenda del alta
    'eval.tipo_auditoria'        => 'Tipo de auditoría',
    'eval.tipo_proximamente'     => 'próximamente',
    'eval.tipo_ayuda'            => 'Modelo contra el que se mide. Hoy los 75 controles son de la familia ISO 27000.',
    'eval.nueva_subtitulo'       => 'Abra una medición fechada para la organización que va a auditar.',
    'eval.nueva_leyenda_titulo'  => '¿Para qué sirve?',
    'eval.nueva_leyenda_texto'   => 'Una auditoría es la medición fechada de una organización: se recorre el '
                                  . 'instrumento control por control y de las respuestas salen el índice de riesgo, '
                                  . 'la matriz C-I-D y las remediaciones. Repetirla es lo que permite comparar.',
    'eval.nueva_modelos_proximos' => 'Próximas actualizaciones',
    'eval.nueva_modelos_texto'   => 'Otros modelos de medición además del ISO 27000. Llegan con el plan Deluxe, '
                                  . 'que cruza las equivalencias entre los tres marcos.',
    'eval.nueva_ver_plan'        => 'Ver el plan Deluxe →',
];
