<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contratos\RepositorioMonitor;
use App\Models\Entidades\Alerta;
use App\Models\Entidades\Episodio;
use App\Models\Entidades\Instancia;
use App\Models\Entidades\Medicion;
use App\Models\Entidades\Metrica;
use App\Models\Entidades\Muestra;
use InvalidArgumentException;

/**
 * `RepositorioMonitor` en memoria, sobre `config/monitor-catalogo.php`.
 *
 * Es al monitor lo que `RepositorioInstrumentoArreglo` es al instrumento: la
 * implementación que permite trabajar sin base de datos. Gracias a la
 * inversión de dependencias, el motor de cálculo no distingue si le respondió
 * esta clase o `RepositorioMonitorOracle`; cambiar de una a otra es una línea
 * en `public/index.php` o en `bin/monitor.php`.
 *
 */
final class RepositorioMonitorArreglo implements RepositorioMonitor
{
    /** @var array<string, Instancia> */
    private array $instancias = [];

    /** @var list<Metrica> */
    private array $metricas = [];

    /** @var list<array{origen: string, consecuencia: string}> */
    private array $precedencias = [];

    /**
     * Historial de muestras ya evaluadas, en el orden en que se precargaron.
     *
     * @var list<array{id: int, evaluada: array<string, mixed>}>
     */
    private array $muestras = [];

    private int $siguienteId = 1;

    /**
     * @param array{
     *     instancias?: list<array<string, mixed>>,
     *     metricas?: list<array<string, mixed>>,
     *     precedencias?: list<array{origen: string, consecuencia: string}>
     * } $catalogo
     */
    public function __construct(array $catalogo)
    {
        foreach ($catalogo['instancias'] ?? [] as $fila) {
            $instancia = Instancia::desdeFila($fila);
            $this->instancias[$instancia->clave] = $instancia;
        }

        foreach ($catalogo['metricas'] ?? [] as $fila) {
            $this->metricas[] = Metrica::desdeFila($fila);
        }

        $this->precedencias = $catalogo['precedencias'] ?? [];
    }

    /**
     * El catálogo de `config/`, que es el que se corresponde con el script
     * semilla de Oracle.
     */
    public static function desdeArchivo(?string $ruta = null): self
    {
        $ruta ??= dirname(__DIR__, 2) . '/config/monitor-catalogo.php';

        if (!is_file($ruta)) {
            throw new InvalidArgumentException("No existe el catálogo del monitor: {$ruta}");
        }

        /** @var array<string, mixed> $catalogo */
        $catalogo = require $ruta;

        return new self($catalogo);
    }

    /**
     * Agrega una muestra ya evaluada al historial y devuelve su identificador.
     *
     * Recibe la estructura del §5 del contrato de muestra, la misma que
     * `RepositorioMonitorEscritura::guardarMuestra()` persiste en Oracle. No se
     * llama igual justamente para que no se confunda con ella: aquí no hay
     * transacción, no hay purga y nada sobrevive al final del proceso.
     *
     * @param array<string, mixed> $muestraEvaluada
     */
    public function precargar(array $muestraEvaluada): int
    {
        $clave = (string) ($muestraEvaluada['instancia'] ?? '');

        if (!isset($this->instancias[$clave])) {
            throw new InvalidArgumentException("Instancia no vigilada: {$clave}");
        }

        $id = $this->siguienteId++;
        $this->muestras[] = ['id' => $id, 'evaluada' => $muestraEvaluada];

        return $id;
    }

    // ── Instancias vigiladas ────────────────────────────────────────────────

    /** @return list<Instancia> */
    public function instancias(): array
    {
        return array_values($this->instancias);
    }

    public function instancia(string $clave): ?Instancia
    {
        return $this->instancias[$clave] ?? null;
    }

    // ── Estado actual ───────────────────────────────────────────────────────

    /** @return list<Muestra> */
    public function ultimasMuestras(): array
    {
        $ultimas = [];

        foreach (array_keys($this->instancias) as $clave) {
            $muestra = $this->ultimaMuestra($clave);

            if ($muestra !== null) {
                $ultimas[] = $muestra;
            }
        }

        return $ultimas;
    }

