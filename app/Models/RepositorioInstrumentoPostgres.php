<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatosPostgres;
use App\Models\Contratos\RepositorioCatalogo;
use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Proceso;

/**
 * Lee el instrumento desde las tablas dominio, proceso y control de
 * PostgreSQL.
 *
 * Ruta ALTERNATIVA a RepositorioInstrumentoOracle.php — mismo contrato
 * (RepositorioCatalogo), misma delegación de meta()/escala()/marco()/
 * referencias() al complemento de arreglo (ver esa clase para el porqué),
 * mismo caché en memoria invalidado en cada escritura. Dos diferencias con el
 * SQL, nada más:
 *
 * - NVL(MAX(orden), 0) se escribe COALESCE(MAX(orden), 0): es el nombre
 *   estándar de la misma función, que Oracle además soporta por alias pero
 *   Postgres no conoce NVL en absoluto.
 * - enunciado, evidencia_esperada y pregunta no necesitan el mecanismo de
 *   $clobs: son TEXT y se enlazan como cualquier otro parámetro.
 */
final class RepositorioInstrumentoPostgres implements RepositorioCatalogo
{
    /** @var list<Dominio>|null */
    private ?array $dominios = null;

    /** @var list<Proceso>|null */
    private ?array $procesos = null;

    /** @var list<Control>|null */
    private ?array $controles = null;

    public function __construct(
        private readonly BaseDatosPostgres $bd,
        private readonly RepositorioInstrumento $complemento,
    ) {
    }

    /** @return array{titulo: string, descripcion: string, version: string} */
    public function meta(): array
    {
        return $this->complemento->meta();
    }

    /** @return list<Dominio> */
    public function dominios(): array
    {
        if ($this->dominios !== null) {
            return $this->dominios;
        }

        $filas = $this->bd->consultar(
            'SELECT clave, nombre, nombre_corto, descripcion, orden
               FROM dominio
              ORDER BY orden, clave'
        );

        return $this->dominios = array_map(
            static fn (array $fila): Dominio => self::aDominio($fila),
            $filas,
        );
    }

    /** @return list<Proceso> */
    public function procesos(): array
    {
        if ($this->procesos !== null) {
            return $this->procesos;
        }

        $filas = $this->bd->consultar(
            'SELECT numero, clave_dominio, nombre, ancla, orden,
                    relacion_confidencialidad, relacion_integridad, relacion_disponibilidad
               FROM proceso
              ORDER BY orden, numero'
        );

        return $this->procesos = array_map(
            static fn (array $fila): Proceso => self::aProceso($fila),
            $filas,
        );
    }

    /** @return list<Control> */
    public function controles(): array
    {
        if ($this->controles !== null) {
            return $this->controles;
        }

        $filas = $this->bd->consultar(
            'SELECT codigo, numero_proceso, referencia_iso,
                    enunciado, evidencia_esperada, pregunta, peso
               FROM control
              ORDER BY codigo'
        );

        return $this->controles = array_map(
            static fn (array $fila): Control => self::aControl($fila),
            $filas,
        );
    }

    /** @return list<array{nivel: int, nombre: string, descripcion: string}> */
    public function escala(): array
    {
        return $this->complemento->escala();
    }

    /** @return list<array{norma: string, titulo: string, aporte: string}> */
    public function marco(): array
    {
        return $this->complemento->marco();
    }

    /** @return list<array{titulo: string, fuente: string, enlace: string}> */
    public function referencias(): array
    {
        return $this->complemento->referencias();
    }

    // ── Administración: dominios ─────────────────────────────────────────────

    public function dominio(string $clave): ?Dominio
    {
        $fila = $this->bd->consultarUna(
            'SELECT clave, nombre, nombre_corto, descripcion, orden
               FROM dominio WHERE clave = :clave',
            ['clave' => $clave],
        );

        return $fila === null ? null : self::aDominio($fila);
    }

    public function crearDominio(Dominio $dominio): void
    {
        $this->bd->ejecutar(
            'INSERT INTO dominio (clave, nombre, nombre_corto, descripcion, orden)
             VALUES (:clave, :nombre, :corto, :descripcion, :orden)',
            [
                'clave'       => $dominio->clave,
                'nombre'      => $dominio->nombre,
                'corto'       => $dominio->corto,
                'descripcion' => $dominio->descripcion,
                'orden'       => $dominio->orden,
            ],
        );

        $this->olvidarCache();
    }

