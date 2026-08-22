<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Conexión a Oracle y ejecución de sentencias (extensión oci8).
 *
 * Es el ÚNICO lugar del proyecto que llama a funciones oci_*. Los repositorios
 * piden "ejecutá este SQL con estos parámetros" y reciben arreglos de PHP; no
 * saben que existe un recurso de conexión ni tienen que revisar códigos de
 * error. Misma idea que Peticion con $_SERVER o Sesion con $_SESSION.
 *
 * Dos decisiones que conviene conocer:
 *
 * 1. La conexión es PEREZOSA: no se abre en el constructor sino en la primera
 *    consulta. Así el sitio público (que no toca la base) carga aunque Oracle
 *    esté apagado, y nadie del equipo queda bloqueado por no tener la base
 *    levantada para trabajar en una vista.
 *
 * 2. TODO parámetro se enlaza con oci_bind_by_name, nunca se concatena en la
 *    cadena SQL. Es lo que impide una inyección SQL, y de paso permite que
 *    Oracle reutilice el plan de ejecución de la sentencia.
 */
final class BaseDatos
{
    /** @var resource|null Recurso de conexión de oci8; null mientras no se use. */
    private $conexion = null;

    /** @param array{cadena: string, usuario: string, clave: string, charset?: string} $configuracion */
    public function __construct(private readonly array $configuracion)
    {
    }

    /**
     * Ejecuta un SELECT y devuelve todas las filas.
     *
     * @param array<string, scalar|null> $parametros Sin los dos puntos: ['id' => 7]
     * @return list<array<string, mixed>>
     */
    public function consultar(string $sql, array $parametros = []): array
    {
        $sentencia = $this->ejecutarSentencia($sql, $parametros, confirmar: false);
        $filas = $this->leerFilas($sentencia);
        oci_free_statement($sentencia);

        return $filas;
    }

    /**
     * Ejecuta un SELECT que devuelve como mucho una fila.
     *
     * @param array<string, scalar|null> $parametros
     * @return array<string, mixed>|null
     */
    public function consultarUna(string $sql, array $parametros = []): ?array
    {
        return $this->consultar($sql, $parametros)[0] ?? null;
    }

    /**
     * Ejecuta un INSERT, UPDATE, DELETE o MERGE. Devuelve las filas afectadas.
     *
     * Los parámetros que apuntan a columnas CLOB van en $clobs, no en
     * $parametros. Enlazar un texto largo como si fuera VARCHAR2 revienta con
     * "ORA-01461: can bind a LONG value only for insert into a LONG column" en
     * cuanto pasa de 4000 bytes, y el hallazgo que escribe un auditor en un
     * textarea los pasa sin esfuerzo. Con $clobs se enlaza un descriptor de
     * LOB, que no tiene ese límite.
     *
     * $confirmar en false NO hace COMMIT: se usa dentro de una transacción
     * abierta con iniciarTransaccion(), para que varias sentencias se
     * confirmen o reviertan juntas con confirmarTransaccion() / revertirTransaccion().
     * Por omisión queda en true — el comportamiento de siempre no cambia para
     * quien no pide lo contrario.
     *
     * @param array<string, scalar|null> $parametros
     * @param array<string, string|null> $clobs
     */
    public function ejecutar(string $sql, array $parametros = [], array $clobs = [], bool $confirmar = true): int
    {
        if ($clobs === []) {
            $sentencia = $this->ejecutarSentencia($sql, $parametros, $confirmar);
            $afectadas = oci_num_rows($sentencia);
            oci_free_statement($sentencia);

            return $afectadas === false ? 0 : $afectadas;
        }

        return $this->ejecutarConClobs($sql, $parametros, $clobs, $confirmar);
    }

