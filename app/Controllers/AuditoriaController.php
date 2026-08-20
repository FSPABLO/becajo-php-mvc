<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use App\Models\Entidades\Auditoria;
use App\Models\Entidades\Control;
use App\Models\Entidades\EvaluacionControl;
use App\Models\Entidades\EvidenciaDocumento;
use App\Models\Entidades\Usuario;

/**
 * El módulo de evaluación de riesgo: auditorías y respuestas del cuestionario.
 *
 * Todas las acciones empiezan exigiendo sesión, y las que tocan una auditoría
 * concreta pasan además por auditoriaPropia(), que comprueba que sea del
 * auditor conectado. Filtrar la LISTA por auditor no basta: la URL
 * /evaluacion/9 es adivinable, y sin esa comprobación cualquiera con sesión
 * abriría la auditoría de otra consultora.
 *
 * La validación vive aquí y no solo en la base a propósito. Las restricciones
 * CHECK del esquema son la última línea de defensa; si son la primera, el
 * auditor recibe un ORA-02290 en pantalla en vez de "la madurez va de 0 a 5".
 */
final class AuditoriaController extends Controlador
{
    /** Estados válidos de respuesta, según ck_evalctrl_estado. */
    private const ESTADOS = [
        EvaluacionControl::SI,
        EvaluacionControl::NO,
        EvaluacionControl::NO_APLICA,
    ];

    /** Criterios válidos, según ck_evalctrl_criterio. */
    private const CRITERIOS = ['DOCUMENTADO', 'REPETIBLE', 'EVIDENCIA'];

    /** Filas por página en la tabla del panel. */
    private const POR_PAGINA = 4;

    /** Calidad de la evidencia, según ck_evalctrl_calidad_evidencia. */
    private const CALIDADES = [
        EvaluacionControl::CALIDAD_BIEN_IMPLEMENTADO,
        EvaluacionControl::CALIDAD_REQUIERE_MEJORA,
        EvaluacionControl::CALIDAD_DECLARATIVO,
    ];

    // ── Panel ────────────────────────────────────────────────────────────────

    public function panel(): void
    {
        $usuario = $this->exigirUsuario();

        $auditorias = $this->auditorias()->auditoriasDe($usuario->id);

        // auditoriasDe() devuelve de la más reciente a la más antigua, así que
        // la primera es la última trabajada. Es la que se resume en el panel:
        // quien entra viene de ella o va hacia ella.
        $ultima = $auditorias[0] ?? null;

        /*
         * Las empresas que este auditor ha evaluado, sin repetir y en el orden
         * en que aparecen — es decir, la de la auditoría más reciente primero.
         * Salen de la lista que ya está en memoria: preguntarle otra vez a la
         * base por algo que se acaba de traer sería un viaje de más.
         */
        $organizaciones = [];

        foreach ($auditorias as $auditoria) {
            $organizaciones[$auditoria->organizacion] = true;
        }

        $organizaciones = array_keys($organizaciones);

        /*
         * Qué empresa mira el gráfico. El auditor la ESCRIBE, así que lo que
         * llega por la URL se resuelve contra las que este auditor auditó de
         * verdad y nunca se pasa tal cual al repositorio. Sin esa comprobación,
         * /evaluacion?organizacion=Otra sería una forma de preguntar por la
         * cartera de otra consultora — el mismo razonamiento que
         * auditoriaPropia() aplica a /evaluacion/9.
         *
         * Por defecto, la empresa de la última auditoría: es de donde viene
         * quien abre el panel.
         */
        $escrita = $this->peticion()->entrada('organizacion');

        $coincidencia = $escrita === null
            ? null
            : $this->buscarOrganizacion($escrita, $organizaciones);

        $organizacion = $coincidencia ?? ($organizaciones[0] ?? null);

        // ── Tabla: qué se busca, cómo se ordena y qué página se mira ─────────
        $buscar = $this->peticion()->entrada('buscar');

        // Solo dos órdenes, y cualquier otra cosa cae en el de por defecto: un
        // valor inventado en la URL no debe poder dejar la tabla sin ordenar.
        $orden = $this->peticion()->entrada('orden') === 'indice' ? 'indice' : 'reciente';

        $filtradas = $this->ordenarAuditorias($this->buscarAuditorias($auditorias, $buscar), $orden);

        $paginas = max(1, (int) ceil(count($filtradas) / self::POR_PAGINA));

        /*
         * La página se recorta al rango válido en vez de responder un error:
         * ?pagina=99 después de afinar la búsqueda es un accidente corriente
         * —el enlace queda en el historial—, no un intento de romper nada.
         */
        $pagina = max(1, min($paginas, (int) ($this->peticion()->entrada('pagina') ?? '1')));

        $this->verPanel('evaluacion/panel', [
            ...$this->contexto(),
            'meta'       => $this->meta('Mis auditorías'),
            // Ya no se pasa 'usuario': la cabecera dejó de imprimir el nombre y
            // la organización, y el marco los saca de $usuarioActual. Un dato
            // que ninguna vista lee es un dato que nadie mantiene.
            // La lista COMPLETA alimenta el resumen de la cabecera: las cifras
            // de la cartera no pueden encogerse porque alguien esté buscando.
            'auditorias' => $auditorias,
            'total'      => count($this->instrumento()->controles()),
            'ultima'     => $ultima,
            'organizaciones' => $organizaciones,
            'organizacion'   => $organizacion,
            /*
             * Si lo escrito no casó con ninguna empresa, la vista lo dice. El
             * aviso lo decide AQUÍ y no comparando cadenas allí: escribir
             * «cooperativa» encuentra «Cooperativa de Ejemplo R.L.», y una
             * comparación de textos lo tomaría por un fallo.
             */
            'organizacionEscrita'    => $escrita,
            'organizacionSinCoincidencia' => $escrita !== null && $coincidencia === null,
            'conexiones'     => $this->contenedor->conexiones(),

            'visibles'  => array_slice($filtradas, ($pagina - 1) * self::POR_PAGINA, self::POR_PAGINA),
            'encontradas' => count($filtradas),
            'buscar'    => $buscar,
            'orden'     => $orden,
            'pagina'    => $pagina,
            'paginas'   => $paginas,
            // La vista calcula con esto el «mostrando 1–8 de 23». Escrito a
            // mano allí, el recuento mentiría el día que cambie el tamaño.
            'porPagina' => self::POR_PAGINA,
            /*
             * Las dos consultas del tablero solo se hacen si hay algo que
             * dibujar. Sin auditorías no hay matriz ni evolución, y pedirlas
             * sería un viaje a la base para traer dos listas vacías.
             */
            'evaluacionesUltima' => $ultima === null
                ? []
                : $this->auditorias()->evaluaciones($ultima->id),
            'evolucion'          => $ultima === null
                ? []
                : $this->auditorias()->evolucionAuditor($usuario->id, $organizacion),
        ]);
    }

