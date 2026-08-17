<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatosPostgres;
use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Entidades\Auditoria;
use App\Models\Entidades\EvaluacionControl;
use App\Models\Entidades\Remediacion;
use App\Models\Entidades\ResultadoRiesgo;
use App\Models\Entidades\Usuario;

/**
 * Auditorías, usuarios e indicadores sobre PostgreSQL.
 *
 * Ruta ALTERNATIVA a RepositorioAuditoriasOracle.php — mismo contrato
 * (RepositorioAuditorias), mismas reglas de negocio, SQL adaptado al motor:
 *
 * - Los indicadores siguen sin calcularse aquí: se piden a las funciones y
 *   procedimientos de Scripts/postgres/03_procedimientos_indicadores.sql.
 *   Lo que en Oracle era "BEGIN pkg_indicadores.sp_x(:id, :cursor); END;" más
 *   una segunda ejecución para leer el cursor, aquí es un SELECT normal
 *   contra una función que declara RETURNS TABLE: una sola ejecución, sin
 *   cursor de por medio.
 *
 * - El antiguo MERGE de Oracle se convierte en INSERT ... ON CONFLICT ...
 *   DO UPDATE, que es el equivalente de Postgres para "insertar o actualizar
 *   en una sola sentencia atómica".
 *
 * - RETURNING ya no necesita el parámetro :id de salida que pedía oci8: la
 *   fila generada se lee como si el INSERT fuera un SELECT.
 *
 * - Sin CLOB: los campos de texto libre (hallazgo, recomendación, evidencia)
 *   se enlazan como cualquier otro parámetro, sin el mecanismo de
 *   descriptores que exigía Oracle para pasar de 4000 bytes.
 */
final class RepositorioAuditoriasPostgres implements RepositorioAuditorias
{
    /** Igual que en la versión Oracle: se declara una vez y se reutiliza. */
    private const SELECCION_AUDITORIA = <<<'SQL'
        SELECT a.id_auditoria,
               a.id_auditor,
               a.id_administrador_bd,
               a.area_evaluada,
               TO_CHAR(a.fecha, 'YYYY-MM-DD') AS fecha,
               a.estado,
               a.indice_general_riesgo,
               TO_CHAR(a.fecha_finalizacion, 'YYYY-MM-DD HH24:MI') AS fecha_finalizacion,
               auditor.nombre AS nombre_auditor,
               dba.nombre AS nombre_administrador_bd,
               dba.organizacion AS organizacion
          FROM auditoria a
          JOIN usuario auditor ON auditor.id_usuario = a.id_auditor
          JOIN usuario dba ON dba.id_usuario = a.id_administrador_bd
        SQL;

    /**
     * Hash bcrypt válido usado solo para igualar el tiempo de respuesta
     * cuando el correo no existe. No corresponde a ninguna cuenta real.
     */
    private const HASH_DE_RELLENO = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function __construct(private readonly BaseDatosPostgres $bd)
    {
    }

    // ── Usuarios y autenticación ─────────────────────────────────────────────

    public function autenticar(string $correo, string $clave): ?Usuario
    {
        $fila = $this->bd->consultarUna(
            'SELECT id_usuario, nombre, correo, rol, organizacion, activo, contrasena_hash
               FROM usuario
              WHERE LOWER(correo) = LOWER(:correo)',
            ['correo' => $correo],
        );

        $hash = (string) ($fila['contrasena_hash'] ?? self::HASH_DE_RELLENO);
        $claveCorrecta = password_verify($clave, $hash);

        if ($fila === null || !$claveCorrecta) {
            return null;
        }

        if ((int) $fila['activo'] !== 1) {
            return null;
        }

        return Usuario::desdeFila($fila);
    }

    public function usuario(int $id): ?Usuario
    {
        $fila = $this->bd->consultarUna(
            'SELECT id_usuario, nombre, correo, rol, organizacion, activo
               FROM usuario
              WHERE id_usuario = :id',
            ['id' => $id],
        );

        return $fila === null ? null : Usuario::desdeFila($fila);
    }

    /** @return list<Usuario> */
    public function usuariosPorRol(string $rol): array
    {
        $filas = $this->bd->consultar(
            'SELECT id_usuario, nombre, correo, rol, organizacion, activo
               FROM usuario
              WHERE rol = :rol AND activo = 1
              ORDER BY nombre',
            ['rol' => $rol],
        );

        return array_map(
            static fn (array $fila): Usuario => Usuario::desdeFila($fila),
            $filas,
        );
    }

