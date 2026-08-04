<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatos;
use App\Models\Contratos\RepositorioCatalogo;
use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Proceso;

/**
 * Lee el instrumento desde las tablas dominio, proceso y control de Oracle.
 *
 * Implementa el mismo contrato que RepositorioInstrumentoArreglo, así que el
 * controlador y las vistas no se enteran del cambio: es exactamente el
 * escenario que anticipaba el comentario de config/instrumento-bd.php.
 *
 * DOS COSAS QUE CONVIENE SABER
 *
 * 1. Cuatro de los siete métodos del contrato se delegan al repositorio de
 *    arreglo (el "complemento"): meta(), escala(), marco() y referencias(). El
 *    esquema de Persona 2 modela el catálogo evaluable —lo que el auditor
 *    responde— pero no la escala de madurez, el marco normativo ni la
 *    bibliografía, que son texto fijo de la herramienta y no datos de
 *    auditoría. Mientras no existan esas tablas, siguen viniendo del archivo
 *    PHP. Es una decisión pendiente de acordar con Persona 2, no un descuido.
 *
 * 2. El ORDEN DE PRESENTACIÓN sale de la columna 'orden' de dominio y proceso.
 *    Sus claves naturales no sirven para ordenar: por 'clave' los dominios
 *    quedarían alfabéticos (accesos, almacenamiento, configuración...) en vez
 *    de en el orden del instrumento, y proceso.numero está documentado como
 *    "el número original del catálogo, no el orden de presentación".
 *
 *    Hasta la Fase 5 el orden se deducía del menor código de control de cada
 *    grupo, aprovechando que C-001..C-075 sí siguen el orden del instrumento.
 *    Eso dejó de servir al hacerse editable el catálogo: un control nuevo con
 *    código alto movería de sitio a todo su dominio. La columna lo fija.
 */
final class RepositorioInstrumentoOracle implements RepositorioCatalogo
{
    /** @var list<Dominio>|null */
    private ?array $dominios = null;

    /** @var list<Proceso>|null */
    private ?array $procesos = null;

    /** @var list<Control>|null */
    private ?array $controles = null;

    public function __construct(
        private readonly BaseDatos $bd,
        /**
         * Fuente de las secciones que no viven en la base. Se recibe ya
         * construido en vez de crearse aquí para no fijar la ruta del archivo
         * dentro de esta clase.
         */
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
        // Las vistas piden los dominios varias veces por petición (los tabs,
        // el tablero, el filtro). Se consulta una sola vez y se recuerda.
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
            'SELECT numero, clave_dominio, nombre, ancla, orden
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

        // enunciado, evidencia_esperada y pregunta son CLOB; BaseDatos los
        // devuelve ya convertidos a texto (OCI_RETURN_LOBS).
        $filas = $this->bd->consultar(
            'SELECT codigo, numero_proceso, referencia_iso,
                    enunciado, evidencia_esperada, pregunta
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
            'SELECT numero, clave_dominio, nombre, ancla, orden
               FROM proceso WHERE numero = :numero',
            ['numero' => $numero],
        );

        return $fila === null ? null : self::aProceso($fila);
    }

    public function crearProceso(Proceso $proceso): void
    {
        $this->bd->ejecutar(
            'INSERT INTO proceso (numero, clave_dominio, nombre, ancla, orden)
             VALUES (:numero, :dominio, :nombre, :ancla, :orden)',
            [
                'numero'  => $proceso->numero,
                'dominio' => $proceso->dominio,
                'nombre'  => $proceso->nombre,
                'ancla'   => $proceso->ancla,
                'orden'   => $proceso->orden,
            ],
        );

        $this->olvidarCache();
    }

    public function actualizarProceso(Proceso $proceso): void
    {
        $this->bd->ejecutar(
            'UPDATE proceso
                SET clave_dominio = :dominio, nombre = :nombre,
                    ancla = :ancla, orden = :orden
              WHERE numero = :numero',
            [
                'dominio' => $proceso->dominio,
                'nombre'  => $proceso->nombre,
                'ancla'   => $proceso->ancla,
                'orden'   => $proceso->orden,
                'numero'  => $proceso->numero,
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
                    enunciado, evidencia_esperada, pregunta
               FROM control WHERE codigo = :codigo',
            ['codigo' => $codigo],
        );

        return $fila === null ? null : self::aControl($fila);
    }

    /**
     * enunciado, evidencia_esperada y pregunta son CLOB, así que van por la
     * vía de los descriptores de LOB y no como parámetros normales.
     */
    public function crearControl(Control $control): void
    {
        $this->bd->ejecutar(
            'INSERT INTO control (codigo, numero_proceso, referencia_iso,
                                  enunciado, evidencia_esperada, pregunta)
             VALUES (:codigo, :proceso, :iso, :enunciado, :evidencia, :pregunta)',
            [
                'codigo'  => $control->id,
                'proceso' => $control->proceso,
                'iso'     => $control->iso,
            ],
            [
                'enunciado' => $control->enunciado,
                'evidencia' => $control->evidencia,
                'pregunta'  => $control->pregunta,
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
                    pregunta = :pregunta
              WHERE codigo = :codigo',
            [
                'proceso' => $control->proceso,
                'iso'     => $control->iso,
                'codigo'  => $control->id,
            ],
            [
                'enunciado' => $control->enunciado,
                'evidencia' => $control->evidencia,
                'pregunta'  => $control->pregunta,
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
        $fila = $this->bd->consultarUna('SELECT NVL(MAX(orden), 0) + 1 AS n FROM dominio');

        return (int) ($fila['n'] ?? 1);
    }

    public function siguienteOrdenProceso(): int
    {
        $fila = $this->bd->consultarUna('SELECT NVL(MAX(orden), 0) + 1 AS n FROM proceso');

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
        );
    }
}