    /**
     * Busca la empresa que el auditor escribió, o null si no la hay.
     *
     * Devuelve null en vez de un valor por defecto a propósito: quien llama
     * decide qué hacer con el fallo, y así puede distinguir «no encontré nada»
     * de «encontré la de siempre». Mezclar las dos cosas aquí obligaría a la
     * vista a adivinarlo comparando textos.
     *
     * Se busca SIEMPRE dentro de las organizaciones que ese auditor evaluó de
     * verdad: el campo es texto libre y no puede convertirse en una forma de
     * preguntar por la cartera ajena.
     *
     * Se acepta el nombre a medias —«cooperativa» encuentra «Cooperativa de
     * Ejemplo R.L.»— porque nadie escribe la razón social completa. Pero solo
     * si señala a UNA: con dos candidatas no se adivina cuál, porque acertar la
     * mitad de las veces es peor que no elegir.
     *
     * @param list<string> $organizaciones
     */
    private function buscarOrganizacion(string $escrita, array $organizaciones): ?string
    {
        $buscado = $this->normalizar($escrita);

        foreach ($organizaciones as $organizacion) {
            if ($this->normalizar($organizacion) === $buscado) {
                return $organizacion;
            }
        }

        $parciales = array_values(array_filter(
            $organizaciones,
            fn (string $o): bool => str_contains($this->normalizar($o), $buscado),
        ));

        return count($parciales) === 1 ? $parciales[0] : null;
    }

    /**
     * Filtra la cartera por lo que el auditor escribió en el buscador.
     *
     * Busca en la organización Y en el área evaluada. Quien escribe «respaldos»
     * está buscando un alcance, no una empresa, y obligarle a saber en cuál de
     * los dos campos vive lo que recuerda es trasladarle la estructura de la
     * tabla.
     *
     * @param list<Auditoria> $auditorias
     * @return list<Auditoria>
     */
    private function buscarAuditorias(array $auditorias, ?string $termino): array
    {
        if ($termino === null) {
            return $auditorias;
        }

        $buscado = $this->normalizar($termino);

        return array_values(array_filter(
            $auditorias,
            fn (Auditoria $a): bool =>
                str_contains($this->normalizar($a->organizacion), $buscado)
                || str_contains($this->normalizar($a->areaEvaluada), $buscado),
        ));
    }

    /**
     * Ordena la cartera: por fecha o por índice de riesgo.
     *
     * @param list<Auditoria> $auditorias
     * @return list<Auditoria>
     */
    private function ordenarAuditorias(array $auditorias, string $orden): array
    {
        if ($orden === 'indice') {
            /*
             * Mayor índice primero, y las SIN CALCULAR al final — nunca
             * mezcladas con las de índice bajo. Un null no es un cero: una
             * auditoría sin calcular no es una auditoría de riesgo mínimo, y
             * ponerla arriba o abajo del todo por accidente cambia la lectura.
             */
            usort($auditorias, static function (Auditoria $a, Auditoria $b): int {
                $ia = $a->indiceGeneralRiesgo;
                $ib = $b->indiceGeneralRiesgo;

                if ($ia === null || $ib === null) {
                    return ($ia === null ? 1 : 0) <=> ($ib === null ? 1 : 0);
                }

                return $ib <=> $ia ?: $b->fecha <=> $a->fecha;
            });

            return $auditorias;
        }

        // Las últimas realizadas primero. auditoriasDe() ya las trae así, pero
        // se ordena igual: la vista no debe depender de un orden que se decide
        // en un ORDER BY a tres archivos de distancia.
        usort($auditorias, static fn (Auditoria $a, Auditoria $b): int =>
            $b->fecha <=> $a->fecha ?: $b->id <=> $a->id);

        return $auditorias;
    }