    public function correoRegistrado(string $correo): bool
    {
        $fila = $this->bd->consultarUna(
            'SELECT COUNT(*) AS n FROM usuario WHERE LOWER(correo) = LOWER(:correo)',
            ['correo' => $correo],
        );

        return (int) ($fila['n'] ?? 0) > 0;
    }

    public function registrarUsuario(
        string $nombre,
        string $correo,
        string $hash,
        string $rol,
        string $organizacion,
    ): int {
        return $this->bd->insertar(
            'INSERT INTO usuario (nombre, correo, contrasena_hash, rol, organizacion, activo)
             VALUES (:nombre, :correo, :hash, :rol, :organizacion, 1)
             RETURNING id_usuario',
            [
                'nombre'       => $nombre,
                'correo'       => $correo,
                'hash'         => $hash,
                'rol'          => $rol,
                'organizacion' => $organizacion,
            ],
        );
    }

    // ── Auditorías ───────────────────────────────────────────────────────────

    /** @return list<Auditoria> */
    public function auditoriasDe(int $idAuditor): array
    {
        $filas = $this->bd->consultar(
            self::SELECCION_AUDITORIA . '
             WHERE a.id_auditor = :id_auditor
             ORDER BY a.fecha DESC, a.id_auditoria DESC',
            ['id_auditor' => $idAuditor],
        );

        return array_map(
            static fn (array $fila): Auditoria => Auditoria::desdeFila($fila),
            $filas,
        );
    }

    public function auditoria(int $id): ?Auditoria
    {
        $fila = $this->bd->consultarUna(
            self::SELECCION_AUDITORIA . ' WHERE a.id_auditoria = :id',
            ['id' => $id],
        );

        return $fila === null ? null : Auditoria::desdeFila($fila);
    }

    public function crearAuditoria(
        int $idAuditor,
        int $idAdministradorBd,
        string $areaEvaluada,
        string $fecha,
    ): int {
        return $this->bd->insertar(
            "INSERT INTO auditoria
                    (id_auditor, id_administrador_bd, area_evaluada, fecha, estado)
             VALUES (:id_auditor, :id_administrador_bd, :area_evaluada,
                     TO_DATE(:fecha, 'YYYY-MM-DD'), :estado)
             RETURNING id_auditoria",
            [
                'id_auditor'          => $idAuditor,
                'id_administrador_bd' => $idAdministradorBd,
                'area_evaluada'       => $areaEvaluada,
                'fecha'               => $fecha,
                'estado'              => Auditoria::EN_PROGRESO,
            ],
        );
    }

    public function actualizarAuditoria(
        int $id,
        int $idAdministradorBd,
        string $areaEvaluada,
        string $fecha,
    ): void {
        $this->bd->ejecutar(
            "UPDATE auditoria
                SET id_administrador_bd = :id_administrador_bd,
                    area_evaluada = :area_evaluada,
                    fecha = TO_DATE(:fecha, 'YYYY-MM-DD')
              WHERE id_auditoria = :id",
            [
                'id_administrador_bd' => $idAdministradorBd,
                'area_evaluada'       => $areaEvaluada,
                'fecha'               => $fecha,
                'id'                  => $id,
            ],
        );
    }

    public function finalizarAuditoria(int $id): void
    {
        $this->bd->ejecutar(
            'UPDATE auditoria
                SET estado = :estado,
                    fecha_finalizacion = now()
              WHERE id_auditoria = :id',
            ['estado' => Auditoria::FINALIZADA, 'id' => $id],
        );
    }

    public function reabrirAuditoria(int $id): void
    {
        $this->bd->ejecutar(
            'UPDATE auditoria
                SET estado = :estado,
                    fecha_finalizacion = NULL
              WHERE id_auditoria = :id',
            ['estado' => Auditoria::EN_PROGRESO, 'id' => $id],
        );
    }

    // ── Evaluación de controles ──────────────────────────────────────────────

    /** @return list<EvaluacionControl> */
    public function evaluaciones(int $idAuditoria): array
    {
        $filas = $this->bd->consultar(
            'SELECT *
               FROM evaluacion_control
              WHERE id_auditoria = :id_auditoria
              ORDER BY codigo_control',
            ['id_auditoria' => $idAuditoria],
        );

        return array_map(
            static fn (array $fila): EvaluacionControl => EvaluacionControl::desdeFila($fila),
            $filas,
        );
    }

    public function evaluacion(int $idAuditoria, string $codigoControl): ?EvaluacionControl
    {
        $fila = $this->bd->consultarUna(
            'SELECT *
               FROM evaluacion_control
              WHERE id_auditoria = :id_auditoria
                AND codigo_control = :codigo_control',
            ['id_auditoria' => $idAuditoria, 'codigo_control' => $codigoControl],
        );

        return $fila === null ? null : EvaluacionControl::desdeFila($fila);
    }

