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

    /**
     * @param array<string, mixed> $muestraCruda
     * @return array<string, mixed>
     */
    public function evaluar(array $muestraCruda, RepositorioMonitor $repositorio): array
    {
        $catalogo = $this->catalogoPorCodigo($repositorio);

        /** @var array<string, array<string, mixed>> $lecturas */
        $lecturas = $muestraCruda['lecturas'] ?? [];

        $mediciones = [];
        $fuera = [];

        // Se recorre el CATÁLOGO, no las lecturas. Así una métrica planificada
        // que el recolector no reportó aparece igual —como SIN_LECTURA— en vez
        // de desaparecer sin dejar rastro, y el orden del resultado es siempre
        // el mismo aunque el agente reordene su salida.
        foreach ($catalogo as $codigo => $metrica) {
            $lectura = $lecturas[$codigo] ?? null;

            if (!is_array($lectura)) {
                $fuera[$codigo] = self::SIN_LECTURA;
                continue;
            }

            $estadoRecoleccion = (string) ($lectura['estado'] ?? '');

            if ($estadoRecoleccion !== 'OK') {
                // VACIA, ERROR o NO_APLICA viajan tal cual: la diferencia entre
                // ellos la necesita el cálculo de cobertura, no este bloque.
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

        return [
            'instancia'   => (string) ($muestraCruda['instancia'] ?? ''),
            'tomada_en'   => (string) ($muestraCruda['tomada_en'] ?? ''),
            'duracion_ms' => isset($muestraCruda['duracion_ms']) ? (int) $muestraCruda['duracion_ms'] : null,
            'mediciones'  => $mediciones,
            'fuera'       => $fuera,
            'ignoradas'   => $this->lecturasDesconocidas($lecturas, $catalogo),
        ];
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
