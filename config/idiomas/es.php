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
    'nav.ir_tablero'      => 'Ir al tablero',
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
    'panel.grupo_monitoreo'       => 'Monitoreo',
    'panel.catalogo_controles'    => 'Catálogo de controles',
    'panel.matriz_cid'            => 'Mapa de procesos vs C-I-D',
    'panel.remediaciones_vencidas' => 'Remediaciones vencidas',
    'panel.volver_sitio'          => 'Volver al sitio',
    'panel.rol_auditor'           => 'Auditor',
    'panel.rol_admin'             => 'Administrador de BD',

    // Asistente del módulo: lanzador de la esquina y panel de la derecha.
    // El NOMBRE vive en una sola clave y el resto lo recibe como %s: rebautizarlo
    // es cambiar esta línea (y su gemela en en.php), no buscarlo por el archivo.
    'asistente.nombre'              => 'Lembas',
    'asistente.funcion'             => 'Asistente de auditoría',
    'asistente.abrir'               => 'Abrir %s',
    'asistente.cerrar'              => 'Cerrar %s',
    'asistente.responde_como'       => 'Con los permisos de su cuenta · %s',
    'asistente.contexto'            => 'Pantalla actual',
    'asistente.conversacion'        => 'Conversación con %s',
    'asistente.bienvenida'          => 'Hola, %s. Soy %s, ¿qué necesita consultar?',
    'asistente.alcance_auditor'     => 'Puedo mostrarle sus auditorías, el resumen y los riesgos de cada una, abrirle un control para llenarlo y explicarle el catálogo de controles. Solo trabajo con las auditorías que usted condujo.',
    'asistente.alcance_admin'       => 'Puedo mostrarle sus auditorías, el resumen y los riesgos de cada una, abrirle un control para llenarlo, explicarle el catálogo de controles y mostrarle las remediaciones vencidas de todas las auditorías.',
    'asistente.privacidad'          => 'Los datos de sus auditorías se muestran aquí, pero nunca se envían a %s. No escriba hallazgos ni evidencias en el chat: se registran en el formulario del control.',
    'asistente.para_empezar'        => 'Para empezar',
    'asistente.sugerencia_auditorias' => 'Muéstrame mis auditorías',
    'asistente.sugerencia_resumen'  => 'Resumen de esta auditoría',
    'asistente.sugerencia_riesgo'   => '¿Qué controles tienen mayor riesgo?',
    'asistente.sugerencia_llenar'   => 'Quiero llenar el control de respaldos',
    'asistente.sugerencia_catalogo' => '¿Qué evidencia pide el control C-014?',
    'asistente.sugerencia_vencidas' => '¿Qué remediaciones están vencidas?',
    'asistente.mensaje'             => 'Mensaje para %s',
    'asistente.marcador'            => 'Pregúntele a %s…',
    'asistente.enviar'              => 'Enviar',
    'asistente.ayuda_teclado'       => 'Intro envía; Mayús + Intro, nueva línea.',
    'asistente.aviso_ia'            => '%s es una IA: contraste sus respuestas con la evidencia.',
    'asistente.usted'               => 'Usted',
    'asistente.nueva_conversacion'  => 'Nueva conversación',
    'asistente.pensando'            => '%s está consultando…',

    // Fichas: los datos que pinta el servidor.
    'asistente.ficha_auditorias'    => 'Sus auditorías',
    'asistente.ficha_sin_auditorias' => 'Todavía no tiene auditorías.',
    'asistente.ficha_resumen'       => 'Resumen · Auditoría %s',
    'asistente.ficha_riesgo'        => 'Mayor riesgo · Auditoría %s',
    'asistente.ficha_sin_riesgo'    => 'Ningún control de esta auditoría tiene todavía un nivel de riesgo calculado.',
    'asistente.ficha_sin_vencidas'  => 'No hay remediaciones vencidas.',
    'asistente.auditoria_n'         => 'Auditoría %s',
    'asistente.ver_todas'           => 'Ver todas',
    'asistente.mostrando_de'        => 'Se muestran %s de %s.',
    'asistente.respuestas_si_no_na' => 'Respuestas: %s Sí · %s No · %s No aplica',
    'asistente.respuesta_registrada' => 'Respuesta registrada: %s',
    'asistente.pregunta_auditoria'  => 'Pregunta de auditoría:',
    'asistente.evidencia_esperada'  => 'Evidencia esperada:',
    'asistente.auditoria_finalizada' => 'La auditoría está finalizada: hay que reabrirla para modificar este control.',
    'asistente.abrir_formulario'    => 'Llenar este control',
    'asistente.revisar_formulario'  => 'Revisar la respuesta',
    'asistente.vencio'              => 'Venció: %s',
    'asistente.responsable'         => 'Responsable: %s',

    // Avisos dentro de un turno.
    'asistente.aviso_auditoria_no_encontrada' => 'No encontré la auditoría %s entre las suyas.',
    'asistente.aviso_control_no_encontrado'   => 'El código %s no corresponde a ningún control del catálogo.',
    'asistente.aviso_rechazo'       => 'No puedo responder esa consulta. Pruebe a formularla de otra manera.',
    'asistente.aviso_cortada'       => 'La respuesta se cortó por ser demasiado larga.',
    'asistente.aviso_incompleta'    => 'No pude terminar la consulta en los pasos disponibles. Pruebe con una pregunta más concreta.',

    // Errores: el tipo decide el texto; el detalle técnico va al registro.
    'asistente.error_vacia'         => 'Escriba una pregunta antes de enviar.',
    'asistente.error_larga'         => 'La pregunta es demasiado larga. Resúmala en menos de 2000 caracteres.',
    'asistente.error_codificacion'  => 'La pregunta contiene caracteres que no se pudieron leer. Escríbala de nuevo.',
    'asistente.error_limite_usuario' => 'Llegó al límite de %s consultas diarias. Podrá volver a preguntar mañana.',
    'asistente.error_limite_total'  => 'Se alcanzó el límite diario de consultas del sistema. Vuelva a intentarlo mañana.',
    'asistente.error_sesion'        => 'Su sesión expiró. Recargue la página e inicie sesión de nuevo.',
    'asistente.error_apagado'       => 'El asistente no está configurado en este servidor.',
    'asistente.error_red'           => 'No pude comunicarme con el servicio. Revise la conexión e intente de nuevo.',
    'asistente.error_saturado'      => 'El servicio está saturado en este momento. Intente de nuevo en unos minutos.',
    'asistente.error_credencial'    => 'La clave de la API no es válida o fue revocada. Avise a quien administra el sistema.',
    'asistente.error_saldo'         => 'La cuenta de la API se quedó sin saldo o alcanzó su límite de gasto.',
    'asistente.error_peticion'      => 'Algo falló al preparar la consulta. Ya quedó registrado; intente de nuevo.',
    'asistente.error_interno'       => 'Algo falló al procesar la consulta. Intente de nuevo.',

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
    'eval.tablero_titulo'       => 'Estado y evolución',
    'eval.empresa_coincidencias' => 'Empresas que coinciden',
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
    'eval.dominios_lista'       => 'Dominios de la auditoría',
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
    'eval.grado_logro'         => 'Grado de logro de la práctica',
    'eval.grado_ayuda'         => 'N: no (0-15 %) · P: parcial (15-50 %) · L: amplio (50-85 %) · F: total (85-100 %). L y F cuentan como logrado.',
    'eval.grado_n'             => 'N — No logrado',
    'eval.grado_p'             => 'P — Parcialmente',
    'eval.grado_l'             => 'L — Ampliamente',
    'eval.grado_f'             => 'F — Totalmente',
    'eval.capacidad'           => 'Capacidad',
    'eval.capacidad_objetivo'  => 'Capacidad del objetivo',
    'eval.capacidad_sin_declarar' => 'Capacidad sin declarar',
    'eval.justificacion_capacidad' => 'Justificación (qué prácticas y evidencia la sustentan)',
    'eval.guardar_capacidad'   => 'Guardar capacidad',
    'eval.criterio_documentado' => 'Documentado',
    'eval.criterio_repetible'   => 'Repetible',
    'eval.criterio_evidencia'   => 'Con evidencia',
    'eval.evidencia_verificada' => 'Evidencia revisada',
    'eval.evidencia_ayuda'      => 'Obligatorio si la respuesta es "Sí": describa el documento, log, captura o configuración que sustenta la respuesta (ISO/IEC 27007 — la conformidad se prueba con evidencia, no con la afirmación del auditado).',
    'eval.calidad_evidencia'    => 'Calidad de la evidencia',
    'eval.calidad_bien'         => 'Bien implementado',
    'eval.calidad_mejora'       => 'Requiere mejora',
    'eval.calidad_declarativo'  => 'Declarativo (sin evidencia real)',

    // Tarjeta de un control dentro de una auditoría
    // (components/tarjeta-control-auditoria.php)
    'eval.sin_responder'        => 'Sin responder',
    'eval.nivel_riesgo'         => 'Nivel de riesgo',
    'eval.dimensiones'          => 'Compromete',
    'eval.no_marcada'           => 'no marcada',
    'eval.aviso_na_sin_justificar' => 'Marcado como «no aplica» y sin hallazgo escrito: sale del cálculo de cumplimiento sin dejar constancia de por qué (ISO/IEC 27001, cl. 6.1.3).',
    'eval.dominio_anterior'     => 'Dominio anterior',
    'eval.dominio_siguiente'    => 'Dominio siguiente',
    'eval.guardado'             => 'Guardado',
    'eval.remediaciones'        => 'Remediaciones',
    'eval.respondidos'          => 'Respondidos',
    'eval.dominios_palabra'     => 'Dominios',
    'eval.evidencia_marcador'   => 'Documento, log, captura o configuración revisada',

    // El adjunto de la evidencia. Acompaña a la descripción escrita y no la
    // sustituye: por eso «Archivo adjunto» y no «Evidencia», que ya es el
    // rótulo del texto de arriba. La línea de estado se imprime SIEMPRE, con
    // archivo o sin él — un campo de archivo vacío no distingue «no hay
    // ninguno» de «hay uno que el navegador no puede repoblar».
    'eval.evidencia_archivo'          => 'Archivo adjunto',
    'eval.evidencia_archivo_sin'      => 'Sin archivo adjunto.',
    'eval.evidencia_archivo_quitar'   => 'Quitar este archivo al guardar',
    'eval.evidencia_archivo_soltar'   => 'Arrastre el archivo aquí',
    'eval.evidencia_archivo_o'        => 'o',
    'eval.evidencia_archivo_examinar' => 'Búsquelo en el equipo',
    'eval.evidencia_archivo_ayuda'    => 'Opcional. Imagen (PNG, JPG, WEBP o GIF) o PDF, hasta %s MB.',
    'eval.evidencia_archivo_sustituir' => 'Elija otro archivo para sustituir el actual. Imagen o PDF, hasta %s MB.',
    'eval.hallazgo_marcador'    => 'Lo observado durante la verificación',
    'eval.recomendacion_marcador' => 'Acción sugerida y su prioridad',

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
    // Gráfico de columnas del cumplimiento por dominio.
    // El resumen NO describe el dibujo («un gráfico de barras»), que a quien no
    // lo ve no le sirve: da la lectura, que es con lo que uno se queda al mirarlo.
    'eval.gr_dominios_resumen'  => 'Cumplimiento por dominio, %s en total. El más bajo es %s, con %s.',
    'eval.detalle_dominio'      => 'Detalle por dominio',
    'eval.detalle_dominio_ayuda' => 'Abra un dominio para ver sus controles señalados.',
    'eval.gr_dominios_pie'      => 'Las líneas de 50 % y 80 % son los cortes de zona: por debajo del 50 % el riesgo es alto, y por encima del 80 % es bajo.',
    'eval.de_cinco'             => 'de 5',
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

    // Comparar histórico — la antesala: elegir la empresa.
    //
    // Los VALORES de los filtros («VERDE», «TENDENCIA») viajan en la URL y no
    // se traducen: una dirección compartida tiene que seguir valiendo cuando
    // quien la abre trabaja en el otro idioma. Lo que se traduce es el rótulo.
    'eval.filtros'               => 'Filtros',
    'eval.aplicar_filtros'       => 'Aplicar filtros',
    'eval.limpiar_filtros'       => 'Limpiar todo',
    'eval.filtros_activos'       => 'Filtros aplicados',
    // El verbo va en el rótulo de accesibilidad: «Riesgo bajo ×» no anuncia
    // qué pasa al pulsarlo.
    'eval.quitar_filtro'         => 'Quitar el filtro «%s»',
    'eval.filtro_zona'           => 'Riesgo de la última auditoría',
    'eval.filtro_sin_indice'     => 'Sin índice',
    'eval.filtro_estado'         => 'Estado de la cartera',
    'eval.filtro_con_progreso'   => 'Con trabajo en progreso',
    'eval.filtro_finalizadas'    => 'Todo finalizado',
    'eval.filtro_historico'      => 'Histórico',
    'eval.filtro_con_tendencia'  => 'Dos auditorías o más',
    'eval.filtro_una_sola'       => 'Una sola auditoría',
    // «Empresas: 3 de 7» y no «3 empresas»: la cifra detrás de los dos puntos
    // vale igual en singular y en plural.
    'eval.empresas_rango'        => 'Empresas: %s de %s',
    'eval.orden_empresa_reciente'   => 'Auditada hace menos',
    'eval.orden_empresa_auditorias' => 'Más auditorías',
    'eval.orden_empresa_indice'     => 'Mejor índice',
    'eval.orden_empresa_nombre'     => 'Nombre (A-Z)',
    'eval.sin_empresas_filtro'      => 'Ninguna empresa coincide con el filtro.',
    'eval.sin_empresas_filtro_texto' => 'Quite alguna condición para ampliar el resultado.',
    'eval.ultimo_indice'         => 'Último índice',
    'eval.variacion_anterior'    => '%s desde la anterior',
    'eval.primera_lectura'       => 'Primera lectura con índice',
    'eval.en_progreso_n'         => '%s en progreso',
    'eval.periodo_auditado'      => 'Periodo auditado',

    // Formulario de encabezado (alta/edición de auditoría)
    'eval.admin_entrevistado_label' => 'Administrador de base de datos entrevistado',
    'eval.seleccione'               => '— Seleccione —',
    'eval.organizacion_registrada'  => 'Su organización es la que queda registrada como entidad auditada.',
    'eval.origen_registrado'        => 'De la lista',
    'eval.origen_manual'            => 'Editar detalles',
    'eval.admin_nombre'             => 'Nombre de la persona entrevistada',
    'eval.admin_nombre_marcador'    => 'Ej.: Marta Jiménez',
    'eval.admin_empresa'            => 'Empresa a la que pertenece',
    'eval.admin_empresa_marcador'   => 'Ej.: Cooperativa de Ejemplo R.L.',
    'eval.admin_manual_ayuda'       => 'La empresa que escriba aquí es la que queda registrada como entidad auditada. No se crea ninguna cuenta: el dato vive en esta auditoría.',
    'eval.area_evaluada'            => 'Área evaluada',
    'eval.fecha_auditoria'          => 'Fecha de la auditoría',

    // Tipo de auditoría (modelo de medición) y leyenda del alta
    'eval.tipo_auditoria'        => 'Norma de la auditoría',
    'eval.tipo_proximamente'     => 'próximamente',
    'eval.tipo_ayuda'            => 'Define el catálogo de controles y la escala de madurez. No se puede cambiar después.',
    'eval.nueva_subtitulo'       => 'Abra una medición fechada para la organización que va a auditar.',
    'eval.nueva_leyenda_titulo'  => '¿Para qué sirve?',
    'eval.nueva_leyenda_texto'   => 'Una auditoría es la medición fechada de una organización: se recorre el '
                                  . 'instrumento control por control y de las respuestas salen el índice de riesgo, '
                                  . 'la matriz C-I-D y las remediaciones. Repetirla es lo que permite comparar.',
    'eval.nueva_modelos_proximos' => 'Próximas actualizaciones',
    'eval.nueva_modelos_texto'   => 'Otros modelos de medición además del ISO 27000. Llegan con el plan Deluxe, '
                                  . 'que cruza las equivalencias entre los tres marcos.',
    'eval.nueva_ver_plan'        => 'Ver el plan Deluxe →',

    // ── Monitor de salud de bases de datos (parte 2) ─────────────────────────
    //
    // Vocabulario ÚNICO de cinco bandas: las mismas claves sirven para una
    // métrica, un componente y el índice. Si alguna pantalla necesitara un
    // segundo juego de nombres, eso sería la tabla de traducción que el §5.2
    // del plan existe para evitar.
    'mon.banda_optimo'       => 'Óptimo',
    'mon.banda_saludable'    => 'Saludable',
    'mon.banda_advertencia'  => 'Advertencia',
    'mon.banda_degradado'    => 'Degradado',
    'mon.banda_critico'      => 'Crítico',
    'mon.banda_sin_dato'     => 'Sin dato',

    'mon.monitor'            => 'Monitor',
    'mon.titulo'             => 'Monitor de salud de bases de datos',

    'mon.instancia_no_encontrada' => 'No hay ninguna base de datos vigilada con esa clave.',

    // Antesala: una ficha por base de datos vigilada
    'mon.buscar_etiqueta'    => 'Buscar por clave, motor o entorno',
    'mon.buscar_marcador'    => 'Ej.: prodcore, 19c, legado…',
    'mon.filtro_banda'       => 'Estado de salud',
    'mon.filtro_conexion'    => 'Conexión',
    'mon.filtro_entorno'     => 'Entorno',
    'mon.filtro_motor'       => 'Motor',
    'mon.filtro_sin_indice'  => 'Sin índice',
    'mon.instancias_rango'   => 'Bases de datos: %s de %s',
    'mon.orden_atencion'     => 'Más urgente',
    'mon.orden_indice'       => 'Mejor índice',
    'mon.orden_reciente'     => 'Muestra más reciente',
    'mon.orden_clave'        => 'Clave (A-Z)',
    'mon.sin_instancias_filtro' => 'Ninguna base de datos coincide con el filtro.',
    'mon.ultima_muestra'     => 'Última muestra %s',

    'mon.duracion'           => 'Duración de la toma',
    'mon.hace_min'           => 'hace %s min',
    'mon.hace_horas'         => 'hace %s h %s min',

    'mon.contexto_raiz'       => 'Conexión a la raíz sin respuesta',
    'mon.contexto_contenedor' => 'Conexión al contenedor sin respuesta',

    'mon.isbd'               => 'ISBD',
    'mon.formula'            => 'ISBD = 0,30·Procesos + 0,35·Memoria + 0,35·Archivos, sobre los valores '
                              . 'ya publicados de cada componente y topado por el peor estado observado.',

    'mon.sin_respuesta'      => 'Sin respuesta',
    'mon.caida_explicacion'  => 'La instancia no respondió a esta toma. La muestra se guarda igual, porque '
                              . '«no respondió a esta hora» también es un dato: saltarla dejaría un hueco '
                              . 'que después parece un periodo sano.',
    'mon.ultimo_conocido'    => 'Último valor conocido',

    'mon.muestra_incompleta' => 'Muestra incompleta',
    'mon.incompleta_explicacion' => 'La cobertura de esta muestra es %s y el piso es %s, así que no se '
                              . 'publica índice. Un índice calculado sobre la mitad de la evidencia es '
                              . 'peor que ningún índice, porque parece uno bueno.',

    // Ficha «Por qué vale eso»: solo aparece cuando el eslabón más débil topó el ISBD.
    'mon.causa'              => 'Por qué vale eso',
    'mon.tope_explicacion'   => 'El promedio ponderado de los tres componentes daría %s, pero %s está en '
                              . 'banda %s y topa el ISBD en %s: el índice publicado nunca supera lo que '
                              . 'permite su componente más débil.',

    'mon.componentes'        => 'Componentes',
    'mon.componentes_vacio'  => 'No hay componentes que calcular: la instancia no respondió a esta toma, así que ninguna métrica llegó a medirse.',
    'mon.comp_procesos'      => 'Procesos',
    'mon.comp_memoria'       => 'Memoria',
    'mon.comp_archivos'      => 'Archivos',
    'mon.peso'               => 'peso %s %%',
    'mon.comp_topado'        => 'Promedio %s, topado en %s por el peor estado del componente.',
    'mon.comp_sin_metricas'  => 'Ninguna de sus métricas se pudo recolectar en esta muestra.',

    // Ficha de CONSULTAS: se recolecta y se muestra, pero no es un sumando del ISBD (§3.1).
    'mon.comp_consultas'       => 'Consultas',
    'mon.consultas_fuera_isbd' => 'Se recolecta y se muestra, pero no suma en la fórmula del ISBD: mide el '
                                . 'trabajo que se le pide a la base, no su salud.',

    // Selector de base de datos vigilada
    'mon.base_vigilada'      => 'Base de datos vigilada',
    'mon.conectada'          => 'Conectada',
    'mon.sin_conexion'       => 'Sin conexión',

    // Consola de operación
    'mon.consola'            => 'Panel de operación',
    'mon.medidor_lectura'    => 'Índice de salud de la base de datos: %s, banda %s.',
    'mon.desc_procesos'      => 'Cupo de sesiones y procesos, vitalidad de los procesos de fondo y espera de escritura de redo.',
    'mon.desc_memoria'       => 'PGA contra su objetivo, aciertos de caché de PGA y memoria libre de la shared pool.',
    'mon.desc_archivos'      => 'Ocupación de los tablespaces, datafiles en línea y grupos de redo sin miembros inválidos.',

    // Tendencia del ISBD
    'mon.tendencia'          => 'Tendencia del ISBD',
    'mon.isbd_publicado'     => 'ISBD publicado',
    'mon.leyenda_topada'     => 'Lectura topada por el eslabón más débil',
    'mon.leyenda_hueco'      => 'Sin índice publicado',
    'mon.sin_tendencia'      => 'Todavía no hay suficientes lecturas para dibujar una tendencia.',
    'mon.tendencia_resumen'  => 'Tendencia del índice de salud sobre %s lecturas; la última publicada es %s y %s quedaron topadas por el eslabón más débil.',
    'mon.tendencia_pie'      => '%s lecturas, una cada %s minutos: las últimas %s minutos. Las mesetas planas sobre '
                              . '40, 60, 75 y 90 no son un fallo del dibujo: son las lecturas en las que el promedio '
                              . 'daba más y el eslabón más débil lo bajó a la frontera de la banda.',
    'mon.punto_lectura'      => 'Lectura %s: %s',
    'mon.punto_topado'       => 'Lectura %s: %s, topada por el eslabón más débil',

    // Fichas de índice y semáforo
    'mon.indices_lista'      => 'Índices que componen el ISBD',
    'mon.ver_procesos'       => 'Ver los %s procesos evaluados',
    'mon.semaforo_leyenda'   => 'El semáforo agrupa las cinco bandas del instrumento, con sus mismas '
                              . 'fronteras: rojo es Crítico o Degradado (hasta 60), ámbar es Advertencia '
                              . '(60 a 75) y verde es Saludable u Óptimo (más de 75). El ISBD se pinta '
                              . 'de rojo si cualquiera de los tres índices está en rojo, y solo se pinta '
                              . 'de verde cuando los tres lo están.',

    // Tabla de procesos por índice
    'mon.procesos_de'        => 'Procesos evaluados · %s',
    'mon.sin_procesos'       => 'Este índice no tiene procesos declarados en el catálogo.',
    'mon.col_proceso'        => 'Proceso',
    'mon.col_metricas'       => 'Métricas',
    'mon.col_resultado'      => 'Resultado',
    'mon.col_descripcion'    => 'Descripción',
    'mon.col_recomendacion'  => 'Recomendación',
    'mon.ayuda_tabla'        => 'Cómo se lee esta tabla',
    'mon.ayuda_consola'      => 'Cómo se calcula y se colorea este panel',
    'mon.res_correcto'       => 'Correcto',
    'mon.res_hallazgo'       => 'Con hallazgo',
    'mon.res_sin_dato'       => 'Sin dato',
    'mon.procesos_nota'      => 'Cada métrica tiene su columna y va normalizada de 0 a 1, para que '
                              . 'midan lo mismo aunque observen cosas distintas. Una celda EN BLANCO '
                              . 'significa que esa métrica no evalúa a ese proceso; un guion, que sí lo '
                              . 'evalúa pero no se pudo recolectar. Un proceso se marca correcto cuando '
                              . 'ninguna de sus métricas enciende el semáforo, y «sin dato» no es un '
                              . 'fallo suyo: por eso no lleva aspa. El código en oro junto al nombre del '
                              . 'proceso es el objetivo de gestión de COBIT 2019 cuyo riesgo vigila.',

    /*
     * Trazabilidad COBIT 2019 de la tabla de procesos (ver `catalogo_cobit`
     * en config/monitor-mockup.php). Nombre oficial del objetivo de gestión.
     */
    'mon.cobit_dss01' => 'Gestión de las operaciones',
    'mon.cobit_dss04' => 'Gestión de la continuidad',
    'mon.cobit_apo14' => 'Gestión de los datos',

    /*
     * Ficha de cada métrica (catalogo_metricas en config/monitor-mockup.php):
     * nombre y qué mide, para el panel emergente del código en la cabecera de
     * la tabla de procesos.
     */
    'mon.metrica_mpro01_nombre'      => 'Utilización de sesiones',
    'mon.metrica_mpro01_descripcion' => 'Qué proporción de las sesiones que la instancia admite está en uso. '
        . 'Al llegar al techo, las conexiones nuevas se rechazan con ORA-00018 aunque la base esté sana por dentro.',
    'mon.metrica_mpro02_nombre'      => 'Utilización de procesos',
    'mon.metrica_mpro02_descripcion' => 'Cuánto del arreglo de procesos del sistema operativo está ocupado. Se '
        . 'agota antes que el de sesiones en instancias con servidor dedicado, y su falla (ORA-00020) es igual '
        . 'de abrupta.',
    'mon.metrica_mpro03_nombre'      => 'Procesos de fondo obligatorios presentes',
    'mon.metrica_mpro03_descripcion' => 'Compuerta: comprueba que los cinco procesos de fondo —CKPT, DBW0, LGWR, '
        . 'PMON y SMON— están vivos en esta muestra. No tiene término medio: vale 1 o 0.',
    'mon.metrica_mpro04_nombre'      => 'Espera media de escritura de redo',
    'mon.metrica_mpro04_descripcion' => 'Milisegundos que tarda de media una escritura del registro de rehacer. '
        . 'Es la latencia que siente la aplicación al confirmar, así que se lee como experiencia de usuario y '
        . 'no como infraestructura.',
    'mon.metrica_mpro05_nombre'      => 'Reinicio de proceso de fondo detectado',
    'mon.metrica_mpro05_descripcion' => 'Compuerta: compara la huella de cada proceso de fondo con la de la '
        . 'muestra anterior. Ve lo que M-PRO-03 no puede ver — un proceso que cayó y volvió a levantarse entre '
        . 'dos muestras.',
    'mon.metrica_mpro06_nombre'      => 'Antigüedad del punto de control',
    'mon.metrica_mpro06_descripcion' => 'Cuánto se ha atrasado el punto de control frente al objetivo de MTTR. '
        . 'Dice cuánto tardaría la recuperación si la instancia cayera ahora mismo.',
    'mon.metrica_mmem01_nombre'      => 'Aciertos de caché de PGA',
    'mon.metrica_mmem01_descripcion' => 'Proporción del trabajo de PGA que se resolvió íntegramente en memoria, '
        . 'sin pasar por disco. MAYOR ES MEJOR: es la medida de resultado de la PGA, mientras que M-MEM-02 es '
        . 'la de consumo.',
    'mon.metrica_mmem02_nombre'      => 'PGA asignada sobre el objetivo',
    'mon.metrica_mmem02_descripcion' => 'Cuánta memoria privada de sesión se ha asignado frente al objetivo '
        . 'configurado. Al pasarse, el trabajo no falla: se traslada al tablespace temporal y todo se vuelve '
        . 'más lento en silencio.',
    'mon.metrica_mmem03_nombre'      => 'Memoria libre de la shared pool',
    'mon.metrica_mmem03_descripcion' => 'Cuánto espacio libre queda en la zona de la SGA donde viven los planes '
        . 'de ejecución y el diccionario en caché. MAYOR ES MEJOR: aquí el espacio libre es el margen de '
        . 'maniobra.',
    'mon.metrica_marc01_nombre'      => 'Utilización del peor tablespace',
    'mon.metrica_marc01_descripcion' => 'Ocupación del tablespace permanente peor situado, nunca el promedio: '
        . 'un promedio sano esconde el archivo que está a punto de reventar. Al llenarse, la escritura falla '
        . 'con ORA-01653.',
    'mon.metrica_marc02_nombre'      => 'Datafiles en estado válido',
    'mon.metrica_marc02_descripcion' => 'Compuerta: comprueba que todos los archivos de datos están en línea. '
        . 'Uno fuera de línea deja inaccesible su parte de los datos aunque el resto de la instancia responda '
        . 'con normalidad.',
    'mon.metrica_marc03_nombre'      => 'Grupos de redo sin miembros inválidos',
    'mon.metrica_marc03_descripcion' => 'Compuerta sobre los miembros inutilizables de los grupos del registro '
        . 'de rehacer. Con todos los grupos inservibles la base se detiene, porque no puede rotar el registro.',
    'mon.metrica_marc04_nombre'      => 'Utilización del peor tablespace temporal',
    'mon.metrica_marc04_descripcion' => 'Ocupación del tablespace temporal peor situado: el espacio de trabajo '
        . 'de lo que no cupo en PGA. Al agotarse, la consulta en curso falla con ORA-01652, pero no se pierden '
        . 'datos permanentes.',
    'mon.metrica_marc05_nombre'      => 'Utilización del peor tablespace sin crecimiento automático',
    'mon.metrica_marc05_descripcion' => 'Ocupación del peor tablespace que NO puede autoextenderse. Cubre el '
        . 'punto ciego de M-ARC-01: con crecimiento automático el porcentaje se mide contra el máximo '
        . 'alcanzable y casi nunca alarma.',
    'mon.metrica_mcon01_nombre'      => 'Sentencias sobre el umbral de tiempo por ejecución',
    'mon.metrica_mcon01_descripcion' => 'Cuántas sentencias del top-20 superan el tiempo por ejecución pactado. '
        . 'Es un conteo, no una proporción, y mide el trabajo que se le pide a la base, no su salud.',

    /*
     * Catálogo de procesos (catalogo_procesos en config/monitor-mockup.php):
     * nombre, descripción y recomendación por proceso, agrupados por índice.
     */
    'mon.proceso_pmon_nombre'        => 'PMON',
    'mon.proceso_pmon_descripcion'   => 'Monitor de procesos. Limpia lo que dejan las sesiones que terminan de '
        . 'forma anormal: deshace su transacción, libera los bloqueos que retenían y devuelve su hueco al '
        . 'arreglo de procesos. Si PMON no está, la instancia no está.',
    'mon.proceso_pmon_recomendacion' => 'Se recomienda alertar a la primera muestra en que falte, sin esperar '
        . 'confirmación: no hay degradación parcial de este proceso.',

    'mon.proceso_smon_nombre'        => 'SMON',
    'mon.proceso_smon_descripcion'   => 'Monitor del sistema. Recupera la instancia al arrancar tras una caída, '
        . 'fusiona los extents libres contiguos y limpia los segmentos temporales que quedaron huérfanos.',
    'mon.proceso_smon_recomendacion' => 'Se recomienda mirarlo junto a M-ARC-04: cuando SMON se atrasa, el '
        . 'tablespace temporal es el primero en notarlo.',

    'mon.proceso_dbw0_nombre'        => 'DBW0',
    'mon.proceso_dbw0_descripcion'   => 'Escritor de base de datos. Baja a los datafiles los bloques sucios del '
        . 'buffer cache para que haya sitio libre donde leer los siguientes.',
    'mon.proceso_dbw0_recomendacion' => 'Se recomienda no leerlo solo: si DBW0 está vivo pero el punto de '
        . 'control se atrasa (M-PRO-06), el cuello de botella es la E/S de disco y no el proceso.',

    'mon.proceso_lgwr_nombre'        => 'LGWR',
    'mon.proceso_lgwr_descripcion'   => 'Escritor del registro de rehacer. Vuelca el búfer de redo a los '
        . 'archivos de log en cada COMMIT, y por eso su latencia es la latencia que siente la aplicación al '
        . 'confirmar.',
    'mon.proceso_lgwr_recomendacion' => 'Se recomienda tratar M-PRO-04 como métrica de experiencia de usuario, '
        . 'no de infraestructura: por encima de 20 ms los COMMIT se notan desde fuera.',

    'mon.proceso_ckpt_nombre'        => 'CKPT',
    'mon.proceso_ckpt_descripcion'   => 'Proceso de punto de control. Marca hasta dónde está garantizado el '
        . 'contenido en disco y actualiza las cabeceras de los datafiles. Cuanto más atrasado va, más tarda la '
        . 'recuperación tras una caída.',
    'mon.proceso_ckpt_recomendacion' => 'Se recomienda comparar M-PRO-06 con el objetivo de MTTR pactado con el '
        . 'negocio, no con un número absoluto.',

    'mon.proceso_cupo_sesiones_nombre'        => 'Cupo de sesiones',
    'mon.proceso_cupo_sesiones_descripcion'   => 'No es un proceso de fondo: es el techo de sesiones '
        . 'concurrentes que la instancia admite. Al agotarse, las conexiones nuevas se rechazan con ORA-00018 '
        . 'aunque la base esté perfectamente sana por dentro.',
    'mon.proceso_cupo_sesiones_recomendacion' => 'Se recomienda medirlo contra el límite efectivo de '
        . 'V$RESOURCE_LIMIT y nunca contra una cifra supuesta.',

    'mon.proceso_cupo_procesos_nombre'        => 'Cupo de procesos',
    'mon.proceso_cupo_procesos_descripcion'   => 'Techo del arreglo de procesos del sistema operativo. Se agota '
        . 'antes que el de sesiones en instancias con servidor dedicado, y su falla (ORA-00020) es igual de '
        . 'abrupta.',
    'mon.proceso_cupo_procesos_recomendacion' => 'Se recomienda vigilarlo junto al cupo de sesiones: suben '
        . 'juntos y quien avisa primero depende de la configuración, no de la carga.',

    'mon.proceso_shared_pool_nombre'        => 'Shared pool',
    'mon.proceso_shared_pool_descripcion'   => 'Zona de la SGA donde viven los planes de ejecución y el '
        . 'diccionario de datos en caché. Cuando se queda sin espacio libre, Oracle empieza a expulsar planes y '
        . 'a recompilar sentencias que ya tenía resueltas, y el coste aparece como CPU, no como memoria.',
    'mon.proceso_shared_pool_recomendacion' => 'Se recomienda no perseguir el 100 % de ocupación: aquí el '
        . 'espacio libre es el margen de maniobra.',

    'mon.proceso_pga_nombre'        => 'PGA',
    'mon.proceso_pga_descripcion'   => 'Área global de programa: la memoria privada de cada sesión para '
        . 'ordenamientos, agrupaciones y uniones por hash. Al pasarse del objetivo, el trabajo no falla: se '
        . 'traslada al tablespace temporal y todo se vuelve más lento en silencio.',
    'mon.proceso_pga_recomendacion' => 'Se recomienda leerla junto a M-ARC-04, que es donde aterriza lo que no '
        . 'cupo.',

    'mon.proceso_cache_pga_nombre'        => 'Caché de PGA',
    'mon.proceso_cache_pga_descripcion'   => 'Proporción del trabajo de PGA que se resolvió íntegramente en '
        . 'memoria, sin pasar por disco. Es la medida de resultado de la PGA, mientras que M-MEM-02 es la de '
        . 'consumo.',
    'mon.proceso_cache_pga_recomendacion' => 'Se recomienda actuar cuando esta baja aunque la asignada esté '
        . 'dentro del objetivo: significa que el objetivo se quedó corto para la carga real.',

    'mon.proceso_tablespaces_permanentes_nombre'        => 'Tablespaces permanentes',
    'mon.proceso_tablespaces_permanentes_descripcion'   => 'Espacio ocupado en el tablespace peor situado, '
        . 'nunca el promedio: un promedio sano esconde el archivo que está a punto de reventar. Al llenarse, '
        . 'la escritura falla con ORA-01653 y la transacción se pierde.',
    'mon.proceso_tablespaces_permanentes_recomendacion' => 'Se recomienda revisar el crecimiento semanal además '
        . 'del porcentaje: el porcentaje dice dónde está, la pendiente dice cuándo llega.',

    'mon.proceso_datafiles_nombre'        => 'Datafiles',
    'mon.proceso_datafiles_descripcion'   => 'Compuerta: o todos los archivos de datos están en línea, o no lo '
        . 'están. Un datafile fuera de línea deja inaccesible su parte de los datos aunque el resto de la '
        . 'instancia responda con normalidad.',
    'mon.proceso_datafiles_recomendacion' => 'Se recomienda no promediarla con nada: cerrada manda el índice de '
        . 'archivos a crítico sin discusión.',

    'mon.proceso_grupos_redo_nombre'        => 'Grupos de redo',
    'mon.proceso_grupos_redo_descripcion'   => 'Compuerta sobre los miembros inválidos de los grupos de redo. '
        . 'Con todos los grupos inutilizables la base se detiene, porque no puede rotar el registro.',
    'mon.proceso_grupos_redo_recomendacion' => 'Se recomienda mantener al menos dos miembros por grupo en '
        . 'discos distintos: la métrica mide validez, no redundancia.',

    'mon.proceso_tablespace_temporal_nombre'        => 'Tablespace temporal',
    'mon.proceso_tablespace_temporal_descripcion'   => 'Espacio de trabajo para lo que no cupo en PGA. Al '
        . 'agotarse, la consulta o el índice que se estaba construyendo falla con ORA-01652, pero no se pierden '
        . 'datos permanentes.',
    'mon.proceso_tablespace_temporal_recomendacion' => 'Se recomienda dimensionarlo a partir del pico observado '
        . 'y no del promedio: lo consume una sola consulta grande, no el uso diario.',

    'mon.proceso_tablespace_sin_autoextend_nombre'        => 'Tablespace sin autoextend',
    'mon.proceso_tablespace_sin_autoextend_descripcion'   => 'Cubre el punto ciego de M-ARC-01: con crecimiento '
        . 'automático activo, el porcentaje se mide contra el máximo alcanzable y se queda en óptimo por mucho '
        . 'que crezca el archivo. Sin autoextend, ese mismo porcentaje sí significa «qué tan lleno está».',
    'mon.proceso_tablespace_sin_autoextend_recomendacion' => 'Se recomienda tratarlo como el aviso temprano de '
        . 'los dos.',

    // Gráfico de memoria
    'mon.memoria_titulo'     => 'Memoria de la base de datos',
    'mon.memoria_pie'        => 'Se añade una lectura cada %s segundos · ventana de %s minutos',
    'mon.memoria_nota'       => 'Maqueta: la lectura que aparece cada medio minuto la genera este '
                              . 'navegador, no el agente de recolección. El eje horizontal es el tiempo '
                              . 'activo de la instancia, no la hora del reloj.',
    'mon.mem_de_total'       => 'de %s asignados',
    'mon.mem_normal'         => 'Bajo el umbral',
    'mon.mem_sobre_aceptacion' => 'Sobre el umbral de aceptación',
    'mon.mem_sobre_peligro'  => 'Sobre el umbral de peligro',
    'mon.mem_resumen'        => 'Memoria en uso: %s de %s asignados, un %s por ciento.',
    'mon.umbral_aceptacion'  => 'Aceptación %s %% · %s',
    'mon.umbral_peligro'     => 'Peligro %s %% · %s',
    'mon.eje_uptime'         => 'Tiempo activo de la instancia',
    'mon.uptime_min'         => '%s min',
    'mon.uptime_horas'       => '%s h %s min',

    'mon.vacio_titulo'       => 'Todavía no hay instancias vigiladas',
    'mon.vacio_texto'        => 'Cuando el agente de recolección deje su primera muestra, esta pantalla '
                              . 'mostrará el índice de salud de cada instancia, sus componentes y sus alertas.',

    // ── Perfil del usuario ───────────────────────────────────────────────────
    'perfil.titulo'             => 'Mi perfil',
    'perfil.ir'                 => 'Ver mi perfil',
    'perfil.id'                 => 'Identificador',
    'perfil.descripcion'        => 'Sobre mí',
    'perfil.descripcion_ayuda'  => 'Una nota breve sobre su rol o su especialidad. La ve solo usted.',
    'perfil.descripcion_marcador' => 'Ej.: Auditora de bases de datos, especializada en continuidad y respaldo.',
    'perfil.guardar_descripcion' => 'Guardar descripción',
    'perfil.subir_foto'         => 'Subir fotografía',
    'perfil.cambiar_foto'       => 'Cambiar fotografía',
    'perfil.subir_ahora'        => 'Subir ahora',
    'perfil.quitar_foto'        => 'Quitar la fotografía',
    'perfil.foto_ayuda'         => 'PNG, JPG, WEBP o GIF, hasta %s MB.',
    'perfil.calendario'         => 'Auditorías del mes',
    'perfil.mis_auditorias'     => 'Auditorías realizadas',
    'perfil.total_auditorias'   => 'Auditorías',
    'perfil.finalizadas'        => 'Finalizadas',
    'perfil.en_progreso'        => 'En progreso',
    // Sin concordancia de plural que romper: «1 auditorías en 1 días» era lo
    // que salía al poner la cifra delante del sustantivo.
    'perfil.mes_resumen'        => 'Auditorías del mes: %s · Días con trabajo: %s',
    'perfil.mes_sin_auditorias' => 'Sin auditorías este mes.',
    'perfil.y_mas'              => 'y %s más',
    'perfil.pagina_de'          => 'Página %s de %s',

    // ── Calendario ───────────────────────────────────────────────────────────
    // Doce claves y siete, y no strftime(): esa funcion esta obsoleta, e
    // IntlDateFormatter pide la extension intl, que esta imagen no compila.
    // Aburrido, pero funciona en cualquier PHP y se traduce como todo lo demas.
    'cal.mes_anterior'  => 'Mes anterior',
    'cal.mes_siguiente' => 'Mes siguiente',
    'cal.mes_1'  => 'Enero',
    'cal.mes_2'  => 'Febrero',
    'cal.mes_3'  => 'Marzo',
    'cal.mes_4'  => 'Abril',
    'cal.mes_5'  => 'Mayo',
    'cal.mes_6'  => 'Junio',
    'cal.mes_7'  => 'Julio',
    'cal.mes_8'  => 'Agosto',
    'cal.mes_9'  => 'Septiembre',
    'cal.mes_10' => 'Octubre',
    'cal.mes_11' => 'Noviembre',
    'cal.mes_12' => 'Diciembre',
    // La semana empieza en lunes, como en la region.
    'cal.dia_1' => 'Lu',
    'cal.dia_2' => 'Ma',
    'cal.dia_3' => 'Mi',
    'cal.dia_4' => 'Ju',
    'cal.dia_5' => 'Vi',
    'cal.dia_6' => 'Sa',
    'cal.dia_7' => 'Do',
];