    public function actualizarDominio(Dominio $dominio): void
    {
        $this->bd->ejecutar(
            'UPDATE dominio
                SET nombre = :nombre, nombre_corto = :corto,
                    descripcion = :descripcion, orden = :orden
              WHERE clave = :clave',
            [
                'nombre'      => $dominio->nombre,
                'corto'       => $dominio->corto,
                'descripcion' => $dominio->descripcion,
                'orden'       => $dominio->orden,
                'clave'       => $dominio->clave,
            ],
        );

        $this->olvidarCache();
    }

    public function eliminarDominio(string $clave): void
    {
        $this->bd->ejecutar('DELETE FROM dominio WHERE clave = :clave', ['clave' => $clave]);

        $this->olvidarCache();
    }

    // ── Administración: procesos ─────────────────────────────────────────────

    public function proceso(int $numero): ?Proceso
    {
        $fila = $this->bd->consultarUna(
            'SELECT numero, clave_dominio, nombre, ancla, orden,
                    relacion_confidencialidad, relacion_integridad, relacion_disponibilidad
               FROM proceso WHERE numero = :numero',
            ['numero' => $numero],
        );

        return $fila === null ? null : self::aProceso($fila);
    }

    public function crearProceso(Proceso $proceso): void
    {
        $this->bd->ejecutar(
            'INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden,
                                  relacion_confidencialidad, relacion_integridad, relacion_disponibilidad)
             VALUES (:numero, :dominio, :nombre, :ancla, :orden,
                     :relacion_c, :relacion_i, :relacion_d)',
            [
                'numero'      => $proceso->numero,
                'dominio'     => $proceso->dominio,
                'nombre'      => $proceso->nombre,
                'ancla'       => $proceso->ancla,
                'orden'       => $proceso->orden,
                'relacion_c'  => $proceso->relacionConfidencialidad,
                'relacion_i'  => $proceso->relacionIntegridad,
                'relacion_d'  => $proceso->relacionDisponibilidad,
            ],
        );

