<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatosPostgres;
use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Entidades\ArchivoEvidencia;
use App\Models\Entidades\Auditoria;
use App\Models\Entidades\Estandar;
use App\Models\Entidades\EvaluacionControl;
use App\Models\Entidades\EvaluacionObjetivo;
use App\Models\Entidades\FotoPerfil;
use App\Models\Entidades\Remediacion;
use App\Models\Entidades\ResultadoRiesgo;
use App\Models\Entidades\Usuario;

/**
 * Auditorías, usuarios e indicadores sobre PostgreSQL.
 *
 * Ruta ALTERNATIVA a RepositorioAuditoriasOracle. Traducción método por
 * método, con estas diferencias de motor:
 *
 * - TO_CHAR / TO_DATE existen igual en Postgres (misma sintaxis) — no
 *   cambiaron. SYSTIMESTAMP -> now(), TRUNC(SYSDATE) -> CURRENT_DATE.
 * - No hay $clobs ni $blobs: TEXT y BYTEA se enlazan como cualquier otro
 *   parámetro (BaseDatosPostgres::ejecutar tiene un parámetro $binarios solo
 *   para BYTEA, que sí necesita PDO::PARAM_LOB).
 * - MERGE (Oracle) -> INSERT ... ON CONFLICT (...) DO UPDATE SET ... Los dos
 *   MERGE de este archivo (evaluacion_control, evaluacion_objetivo) tienen
 *   una restricción UNIQUE/PK real de respaldo (uq_evalctrl_auditoria_control,
 *   pk_evaluacion_objetivo), así que ON CONFLICT es tan atómico como el MERGE.
 * - "BEGIN pkg_indicadores.sp_x(:a, :cursor); END;" + cursor() -> las doce
 *   rutinas de pkg_indicadores son FUNCTIONs en Postgres (03_procedimientos_indicadores.sql):
 *   las que "abrían un cursor" se llaman con SELECT * FROM sp_x(...); las que
 *   solo escribían se llaman con SELECT sp_x(...) y no devuelven filas.
 * - RETURNING id INTO :id (Oracle) -> RETURNING id (Postgres ya lo entrega
 *   como si fuera un SELECT; BaseDatosPostgres::insertar() lo lee con
 *   fetchColumn()).
 */
final class RepositorioAuditoriasPostgres implements RepositorioAuditorias
{
    private const SELECCION_AUDITORIA = <<<'SQL'
        SELECT a.id_auditoria,
               a.id_auditor,
               a.id_administrador_bd,
               a.area_evaluada,
               TO_CHAR(a.fecha, 'YYYY-MM-DD') AS fecha,
               a.estado,
               a.indice_general_riesgo,
               a.codigo_estandar,
               TO_CHAR(a.fecha_finalizacion, 'YYYY-MM-DD HH24:MI') AS fecha_finalizacion,
               auditor.nombre AS nombre_auditor,
               entrevistado.nombre_administrador_bd,
               entrevistado.organizacion
          FROM auditoria a
          JOIN usuario auditor ON auditor.id_usuario = a.id_auditor
          JOIN v_auditoria_entrevistado entrevistado
            ON entrevistado.id_auditoria = a.id_auditoria
        SQL;

    /**
     * Hash bcrypt válido usado solo para igualar el tiempo de respuesta cuando
     * el correo no existe. No corresponde a ninguna cuenta del sistema.
     */
    private const HASH_DE_RELLENO = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function __construct(private readonly BaseDatosPostgres $bd)
    {
    }

    // ── Usuarios y autenticación ─────────────────────────────────────────────

