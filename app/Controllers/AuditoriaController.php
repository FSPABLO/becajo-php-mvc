<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use App\Models\Entidades\Auditoria;
use App\Models\Entidades\Control;
use App\Models\Entidades\EvaluacionControl;
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

    // ── Panel ────────────────────────────────────────────────────────────────

    public function panel(): void
    {
        $usuario = $this->exigirUsuario();

        $this->ver('evaluacion/panel', [
            ...$this->contexto(),
            'meta'       => $this->meta('Mis auditorías'),
            'usuario'    => $usuario,
            'auditorias' => $this->auditorias()->auditoriasDe($usuario->id),
            'total'      => count($this->instrumento()->controles()),
        ]);
    }

    // ── Alta de auditoría ────────────────────────────────────────────────────

    public function nuevaFormulario(): void
    {
        $this->exigirUsuario();

        $this->ver('evaluacion/nueva', [
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

        $this->ver('evaluacion/mostrar', [
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

        $this->ver('evaluacion/control', [
            ...$this->contexto(),
            'meta'       => $this->meta($control->id . ' · Auditoría ' . $auditoria->id),
            'auditoria'  => $auditoria,
            'control'    => $control,
            'proceso'    => $this->indexarProcesos()[$control->proceso] ?? null,
            'evaluacion' => $this->auditorias()->evaluacion($auditoria->id, $control->id),
            'escala'     => $this->instrumento()->escala(),
            'estados'    => self::ESTADOS,
            'criterios'  => self::CRITERIOS,
            'errores'    => $this->erroresGuardados(),
            'vecinos'    => $this->vecinos($control),
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
        ));

        // Se recalcula en cada guardado, no solo al finalizar: el auditor puede
        // consultar los indicadores de una auditoría a medias, y verlos
        // desactualizados sería peor que no verlos.
        $this->auditorias()->recalcularRiesgo($auditoria->id);

        $this->sesion()->destello('aviso', 'Control ' . $control->id . ' guardado.');

        // "Guardar y siguiente" encadena los 75 controles sin volver al índice.
        $siguiente = $this->vecinos($control)['siguiente'] ?? null;

        if ($this->peticion()->entrada('siguiente') !== null && $siguiente !== null) {
            $this->redirigir('/evaluacion/' . $auditoria->id . '/controles/' . $siguiente->id);
        }

        $this->redirigir($destino);
    }

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

        $this->ver('evaluacion/resultados', [
            ...$this->contexto(),
            'meta'        => $this->meta('Resultados · Auditoría ' . $auditoria->id),
            'auditoria'   => $auditoria,
            'resumen'     => $repositorio->resumen($auditoria->id),
            'dominios'    => $repositorio->cumplimientoPorDominio($auditoria->id),
            'exposicion'  => $repositorio->exposicionRiesgo($auditoria->id),
            'menorMadurez' => $repositorio->menorMadurez($auditoria->id, 5),
            'mayorRiesgo' => $repositorio->mayorRiesgo($auditoria->id, 5),
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
        ], 'imprimir');
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

        $this->ver('evaluacion/comparar', [
            ...$this->contexto(),
            'meta'            => $this->meta('Comparación histórica'),
            'usuario'         => $usuario,
            'porOrganizacion' => $porOrganizacion,
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