    /**
     * Inserta o actualiza en una sola sentencia con INSERT ... ON CONFLICT
     * ... DO UPDATE — el equivalente de Postgres al MERGE de Oracle. La
     * restricción uq_evalctrl_auditoria_control es lo que ON CONFLICT
     * necesita nombrar para saber cuál fila está en conflicto.
     */
    public function guardarEvaluacion(EvaluacionControl $evaluacion): void
    {
        $this->bd->ejecutar(
            'INSERT INTO evaluacion_control
                    (id_auditoria, codigo_control, pregunta_personalizada,
                     estado, madurez, criterio,
                     afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
                     impacto, probabilidad, nivel_riesgo, hallazgo, recomendacion,
                     evidencia_verificada, calidad_evidencia)
             VALUES (:id_auditoria, :codigo_control, :pregunta_personalizada,
                     :estado, :madurez, :criterio,
                     :afecta_confidencialidad, :afecta_integridad, :afecta_disponibilidad,
                     :impacto, :probabilidad, :nivel_riesgo, :hallazgo, :recomendacion,
                     :evidencia_verificada, :calidad_evidencia)
             ON CONFLICT (id_auditoria, codigo_control) DO UPDATE SET
                    pregunta_personalizada   = EXCLUDED.pregunta_personalizada,
                    estado                   = EXCLUDED.estado,
                    madurez                  = EXCLUDED.madurez,
                    criterio                 = EXCLUDED.criterio,
                    afecta_confidencialidad  = EXCLUDED.afecta_confidencialidad,
                    afecta_integridad        = EXCLUDED.afecta_integridad,
                    afecta_disponibilidad    = EXCLUDED.afecta_disponibilidad,
                    impacto                  = EXCLUDED.impacto,
                    probabilidad             = EXCLUDED.probabilidad,
                    nivel_riesgo             = EXCLUDED.nivel_riesgo,
                    hallazgo                 = EXCLUDED.hallazgo,
                    recomendacion            = EXCLUDED.recomendacion,
                    evidencia_verificada     = EXCLUDED.evidencia_verificada,
                    calidad_evidencia        = EXCLUDED.calidad_evidencia',
            [
                'id_auditoria'            => $evaluacion->idAuditoria,
                'codigo_control'          => $evaluacion->codigoControl,
                'pregunta_personalizada'  => $evaluacion->preguntaPersonalizada,
                'estado'                  => $evaluacion->estado,
                'madurez'                 => $evaluacion->madurez,
                'criterio'                => $evaluacion->criterio,
                'afecta_confidencialidad' => $evaluacion->afectaConfidencialidad ? 1 : 0,
                'afecta_integridad'       => $evaluacion->afectaIntegridad ? 1 : 0,
                'afecta_disponibilidad'   => $evaluacion->afectaDisponibilidad ? 1 : 0,
                'impacto'                 => $evaluacion->impacto,
                'probabilidad'            => $evaluacion->probabilidad,
                'nivel_riesgo'            => $evaluacion->nivelRiesgo ?? $evaluacion->nivelRiesgoCalculado(),
                'hallazgo'                => $evaluacion->hallazgo,
                'recomendacion'           => $evaluacion->recomendacion,
                'evidencia_verificada'    => $evaluacion->evidenciaVerificada,
                'calidad_evidencia'       => $evaluacion->calidadEvidencia,
            ],
        );
    }

    public function eliminarEvaluacion(int $idAuditoria, string $codigoControl): void
    {
        $this->bd->ejecutar(
            'DELETE FROM evaluacion_control
              WHERE id_auditoria = :id_auditoria
                AND codigo_control = :codigo_control',
            ['id_auditoria' => $idAuditoria, 'codigo_control' => $codigoControl],
        );
    }

    public function controlesEvaluados(int $idAuditoria): int
    {
        $fila = $this->bd->consultarUna(
            'SELECT COUNT(*) AS total
               FROM evaluacion_control
              WHERE id_auditoria = :id_auditoria
                AND estado IS NOT NULL',
            ['id_auditoria' => $idAuditoria],
        );

        return (int) ($fila['total'] ?? 0);
    }

    // ── Indicadores ──────────────────────────────────────────────────────────
    //
    // Cada sp_x de aquí es una FUNCTION "RETURNS TABLE" de
    // Scripts/postgres/03_procedimientos_indicadores.sql, así que se lee con
    // un SELECT normal contra ella — no hace falta un método cursor()
    // aparte como en BaseDatos (Oracle).

