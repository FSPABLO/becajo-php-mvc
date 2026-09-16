<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\BaseDatos;
use App\Models\Contratos\RepositorioCatalogo;
use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Estandar;
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
 * 1. meta(), marco() y referencias() se delegan al repositorio de arreglo
 *    (el "complemento"): son texto fijo de la herramienta, sin tabla. La
 *    escala sí vive en la base desde multinorma (nivel_madurez), porque cada
 *    norma tiene la suya.
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
    /** @var array<string, list<Dominio>> Por norma. */
    private array $dominios = [];

    /** @var array<string, list<Proceso>> */
    private array $procesos = [];

    /** @var array<string, list<Control>> */
    private array $controles = [];

    /** @var list<Estandar>|null */
    private ?array $estandares = null;

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

    /** @return list<Estandar> */
    public function estandares(): array
    {
        if ($this->estandares !== null) {
            return $this->estandares;
        }

        $filas = $this->bd->consultar(
            'SELECT codigo, nombre, version, organismo, escala_niveles, orden, modo_evaluacion
               FROM estandar
              ORDER BY orden, codigo'
        );

        return $this->estandares = array_map(
            static fn (array $fila): Estandar => Estandar::desdeArreglo($fila),
            $filas,
        );
    }

    /** @return list<Dominio> */
    public function dominios(string $estandar = Estandar::ISO): array
    {
        // Las vistas piden los dominios varias veces por petición (los tabs,
        // el tablero, el filtro). Se consulta una sola vez y se recuerda.
        if (isset($this->dominios[$estandar])) {
            return $this->dominios[$estandar];
        }

        $filas = $this->bd->consultar(
            'SELECT clave, nombre, nombre_corto, descripcion, orden
               FROM dominio
              WHERE codigo_estandar = :estandar
              ORDER BY orden, clave',
            ['estandar' => $estandar],
        );

        return $this->dominios[$estandar] = array_map(
            static fn (array $fila): Dominio => self::aDominio($fila),
            $filas,
        );
    }

    /** @return list<Proceso> */
    public function procesos(string $estandar = Estandar::ISO): array
    {
        if (isset($this->procesos[$estandar])) {
            return $this->procesos[$estandar];
        }

        $filas = $this->bd->consultar(
            'SELECT p.numero, p.clave_dominio, p.nombre, p.ancla, p.orden,
                    p.relacion_confidencialidad, p.relacion_integridad, p.relacion_disponibilidad
               FROM proceso p
               JOIN dominio d ON d.clave = p.clave_dominio
              WHERE d.codigo_estandar = :estandar
              ORDER BY p.orden, p.numero',
            ['estandar' => $estandar],
        );

        return $this->procesos[$estandar] = array_map(
            static fn (array $fila): Proceso => self::aProceso($fila),
            $filas,
        );
    }

    /** @return list<Control> */
    public function controles(string $estandar = Estandar::ISO): array
    {
        if (isset($this->controles[$estandar])) {
            return $this->controles[$estandar];
        }

        // enunciado, evidencia_esperada y pregunta son CLOB; BaseDatos los
        // devuelve ya convertidos a texto (OCI_RETURN_LOBS).
        $filas = $this->bd->consultar(
            'SELECT c.codigo, c.numero_proceso, c.referencia_iso,
                    c.enunciado, c.evidencia_esperada, c.pregunta, c.peso
               FROM control c
               JOIN proceso p ON p.numero = c.numero_proceso
               JOIN dominio d ON d.clave = p.clave_dominio
              WHERE d.codigo_estandar = :estandar
              ORDER BY c.codigo',
            ['estandar' => $estandar],
        );

        return $this->controles[$estandar] = array_map(
            static fn (array $fila): Control => self::aControl($fila),
            $filas,
        );
    }

    /** @return list<array{nivel: int, nombre: string, descripcion: string}> */
    public function escala(string $estandar = Estandar::ISO): array
    {
        $filas = $this->bd->consultar(
            'SELECT nivel, nombre, descripcion
               FROM nivel_madurez
              WHERE codigo_estandar = :estandar
              ORDER BY nivel',
            ['estandar' => $estandar],
        );

        return array_map(
            static fn (array $fila): array => [
                'nivel'       => (int) $fila['nivel'],
                'nombre'      => (string) $fila['nombre'],
                'descripcion' => (string) $fila['descripcion'],
            ],
            $filas,
        );
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

    /**
     * enunciado, evidencia_esperada y pregunta son CLOB, así que van por la
     * vía de los descriptores de LOB y no como parámetros normales.
     */
    public function crearControl(Control $control): void
    {
        $this->bd->ejecutar(
            'INSERT INTO control (codigo, numero_proceso, referencia_iso,
                                  enunciado, evidencia_esperada, pregunta, peso)
             VALUES (:codigo, :proceso, :iso, :enunciado, :evidencia, :pregunta, :peso)',
            [
                'codigo'  => $control->id,
                'proceso' => $control->proceso,
                'iso'     => $control->iso,
                'peso'    => $control->peso,
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
                    pregunta = :pregunta, peso = :peso
              WHERE codigo = :codigo',
            [
                'proceso' => $control->proceso,
                'iso'     => $control->iso,
                'peso'    => $control->peso,
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
        $this->dominios = [];
        $this->procesos = [];
        $this->controles = [];
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