    /**
     * Pasa un texto a minúsculas y sin tildes, para comparar.
     *
     * Sin quitar las tildes, buscar «produccion» no encontraría «producción», y
     * es exactamente lo que se escribe con prisa. El mapa es explícito y no
     * iconv //TRANSLIT: ese depende de la configuración regional del servidor y
     * devuelve cosas distintas en la máquina de cada quien.
     */
    private function normalizar(string $texto): string
    {
        return strtr(mb_strtolower(trim($texto), 'UTF-8'), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n', 'ç' => 'c',
        ]);
    }

    // ── Alta de auditoría ────────────────────────────────────────────────────

    public function nuevaFormulario(): void
    {
        $this->exigirUsuario();

        $this->verPanel('evaluacion/nueva', [
            ...$this->contexto(),
            'meta'           => $this->meta('Nueva auditoría'),
            'administradores' => $this->auditorias()->usuariosPorRol(Usuario::ROL_ADMIN_BD),
            'errores'        => $this->erroresGuardados(),
            'valores'        => $this->valoresGuardados(),
        ]);
    }

    public function crear(): void
    {
        $usuario = $this->exigirUsuario();
        $this->exigirToken('/evaluacion/nueva');

        $datos = $this->leerEncabezado();
        $errores = $this->validarEncabezado($datos);

        if ($errores !== []) {
            $this->guardarIntento($errores, $datos);
            $this->redirigir('/evaluacion/nueva');
        }

        $id = $this->auditorias()->crearAuditoria(
            idAuditor:         $usuario->id,
            idAdministradorBd: (int) $datos['administrador'],
            areaEvaluada:      $datos['area'],
            fecha:             $datos['fecha'],
        );

        $this->sesion()->destello('aviso', 'Auditoría creada. Ya puede evaluar controles.');
        $this->redirigir('/evaluacion/' . $id);
    }

    // ── Detalle y encabezado ─────────────────────────────────────────────────

    public function mostrar(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();

        $evaluaciones = $this->auditorias()->evaluaciones($auditoria->id);

        // Se indexan por código para que la vista resuelva "¿qué respondí en
        // C-042?" sin recorrer la lista entera por cada uno de los 75 controles.
        $porCodigo = [];

        foreach ($evaluaciones as $evaluacion) {
            $porCodigo[$evaluacion->codigoControl] = $evaluacion;
        }

        $controles = $this->instrumento()->controles();

        $this->verPanel('evaluacion/mostrar', [
            ...$this->contexto(),
            'meta'         => $this->meta('Auditoría ' . $auditoria->id),
            'auditoria'    => $auditoria,
            'controles'    => $controles,
            'procesos'     => $this->indexarProcesos(),
            'evaluaciones' => $porCodigo,
            'evaluados'    => $this->auditorias()->controlesEvaluados($auditoria->id),
            'total'        => count($controles),
            'administradores' => $this->auditorias()->usuariosPorRol(Usuario::ROL_ADMIN_BD),
            'errores'      => $this->erroresGuardados(),
        ]);
    }

    public function actualizar(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $this->exigirToken('/evaluacion/' . $auditoria->id);
        $this->exigirAbierta($auditoria);

        $datos = $this->leerEncabezado();
        $errores = $this->validarEncabezado($datos);

        if ($errores !== []) {
            $this->guardarIntento($errores, $datos);
            $this->redirigir('/evaluacion/' . $auditoria->id);
        }

        $this->auditorias()->actualizarAuditoria(
            id:                $auditoria->id,
            idAdministradorBd: (int) $datos['administrador'],
            areaEvaluada:      $datos['area'],
            fecha:             $datos['fecha'],
        );

        $this->sesion()->destello('aviso', 'Encabezado actualizado.');
        $this->redirigir('/evaluacion/' . $auditoria->id);
    }

    // ── Plantilla de un control ──────────────────────────────────────────────

   public function plantillaControl(): void
       {
           $this->exigirUsuario();
           $auditoria = $this->auditoriaPropia();
           $control = $this->controlDelCatalogo();
           $evaluacion = $this->auditorias()->evaluacion($auditoria->id, $control->id);

           $this->verPanel('evaluacion/control', [
               ...$this->contexto(),
               'meta'       => $this->meta($control->id . ' · Auditoría ' . $auditoria->id),
               'auditoria'  => $auditoria,
               'control'    => $control,
               'proceso'    => $this->indexarProcesos()[$control->proceso] ?? null,
               'evaluacion' => $evaluacion,
               'escala'     => $this->instrumento()->escala(),
               'estados'    => self::ESTADOS,
               'criterios'  => self::CRITERIOS,
               'errores'    => $this->erroresGuardados(),
               'vecinos'    => $this->vecinos($control),
               'evidencias'            => $evaluacion !== null && $evaluacion->id !== 0
                   ? $this->auditorias()->evidenciasDeControl($evaluacion->id)
                   : [],
               'evidenciasDisponibles' => $this->auditorias()->evidenciasDeAuditoria($auditoria->id),
               'formatosEvidencia'     => EvidenciaDocumento::FORMATOS,
           ]);
       }

    public function guardarControl(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $control = $this->controlDelCatalogo();

        $destino = '/evaluacion/' . $auditoria->id . '/controles/' . $control->id;

        $this->exigirToken($destino);
        $this->exigirAbierta($auditoria);

        $resultado = $this->validarRespuesta($this->leerRespuesta());

        if ($resultado['errores'] !== []) {
            $this->guardarIntento($resultado['errores'], []);
            $this->redirigir($destino);
        }

        $datos = $resultado['datos'];

        $this->auditorias()->guardarEvaluacion(new EvaluacionControl(
            idAuditoria:            $auditoria->id,
            codigoControl:          $control->id,
            estado:                 $datos['estado'],
            madurez:                $datos['madurez'],
            criterio:               $datos['criterio'],
            afectaConfidencialidad: $datos['confidencialidad'],
            afectaIntegridad:       $datos['integridad'],
            afectaDisponibilidad:   $datos['disponibilidad'],
            impacto:                $datos['impacto'],
            probabilidad:           $datos['probabilidad'],
            hallazgo:               $datos['hallazgo'],
            recomendacion:          $datos['recomendacion'],
            preguntaPersonalizada:  $datos['pregunta'],
            evidenciaVerificada:    $datos['evidencia'],
            calidadEvidencia:       $datos['calidad'],
        ));

        // Se recalcula en cada guardado, no solo al finalizar: el auditor puede
        // consultar los indicadores de una auditoría a medias, y verlos
        // desactualizados sería peor que no verlos.
        $this->auditorias()->recalcularRiesgo($auditoria->id);

        $this->registrarBitacora('control.guardar', 'control', $control->id, 'estado=' . $datos['estado']);

        $this->sesion()->destello('aviso', 'Control ' . $control->id . ' guardado.');

        // "Guardar y siguiente" encadena los 75 controles sin volver al índice.
        $siguiente = $this->vecinos($control)['siguiente'] ?? null;

        if ($this->peticion()->entrada('siguiente') !== null && $siguiente !== null) {
            $this->redirigir('/evaluacion/' . $auditoria->id . '/controles/' . $siguiente->id);
        }

        $this->redirigir($destino);
    }

 // ── Evidencia de respaldo ─────────────────────────────────────────────────

    public function agregarEvidencia(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $control = $this->controlDelCatalogo();
        $destino = '/evaluacion/' . $auditoria->id . '/controles/' . $control->id;

        // construcción de rutas siguen necesitando la URL sin el ancla. (Cuando conectemos bd online)
        $destinoConAncla = $destino . '#documentos-respaldo';

        $this->exigirToken($destino);
        $this->exigirAbierta($auditoria);

        $evaluacion = $this->auditorias()->evaluacion($auditoria->id, $control->id);

        if ($evaluacion === null || $evaluacion->id === 0) {
            $this->sesion()->destello(
                'error',
                'Guarde primero la calificación del control antes de adjuntar evidencia.',
            );
            $this->redirigir($destinoConAncla);
        }

        $nombre      = trim((string) $this->peticion()->entrada('nombre_documento', ''));
        $formato     = (string) $this->peticion()->entrada('formato', '');
        $version     = trim((string) $this->peticion()->entrada('version', ''));
        $responsable = trim((string) $this->peticion()->entrada('responsable', ''));
        $fecha       = (string) $this->peticion()->entrada('fecha_documento', '');

        $errores = [];

        if ($nombre === '') {
            $errores['nombre_documento'] = 'Escriba el nombre del documento.';
        }

        if (!in_array($formato, EvidenciaDocumento::FORMATOS, true)) {
            $errores['formato'] = 'Elija un formato de la lista.';
        }

        if ($version === '') {
            $errores['version'] = 'Indique la versión, por ejemplo v1.';
        }

        if ($responsable === '') {
            $errores['responsable'] = 'Indique quién elaboró el documento.';
        }

        if ($fecha === '' || \DateTime::createFromFormat('Y-m-d', $fecha) === false) {
            $errores['fecha_documento'] = 'Indique una fecha válida.';
        }

        if ($errores !== []) {
            $this->guardarIntento($errores, []);
            $this->redirigir($destinoConAncla);
        }

        // Aviso de nombre parecido: no bloquea la carga (el auditor puede
        // tener una buena razón para dos documentos con nombres similares),
        // solo lo alerta por si en realidad quería usar "Vincular documento
        // ya cargado" en vez de crear uno nuevo por accidente.
        $nombreParecido = null;

        foreach ($this->auditorias()->evidenciasDeAuditoria($auditoria->id) as $existente) {
            similar_text($this->normalizar($existente->nombreDocumento), $this->normalizar($nombre), $porcentaje);

            if ($porcentaje >= 85.0) {
                $nombreParecido = $existente->nombreDocumento;
                break;
            }
        }

        $this->auditorias()->agregarEvidencia(
            $auditoria->id,
            $evaluacion->id,
            $nombre,
            $formato,
            $version,
            $responsable,
            $fecha,
        );

        if ($nombreParecido !== null) {
            $this->sesion()->destello(
                'aviso',
                'Documento agregado. Ya existe uno con nombre parecido en esta auditoría, "'
                . $nombreParecido . '". Si es el mismo, la próxima vez puede usar '
                . '"Vincular documento ya cargado" en vez de crear uno nuevo.',
            );
        } else {
            $this->sesion()->destello('aviso', 'Documento agregado.');
        }

        $this->registrarBitacora('evidencia.agregar', 'evaluacion_control', (string) $evaluacion->id, $nombre);

        $this->redirigir($destinoConAncla);
    }

    public function vincularEvidenciaExistente(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $control = $this->controlDelCatalogo();
        $destino = '/evaluacion/' . $auditoria->id . '/controles/' . $control->id;
        $destinoConAncla = $destino . '#documentos-respaldo';

        $this->exigirToken($destino);
        $this->exigirAbierta($auditoria);

        $evaluacion = $this->auditorias()->evaluacion($auditoria->id, $control->id);

        if ($evaluacion === null || $evaluacion->id === 0) {
            $this->sesion()->destello(
                'error',
                'Guarde primero la calificación del control antes de adjuntar evidencia.',
            );
            $this->redirigir($destinoConAncla);
        }

        $idEvidencia = (int) $this->peticion()->entrada('id_evidencia', '0');

        if ($idEvidencia <= 0) {
            $this->sesion()->destello('error', 'Elija un documento de la lista.');
            $this->redirigir($destinoConAncla);
        }

        $this->auditorias()->vincularEvidencia($idEvidencia, $evaluacion->id);
        $this->registrarBitacora('evidencia.vincular', 'evaluacion_control', (string) $evaluacion->id, 'id_evidencia=' . $idEvidencia);

        $this->sesion()->destello('aviso', 'Documento vinculado.');
        $this->redirigir($destinoConAncla);
    }

    public function quitarEvidencia(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $control = $this->controlDelCatalogo();
        $destino = '/evaluacion/' . $auditoria->id . '/controles/' . $control->id;
        $destinoConAncla = $destino . '#documentos-respaldo';

        $this->exigirToken($destino);
        $this->exigirAbierta($auditoria);

        $evaluacion = $this->auditorias()->evaluacion($auditoria->id, $control->id);
        $idEvidencia = (int) $this->parametro('idEvidencia', '0');

        if ($evaluacion !== null && $idEvidencia > 0) {
            $this->auditorias()->desvincularEvidencia($idEvidencia, $evaluacion->id);
            $this->registrarBitacora('evidencia.quitar', 'evaluacion_control', (string) $evaluacion->id, 'id_evidencia=' . $idEvidencia);

            $this->sesion()->destello('aviso', 'Documento desvinculado de este control.');
        }

        $this->redirigir($destinoConAncla);
    }

    /**
     * Registra una acción en la bitácora del sistema, usando al usuario de la sesión
     * actual. Punto único para no repetir la lógica de obtener el usuario y la hora en cada acción que se quiera registrar.
     */
    // ── Cierre y resultados ──────────────────────────────────────────────────

    public function finalizar(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $this->exigirToken('/evaluacion/' . $auditoria->id);

        if ($this->auditorias()->controlesEvaluados($auditoria->id) === 0) {
            $this->sesion()->destello('error', 'No se puede finalizar una auditoría sin ningún control evaluado.');
            $this->redirigir('/evaluacion/' . $auditoria->id);
        }

        $this->auditorias()->recalcularRiesgo($auditoria->id);
        $this->auditorias()->finalizarAuditoria($auditoria->id);

        $this->sesion()->destello('aviso', 'Auditoría finalizada.');
        $this->redirigir('/evaluacion/' . $auditoria->id . '/resultados');
    }

    public function reabrir(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $this->exigirToken('/evaluacion/' . $auditoria->id);

        $this->auditorias()->reabrirAuditoria($auditoria->id);

        $this->sesion()->destello('aviso', 'Auditoría reabierta para continuar la evaluación.');
        $this->redirigir('/evaluacion/' . $auditoria->id);
    }

    public function resultados(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $repositorio = $this->auditorias();

        $this->verPanel('evaluacion/resultados', [
            ...$this->contexto(),
            'meta'        => $this->meta('Resultados · Auditoría ' . $auditoria->id),
            'auditoria'   => $auditoria,
            'resumen'     => $repositorio->resumen($auditoria->id),
            'dominios'    => $repositorio->cumplimientoPorDominio($auditoria->id),
            'exposicion'  => $repositorio->exposicionRiesgo($auditoria->id),
            'menorMadurez' => $repositorio->menorMadurez($auditoria->id, 5),
            'mayorRiesgo' => $repositorio->mayorRiesgo($auditoria->id, 5),
            'evaluaciones' => $repositorio->evaluaciones($auditoria->id),
        ]);
    }


    public function evidencias(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $repositorio = $this->auditorias();
        $catalogoPorCodigo = [];

        foreach ($this->instrumento()->controles() as $control) {
            $catalogoPorCodigo[$control->id] = $control;
        }

        $controlesSinEvidencia = [];

        foreach ($repositorio->controlesSinEvidencia($auditoria->id) as $codigo) {
            if (isset($catalogoPorCodigo[$codigo])) {
                $controlesSinEvidencia[] = $catalogoPorCodigo[$codigo];
            }
        }

        $documentos = $repositorio->evidenciasConControlesDeAuditoria($auditoria->id);

        $this->verPanel('evaluacion/evidencias', [
            ...$this->contexto(),
            'meta'                  => $this->meta('Evidencia · Auditoría ' . $auditoria->id),
            'auditoria'             => $auditoria,
            'documentos'            => $documentos,
            'controlesSinEvidencia' => $controlesSinEvidencia,
            'totalDocumentos'       => count($documentos),
            'totalConEvidencia'     => count(array_unique(
                array_merge(...array_map(
                    static fn (\App\Models\Entidades\EvidenciaDocumento $d): array =>
                        $d->controlesVinculados === '' ? [] : explode(', ', $d->controlesVinculados),
                    $documentos,
                )),
            )),
        ]);
    }

    // Reporte ejecutivo para imprimir o guardar como PDF desde el navegador.
    public function reporte(): void
    {
        $usuario = $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $repositorio = $this->auditorias();

        $this->ver('evaluacion/reporte', [
            'empresa'     => $this->repositorio()->empresa(),
            'meta'        => $this->meta('Reporte ejecutivo · Auditoría ' . $auditoria->id),
            'usuario'     => $usuario,
            'auditoria'   => $auditoria,
            'resumen'     => $repositorio->resumen($auditoria->id),
            'dominios'    => $repositorio->cumplimientoPorDominio($auditoria->id),
            'exposicion'  => $repositorio->exposicionRiesgo($auditoria->id),
            'menorMadurez' => $repositorio->menorMadurez($auditoria->id, 5),
            'mayorRiesgo' => $repositorio->mayorRiesgo($auditoria->id, 5),
            'evaluaciones' => $repositorio->evaluaciones($auditoria->id),
        ], 'imprimir');
    }

    // ── Remediación y re-auditoría ─────────────────────────────────

    /** Panel de plazos de corrección de una auditoría. */
    public function remediaciones(): void
    {
        $usuario = $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();

        $this->verPanel('evaluacion/remediaciones', [
            ...$this->contexto(),
            'meta'                  => $this->meta('Remediaciones · Auditoría ' . $auditoria->id),
            'usuario'               => $usuario,
            'auditoria'             => $auditoria,
            'remediaciones'         => $this->auditorias()->remediacionesAuditoria($auditoria->id),
            'controlesElegibles'    => $this->controlesConHallazgo($auditoria->id),
            'auditoriasSeguimiento' => $this->auditoriasSeguimientoDisponibles($usuario->id, $auditoria->id),
        ]);
    }

    /**
     * Crea el plazo de corrección de UN hallazgo puntual.
     *
     * Cuelga de la evaluación del control, no de la auditoría completa: por
     * eso necesita el código del control además del id de la auditoría, igual
     * que guardarControl().
     */
    public function crearRemediacion(): void
    {
        $this->exigirUsuario();
        $auditoria = $this->auditoriaPropia();
        $destino = '/evaluacion/' . $auditoria->id . '/remediaciones';

        $this->exigirToken($destino);

        // No se usa controlDelCatalogo() aquí a propósito: esa función responde
        // un 404 duro cuando el código no existe, y eso sacaba al auditor de la
        // pantalla de remediaciones. Un código inválido en esta acción es un
        // error de formulario, no una URL rota, así que se valida a mano y se
        // vuelve al mismo destino con el aviso puesto.
        $codigo = (string) $this->parametro('codigo', '');
        $control = null;

        foreach ($this->instrumento()->controles() as $candidato) {
            if ($candidato->id === $codigo) {
                $control = $candidato;
                break;
            }
        }

        if ($control === null) {
            $this->sesion()->destello('error', 'Ese código de control no existe en el catálogo.');
            $this->redirigir($destino);
        }

        $evaluacion = $this->auditorias()->evaluacion($auditoria->id, $control->id);

        if ($evaluacion === null || $evaluacion->id === 0) {
            $this->sesion()->destello('error', 'Ese control todavía no tiene una evaluación guardada.');
            $this->redirigir($destino);
        }

        $fechaLimite = (string) $this->peticion()->entrada('fecha_limite', '');
        $responsable = $this->peticion()->entrada('responsable');

        if ($fechaLimite === '' || !$this->fechaValida($fechaLimite)) {
            $this->sesion()->destello('error', 'Indique una fecha límite válida (AAAA-MM-DD).');
            $this->redirigir($destino);
        }

        $this->auditorias()->crearRemediacion($evaluacion->id, $fechaLimite, $responsable);

        $this->sesion()->destello('aviso', 'Plazo de corrección creado para ' . $control->id . '.');
        $this->redirigir($destino);
    }

    /** Enlaza una remediación con la auditoría de seguimiento ya creada. */
    public function programarReauditoria(): void
    {
        $usuario = $this->exigirUsuario();

        $idRemediacion = (int) $this->parametro('idRemediacion', '0');
        $idAuditoriaReauditoria = (int) $this->peticion()->entrada('id_auditoria_reauditoria', '0');

        // 'volver' viaja en el propio formulario (ver remediaciones.php): es la
        // pantalla de remediaciones desde la que se disparó la acción. Se usa
        // para el token y para todo redirect, así un id de auditoría inválido
        // o ajeno deja al auditor donde estaba, en vez de un 404 que lo saca
        // de la vista actual.
        $volver = (string) $this->peticion()->entrada('volver', '/evaluacion');

        $this->exigirToken($volver);

        $seguimiento = $this->auditorias()->auditoria($idAuditoriaReauditoria);

        if ($seguimiento === null || $seguimiento->idAuditor !== $usuario->id) {
            $this->sesion()->destello('error', 'Esa auditoría de seguimiento no existe o no le pertenece.');
            $this->redirigir($volver);
        }

        $this->auditorias()->programarReauditoria($idRemediacion, $idAuditoriaReauditoria);

        $this->sesion()->destello('aviso', 'Re-auditoría programada con la auditoría ' . $seguimiento->id . '.');
        $this->redirigir($volver);
    }

    /** Marca una remediación como cumplida, en proceso o pendiente a mano. */
    public function actualizarEstadoRemediacion(): void
    {
        $this->exigirUsuario();

        $idRemediacion = (int) $this->parametro('idRemediacion', '0');
        $estado = (string) $this->peticion()->entrada('estado', '');
        $destino = (string) $this->peticion()->entrada('volver', '/evaluacion');

        $this->exigirToken($destino);

        if (!in_array($estado, ['PENDIENTE', 'EN_PROCESO', 'CUMPLIDO'], true)) {
            $this->sesion()->destello('error', 'Estado de remediación no válido.');
            $this->redirigir($destino);
        }

        $this->auditorias()->actualizarEstadoRemediacion($idRemediacion, $estado);

        $this->sesion()->destello('aviso', 'Estado de la remediación actualizado.');
        $this->redirigir($destino);
    }

    /** Panel global (todas las organizaciones) de remediaciones vencidas. */
    public function remediacionesVencidas(): void
    {
        $usuario = $this->exigirAdministrador();

        $this->verPanel('evaluacion/remediaciones-vencidas', [
            ...$this->contexto(),
            'meta'          => $this->meta('Remediaciones vencidas'),
            'usuario'       => $usuario,
            'remediaciones' => $this->auditorias()->remediacionesVencidas(),
        ]);
    }

    /**
     * Bitácora del sistema
     * Restringida a Administrador de BD: quién hizo qué es información
     * sensible.
     */
    public function bitacora(): void
    {
        $usuario = $this->exigirAdministrador();

        $this->verPanel('evaluacion/bitacora', [
            ...$this->contexto(),
            'meta'     => $this->meta('Bitácora del sistema'),
            'usuario'  => $usuario,
            'registros' => $this->auditorias()->bitacora(200),
        ]);
    }

    // Compara el histórico de auditorías del auditor, agrupado por organización.
    public function comparar(): void
    {
        $usuario = $this->exigirUsuario();
        $auditorias = $this->auditorias()->auditoriasDe($usuario->id);

        $porOrganizacion = [];

        foreach ($auditorias as $auditoria) {
            $porOrganizacion[$auditoria->organizacion][] = $auditoria;
        }

        // Más antigua primero, así la tendencia se lee de izquierda a derecha.
        foreach ($porOrganizacion as &$grupo) {
            usort($grupo, static fn ($a, $b) => $a->fecha <=> $b->fecha);
        }
        unset($grupo);

        // Punto 18: madurez ponderada por dominio, a través del tiempo, para
        // cada organización que este auditor ya evaluó. Complementa las
        // barras de índice general que ya se mostraban con el desglose por
        // dominio, para ver en qué áreas mejoró o empeoró cada auditoría.
        //
        // El procedimiento agrega por ORGANIZACIÓN y no sabe de auditores: si
        // dos consultoras auditaron a la misma empresa, devuelve las dos
        // carteras mezcladas. Aquí se recorta a las auditorías propias, que es
        // el mismo criterio de auditoriaPropia() frente a /evaluacion/9 —
        // filtrar la lista por auditor no basta si el desglose de al lado
        // sigue enseñando el trabajo de otro. Además la pantalla se
        // contradecía sola: la cabecera fechaba la última auditoría del
        // auditor y la tabla llegaba hasta la de otra consultora.
        $historicoPorOrganizacion = [];

        foreach ($porOrganizacion as $organizacion => $grupo) {
            $propias = array_flip(array_map(
                static fn (Auditoria $auditoria): int => $auditoria->id,
                $grupo,
            ));

            $historicoPorOrganizacion[$organizacion] = array_values(array_filter(
                $this->auditorias()->historicoPorDominio($organizacion),
                static fn (array $fila): bool => isset($propias[(int) $fila['id_auditoria']]),
            ));
        }

        $this->verPanel('evaluacion/comparar', [
            ...$this->contexto(),
            'meta'                     => $this->meta('Comparación histórica'),
            'usuario'                  => $usuario,
            'porOrganizacion'          => $porOrganizacion,
            'historicoPorOrganizacion' => $historicoPorOrganizacion,
        ]);
    }

    // ── Lectura del formulario ───────────────────────────────────────────────

    /** @return array{administrador: string, area: string, fecha: string} */
    private function leerEncabezado(): array
    {
        $peticion = $this->peticion();

        return [
            'administrador' => (string) $peticion->entrada('administrador', ''),
            'area'          => (string) $peticion->entrada('area', ''),
            'fecha'         => (string) $peticion->entrada('fecha', ''),
        ];
    }

    /** @return array<string, mixed> */
    private function leerRespuesta(): array
    {
        $peticion = $this->peticion();

        return [
            'estado'           => $peticion->entrada('estado'),
            'madurez'          => $peticion->entrada('madurez'),
            'criterio'         => $peticion->entrada('criterio'),
            'confidencialidad' => $peticion->marcada('confidencialidad'),
            'integridad'       => $peticion->marcada('integridad'),
            'disponibilidad'   => $peticion->marcada('disponibilidad'),
            'impacto'          => $peticion->entrada('impacto'),
            'probabilidad'     => $peticion->entrada('probabilidad'),
            'hallazgo'         => $peticion->entrada('hallazgo'),
            'recomendacion'    => $peticion->entrada('recomendacion'),
            'pregunta'         => $peticion->entrada('pregunta'),
            'evidencia'        => $peticion->entrada('evidencia'),
            'calidad'          => $peticion->entrada('calidad'),
        ];
    }

    // ── Validación ───────────────────────────────────────────────────────────

    /**
     * @param array{administrador: string, area: string, fecha: string} $datos
     * @return array<string, string>
     */
    private function validarEncabezado(array $datos): array
    {
        $errores = [];

        $administrador = $this->auditorias()->usuario((int) $datos['administrador']);

        if ($datos['administrador'] === '' || $administrador === null) {
            $errores['administrador'] = 'Seleccione el administrador de base de datos entrevistado.';
        } elseif (!$administrador->esAdministrador()) {
            // Que el desplegable solo ofrezca ADMIN_BD no impide enviar otro id
            // a mano: la comprobación tiene que estar en el servidor.
            $errores['administrador'] = 'La persona seleccionada no tiene perfil de administrador de base de datos.';
        }

        if (trim($datos['area']) === '') {
            $errores['area'] = 'El área evaluada es obligatoria.';
        } elseif (mb_strlen($datos['area']) > 200) {
            $errores['area'] = 'El área evaluada no puede pasar de 200 caracteres.';
        }

        if ($datos['fecha'] === '') {
            $errores['fecha'] = 'La fecha de la auditoría es obligatoria.';
        } elseif (!$this->fechaValida($datos['fecha'])) {
            $errores['fecha'] = 'La fecha debe tener el formato AAAA-MM-DD y existir en el calendario.';
        }

        return $errores;
    }

    /**
     * Valida la respuesta y, si es correcta, la devuelve ya convertida.
     *
     * Devuelve las dos cosas juntas porque la conversión depende de la
     * validación: 'madurez' => '0' es un cero legítimo, pero 'madurez' => ''
     * es un campo vacío, y solo tras comprobar el rango se sabe cuál es cuál.
     * Convertir antes de validar perdería esa diferencia.
     *
     * @param array<string, mixed> $datos
     * @return array{errores: array<string, string>, datos: array<string, mixed>}
     */
    private function validarRespuesta(array $datos): array
    {
        $errores = [];
        $estado = $datos['estado'];

        if ($estado !== null && !in_array($estado, self::ESTADOS, true)) {
            $errores['estado'] = 'La respuesta debe ser Sí, No o No aplica.';
        }

        $madurez = $this->enteroEnRango($datos['madurez'], 0, 5);

        if ($datos['madurez'] !== null && $madurez === null) {
            $errores['madurez'] = 'El nivel de madurez va de 0 a 5.';
        }

        // Un control respondido Sí o No tiene que llevar madurez: es la nota
        // que alimenta todos los indicadores. "No aplica" queda exento porque
        // no se evalúa nada que graduar.
        //
        // Solo si no hay ya un error de madurez: quien envió un 9 necesita
        // leer "va de 0 a 5", no "indique la madurez", que sugiere que la dejó
        // en blanco cuando en realidad la puso mal.
        if (!isset($errores['madurez'])
            && in_array($estado, [EvaluacionControl::SI, EvaluacionControl::NO], true)
            && $madurez === null
        ) {
            $errores['madurez'] = 'Indique el nivel de madurez observado (0 a 5).';
        }

        if ($datos['criterio'] !== null && !in_array($datos['criterio'], self::CRITERIOS, true)) {
            $errores['criterio'] = 'El criterio indicado no es válido.';
        }

        // ISO-IEC 27007: la conformidad se determina contra
        // evidencia verificable, no contra la afirmación del auditado. Un
        // "Sí" sin evidencia ni clasificación de calidad no se puede guardar
        // — mismo espíritu que ck_evalctrl_evidencia_si en la base de datos,
        // pero comprobado aquí primero para dar un mensaje claro en el
        // campo exacto, en vez de que el auditor reciba un ORA-02290.
        if ($estado === EvaluacionControl::SI) {
            if (trim((string) ($datos['evidencia'] ?? '')) === '') {
                $errores['evidencia'] = 'Si la respuesta es "Sí", debe describir la evidencia revisada.';
            }

            if ($datos['calidad'] === null || !in_array($datos['calidad'], self::CALIDADES, true)) {
                $errores['calidad'] = 'Indique si la evidencia está bien implementada, requiere mejora o es solo declarativa.';
            }
        }

        foreach (['impacto' => 'El impacto', 'probabilidad' => 'La probabilidad'] as $campo => $etiqueta) {
            if ($datos[$campo] !== null && $this->enteroEnRango($datos[$campo], 1, 5) === null) {
                $errores[$campo] = $etiqueta . ' va de 1 a 5.';
            }
        }

        // El nivel de riesgo es el promedio de ambos, así que uno solo no
        // permite calcularlo y quedaría a medias en la base.
        $tieneImpacto = $datos['impacto'] !== null;
        $tieneProbabilidad = $datos['probabilidad'] !== null;

        if ($tieneImpacto !== $tieneProbabilidad) {
            $errores['impacto'] = 'Indique impacto y probabilidad juntos, o ninguno de los dos.';
        }

        if ($errores !== []) {
            return ['errores' => $errores, 'datos' => []];
        }

        $datos['madurez'] = $madurez;
        $datos['impacto'] = $this->enteroEnRango($datos['impacto'], 1, 5);
        $datos['probabilidad'] = $this->enteroEnRango($datos['probabilidad'], 1, 5);

        return ['errores' => [], 'datos' => $datos];
    }

    private function fechaValida(string $fecha): bool
    {
        $partes = date_parse_from_format('Y-m-d', $fecha);

        return $partes['error_count'] === 0
            && $partes['warning_count'] === 0
            && checkdate((int) $partes['month'], (int) $partes['day'], (int) $partes['year']);
    }

    private function enteroEnRango(?string $valor, int $minimo, int $maximo): ?int
    {
        if ($valor === null || !ctype_digit($valor)) {
            return null;
        }

        $entero = (int) $valor;

        return ($entero >= $minimo && $entero <= $maximo) ? $entero : null;
    }

    // ── Apoyo ────────────────────────────────────────────────────────────────

    /**
     * La auditoría de la URL, siempre que sea del auditor conectado.
     *
     * Se responde 404 y no 403 cuando es de otro: un 403 confirmaría que esa
     * auditoría existe, que es justo lo que no hace falta que sepa.
     */
    private function auditoriaPropia(): Auditoria
    {
        $usuario = $this->autenticacion()->usuario();
        $id = (int) $this->parametro('id', '0');
        $auditoria = $id > 0 ? $this->auditorias()->auditoria($id) : null;

        if ($auditoria === null || $auditoria->idAuditor !== $usuario?->id) {
            $this->noEncontrado();
        }

        return $auditoria;
    }

    private function controlDelCatalogo(): Control
    {
        $codigo = (string) $this->parametro('codigo', '');

        foreach ($this->instrumento()->controles() as $control) {
            if ($control->id === $codigo) {
                return $control;
            }
        }

        $this->noEncontrado();
    }

    /**
     * Una auditoría finalizada no se edita: primero hay que reabrirla.
     *
     * Sin esto, los resultados presentados al cliente podrían cambiar después
     * de entregados sin que quede rastro.
     */
    private function exigirAbierta(Auditoria $auditoria): void
    {
        if ($auditoria->estaFinalizada()) {
            $this->sesion()->destello('error', 'La auditoría está finalizada. Reábrala para modificarla.');
            $this->redirigir('/evaluacion/' . $auditoria->id);
        }
    }

    private function exigirToken(string $destino): void
    {
        if (!$this->autenticacion()->tokenValido($this->peticion()->entrada('_token'))) {
            $this->sesion()->destello('error', 'La sesión expiró. Intente de nuevo.');
            $this->redirigir($destino);
        }
    }

    /** @return array<int, \App\Models\Entidades\Proceso> */
    private function indexarProcesos(): array
    {
        $indice = [];

        foreach ($this->instrumento()->procesos() as $proceso) {
            $indice[$proceso->numero] = $proceso;
        }

        return $indice;
    }

    /**
     * Controles de esta auditoría con un hallazgo ("No") ya evaluado — los
     * únicos que tiene sentido remediar. Alimenta el desplegable de "Código
     * del control" en la pantalla de remediaciones, para que solo se pueda
     * elegir un código que de verdad existe y de verdad tiene algo que
     * corregir, en vez de escribirlo a mano.
     *
     * @return list<Control>
     */
    private function controlesConHallazgo(int $idAuditoria): array
    {
        $codigosConHallazgo = [];

        foreach ($this->auditorias()->evaluaciones($idAuditoria) as $evaluacion) {
            if ($evaluacion->estado === EvaluacionControl::NO) {
                $codigosConHallazgo[$evaluacion->codigoControl] = true;
            }
        }

        return array_values(array_filter(
            $this->instrumento()->controles(),
            static fn (Control $control): bool => isset($codigosConHallazgo[$control->id]),
        ));
    }

    /**
     * Las demás auditorías propias del auditor, candidatas a servir de
     * seguimiento de una remediación. Alimenta el desplegable de "auditoría
     * de seguimiento", para no depender de que el auditor copie un id a mano.
     *
     * @return list<Auditoria>
     */
    private function auditoriasSeguimientoDisponibles(int $idAuditor, int $idAuditoriaActual): array
    {
        return array_values(array_filter(
            $this->auditorias()->auditoriasDe($idAuditor),
            static fn (Auditoria $candidata): bool => $candidata->id !== $idAuditoriaActual,
        ));
    }

    /** @return array{anterior: Control|null, siguiente: Control|null} */
    private function vecinos(Control $control): array
    {
        $controles = $this->instrumento()->controles();
        $posicion = null;

        foreach ($controles as $i => $candidato) {
            if ($candidato->id === $control->id) {
                $posicion = $i;
                break;
            }
        }

        return [
            'anterior'  => $posicion !== null ? ($controles[$posicion - 1] ?? null) : null,
            'siguiente' => $posicion !== null ? ($controles[$posicion + 1] ?? null) : null,
        ];
    }

    /** @param array<string, string> $errores @param array<string, mixed> $valores */
    private function guardarIntento(array $errores, array $valores): void
    {
        $this->sesion()->poner('form.errores', $errores);
        $this->sesion()->poner('form.valores', $valores);
    }

    /** @return array<string, string> */
    private function erroresGuardados(): array
    {
        $errores = $this->sesion()->obtener('form.errores', []);
        $this->sesion()->olvidar('form.errores');

        return is_array($errores) ? $errores : [];
    }

    /** @return array<string, mixed> */
    private function valoresGuardados(): array
    {
        $valores = $this->sesion()->obtener('form.valores', []);
        $this->sesion()->olvidar('form.valores');

        return is_array($valores) ? $valores : [];
    }
}