    public function autenticar(string $correo, string $clave): ?Usuario
    {
        $fila = $this->bd->consultarUna(
            'SELECT id_usuario, nombre, correo, rol, organizacion, activo, descripcion, contrasena_hash
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
            'SELECT id_usuario, nombre, correo, rol, organizacion, activo, descripcion
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
            'SELECT id_usuario, nombre, correo, rol, organizacion, activo, descripcion
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

    // ── Perfil del usuario ───────────────────────────────────────────────────

    public function actualizarDescripcionUsuario(int $idUsuario, ?string $descripcion): void
    {
        $this->bd->ejecutar(
            'UPDATE usuario SET descripcion = :descripcion WHERE id_usuario = :id',
            [
                'id' => $idUsuario,
                'descripcion' => ($descripcion === null || trim($descripcion) === '')
                    ? null
                    : mb_substr(trim($descripcion), 0, 500),
            ],
        );
    }

    private const COLUMNAS_FICHA_FOTO =
        "id_usuario_foto, id_usuario, nombre, tipo_mime, tamano_bytes,
         TO_CHAR(fecha_carga, 'YYYY-MM-DD HH24:MI') AS fecha_carga";

    public function fotoUsuario(int $idUsuario): ?FotoPerfil
    {
        $fila = $this->bd->consultarUna(
            'SELECT ' . self::COLUMNAS_FICHA_FOTO . '
               FROM usuario_foto
              WHERE id_usuario = :id',
            ['id' => $idUsuario],
        );

        return $fila === null ? null : FotoPerfil::desdeFila($fila);
    }

    public function contenidoFotoUsuario(int $idUsuario): ?string
    {
        $fila = $this->bd->consultarUna(
            'SELECT contenido FROM usuario_foto WHERE id_usuario = :id',
            ['id' => $idUsuario],
        );

        $contenido = $fila['contenido'] ?? null;

        return is_string($contenido) ? $contenido : null;
    }

    /**
     * Sustituye la foto: fuera la anterior, dentro la nueva, en una
     * transacción PDO (BaseDatosPostgres::iniciarTransaccion/confirmar/revertir).
     */
    public function guardarFotoUsuario(
        int $idUsuario,
        string $nombre,
        string $tipoMime,
        string $contenido,
    ): void {
        $this->bd->iniciarTransaccion();

        try {
            $this->bd->ejecutar(
                'DELETE FROM usuario_foto WHERE id_usuario = :id',
                ['id' => $idUsuario],
            );

            $this->bd->ejecutar(
                'INSERT INTO usuario_foto (id_usuario, nombre, tipo_mime, tamano_bytes, contenido)
                 VALUES (:id, :nombre, :tipo_mime, :tamano_bytes, :contenido)',
                [
                    'id'           => $idUsuario,
                    'nombre'       => $nombre,
                    'tipo_mime'    => $tipoMime,
                    'tamano_bytes' => strlen($contenido),
                ],
                ['contenido' => $contenido],
            );

            $this->bd->confirmarTransaccion();
        } catch (\Throwable $error) {
            $this->bd->revertirTransaccion();

            throw $error;
        }
    }

    public function eliminarFotoUsuario(int $idUsuario): void
    {
        $this->bd->ejecutar(
            'DELETE FROM usuario_foto WHERE id_usuario = :id',
            ['id' => $idUsuario],
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
        ?int $idAdministradorBd,
        string $areaEvaluada,
        string $fecha,
        ?string $administradorNombre = null,
        ?string $administradorOrganizacion = null,
        string $codigoEstandar = Estandar::ISO,
    ): int {
        return $this->bd->insertar(
            "INSERT INTO auditoria
                    (id_auditor, id_administrador_bd,
                     administrador_nombre, administrador_organizacion,
                     area_evaluada, fecha, estado, codigo_estandar)
             VALUES (:id_auditor, :id_administrador_bd,
                     :administrador_nombre, :administrador_organizacion,
                     :area_evaluada, TO_DATE(:fecha, 'YYYY-MM-DD'), :estado, :codigo_estandar)
             RETURNING id_auditoria",
            [
                'id_auditor'          => $idAuditor,
                'id_administrador_bd' => $idAdministradorBd,
                'administrador_nombre'       => $administradorNombre,
                'administrador_organizacion' => $administradorOrganizacion,
                'area_evaluada'       => $areaEvaluada,
                'fecha'               => $fecha,
                'estado'              => Auditoria::EN_PROGRESO,
                'codigo_estandar'     => $codigoEstandar,
            ],
        );
    }

    public function actualizarAuditoria(
        int $id,
        ?int $idAdministradorBd,
        string $areaEvaluada,
        string $fecha,
        ?string $administradorNombre = null,
        ?string $administradorOrganizacion = null,
    ): void {
        $this->bd->ejecutar(
            "UPDATE auditoria
                SET id_administrador_bd = :id_administrador_bd,
                    administrador_nombre = :administrador_nombre,
                    administrador_organizacion = :administrador_organizacion,
                    area_evaluada = :area_evaluada,
                    fecha = TO_DATE(:fecha, 'YYYY-MM-DD')
              WHERE id_auditoria = :id",
            [
                'id_administrador_bd' => $idAdministradorBd,
                'administrador_nombre'       => $administradorNombre,
                'administrador_organizacion' => $administradorOrganizacion,
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
     * Inserta o actualiza en una sola sentencia: INSERT ... ON CONFLICT, el
     * equivalente atómico del MERGE de Oracle. uq_evalctrl_auditoria_control
     * es la restricción que detecta el conflicto.
     */
    public function guardarEvaluacion(EvaluacionControl $evaluacion): void
    {
        $this->bd->ejecutar(
            'INSERT INTO evaluacion_control
                    (id_auditoria, codigo_control, pregunta_personalizada,
                     estado, madurez, criterio,
                     afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
                     impacto, probabilidad, nivel_riesgo, hallazgo, recomendacion,
                     evidencia_verificada, calidad_evidencia, grado_logro)
             VALUES (:id_auditoria, :codigo_control, :pregunta_personalizada,
                     :estado, :madurez, :criterio,
                     :afecta_confidencialidad, :afecta_integridad, :afecta_disponibilidad,
                     :impacto, :probabilidad, :nivel_riesgo, :hallazgo, :recomendacion,
                     :evidencia_verificada, :calidad_evidencia, :grado_logro)
             ON CONFLICT (id_auditoria, codigo_control) DO UPDATE SET
                    pregunta_personalizada = EXCLUDED.pregunta_personalizada,
                    estado = EXCLUDED.estado,
                    madurez = EXCLUDED.madurez,
                    criterio = EXCLUDED.criterio,
                    afecta_confidencialidad = EXCLUDED.afecta_confidencialidad,
                    afecta_integridad = EXCLUDED.afecta_integridad,
                    afecta_disponibilidad = EXCLUDED.afecta_disponibilidad,
                    impacto = EXCLUDED.impacto,
                    probabilidad = EXCLUDED.probabilidad,
                    nivel_riesgo = EXCLUDED.nivel_riesgo,
                    hallazgo = EXCLUDED.hallazgo,
                    recomendacion = EXCLUDED.recomendacion,
                    evidencia_verificada = EXCLUDED.evidencia_verificada,
                    calidad_evidencia = EXCLUDED.calidad_evidencia,
                    grado_logro = EXCLUDED.grado_logro',
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
                'grado_logro'             => $evaluacion->gradoLogro,
            ],
        );
    }

    /** @return array<int, EvaluacionObjetivo> */
    public function evaluacionesObjetivo(int $idAuditoria): array
    {
        $filas = $this->bd->consultar(
            "SELECT id_auditoria, numero_proceso, capacidad, justificacion,
                    TO_CHAR(fecha_actualizacion, 'YYYY-MM-DD HH24:MI') AS fecha_actualizacion
               FROM evaluacion_objetivo
              WHERE id_auditoria = :id_auditoria",
            ['id_auditoria' => $idAuditoria],
        );

        $indice = [];

        foreach ($filas as $fila) {
            $evaluacion = EvaluacionObjetivo::desdeFila($fila);
            $indice[$evaluacion->numeroProceso] = $evaluacion;
        }

        return $indice;
    }

    public function guardarEvaluacionObjetivo(EvaluacionObjetivo $evaluacion): void
    {
        $this->bd->ejecutar(
            'INSERT INTO evaluacion_objetivo (id_auditoria, numero_proceso, capacidad, justificacion)
             VALUES (:id_auditoria, :numero_proceso, :capacidad, :justificacion)
             ON CONFLICT (id_auditoria, numero_proceso) DO UPDATE SET
                    capacidad = EXCLUDED.capacidad,
                    justificacion = EXCLUDED.justificacion,
                    fecha_actualizacion = now()',
            [
                'id_auditoria'   => $evaluacion->idAuditoria,
                'numero_proceso' => $evaluacion->numeroProceso,
                'capacidad'      => $evaluacion->capacidad,
                'justificacion'  => $evaluacion->justificacion,
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

    // ── Adjunto de la evidencia ──────────────────────────────────────────────

    private const COLUMNAS_FICHA_ARCHIVO =
        "ea.id_evidencia_archivo, ea.nombre, ea.tipo_mime, ea.tamano_bytes,
         TO_CHAR(ea.fecha_carga, 'YYYY-MM-DD HH24:MI') AS fecha_carga,
         ec.codigo_control";

    /** @return array<string, ArchivoEvidencia> */
    public function archivosEvidencia(int $idAuditoria): array
    {
        $filas = $this->bd->consultar(
            'SELECT ' . self::COLUMNAS_FICHA_ARCHIVO . '
               FROM evidencia_archivo ea
               JOIN evaluacion_control ec
                 ON ec.id_evaluacion_control = ea.id_evaluacion_control
              WHERE ec.id_auditoria = :id_auditoria',
            ['id_auditoria' => $idAuditoria],
        );

        $porControl = [];

        foreach ($filas as $fila) {
            $archivo = ArchivoEvidencia::desdeFila($fila);
            $porControl[$archivo->codigoControl] = $archivo;
        }

        return $porControl;
    }

    public function archivoEvidencia(int $idAuditoria, string $codigoControl): ?ArchivoEvidencia
    {
        $fila = $this->bd->consultarUna(
            'SELECT ' . self::COLUMNAS_FICHA_ARCHIVO . '
               FROM evidencia_archivo ea
               JOIN evaluacion_control ec
                 ON ec.id_evaluacion_control = ea.id_evaluacion_control
              WHERE ec.id_auditoria = :id_auditoria
                AND ec.codigo_control = :codigo_control',
            ['id_auditoria' => $idAuditoria, 'codigo_control' => $codigoControl],
        );

        return $fila === null ? null : ArchivoEvidencia::desdeFila($fila);
    }

    public function contenidoArchivoEvidencia(int $idAuditoria, string $codigoControl): ?string
    {
        $fila = $this->bd->consultarUna(
            'SELECT ea.contenido
               FROM evidencia_archivo ea
               JOIN evaluacion_control ec
                 ON ec.id_evaluacion_control = ea.id_evaluacion_control
              WHERE ec.id_auditoria = :id_auditoria
                AND ec.codigo_control = :codigo_control',
            ['id_auditoria' => $idAuditoria, 'codigo_control' => $codigoControl],
        );

        $contenido = $fila['contenido'] ?? null;

        return is_string($contenido) ? $contenido : null;
    }

    public function guardarArchivoEvidencia(
        int $idAuditoria,
        string $codigoControl,
        string $nombre,
        string $tipoMime,
        string $contenido,
    ): void {
        $claves = ['id_auditoria' => $idAuditoria, 'codigo_control' => $codigoControl];

        $this->bd->iniciarTransaccion();

        try {
            $this->bd->ejecutar($this->sqlBorrarArchivo(), $claves);

            $this->bd->ejecutar(
                'INSERT INTO evidencia_archivo
                     (id_evaluacion_control, nombre, tipo_mime, tamano_bytes, contenido)
                 SELECT ec.id_evaluacion_control, :nombre, :tipo_mime, :tamano_bytes, :contenido
                   FROM evaluacion_control ec
                  WHERE ec.id_auditoria = :id_auditoria
                    AND ec.codigo_control = :codigo_control',
                $claves + [
                    'nombre'       => $nombre,
                    'tipo_mime'    => $tipoMime,
                    'tamano_bytes' => strlen($contenido),
                ],
                ['contenido' => $contenido],
            );

            $this->bd->confirmarTransaccion();
        } catch (\Throwable $error) {
            $this->bd->revertirTransaccion();

            throw $error;
        }
    }

    public function eliminarArchivoEvidencia(int $idAuditoria, string $codigoControl): void
    {
        $this->bd->ejecutar(
            $this->sqlBorrarArchivo(),
            ['id_auditoria' => $idAuditoria, 'codigo_control' => $codigoControl],
        );
    }

    private function sqlBorrarArchivo(): string
    {
        return 'DELETE FROM evidencia_archivo
                 WHERE id_evaluacion_control IN (
                       SELECT ec.id_evaluacion_control
                         FROM evaluacion_control ec
                        WHERE ec.id_auditoria = :id_auditoria
                          AND ec.codigo_control = :codigo_control)';
    }

    // ── Indicadores (funciones sp_*, ver Scripts/postgres/03_procedimientos_indicadores.sql) ──

    public function recalcularRiesgo(int $idAuditoria): void
    {
        $this->bd->ejecutar(
            'SELECT calcular_riesgo_auditoria(:id_auditoria)',
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

    // ── Punto 18: histórico por dominio ──────────────────────────────────────

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

    // ── Punto 19: remediación y re-auditoría ─────────────────────────────────

    public function crearRemediacion(
        int $idEvaluacionControl,
        string $fechaLimite,
        ?string $responsable,
    ): void {
        $this->bd->ejecutar(
            "SELECT sp_crear_remediacion(:id_evaluacion_control, TO_DATE(:fecha_limite, 'YYYY-MM-DD'), :responsable)",
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
        $this->bd->ejecutar(
            'SELECT sp_programar_reauditoria(:id_remediacion, :id_auditoria_reauditoria)',
            [
                'id_remediacion'            => $idRemediacion,
                'id_auditoria_reauditoria'  => $idAuditoriaReauditoria,
            ],
        );
    }

    public function actualizarEstadoRemediacion(int $idRemediacion, string $estado): void
    {
        $this->bd->ejecutar(
            'SELECT sp_actualizar_estado_remediacion(:id_remediacion, :estado)',
            ['id_remediacion' => $idRemediacion, 'estado' => $estado],
        );
    }

    // ── Lembas, el asistente ─────────────────────────────────────────────────

    public function consultasAsistenteHoy(?int $idUsuario = null): int
    {
        $sql = 'SELECT COUNT(*) AS total
                  FROM asistente_consulta
                 WHERE fecha >= CURRENT_DATE';
        $parametros = [];

        if ($idUsuario !== null) {
            $sql .= ' AND id_usuario = :id_usuario';
            $parametros['id_usuario'] = $idUsuario;
        }

        $fila = $this->bd->consultarUna($sql, $parametros);

        return (int) ($fila['total'] ?? 0);
    }

    public function registrarConsultaAsistente(
        int $idUsuario,
        string $pantalla,
        array $herramientas,
        int $tokensEntrada,
        int $tokensSalida,
        string $resultado,
    ): void {
        $this->bd->ejecutar(
            'INSERT INTO asistente_consulta
                    (id_usuario, pantalla, herramientas, tokens_entrada, tokens_salida, resultado)
             VALUES (:id_usuario, :pantalla, :herramientas, :tokens_entrada, :tokens_salida, :resultado)',
            [
                'id_usuario'     => $idUsuario,
                'pantalla'       => mb_substr($pantalla, 0, 300),
                'herramientas'   => $herramientas === [] ? null : mb_substr(implode(', ', array_unique($herramientas)), 0, 500),
                'tokens_entrada' => max(0, $tokensEntrada),
                'tokens_salida'  => max(0, $tokensSalida),
                'resultado'      => $resultado,
            ],
        );
    }
}
