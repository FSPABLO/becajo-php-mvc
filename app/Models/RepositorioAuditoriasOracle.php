<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatos;
use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Entidades\Auditoria;
use App\Models\Entidades\EvaluacionControl;
use App\Models\Entidades\ResultadoRiesgo;
use App\Models\Entidades\Usuario;

/**
 * Auditorías, usuarios e indicadores sobre Oracle.
 *
 * Tres convenciones que se repiten en todo el archivo:
 *
 * - Las fechas se leen con TO_CHAR y se escriben con TO_DATE, siempre en
 *   formato ISO. Sin esto, el formato dependería de la configuración regional
 *   de la sesión de Oracle (NLS_DATE_FORMAT) y la misma consulta daría
 *   resultados distintos en la máquina de cada quien.
 *
 * - Los indicadores NO se calculan aquí: se piden a pkg_indicadores. Fue un
 *   requisito del curso que los reportes salgan de procedimientos almacenados,
 *   y además evita tener la fórmula del riesgo escrita en dos idiomas.
 *
 * - Ningún valor se concatena dentro del SQL. Todo va enlazado por nombre.
 */
final class RepositorioAuditoriasOracle implements RepositorioAuditorias
{
    /**
     * Columnas de una auditoría con los nombres ya resueltos.
     *
     * Se declara una vez y se reutiliza en las tres consultas de lectura: si
     * mañana se agrega un campo, se agrega en un solo sitio y las tres lo ven.
     * El JOIN doble contra usuario es lo que evita el problema N+1 al listar.
     */
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
     * Hash bcrypt válido usado solo para igualar el tiempo de respuesta cuando
     * el correo no existe. No corresponde a ninguna cuenta del sistema.
     */
    private const HASH_DE_RELLENO = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function __construct(private readonly BaseDatos $bd)
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

        // Se verifica el hash SIEMPRE, exista el usuario o no. Si se saliera
        // antes, la respuesta llegaría antes para un correo no registrado que
        // para uno real, y esa diferencia de tiempo basta para ir averiguando
        // qué correos existen. HASH_DE_RELLENO es un hash válido cualquiera:
        // solo está para que el trabajo de comparar se haga igual.
        $hash = (string) ($fila['contrasena_hash'] ?? self::HASH_DE_RELLENO);
        $claveCorrecta = password_verify($clave, $hash);

        if ($fila === null || !$claveCorrecta) {
            return null;
        }

