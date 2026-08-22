<?php

declare(strict_types=1);

namespace App\Models\Calculo;

use App\Models\Contratos\MotorCalculo;
use App\Models\Contratos\RepositorioMonitor;
use App\Models\Entidades\Metrica;
use InvalidArgumentException;

/**
 * El motor de cálculo: de muestra cruda a muestra evaluada.
 *
 * Esta primera etapa resuelve el bloque `mediciones` del §5 del contrato de
 * muestra, es decir, la evaluación de **cada métrica por separado**: qué valor
 * crudo se leyó, a qué salud normaliza y en qué banda cae. Los componentes, el
 * ISBD y la cobertura llegan después; hasta entonces la estructura que devuelve
 * `evaluar()` está incompleta a propósito y no debe persistirse.
 *
 * El motor **no consulta Oracle**. Recibe muestras crudas —de la base o de un
 * archivo de ejemplo, le da igual— y un `RepositorioMonitor` del que solo usa,
 * por ahora, el catálogo. Mirar hacia atrás (tasas, identidad, histéresis,
 * línea base) llega con las etapas que lo necesitan.
 *
 * Dos reglas gobiernan todo lo que hay aquí:
 *
 * **Sin dato no es cero** (invariante 3). Una métrica que no se pudo recolectar
 * no aparece en `mediciones`: se anota en `fuera` con el motivo. Publicar un 0
 * en su lugar convertiría una falla de recolección en una falla de la base, que
 * es el error más peligroso que puede cometer un monitor porque se ve idéntico
 * a un dato real.
 *
 * **Las compuertas no promedian.** Una métrica de familia ESTADO se guarda con
 * su estado y sin valor normalizado. No aporta un 100 al promedio cuando está
 * abierta; cierra el componente cuando está cerrada (§1.3 del catálogo).
 */
final class MotorCalculoSalud implements MotorCalculo
{
    /** La lectura no vino en la muestra. El silencio no es un estado (§8 del contrato). */
    public const SIN_LECTURA = 'SIN_LECTURA';

    /** La lectura vino incompleta: declaró OK pero le falta el campo de su familia. */
    public const LECTURA_INCOMPLETA = 'LECTURA_INCOMPLETA';

    /**
     * Tasa o identidad: su valor no se puede derivar de una sola muestra.
     *
     * Es un estado transitorio de esta etapa del motor, no del modelo: la
     * derivación contra la muestra anterior se implementa con el resto de lo
     * que depende del tiempo.
     */
    public const SIN_DERIVAR = 'SIN_DERIVAR';

    /** La conexión de su ámbito no respondió, la lectura no se intentó o no es fiable.  */
    public const CONTEXTO_CAIDO = 'CONTEXTO_CAIDO';

    /**
     * Motivos que sacan a la métrica también de las **planificadas**, no solo
     * de las recolectadas.
     *
     * `NO_APLICA`  la métrica no corresponde a esta instancia y nunca iba
     * a dar datos.
     *`SIN_DERIVAR` una tasa en su primera muestra no es
     * una falla de recolección: el acumulado llegó, lo que falta es el punto de
     * comparación.
     * Todo lo demás —`VACIA`, `ERROR`, silencio, lectura incompleta, contexto caído—
     * sí baja la cobertura, porque en esos casos la métrica **debía** dar datos y no los dio.
     *
     * @var list<string>
     */
    private const FUERA_DEL_DENOMINADOR = [/'NO_APLICA', self::SIN_DERIVAR];

    /**
     * @param float $pisoCobertura Por debajo de este porcentaje la muestra se
     *   marca PARCIAL y no publica índice (invariante 4). Se propone 80 %.
     */
    public function __construct(private readonly float $pisoCobertura = 80.0)
    {
    }