    public function recalcularRiesgo(int $idAuditoria): void
    {
        $this->bd->procedimiento(
            'CALL calcular_riesgo_auditoria(:id_auditoria)',
            ['id_auditoria' => $idAuditoria],
        );
    }

    /** @return array<string, mixed> */
    public function resumen(int $idAuditoria): array
    {
        $filas = $this->bd->consultar(
            'SELECT * FROM sp_resumen_auditoria(:id_auditoria)',
            ['id_auditoria' => $idAuditoria],
        );

        return $filas[0] ?? [];
    }

    /** @return list<array<string, mixed>> */
    public function cumplimientoPorDominio(int $idAuditoria): array
    {
        return $this->bd->consultar(
            'SELECT * FROM sp_cumplimiento_dominio(:id_auditoria)',
            ['id_auditoria' => $idAuditoria],
        );
    }

    /** @return list<array<string, mixed>> */
    public function menorMadurez(int $idAuditoria, int $cuantos = 5): array
    {
        return $this->bd->consultar(
            'SELECT * FROM sp_menor_madurez(:id_auditoria, :top_n)',
            ['id_auditoria' => $idAuditoria, 'top_n' => $cuantos],
        );
    }

    /** @return list<array<string, mixed>> */
    public function mayorRiesgo(int $idAuditoria, int $cuantos = 5): array
    {
        return $this->bd->consultar(
            'SELECT * FROM sp_mayor_riesgo(:id_auditoria, :top_n)',
            ['id_auditoria' => $idAuditoria, 'top_n' => $cuantos],
        );
    }

    /** @return list<ResultadoRiesgo> */
    public function exposicionRiesgo(int $idAuditoria): array
    {
        $filas = $this->bd->consultar(
            'SELECT * FROM sp_exposicion_riesgo(:id_auditoria)',
            ['id_auditoria' => $idAuditoria],
        );

        return array_map(
            static fn (array $fila): ResultadoRiesgo => ResultadoRiesgo::desdeFila($fila),
            $filas,
        );
    }

    /** @return list<array<string, mixed>> */
    public function historicoPorDominio(string $organizacion): array
    {
        return $this->bd->consultar(
            'SELECT * FROM sp_historico_dominio(:organizacion)',
            ['organizacion' => $organizacion],
        );
    }

    /** @return list<array<string, mixed>> */
    public function evolucionAuditor(int $idAuditor, ?string $organizacion = null): array
    {
        return $this->bd->consultar(
            'SELECT * FROM sp_evolucion_auditor(:id_auditor, :organizacion)',
            ['id_auditor' => $idAuditor, 'organizacion' => $organizacion],
        );
    }

    // ── Remediación y re-auditoría ───────────────────────────────────────────

    public function crearRemediacion(
        int $idEvaluacionControl,
        string $fechaLimite,
        ?string $responsable,
    ): void {
        $this->bd->procedimiento(
            "CALL sp_crear_remediacion(:id_evaluacion_control, TO_DATE(:fecha_limite, 'YYYY-MM-DD'), :responsable)",
            [
                'id_evaluacion_control' => $idEvaluacionControl,
                'fecha_limite'          => $fechaLimite,
                'responsable'           => $responsable,
            ],
        );
    }

    /** @return list<Remediacion> */
    public function remediacionesAuditoria(int $idAuditoria): array
    {
        $filas = $this->bd->consultar(
            'SELECT * FROM sp_remediaciones_auditoria(:id_auditoria)',
            ['id_auditoria' => $idAuditoria],
        );

        return array_map(
            static fn (array $fila): Remediacion => Remediacion::desdeFila($fila),
            $filas,
        );
    }

    /** @return list<array<string, mixed>> */
    public function remediacionesVencidas(): array
    {
        return $this->bd->consultar('SELECT * FROM sp_remediaciones_vencidas()');
    }

    public function programarReauditoria(int $idRemediacion, int $idAuditoriaReauditoria): void
    {
        $this->bd->procedimiento(
            'CALL sp_programar_reauditoria(:id_remediacion, :id_auditoria_reauditoria)',
            [
                'id_remediacion'           => $idRemediacion,
                'id_auditoria_reauditoria' => $idAuditoriaReauditoria,
            ],
        );
    }

    public function actualizarEstadoRemediacion(int $idRemediacion, string $estado): void
    {
        $this->bd->procedimiento(
            'CALL sp_actualizar_estado_remediacion(:id_remediacion, :estado)',
            ['id_remediacion' => $idRemediacion, 'estado' => $estado],
        );
    }
}