        // Una cuenta desactivada se rechaza igual que una clave incorrecta, y
        // sin decir cuál de las dos cosas pasó.
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
             RETURNING id_usuario INTO :id',
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
             RETURNING id_auditoria INTO :id",
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
                    fecha_finalizacion = SYSTIMESTAMP
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
     * Inserta o actualiza en una sola sentencia (MERGE).
     *
     * La alternativa —consultar si existe y luego decidir— deja una ventana
     * entre la consulta y la escritura en la que otra petición puede insertar
     * la misma fila; ahí saltaría uq_evalctrl_auditoria_control. El MERGE lo
     * resuelve Oracle de forma atómica.
     *
     * Los tres campos de texto libre van por $clobs porque las columnas son
     * CLOB y un hallazgo largo no cabe en un enlace normal.
     */
    public function guardarEvaluacion(EvaluacionControl $evaluacion): void
    {
        $this->bd->ejecutar(
            'MERGE INTO evaluacion_control destino
             USING (SELECT :id_auditoria AS id_auditoria,
                           :codigo_control AS codigo_control
                      FROM dual) origen
                ON (destino.id_auditoria = origen.id_auditoria
                AND destino.codigo_control = origen.codigo_control)
             WHEN MATCHED THEN
                UPDATE SET destino.pregunta_personalizada = :pregunta_personalizada,
                           destino.estado = :estado,
                           destino.madurez = :madurez,
                           destino.criterio = :criterio,
                           destino.afecta_confidencialidad = :afecta_confidencialidad,
                           destino.afecta_integridad = :afecta_integridad,
                           destino.afecta_disponibilidad = :afecta_disponibilidad,
                           destino.impacto = :impacto,
                           destino.probabilidad = :probabilidad,
                           destino.nivel_riesgo = :nivel_riesgo,
                           destino.hallazgo = :hallazgo,
                           destino.recomendacion = :recomendacion
             WHEN NOT MATCHED THEN
                INSERT (id_auditoria, codigo_control, pregunta_personalizada,
                        estado, madurez, criterio,
                        afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
                        impacto, probabilidad, nivel_riesgo, hallazgo, recomendacion)
                VALUES (:id_auditoria, :codigo_control, :pregunta_personalizada,
                        :estado, :madurez, :criterio,
                        :afecta_confidencialidad, :afecta_integridad, :afecta_disponibilidad,
                        :impacto, :probabilidad, :nivel_riesgo, :hallazgo, :recomendacion)',
            [
                'id_auditoria'            => $evaluacion->idAuditoria,
                'codigo_control'          => $evaluacion->codigoControl,
                'estado'                  => $evaluacion->estado,
                'madurez'                 => $evaluacion->madurez,
                'criterio'                => $evaluacion->criterio,
                'afecta_confidencialidad' => $evaluacion->afectaConfidencialidad ? 1 : 0,
                'afecta_integridad'       => $evaluacion->afectaIntegridad ? 1 : 0,
                'afecta_disponibilidad'   => $evaluacion->afectaDisponibilidad ? 1 : 0,
                'impacto'                 => $evaluacion->impacto,
                'probabilidad'            => $evaluacion->probabilidad,
                'nivel_riesgo'            => $evaluacion->nivelRiesgo ?? $evaluacion->nivelRiesgoCalculado(),
            ],
            [
                'pregunta_personalizada' => $evaluacion->preguntaPersonalizada,
                'hallazgo'               => $evaluacion->hallazgo,
                'recomendacion'          => $evaluacion->recomendacion,
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

    // ── Indicadores (pkg_indicadores) ────────────────────────────────────────

    public function recalcularRiesgo(int $idAuditoria): void
    {
        $this->bd->procedimiento(
            'BEGIN pkg_indicadores.calcular_riesgo_auditoria(:id_auditoria); END;',
            ['id_auditoria' => $idAuditoria],
        );
    }

    /** @return array<string, mixed> */
    public function resumen(int $idAuditoria): array
    {
        $filas = $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_resumen_auditoria(:id_auditoria, :cursor); END;',
            ['id_auditoria' => $idAuditoria],
        );

        return $filas[0] ?? [];
    }

    /** @return list<array<string, mixed>> */
    public function cumplimientoPorDominio(int $idAuditoria): array
    {
        return $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_cumplimiento_dominio(:id_auditoria, :cursor); END;',
            ['id_auditoria' => $idAuditoria],
        );
    }

    /** @return list<array<string, mixed>> */
    public function menorMadurez(int $idAuditoria, int $cuantos = 5): array
    {
        return $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_menor_madurez(:id_auditoria, :top_n, :cursor); END;',
            ['id_auditoria' => $idAuditoria, 'top_n' => $cuantos],
        );
    }

    /** @return list<array<string, mixed>> */
    public function mayorRiesgo(int $idAuditoria, int $cuantos = 5): array
    {
        return $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_mayor_riesgo(:id_auditoria, :top_n, :cursor); END;',
            ['id_auditoria' => $idAuditoria, 'top_n' => $cuantos],
        );
    }

    /** @return list<ResultadoRiesgo> */
    public function exposicionRiesgo(int $idAuditoria): array
    {
        $filas = $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_exposicion_riesgo(:id_auditoria, :cursor); END;',
            ['id_auditoria' => $idAuditoria],
        );

        return array_map(
            static fn (array $fila): ResultadoRiesgo => ResultadoRiesgo::desdeFila($fila),
            $filas,
        );
    }
}