    /**
     * @param array<string, mixed> $muestraCruda
     * @return array<string, mixed>
     */
    public function evaluar(array $muestraCruda, RepositorioMonitor $repositorio): array
    {
        $catalogo = $this->catalogoPorCodigo($repositorio);

        /** @var array<string, array<string, mixed>> $lecturas */
        $lecturas = $muestraCruda['lecturas'] ?? [];

        /** @var array<string, array<string, mixed>> $contextos */
        $contextos = $muestraCruda['contextos'] ?? [];

        $mediciones = [];
        $fuera = [];

        // Se recorre el CATÁLOGO, no las lecturas. Así una métrica planificada
        // que el recolector no reportó aparece igual —como SIN_LECTURA— en vez
        // de desaparecer sin dejar rastro, y el orden del resultado es siempre
        // el mismo aunque el agente reordene su salida.
        foreach ($catalogo as $codigo => $metrica) {

            if (!$this->contextoRespondio($contextos, $metrica->ambito)) {
                $fuera[$codigo] = self::CONTEXTO_CAIDO;
                continue;
            }

            $lectura = $lecturas[$codigo] ?? null;

            if (!is_array($lectura)) {
                $fuera[$codigo] = self::SIN_LECTURA;
                continue;
            }

            $estadoRecoleccion = (string) ($lectura['estado'] ?? '');

            if ($estadoRecoleccion !== 'OK') {
                $fuera[$codigo] = $estadoRecoleccion === '' ? self::LECTURA_INCOMPLETA : $estadoRecoleccion;
                continue;
            }

            if ($metrica->acumulada || $metrica->esIdentidad) {
                $fuera[$codigo] = self::SIN_DERIVAR;
                continue;
            }

            $medicion = $metrica->esCompuerta()
                ? $this->evaluarCompuerta($lectura)
                : $this->evaluarProporcion($metrica, $lectura);

            if ($medicion === null) {
                $fuera[$codigo] = self::LECTURA_INCOMPLETA;
                continue;
            }

            $mediciones[$codigo] = $medicion;
        }

        $planificadas = count($mediciones) + count(array_filter(
            $fuera,
            static fn (string $motivo): bool => !in_array($motivo, self::FUERA_DEL_DENOMINADOR, true),
        ));

        $cobertura = $planificadas === 0 ? 0.0 : round(count($mediciones) / $planificadas * 100, 1);
        $resultado = $this->resultado($contextos, $cobertura);

        $evaluada = [
            'instancia'     => (string) ($muestraCruda['instancia'] ?? ''),
            'tomada_en'     => (string) ($muestraCruda['tomada_en'] ?? ''),
            'duracion_ms'   => isset($muestraCruda['duracion_ms']) ? (int) $muestraCruda['duracion_ms'] : null,
            'resultado'     => $resultado,
            'cobertura_pct' => $cobertura,
            'mensaje'       => $this->mensaje($resultado, $contextos, $cobertura, $fuera),
            'mediciones'    => $mediciones,
            'fuera'         => $fuera,
            'ignoradas'     => $this->lecturasDesconocidas($lecturas, $catalogo),
        ];

        if (isset($muestraCruda['consultas_observadas'])) {
            $evaluada['consultas_observadas'] = $muestraCruda['consultas_observadas'];
        }

        return $evaluada;
    }

    /**
     * ¿Respondió la conexión de este ámbito?
     *
     * Un contexto que no viene declarado se considera caído. El agente abre las
     * dos conexiones en cada muestra y el §2 del contrato le exige anotar cómo
     * fue cada una
     *
     * @param array<string, array<string, mixed>> $contextos
     */
    private function contextoRespondio(array $contextos, string $ambito): bool
    {
        return ($contextos[$ambito]['estado'] ?? null) === 'OK';
    }

    /**
     * OK, PARCIAL o FALLIDA.
     *
     * FALLIDA cuando ninguna conexión respondió: la instancia no contestó a esa
     * hora. La muestra se persiste igual, porque saltarla dejaría un hueco que
     * después parece un periodo sano.
     *
     * PARCIAL cuando la cobertura queda por debajo del piso. Es el invariante 4:
     * un índice calculado sobre la mitad de la evidencia es peor que ningún
     * índice, porque parece uno bueno. El paso que publica el ISBD consulta
     * este campo, no lo recalcula.
     *
     * @param array<string, array<string, mixed>> $contextos
     */
    private function resultado(array $contextos, float $cobertura): string
    {
        $algunoRespondio = false;

        foreach ([Metrica::RAIZ, Metrica::CONTENEDOR] as $ambito) {
            if ($this->contextoRespondio($contextos, $ambito)) {
                $algunoRespondio = true;
            }
        }

        if (!$algunoRespondio) {
            return 'FALLIDA';
        }

        return $cobertura >= $this->pisoCobertura ? 'OK' : 'PARCIAL';
    }

    /**
     * El texto que acompaña a una muestra que no salió bien.
     *
     * Existe para que el tablero pueda decir «muestra incompleta» con su razón
     * en lugar de un número. Una muestra OK no lleva mensaje: el dato habla
     * solo.
     *
     * @param array<string, array<string, mixed>> $contextos
     * @param array<string, string> $fuera
     */
    private function mensaje(string $resultado, array $contextos, float $cobertura, array $fuera): ?string
    {
        if ($resultado === 'OK') {
            return null;
        }

        if ($resultado === 'FALLIDA') {
            foreach ($contextos as $ambito => $contexto) {
                if (isset($contexto['mensaje'])) {
                    return $ambito . ': ' . (string) $contexto['mensaje'];
                }
            }

            return 'La instancia no respondió: ninguna conexión pudo abrirse.';
        }

        return sprintf(
            'Muestra incompleta: cobertura %s %% bajo el piso de %s %%.%s',
            number_format($cobertura, 1, ',', ''),
            number_format($this->pisoCobertura, 1, ',', ''),
            $this->resumenDeMotivos($fuera),
        );
    }