    public function ultimaMuestra(string $clave): ?Muestra
    {
        $ultima = $this->ultimaEntradaDe($clave);

        return $ultima === null ? null : $this->comoMuestra($ultima);
    }

    /** @return list<Medicion> */
    public function mediciones(int $idMuestra): array
    {
        $entrada = $this->entradaPorId($idMuestra);

        if ($entrada === null) {
            return [];
        }

        $mediciones = [];
        $secuencia = 1;

        /** @var array<string, array<string, mixed>> $filas */
        $filas = $entrada['evaluada']['mediciones'] ?? [];

        foreach ($filas as $codigo => $medicion) {
            $mediciones[] = new Medicion(
                id:               $idMuestra * 100 + $secuencia++,
                idMuestra:        $idMuestra,
                codigoMetrica:    (string) $codigo,
                valorCrudo:       self::numeroONulo($medicion['valor_crudo'] ?? null),
                valorNormalizado: self::numeroONulo($medicion['valor_normalizado'] ?? null),
                estado:           isset($medicion['estado']) ? (string) $medicion['estado'] : null,
                idUmbral:         isset($medicion['umbral_id']) ? (int) $medicion['umbral_id'] : null,
                valorAcumulado:   self::numeroONulo($medicion['valor_acumulado'] ?? null),
                huella:           isset($medicion['huella']) ? (string) $medicion['huella'] : null,
            );
        }

        return $mediciones;
    }

    // ── Series históricas ───────────────────────────────────────────────────

    public function serieIndice(string $clave, string $desdeUtc, string $hastaUtc): array
    {
        $serie = [];

        foreach ($this->entradasDe($clave, $desdeUtc, $hastaUtc) as $entrada) {
            $indice = $entrada['evaluada']['indice'] ?? null;

            if (!is_array($indice) || !isset($indice['isbd'])) {
                continue;
            }

            $serie[] = [
                'tomada_en' => (string) $entrada['evaluada']['tomada_en'],
                'isbd'      => (float) $indice['isbd'],
                'estado'    => (string) ($indice['estado'] ?? ''),
            ];
        }

        return $serie;
    }

    /** @return list<array<string, mixed>> */
    public function serieMetrica(
        string $clave,
        string $codigoMetrica,
        string $desdeUtc,
        string $hastaUtc,
    ): array {
        $serie = [];

        foreach ($this->entradasDe($clave, $desdeUtc, $hastaUtc) as $entrada) {
            $medicion = $entrada['evaluada']['mediciones'][$codigoMetrica] ?? null;

            if (!is_array($medicion)) {
                continue;
            }

            $serie[] = [
                'tomada_en'         => (string) $entrada['evaluada']['tomada_en'],
                'valor_crudo'       => self::numeroONulo($medicion['valor_crudo'] ?? null),
                'valor_normalizado' => self::numeroONulo($medicion['valor_normalizado'] ?? null),
                'estado'            => isset($medicion['estado']) ? (string) $medicion['estado'] : null,
            ];
        }

        return $serie;
    }

    // ── Alertas y episodios ─────────────────────────────────────────────────

    public function alertasAbiertas(?string $clave = null): array
    {
        return [];
    }

    /** @return list<Episodio> */
    public function episodios(string $clave, string $desdeUtc, string $hastaUtc): array
    {
        return [];
    }

    // ── Catálogo ────────────────────────────────────────────────────────────

    /** @return list<Metrica> */
    public function metricas(): array
    {
        return $this->metricas;
    }

    /** @return list<array{origen: string, consecuencia: string}> */
    public function precedencias(): array
    {
        return $this->precedencias;
    }

    // ── Lo que necesita el motor de cálculo ─────────────────────────────────

