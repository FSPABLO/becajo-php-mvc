<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatos;
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

    public function __construct(private readonly BaseDatos $bd)
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

    // ── Perfil del usuario ───────────────────────────────────────────────────

    public function actualizarDescripcionUsuario(int $idUsuario, ?string $descripcion): void
    {
        $this->bd->ejecutar(
            'UPDATE usuario SET descripcion = :descripcion WHERE id_usuario = :id',
            [
                'id' => $idUsuario,
                // Vacía es NULL: «sin descripción» y «descripción en blanco» no
                // son dos estados distintos, y la columna admite nulos.
                'descripcion' => ($descripcion === null || trim($descripcion) === '')
                    ? null
                    : mb_substr(trim($descripcion), 0, 500),
            ],
        );
    }

    /**
     * Las columnas de la FICHA, sin `contenido`.
     *
     * Escritas una vez para que ninguna lectura se cuele el BLOB: con SELECT *
     * el driver lo materializa entero (OCI_RETURN_LOBS), y esta ficha se pinta
     * en la barra lateral de todas las pantallas del módulo.
     */
    private const COLUMNAS_FICHA_FOTO =
        'id_usuario_foto, id_usuario, nombre, tipo_mime, tamano_bytes,
         TO_CHAR(fecha_carga, \'YYYY-MM-DD HH24:MI\') AS fecha_carga';

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

    /** Aquí sí se pide `contenido`, y solo aquí. */
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
     * Sustituye la foto: fuera la anterior, dentro la nueva.
     *
     * DELETE + INSERT y no MERGE, igual que el adjunto de la evidencia: el
     * destino lo identifica uq_usufoto_usuario y un MERGE necesitaría enlazar
     * el BLOB dos veces —en el UPDATE y en el INSERT— con un solo descriptor.
     * Los dos pasos van en UNA transacción: si el INSERT falla, la foto vieja
     * sigue ahí en vez de haberse perdido a cambio de nada.
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
                [],
                false,
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
                [],
                false,
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
             RETURNING id_auditoria INTO :id",
            [
                'id_auditor'          => $idAuditor,
                'id_administrador_bd' => $idAdministradorBd,
                /*
                 * Los tres van SIEMPRE, y el lado que no se usa va a NULL a
                 * propósito: es lo que ck_auditoria_administrador exige y lo
                 * que impide que una auditoría acabe con cuenta y con texto,
                 * o sea con dos respuestas a «¿a quién se entrevistó?».
                 */
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
                // Las tres columnas se escriben en el mismo UPDATE: cambiar de
                // cuenta registrada a nombre escrito a mano tiene que BORRAR
                // el otro lado, o la fila deja de pasar el CHECK.
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
                           destino.recomendacion = :recomendacion,
                           destino.evidencia_verificada = :evidencia_verificada,
                           destino.calidad_evidencia = :calidad_evidencia,
                           destino.grado_logro = :grado_logro
             WHEN NOT MATCHED THEN
                INSERT (id_auditoria, codigo_control, pregunta_personalizada,
                        estado, madurez, criterio,
                        afecta_confidencialidad, afecta_integridad, afecta_disponibilidad,
                        impacto, probabilidad, nivel_riesgo, hallazgo, recomendacion,
                        evidencia_verificada, calidad_evidencia, grado_logro)
                VALUES (:id_auditoria, :codigo_control, :pregunta_personalizada,
                        :estado, :madurez, :criterio,
                        :afecta_confidencialidad, :afecta_integridad, :afecta_disponibilidad,
                        :impacto, :probabilidad, :nivel_riesgo, :hallazgo, :recomendacion,
                        :evidencia_verificada, :calidad_evidencia, :grado_logro)',
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
                'calidad_evidencia'       => $evaluacion->calidadEvidencia,
                'grado_logro'             => $evaluacion->gradoLogro,
            ],
            [
                'pregunta_personalizada' => $evaluacion->preguntaPersonalizada,
                'hallazgo'               => $evaluacion->hallazgo,
                'recomendacion'          => $evaluacion->recomendacion,
                'evidencia_verificada'   => $evaluacion->evidenciaVerificada,
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
            'MERGE INTO evaluacion_objetivo destino
             USING (SELECT :id_auditoria AS id_auditoria,
                           :numero_proceso AS numero_proceso
                      FROM dual) origen
                ON (destino.id_auditoria = origen.id_auditoria
                AND destino.numero_proceso = origen.numero_proceso)
             WHEN MATCHED THEN
                UPDATE SET destino.capacidad = :capacidad,
                           destino.justificacion = :justificacion,
                           destino.fecha_actualizacion = SYSTIMESTAMP
             WHEN NOT MATCHED THEN
                INSERT (id_auditoria, numero_proceso, capacidad, justificacion)
                VALUES (:id_auditoria, :numero_proceso, :capacidad, :justificacion)',
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

    /**
     * Las columnas de la FICHA, sin `contenido`.
     *
     * Escritas una vez y compartidas por las dos lecturas de ficha para que
     * ninguna se cuele el BLOB: con SELECT * el driver lo materializa entero
     * (OCI_RETURN_LOBS), y las 75 tarjetas del panel se traerían varios
     * megabytes para escribir un nombre y un tamaño.
     */
    private const COLUMNAS_FICHA_ARCHIVO =
        'ea.id_evidencia_archivo, ea.nombre, ea.tipo_mime, ea.tamano_bytes,
         TO_CHAR(ea.fecha_carga, \'YYYY-MM-DD HH24:MI\') AS fecha_carga,
         ec.codigo_control';

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

    /**
     * Los bytes. Aquí sí se pide `contenido`, y solo aquí.
     *
     * Llega como cadena porque leerFilas() usa OCI_RETURN_LOBS. Para un tope de
     * 5 MB eso es aceptable y ahorra la danza de descriptores en la lectura;
     * si algún día el tope subiera de verdad, este es el método que habría que
     * convertir en una lectura por trozos.
     */
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

    /**
     * Sustituye el adjunto de un control: fuera el anterior, dentro el nuevo.
     *
     * Es un DELETE + INSERT y no un MERGE porque el destino lo identifica
     * uq_evidarch_evalctrl (una fila por evaluación) y el MERGE necesitaría
     * enlazar el BLOB dos veces —en el UPDATE y en el INSERT— con un solo
     * descriptor. Los dos pasos van en UNA transacción: si el INSERT falla, el
     * adjunto viejo sigue ahí en vez de haberse perdido a cambio de nada.
     *
     * La subconsulta resuelve id_evaluacion_control desde (auditoría, control)
     * para que quien llama no tenga que conocerlo. Si la evaluación no existe,
     * no inserta nada y el adjunto simplemente no se guarda; el controlador
     * guarda la evaluación primero, en la misma pulsación.
     */
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
            $this->bd->ejecutar($this->sqlBorrarArchivo(), $claves, [], false);

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
                [],
                false,
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

    /**
     * El DELETE, escrito una vez: lo usan el borrado a secas y el primer paso
     * de la sustitución, y son la misma sentencia.
     */
    private function sqlBorrarArchivo(): string
    {
        return 'DELETE FROM evidencia_archivo
                 WHERE id_evaluacion_control IN (
                       SELECT ec.id_evaluacion_control
                         FROM evaluacion_control ec
                        WHERE ec.id_auditoria = :id_auditoria
                          AND ec.codigo_control = :codigo_control)';
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

    // ── Punto 18: histórico por dominio ──────────────────────────────────────

    /** @return list<array<string, mixed>> */
    public function historicoPorDominio(string $organizacion): array
    {
        return $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_historico_dominio(:organizacion, :cursor); END;',
            ['organizacion' => $organizacion],
        );
    }

    /** @return list<array<string, mixed>> */
    public function evolucionAuditor(int $idAuditor, ?string $organizacion = null): array
    {
        return $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_evolucion_auditor(:id_auditor, :organizacion, :cursor); END;',
            ['id_auditor' => $idAuditor, 'organizacion' => $organizacion],
        );
    }

    // ── Punto 19: remediación y re-auditoría ─────────────────────────────────

    public function crearRemediacion(
        int $idEvaluacionControl,
        string $fechaLimite,
        ?string $responsable,
    ): void {
        $this->bd->procedimiento(
            'BEGIN pkg_indicadores.sp_crear_remediacion(
                :id_evaluacion_control, TO_DATE(:fecha_limite, \'YYYY-MM-DD\'), :responsable
            ); END;',
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
        $filas = $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_remediaciones_auditoria(:id_auditoria, :cursor); END;',
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
        return $this->bd->cursor(
            'BEGIN pkg_indicadores.sp_remediaciones_vencidas(:cursor); END;',
        );
    }

    public function programarReauditoria(int $idRemediacion, int $idAuditoriaReauditoria): void
    {
        $this->bd->procedimiento(
            'BEGIN pkg_indicadores.sp_programar_reauditoria(:id_remediacion, :id_auditoria_reauditoria); END;',
            [
                'id_remediacion'            => $idRemediacion,
                'id_auditoria_reauditoria'  => $idAuditoriaReauditoria,
            ],
        );
    }

    public function actualizarEstadoRemediacion(int $idRemediacion, string $estado): void
    {
        $this->bd->procedimiento(
            'BEGIN pkg_indicadores.sp_actualizar_estado_remediacion(:id_remediacion, :estado); END;',
            ['id_remediacion' => $idRemediacion, 'estado' => $estado],
        );
    }

    // ── Lembas, el asistente ─────────────────────────────────────────────────

    /**
     * Un SELECT y no un procedimiento: esto no es un indicador que se pinte,
     * es el contador de un límite de uso — la misma categoría que
     * controlesEvaluados(). «Hoy» es el día del reloj de la base, que es el
     * mismo para todos los usuarios y el que usa el límite total.
     */
    public function consultasAsistenteHoy(?int $idUsuario = null): int
    {
        $sql = 'SELECT COUNT(*) AS total
                  FROM asistente_consulta
                 WHERE fecha >= TRUNC(SYSDATE)';
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