    /**
     * Ejecuta un INSERT y devuelve el identificador generado por Oracle.
     *
     * Las tablas del esquema usan GENERATED ALWAYS AS IDENTITY, así que el id
     * no se conoce hasta después del INSERT. La forma de recuperarlo en Oracle
     * es la cláusula RETURNING, que el SQL debe traer ya escrita:
     *
     *     INSERT INTO auditoria (...) VALUES (...) RETURNING id_auditoria INTO :id
     *
     * $confirmar en false no hace COMMIT — ver la nota de ejecutar().
     *
     * @param array<string, scalar|null> $parametros
     */
    public function insertar(string $sql, array $parametros = [], string $parametroId = 'id', bool $confirmar = true): int
    {
        $sentencia = oci_parse($this->conexion(), $sql);

        if ($sentencia === false) {
            throw $this->error('No se pudo preparar el INSERT', $this->conexion());
        }

        // Los valores viven en un arreglo local porque oci_bind_by_name enlaza
        // POR REFERENCIA: necesita una variable, no el resultado de foreach.
        $valores = [];

        foreach ($parametros as $nombre => $valor) {
            $clave = ':' . ltrim((string) $nombre, ':');
            $valores[$clave] = $valor;

            oci_bind_by_name($sentencia, $clave, $valores[$clave]);
        }

        $id = 0;
        oci_bind_by_name($sentencia, ':' . ltrim($parametroId, ':'), $id, 32, \SQLT_INT);

        $modo = $confirmar ? \OCI_COMMIT_ON_SUCCESS : \OCI_NO_AUTO_COMMIT;

        if (!oci_execute($sentencia, $modo)) {
            throw $this->error('Falló el INSERT', $sentencia);
        }

        oci_free_statement($sentencia);

        return (int) $id;
    }

    /**
     * Llama a un procedimiento almacenado que no devuelve cursor.
     *
     * Se usa para pkg_indicadores.calcular_riesgo_auditoria, que no consulta:
     * recalcula y persiste los resultados de riesgo de una auditoría.
     *
     *     $bd->procedimiento('BEGIN pkg_indicadores.calcular_riesgo_auditoria(:id); END;', ['id' => 7]);
     *
     * @param array<string, scalar|null> $parametros
     */
    public function procedimiento(string $bloque, array $parametros = []): void
    {
        $sentencia = $this->ejecutarSentencia($bloque, $parametros, confirmar: true);
        oci_free_statement($sentencia);
    }

    /**
     * Llama a un procedimiento con un parámetro OUT SYS_REFCURSOR y devuelve
     * sus filas.
     *
     * Es la vía por la que la aplicación obtiene TODOS los indicadores: el
     * profesor pidió que los reportes salgan de procedimientos almacenados y
     * no de SELECT sueltos desde PHP. El bloque debe nombrar el cursor :cursor:
     *
     *     BEGIN pkg_indicadores.sp_cumplimiento_dominio(:id, :cursor); END;
     *
     * Son dos ejecuciones: la del bloque PL/SQL (que abre el cursor) y la del
     * cursor mismo (que lo posiciona para leer). Así funciona oci8.
     *
     * @param array<string, scalar|null> $parametros
     * @return list<array<string, mixed>>
     */
    public function cursor(string $bloque, array $parametros = []): array
    {
        $conexion = $this->conexion();
        $sentencia = oci_parse($conexion, $bloque);

        if ($sentencia === false) {
            throw $this->error('No se pudo preparar la llamada al procedimiento', $conexion);
        }

        $valores = [];

        foreach ($parametros as $nombre => $valor) {
            $clave = ':' . ltrim((string) $nombre, ':');
            $valores[$clave] = $valor;

            oci_bind_by_name($sentencia, $clave, $valores[$clave]);
        }

        $cursor = oci_new_cursor($conexion);
        oci_bind_by_name($sentencia, ':cursor', $cursor, -1, \OCI_B_CURSOR);

        if (!oci_execute($sentencia, \OCI_NO_AUTO_COMMIT)) {
            throw $this->error('Falló la llamada al procedimiento', $sentencia);
        }

        if (!oci_execute($cursor, \OCI_NO_AUTO_COMMIT)) {
            throw $this->error('El procedimiento no devolvió un cursor legible', $cursor);
        }

        $filas = $this->leerFilas($cursor);

        oci_free_statement($cursor);
        oci_free_statement($sentencia);

        return $filas;
    }