    /**
     * Los acumulados de la muestra anterior.
     *
     * **Convención provisional.** Una métrica de tasa puede transportar más de
     * un acumulado: `M-PRO-04` entrega `esperas` y `micros`, y su tasa es el
     * cociente de las dos diferencias. La clave del arreglo es entonces
     * `codigo.parte` (`M-PRO-04.micros`) cuando hay varios, y el código a secas
     * cuando hay uno solo. `medicion.valor_acumulado` es hoy una sola columna y
     * no puede sostener el caso de dos, así que esto queda a la espera de la
     * decisión de equipo; cuando se tome, cambian este método y su gemelo de
     * Oracle, y nada más.
     *
     * Arreglo vacío si no hay muestra previa, y en ese caso las métricas de
     * tasa salen del denominador: no es un error, es la primera muestra.
     *
     * @return array<string, float>
     */
    public function acumuladosAnteriores(string $clave): array
    {
        $entrada = $this->ultimaEntradaDe($clave);

        if ($entrada === null) {
            return [];
        }

        $acumulados = [];

        /** @var array<string, array<string, mixed>> $filas */
        $filas = $entrada['evaluada']['mediciones'] ?? [];

        foreach ($filas as $codigo => $medicion) {
            if (isset($medicion['valor_acumulado'])) {
                $acumulados[(string) $codigo] = (float) $medicion['valor_acumulado'];
                continue;
            }

            if (is_array($medicion['acumulados'] ?? null)) {
                foreach ($medicion['acumulados'] as $parte => $valor) {
                    $acumulados[$codigo . '.' . $parte] = (float) $valor;
                }
            }
        }

        return $acumulados;
    }

    /** @return array<string, string> */
    public function huellasAnteriores(string $clave): array
    {
        $entrada = $this->ultimaEntradaDe($clave);

        if ($entrada === null) {
            return [];
        }

        $huellas = [];

        /** @var array<string, array<string, mixed>> $filas */
        $filas = $entrada['evaluada']['mediciones'] ?? [];

        foreach ($filas as $codigo => $medicion) {
            if (isset($medicion['huella'])) {
                $huellas[(string) $codigo] = (string) $medicion['huella'];
            }
        }

        return $huellas;
    }

    /**
     * Del más reciente al más antiguo, que es el orden que espera la
     * histéresis: «k de las últimas n».
     *
     * @return list<string>
     */
    public function estadosRecientes(string $clave, string $codigoMetrica, int $cuantas): array
    {
        $estados = [];

        foreach ($this->entradasDescendentes($clave) as $entrada) {
            $estado = $entrada['evaluada']['mediciones'][$codigoMetrica]['estado'] ?? null;

            if ($estado === null) {
                continue;
            }

            $estados[] = (string) $estado;

            if (count($estados) >= $cuantas) {
                break;
            }
        }

        return $estados;
    }

    /**
     * Los normalizados de la métrica en el mismo tramo horario de los últimos
     * N días. La ventana se cuenta hacia atrás desde la muestra más reciente
     * de la instancia y no desde «ahora»: si se midiera contra el reloj del
     * sistema, la misma prueba pasaría hoy y fallaría dentro de un mes.
     *
     * @return list<float>
     */
    public function ventanaLineaBase(
        string $clave,
        string $codigoMetrica,
        int $dias,
        int $tramoHora,
    ): array {
        $ultima = $this->ultimaEntradaDe($clave);

        if ($ultima === null) {
            return [];
        }

        $hasta = self::instante((string) $ultima['evaluada']['tomada_en']);
        $desde = $hasta - $dias * 86400;
        $valores = [];

        foreach ($this->entradasDe($clave) as $entrada) {
            $tomadaEn = (string) $entrada['evaluada']['tomada_en'];
            $instante = self::instante($tomadaEn);

            if ($instante < $desde || $instante > $hasta) {
                continue;
            }

            if ((int) gmdate('G', $instante) !== $tramoHora) {
                continue;
            }

            $normalizado = $entrada['evaluada']['mediciones'][$codigoMetrica]['valor_normalizado'] ?? null;

            if ($normalizado !== null) {
                $valores[] = (float) $normalizado;
            }
        }

        return $valores;
    }