        $this->olvidarCache();
    }

    public function actualizarProceso(Proceso $proceso): void
    {
        $this->bd->ejecutar(
            'UPDATE proceso
                SET clave_dominio = :dominio, nombre = :nombre,
                    ancla = :ancla, orden = :orden,
                    relacion_confidencialidad = :relacion_c,
                    relacion_integridad = :relacion_i,
                    relacion_disponibilidad = :relacion_d
              WHERE numero = :numero',
            [
                'dominio'    => $proceso->dominio,
                'nombre'     => $proceso->nombre,
                'ancla'      => $proceso->ancla,
                'orden'      => $proceso->orden,
                'relacion_c' => $proceso->relacionConfidencialidad,
                'relacion_i' => $proceso->relacionIntegridad,
                'relacion_d' => $proceso->relacionDisponibilidad,
                'numero'     => $proceso->numero,
            ],
        );

        $this->olvidarCache();
    }

    public function eliminarProceso(int $numero): void
    {
        $this->bd->ejecutar('DELETE FROM proceso WHERE numero = :numero', ['numero' => $numero]);

        $this->olvidarCache();
    }

    // ── Administración: controles ────────────────────────────────────────────

    public function control(string $codigo): ?Control
    {
        $fila = $this->bd->consultarUna(
            'SELECT codigo, numero_proceso, referencia_iso,
                    enunciado, evidencia_esperada, pregunta, peso
               FROM control WHERE codigo = :codigo',
            ['codigo' => $codigo],
        );

        return $fila === null ? null : self::aControl($fila);
    }

    public function crearControl(Control $control): void
    {
        $this->bd->ejecutar(
            'INSERT INTO control (codigo, numero_proceso, referencia_iso,
                                  enunciado, evidencia_esperada, pregunta, peso)
             VALUES (:codigo, :proceso, :iso, :enunciado, :evidencia, :pregunta, :peso)',
            [
                'codigo'    => $control->id,
                'proceso'   => $control->proceso,
                'iso'       => $control->iso,
                'enunciado' => $control->enunciado,
                'evidencia' => $control->evidencia,
                'pregunta'  => $control->pregunta,
                'peso'      => $control->peso,
            ],
        );

        $this->olvidarCache();
    }

    public function actualizarControl(Control $control): void
    {
        $this->bd->ejecutar(
            'UPDATE control
                SET numero_proceso = :proceso, referencia_iso = :iso,
                    enunciado = :enunciado, evidencia_esperada = :evidencia,
                    pregunta = :pregunta, peso = :peso
              WHERE codigo = :codigo',
            [
                'proceso'   => $control->proceso,
                'iso'       => $control->iso,
                'enunciado' => $control->enunciado,
                'evidencia' => $control->evidencia,
                'pregunta'  => $control->pregunta,
                'peso'      => $control->peso,
                'codigo'    => $control->id,
            ],
        );

        $this->olvidarCache();
    }

    public function eliminarControl(string $codigo): void
    {
        $this->bd->ejecutar('DELETE FROM control WHERE codigo = :codigo', ['codigo' => $codigo]);

        $this->olvidarCache();
    }

    // ── Comprobaciones antes de borrar ───────────────────────────────────────

    public function procesosDeDominio(string $clave): int
    {
        return $this->contar('SELECT COUNT(*) AS n FROM proceso WHERE clave_dominio = :v', $clave);
    }

    public function controlesDeProceso(int $numero): int
    {
        return $this->contar('SELECT COUNT(*) AS n FROM control WHERE numero_proceso = :v', $numero);
    }

    public function evaluacionesDeControl(string $codigo): int
    {
        return $this->contar('SELECT COUNT(*) AS n FROM evaluacion_control WHERE codigo_control = :v', $codigo);
    }

    /** @return array<string, int> */
    public function evaluacionesPorControl(): array
    {
        $filas = $this->bd->consultar(
            'SELECT codigo_control, COUNT(*) AS n
               FROM evaluacion_control
              GROUP BY codigo_control'
        );

        $conteo = [];

        foreach ($filas as $fila) {
            $conteo[(string) $fila['codigo_control']] = (int) $fila['n'];
        }

        return $conteo;
    }

    public function siguienteOrdenDominio(): int
    {
        $fila = $this->bd->consultarUna('SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM dominio');

        return (int) ($fila['n'] ?? 1);
    }

    public function siguienteOrdenProceso(): int
    {
        $fila = $this->bd->consultarUna('SELECT COALESCE(MAX(orden), 0) + 1 AS n FROM proceso');

        return (int) ($fila['n'] ?? 1);
    }

    // ── Interno ──────────────────────────────────────────────────────────────

    private function contar(string $sql, string|int $valor): int
    {
        $fila = $this->bd->consultarUna($sql, ['v' => $valor]);

        return (int) ($fila['n'] ?? 0);
    }

    /**
     * Descarta lo memorizado tras cada escritura.
     *
     * Sin esto, guardar un control y volver a listarlos en la misma petición
     * devolvería la lista anterior, y el administrador creería que su cambio
     * no se guardó.
     */
    private function olvidarCache(): void
    {
        $this->dominios = null;
        $this->procesos = null;
        $this->controles = null;
    }

    /** @param array<string, mixed> $fila */
    private static function aDominio(array $fila): Dominio
    {
        return new Dominio(
            clave:       (string) $fila['clave'],
            nombre:      (string) $fila['nombre'],
            corto:       (string) ($fila['nombre_corto'] ?? $fila['nombre']),
            descripcion: (string) ($fila['descripcion'] ?? ''),
            orden:       (int) ($fila['orden'] ?? 0),
        );
    }

    /** @param array<string, mixed> $fila */
    private static function aProceso(array $fila): Proceso
    {
        return new Proceso(
            numero:  (int) $fila['numero'],
            dominio: (string) $fila['clave_dominio'],
            nombre:  (string) $fila['nombre'],
            ancla:   (string) ($fila['ancla'] ?? ''),
            orden:   (int) ($fila['orden'] ?? 0),
            relacionConfidencialidad: self::valorONulo($fila['relacion_confidencialidad'] ?? null),
            relacionIntegridad:       self::valorONulo($fila['relacion_integridad'] ?? null),
            relacionDisponibilidad:   self::valorONulo($fila['relacion_disponibilidad'] ?? null),
        );
    }

    /** @param array<string, mixed> $fila */
    private static function aControl(array $fila): Control
    {
        return new Control(
            id:        (string) $fila['codigo'],
            proceso:   (int) $fila['numero_proceso'],
            iso:       (string) ($fila['referencia_iso'] ?? ''),
            enunciado: (string) ($fila['enunciado'] ?? ''),
            evidencia: (string) ($fila['evidencia_esperada'] ?? ''),
            pregunta:  (string) ($fila['pregunta'] ?? ''),
            peso:      (string) ($fila['peso'] ?? Control::PESO_MEDIA),
        );
    }

    private static function valorONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }
}