    /**
     * Comprueba que la base responde. La usa el diagnóstico de arranque.
     */
    public function disponible(): bool
    {
        try {
            $this->consultar('SELECT 1 AS uno FROM dual');

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    // ── Transacciones ────────────────────────────────────────────────────────
    //
    // Por omisión, ejecutar() e insertar() confirman cada sentencia por su
    // cuenta (OCI_COMMIT_ON_SUCCESS) — es lo que necesita casi todo el
    // repositorio, una sentencia por operación. Cuando varias sentencias deben
    // vivir o morir juntas —guardarMuestra() del monitor inserta una cabecera,
    // N mediciones y opcionalmente un índice con sus causas, y "una muestra a
    // medio guardar es peor que ninguna" (contrato-repositorio-monitor.md §3)—
    // se abre una transacción y cada llamada intermedia pasa confirmar: false.

    /**
     * Abre una transacción. oci8 no tiene un "BEGIN" explícito: la transacción
     * empieza de hecho en la primera sentencia sin confirmar. Este método solo
     * fuerza que la conexión ya esté abierta, para que un fallo de conexión no
     * aparezca a mitad de una transacción que el llamador cree ya iniciada.
     */
    public function iniciarTransaccion(): void
    {
        $this->conexion();
    }

    public function confirmarTransaccion(): void
    {
        if (!oci_commit($this->conexion())) {
            throw $this->error('No se pudo confirmar la transacción', $this->conexion());
        }
    }

    public function revertirTransaccion(): void
    {
        oci_rollback($this->conexion());
    }

    // ── Interno ──────────────────────────────────────────────────────────────

    /**
     * Devuelve la conexión, abriéndola la primera vez que se pide.
     *
     * @return resource
     */
    private function conexion()
    {
        if ($this->conexion !== null) {
            return $this->conexion;
        }

        if (!function_exists('oci_connect')) {
            throw new \RuntimeException(
                'La extensión oci8 no está instalada. Reconstruya la imagen con '
                . '"docker compose build web": el Dockerfile la compila contra el '
                . 'Oracle Instant Client.'
            );
        }

        $conexion = @oci_connect(
            $this->configuracion['usuario'],
            $this->configuracion['clave'],
            $this->configuracion['cadena'],
            $this->configuracion['charset'] ?? 'AL32UTF8',
        );

        if ($conexion === false) {
            $error = oci_error();

            throw new \RuntimeException(
                'No se pudo conectar a Oracle (' . $this->configuracion['cadena'] . '): '
                . ($error['message'] ?? 'error desconocido')
            );
        }

        return $this->conexion = $conexion;
    }

    /**
     * Prepara, enlaza y ejecuta en un solo alcance.
     *
     * El enlace y la ejecución van juntos a propósito: oci_bind_by_name guarda
     * una referencia a la variable, así que separar "preparar" de "ejecutar" en
     * dos métodos dejaría los valores enlazados a variables ya fuera de alcance.
     *
     * El modo se recibe como booleano y no como la constante OCI_* ya
     * resuelta: las constantes de oci8 solo existen si la extensión está
     * cargada, y PHP evalúa los argumentos ANTES de entrar al método. Pasarlas
     * desde fuera hacía que, sin la extensión instalada, reventara con
     * "Undefined constant OCI_NO_AUTO_COMMIT" en vez de con el mensaje que
     * explica qué falta. Aquí ya pasamos por conexion(), que avisa primero.
     *
     * @param array<string, scalar|null> $parametros
     * @return resource
     */
    private function ejecutarSentencia(string $sql, array $parametros, bool $confirmar)
    {
        $conexion = $this->conexion();
        $modo = $confirmar ? \OCI_COMMIT_ON_SUCCESS : \OCI_NO_AUTO_COMMIT;
        $sentencia = oci_parse($conexion, $sql);

        if ($sentencia === false) {
            throw $this->error('No se pudo preparar la sentencia', $conexion);
        }

        $valores = [];

        foreach ($parametros as $nombre => $valor) {
            $clave = ':' . ltrim((string) $nombre, ':');
            $valores[$clave] = $valor;

            if (!oci_bind_by_name($sentencia, $clave, $valores[$clave])) {
                throw $this->error("No se pudo enlazar el parámetro {$clave}", $sentencia);
            }
        }

        if (!oci_execute($sentencia, $modo)) {
            throw $this->error('Falló la ejecución de la sentencia', $sentencia);
        }

        return $sentencia;
    }

    /**
     * Variante de ejecutar() que enlaza descriptores de LOB.
     *
     * El texto se escribe en el descriptor ANTES de ejecutar (writeTemporary):
     * en ese momento el descriptor ya está enlazado a la sentencia, así que al
     * ejecutar Oracle encuentra el contenido esperándolo. Los descriptores se
     * liberan siempre, incluso si la sentencia falla, para no dejar LOBs
     * temporales colgando en la sesión.
     *
     * @param array<string, scalar|null> $parametros
     * @param array<string, string|null> $clobs
     */
    private function ejecutarConClobs(string $sql, array $parametros, array $clobs, bool $confirmar = true): int
    {
        $conexion = $this->conexion();
        $sentencia = oci_parse($conexion, $sql);

        if ($sentencia === false) {
            throw $this->error('No se pudo preparar la sentencia', $conexion);
        }

        $valores = [];

        foreach ($parametros as $nombre => $valor) {
            $clave = ':' . ltrim((string) $nombre, ':');
            $valores[$clave] = $valor;

            oci_bind_by_name($sentencia, $clave, $valores[$clave]);
        }

        $descriptores = [];

        try {
            foreach ($clobs as $nombre => $texto) {
                $clave = ':' . ltrim((string) $nombre, ':');

                // Un campo que el auditor dejó en blanco se guarda como NULL,
                // no como un LOB vacío: así "sin hallazgo" y "hallazgo vacío"
                // no son dos estados distintos en la base. Para eso basta un
                // enlace normal, sin descriptor.
                if ($texto === null || $texto === '') {
                    $valores[$clave] = null;
                    oci_bind_by_name($sentencia, $clave, $valores[$clave]);
                    continue;
                }

                $descriptor = oci_new_descriptor($conexion, \OCI_D_LOB);

                if ($descriptor === false) {
                    throw $this->error("No se pudo crear el LOB de {$clave}", $conexion);
                }

                $descriptores[$clave] = $descriptor;
                oci_bind_by_name($sentencia, $clave, $descriptores[$clave], -1, \OCI_B_CLOB);
                $descriptor->writeTemporary($texto, \OCI_TEMP_CLOB);
            }

            $modo = $confirmar ? \OCI_COMMIT_ON_SUCCESS : \OCI_NO_AUTO_COMMIT;

            if (!oci_execute($sentencia, $modo)) {
                throw $this->error('Falló la ejecución de la sentencia', $sentencia);
            }

            $afectadas = oci_num_rows($sentencia);

            return $afectadas === false ? 0 : $afectadas;
        } finally {
            foreach ($descriptores as $descriptor) {
                $descriptor->free();
            }

            oci_free_statement($sentencia);
        }
    }

    /**
     * Vuelca un recurso de resultados a arreglos de PHP.
     *
     * Dos normalizaciones que evitan sorpresas río abajo:
     *   - Oracle devuelve los nombres de columna en MAYÚSCULAS; se pasan a
     *     minúsculas para que los repositorios lean $fila['codigo'].
     *   - OCI_RETURN_LOBS trae el contenido de las columnas CLOB como texto.
     *     Sin esto, control.enunciado y control.pregunta llegarían como
     *     descriptores de LOB en vez de cadenas.
     *
     * @param resource $sentencia
     * @return list<array<string, mixed>>
     */
    private function leerFilas($sentencia): array
    {
        $filas = [];
        $banderas = \OCI_ASSOC | \OCI_RETURN_NULLS | \OCI_RETURN_LOBS;

        while (($fila = oci_fetch_array($sentencia, $banderas)) !== false) {
            $filas[] = array_change_key_case($fila, \CASE_LOWER);
        }

        return $filas;
    }

    /**
     * Construye la excepción a partir del último error de oci8.
     *
     * @param resource $recurso
     */
    private function error(string $contexto, $recurso): \RuntimeException
    {
        $error = oci_error($recurso);
        $detalle = $error['message'] ?? 'sin detalle';

        return new \RuntimeException("{$contexto}: {$detalle}");
    }
}