    // ── Interno ─────────────────────────────────────────────────────────────

    /** @return list<array{id: int, evaluada: array<string, mixed>}> */
    private function entradasDe(string $clave, ?string $desdeUtc = null, ?string $hastaUtc = null): array
    {
        $entradas = array_values(array_filter(
            $this->muestras,
            static fn (array $e): bool => (string) $e['evaluada']['instancia'] === $clave,
        ));

        usort(
            $entradas,
            static fn (array $a, array $b): int => self::instante((string) $a['evaluada']['tomada_en'])
                <=> self::instante((string) $b['evaluada']['tomada_en']),
        );

        if ($desdeUtc === null && $hastaUtc === null) {
            return $entradas;
        }

        $desde = $desdeUtc === null ? PHP_INT_MIN : self::instante($desdeUtc);
        $hasta = $hastaUtc === null ? PHP_INT_MAX : self::instante($hastaUtc);

        return array_values(array_filter(
            $entradas,
            static function (array $e) use ($desde, $hasta): bool {
                $instante = self::instante((string) $e['evaluada']['tomada_en']);

                return $instante >= $desde && $instante <= $hasta;
            },
        ));
    }

    /** @return list<array{id: int, evaluada: array<string, mixed>}> */
    private function entradasDescendentes(string $clave): array
    {
        return array_reverse($this->entradasDe($clave));
    }

    /** @return array{id: int, evaluada: array<string, mixed>}|null */
    private function ultimaEntradaDe(string $clave): ?array
    {
        $entradas = $this->entradasDe($clave);

        return $entradas === [] ? null : $entradas[count($entradas) - 1];
    }

    /** @return array{id: int, evaluada: array<string, mixed>}|null */
    private function entradaPorId(int $id): ?array
    {
        foreach ($this->muestras as $entrada) {
            if ($entrada['id'] === $id) {
                return $entrada;
            }
        }

        return null;
    }

    /** @param array{id: int, evaluada: array<string, mixed>} $entrada */
    private function comoMuestra(array $entrada): Muestra
    {
        $evaluada = $entrada['evaluada'];

        /** @var array<string, mixed> $componentes */
        $componentes = $evaluada['componentes'] ?? [];

        /** @var array<string, mixed> $indice */
        $indice = $evaluada['indice'] ?? [];

        /** @var list<string> $causa */
        $causa = $indice['causa'] ?? [];

        return new Muestra(
            id:             $entrada['id'],
            claveInstancia: (string) $evaluada['instancia'],
            tomadaEn:       (string) $evaluada['tomada_en'],
            duracionMs:     isset($evaluada['duracion_ms']) ? (int) $evaluada['duracion_ms'] : null,
            resultado:      (string) ($evaluada['resultado'] ?? Muestra::OK),
            coberturaPct:   self::numeroONulo($evaluada['cobertura_pct'] ?? null),
            mensaje:        isset($evaluada['mensaje']) ? (string) $evaluada['mensaje'] : null,
            ip:             self::numeroONulo($componentes['PROCESOS']['publicado'] ?? null),
            im:             self::numeroONulo($componentes['MEMORIA']['publicado'] ?? null),
            ia:             self::numeroONulo($componentes['ARCHIVOS']['publicado'] ?? null),
            isbdBruto:      self::numeroONulo($indice['isbd_bruto'] ?? null),
            isbd:           self::numeroONulo($indice['isbd'] ?? null),
            estado:         isset($indice['estado']) ? (string) $indice['estado'] : null,
            causa:          array_map(strval(...), $causa),
        );
    }

    /** Segundos desde la época para una marca en ISO 8601 con zona explícita. */
    private static function instante(string $marcaUtc): int
    {
        $instante = strtotime($marcaUtc);

        if ($instante === false) {
            throw new InvalidArgumentException("Marca de tiempo ilegible: {$marcaUtc}");
        }

        return $instante;
    }

    private static function numeroONulo(mixed $valor): ?float
    {
        return $valor === null ? null : (float) $valor;
    }
}