    /**
     * «CONTEXTO_CAIDO 10, SIN_LECTURA 3», ordenado de más a menos frecuente.
     *
     * @param array<string, string> $fuera
     */
    private function resumenDeMotivos(array $fuera): string
    {
        $cuenta = [];

        foreach ($fuera as $motivo) {
            if (in_array($motivo, self::FUERA_DEL_DENOMINADOR, true)) {
                continue;
            }

            $cuenta[$motivo] = ($cuenta[$motivo] ?? 0) + 1;
        }

        if ($cuenta === []) {
            return '';
        }

        arsort($cuenta);

        $partes = [];

        foreach ($cuenta as $motivo => $cuantas) {
            $partes[] = $motivo . ' ' . $cuantas;
        }

        return ' Fuera: ' . implode(', ', $partes) . '.';
    }

    /**
     * Una proporción: se normaliza y se clasifica por la banda de su salud.
     *
     * `valor_crudo` guarda la lectura **tal como se midió**, sin transformar.
     * Para una métrica «mayor es mejor» la transformación `techo − v` ocurre
     * dentro de la normalización y no debe filtrarse al dato que se persiste:
     * el tablero tiene que poder decir «18,8 % libre», que es lo que un DBA
     * reconoce, y no «81,2» que no significa nada fuera del motor.
     *
     * @param array<string, mixed> $lectura
     * @return array<string, mixed>|null
     */
    private function evaluarProporcion(Metrica $metrica, array $lectura): ?array
    {
        if (!isset($lectura['valor']) || !is_numeric($lectura['valor'])) {
            return null;
        }

        if ($metrica->umbral === null) {
            throw new InvalidArgumentException(
                "{$metrica->codigo} no es compuerta y no tiene umbral vigente: el catálogo está incompleto."
            );
        }

        $crudo = (float) $lectura['valor'];

        $salud = Escala::normalizar($crudo, $metrica->umbral, $metrica->uMax, $metrica->sentido);

        return [
            'valor_crudo'       => $crudo,
            'valor_normalizado' => Escala::publicar($salud),
            // La banda se decide sobre la salud SIN redondear: en las fronteras,
            // el valor publicado y el exacto pueden caer en bandas distintas.
            'estado'            => Escala::bandaPorSalud($salud),
            'umbral_id'         => $metrica->umbral->id,
        ];
    }

    /**
     * Una compuerta: abierta u ÓPTIMO, cerrada y CRÍTICO. Sin valor normalizado.
     *
     * `detalle` se conserva aunque `medicion` no tenga columna para él. Es lo
     * que permite que la alerta diga qué proceso falta en vez de «compuerta
     * cerrada», que obliga a ir a mirar a mano. Viaja dentro de la muestra
     * evaluada, que es lo que consume el motor de alertas antes de persistir.
     *
     * @param array<string, mixed> $lectura
     * @return array<string, mixed>|null
     */
    private function evaluarCompuerta(array $lectura): ?array
    {
        if (!isset($lectura['abierta']) || !is_bool($lectura['abierta'])) {
            return null;
        }

        $abierta = $lectura['abierta'];

        $medicion = [
            'abierta' => $abierta,
            'estado'  => $abierta ? Escala::OPTIMO : Escala::CRITICO,
        ];

        if (isset($lectura['detalle']) && is_array($lectura['detalle'])) {
            $medicion['detalle'] = $lectura['detalle'];
        }

        return $medicion;
    }

    /**
     * Lecturas que el catálogo no conoce.
     *
     * No detienen la evaluación de las demás: es lo que pasa cuando el agente
     * va por delante del catálogo, y el §8 del contrato pide ignorarlas y
     * registrarlas.
     *
     * @param array<string, mixed> $lecturas
     * @param array<string, Metrica> $catalogo
     * @return list<string>
     */
    private function lecturasDesconocidas(array $lecturas, array $catalogo): array
    {
        $desconocidas = [];

        foreach (array_keys($lecturas) as $codigo) {
            if (!isset($catalogo[(string) $codigo])) {
                $desconocidas[] = (string) $codigo;
            }
        }

        return $desconocidas;
    }

    /** @return array<string, Metrica> */
    private function catalogoPorCodigo(RepositorioMonitor $repositorio): array
    {
        $catalogo = [];

        foreach ($repositorio->metricas() as $metrica) {
            $catalogo[$metrica->codigo] = $metrica;
        }

        return $catalogo;
    }
}
