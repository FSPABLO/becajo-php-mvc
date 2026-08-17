<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Conexión a PostgreSQL y ejecución de sentencias (PDO_PGSQL).
 *
 * Ruta ALTERNATIVA a BaseDatos.php (Oracle/oci8). No implementa ninguna
 * interfaz compartida con esa clase a propósito: son dos motores con formas
 * de trabajar distintas (Postgres no tiene SYS_REFCURSOR, no necesita
 * descriptores de LOB, y RETURNING no exige un parámetro OUT aparte), así
 * que forzarlas a un contrato común habría terminado pareciéndose más a
 * Oracle que a Postgres. Ver RepositorioAuditoriasPostgres y
 * RepositorioInstrumentoPostgres, que son quienes usan esta clase.
 *
 * Misma decisión de diseño que BaseDatos.php en lo que sí aplica:
 *
 * 1. La conexión es PEREZOSA: no se abre en el constructor sino en la
 *    primera consulta.
 *
 * 2. TODO parámetro se enlaza por nombre (:nombre), nunca se concatena en
 *    la cadena SQL.
 *
 * Tres cosas que en Postgres son más simples que en Oracle, y por eso esta
 * clase es más corta que BaseDatos.php:
 *
 * - No hay CLOB aparte: TEXT no tiene el límite de 4000 bytes que sí tiene
 *   VARCHAR2, así que no hace falta un mecanismo especial para hallazgos o
 *   enunciados largos. ejecutar() no necesita un parámetro $clobs.
 *
 * - No hay SYS_REFCURSOR: un procedimiento que en Oracle "abre un cursor"
 *   aquí es una FUNCTION que declara RETURNS TABLE(...), y se consulta con
 *   un SELECT normal — exactamente el mismo consultar() que cualquier otra
 *   lectura. No existe un método cursor() aparte.
 *
 * - RETURNING no necesita un bind :id de salida: Postgres devuelve la fila
 *   generada como si fuera un SELECT, así que insertar() simplemente lee la
 *   primera columna del resultado.
 */
final class BaseDatosPostgres
{
    private ?\PDO $conexion = null;

    /** @param array{cadena: string, usuario: string, clave: string} $configuracion */
    public function __construct(private readonly array $configuracion)
    {
    }

    /**
     * Ejecuta un SELECT (o una función que RETURNS TABLE) y devuelve todas
     * las filas.
     *
     * @param array<string, scalar|null> $parametros Sin los dos puntos: ['id' => 7]
     * @return list<array<string, mixed>>
     */
    public function consultar(string $sql, array $parametros = []): array
    {
        $sentencia = $this->prepararYEjecutar($sql, $parametros);

        /** @var list<array<string, mixed>> $filas */
        $filas = $sentencia->fetchAll(\PDO::FETCH_ASSOC);

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
     * Ejecuta un INSERT, UPDATE, DELETE o MERGE-equivalente (Postgres usa
     * INSERT ... ON CONFLICT en vez de MERGE). Devuelve las filas afectadas.
     *
     * @param array<string, scalar|null> $parametros
     */
    public function ejecutar(string $sql, array $parametros = []): int
    {
        return $this->prepararYEjecutar($sql, $parametros)->rowCount();
    }

    /**
     * Ejecuta un INSERT y devuelve el identificador generado.
     *
     * El SQL debe traer su propio RETURNING, con el nombre de columna que
     * corresponda — a diferencia de Oracle, aquí no hace falta declarar un
     * parámetro :id aparte:
     *
     *     INSERT INTO usuario (...) VALUES (...) RETURNING id_usuario
     *
     * @param array<string, scalar|null> $parametros
     */
    public function insertar(string $sql, array $parametros = []): int
    {
        $sentencia = $this->prepararYEjecutar($sql, $parametros);
        $id = $sentencia->fetchColumn();

        return $id === false ? 0 : (int) $id;
    }

    /**
     * Llama a un PROCEDURE (CALL nombre(:parametro, ...)). No devuelve
     * filas: los procedimientos que sí devuelven datos son FUNCTIONs que se
     * leen con consultar(), no con este método.
     *
     * @param array<string, scalar|null> $parametros
     */
    public function procedimiento(string $sql, array $parametros = []): void
    {
        $this->prepararYEjecutar($sql, $parametros);
    }

    /**
     * Comprueba que la base responde. La usa el diagnóstico de arranque.
     */
    public function disponible(): bool
    {
        try {
            $this->consultar('SELECT 1 AS uno');

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    // ── Interno ──────────────────────────────────────────────────────────────

    private function conexion(): \PDO
    {
        if ($this->conexion !== null) {
            return $this->conexion;
        }

        if (!\extension_loaded('pdo_pgsql')) {
            throw new \RuntimeException(
                'La extensión pdo_pgsql no está instalada. Reconstruya la imagen con '
                . '"docker compose -f docker-compose.postgres.yml build web".'
            );
        }

        try {
            $this->conexion = new \PDO(
                'pgsql:' . $this->configuracion['cadena'],
                $this->configuracion['usuario'],
                $this->configuracion['clave'],
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    // Prepara del lado del servidor: así "::" (los CAST de
                    // Postgres, como en el ::NUMERIC de los procedimientos)
                    // nunca se confunden con un marcador de parámetro. Con
                    // las preparaciones emuladas de PDO sí puede pasar.
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ],
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException(
                'No se pudo conectar a PostgreSQL (' . $this->configuracion['cadena'] . '): '
                . $e->getMessage()
            );
        }

        return $this->conexion;
    }

    /**
     * Prepara, enlaza por nombre y ejecuta en un solo paso.
     *
     * @param array<string, scalar|null> $parametros
     */
    private function prepararYEjecutar(string $sql, array $parametros): \PDOStatement
    {
        try {
            $sentencia = $this->conexion()->prepare($sql);

            foreach ($parametros as $nombre => $valor) {
                $clave = ':' . ltrim((string) $nombre, ':');
                $sentencia->bindValue($clave, $valor);
            }

            $sentencia->execute();
        } catch (\PDOException $e) {
            throw new \RuntimeException('Falló la sentencia SQL: ' . $e->getMessage());
        }

        return $sentencia;
    }
}
