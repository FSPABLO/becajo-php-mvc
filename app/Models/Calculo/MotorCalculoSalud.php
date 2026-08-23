<?php

declare(strict_types=1);

namespace App\Models\Calculo;

use App\Models\Contratos\MotorCalculo;
use App\Models\Contratos\RepositorioMonitor;
use App\Models\Entidades\Metrica;
use InvalidArgumentException;

/**
 * De muestra cruda a muestra evaluada .
 *
 * No consulta Oracle: recibe la muestra ya recolectada y un RepositorioMonitor
 * del que toma el catálogo.
 *
 * Dos reglas gobiernan el cálculo. Sin dato no es cero (invariante 3): una
 * métrica que no se pudo recolectar sale del denominador y se anota en `fuera`
 * con su motivo. Y el promedio puede bajar la nota pero nunca sacar del rojo a
 * lo que está en rojo (§5.4): el tope por peor estado se aplica en los dos
 * niveles, métrica→componente y componente→ISBD.
 */
final class MotorCalculoSalud implements MotorCalculo
{
    /** La lectura no vino en la muestra. */
    public const SIN_LECTURA = 'SIN_LECTURA';

    /** Declaró OK pero le falta el campo de su familia. */
    public const LECTURA_INCOMPLETA = 'LECTURA_INCOMPLETA';

    /** Tasa o identidad: no se deriva de una sola muestra. */
    public const SIN_DERIVAR = 'SIN_DERIVAR';

    /** La conexión de su ámbito no respondió. Manda sobre lo que diga la lectura. */
    public const CONTEXTO_CAIDO = 'CONTEXTO_CAIDO';

    /**
     * @var list<string>
     */
    private const FUERA_DEL_DENOMINADOR = ['NO_APLICA', self::SIN_DERIVAR];

    private const PESOS_ISBD = [
        'PROCESOS' => 0.30,
        'MEMORIA'  => 0.35,
        'ARCHIVOS' => 0.35,
    ];

    /**
     * @param float $pisoCobertura Bajo este porcentaje la muestra es PARCIAL y
     *   no publica índice (invariante 4).
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

        // Se recorre el catálogo y no las lecturas: así una métrica planificada
        // que el recolector no reportó aparece anotada en vez de desaparecer.
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
        $componentes = $this->componentes($mediciones, $catalogo);

        $evaluada = [
            'instancia'     => (string) ($muestraCruda['instancia'] ?? ''),
            'tomada_en'     => (string) ($muestraCruda['tomada_en'] ?? ''),
            'duracion_ms'   => isset($muestraCruda['duracion_ms']) ? (int) $muestraCruda['duracion_ms'] : null,
            'resultado'     => $resultado,
            'cobertura_pct' => $cobertura,
            'mensaje'       => $this->mensaje($resultado, $contextos, $cobertura, $fuera),
            'mediciones'    => $mediciones,
            'componentes'   => $componentes,
            'indice'        => $resultado === 'OK' ? $this->indice($componentes, $mediciones) : null,
            'fuera'         => $fuera,
            'ignoradas'     => $this->lecturasDesconocidas($lecturas, $catalogo),
        ];

        // La evidencia de C-066 viaja intacta (B-11). Solo si viene: un arreglo
        // vacío diría «no hubo consultas costosas», que no es lo mismo.
        if (isset($muestraCruda['consultas_observadas'])) {
            $evaluada['consultas_observadas'] = $muestraCruda['consultas_observadas'];
        }

        return $evaluada;
    }

    // ── Métrica ─────────────────────────────────────────────────────────────

    /**
     * `valor_crudo` guarda la lectura sin transformar: la conversión
     * `techo − v` de las métricas «mayor es mejor» vive dentro de la
     * normalización y no debe llegar al dato que se persiste.
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
            // La banda se decide sobre la salud sin redondear: en las fronteras,
            // el valor publicado y el exacto caen en bandas distintas.
            'estado'            => Escala::bandaPorSalud($salud),
            'umbral_id'         => $metrica->umbral->id,
        ];
    }

    /**
     * Sin valor normalizado: una compuerta no mide cuán bien está algo. El
     * `detalle` se conserva para que la alerta pueda decir qué falta.
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

    // ── Componente ──────────────────────────────────────────────────────────

    /**
     * `I = Σ(peso × salud) / Σpeso` sobre lo recolectado, topado por el peor
     * estado del componente.
     *
     * Un componente sin mediciones no aparece: su ausencia es distinta de un
     * cero, y repartir su peso es cosa del ISBD.
     *
     * @param array<string, array<string, mixed>> $mediciones
     * @param array<string, Metrica> $catalogo
     * @return array<string, array<string, mixed>>
     */
    private function componentes(array $mediciones, array $catalogo): array
    {
        $agrupadas = [];

        foreach ($mediciones as $codigo => $medicion) {
            $agrupadas[$catalogo[$codigo]->componente][$codigo] = $medicion;
        }

        ksort($agrupadas);

        $indicadores = [];

        foreach ($agrupadas as $componente => $suyas) {
            $indicadores[$componente] = $this->indicador($suyas, $catalogo);
        }

        return $indicadores;
    }

    /**
     * @param array<string, array<string, mixed>> $mediciones
     * @param array<string, Metrica> $catalogo
     * @return array<string, mixed>
     */
    private function indicador(array $mediciones, array $catalogo): array
    {
        $cerradas = [];
        $peso = 0.0;
        $acumulado = 0.0;
        $estados = [];

        foreach ($mediciones as $codigo => $medicion) {
            $estados[] = (string) $medicion['estado'];

            if (($medicion['abierta'] ?? null) === false) {
                $cerradas[] = (string) $codigo;
                continue;
            }

            // Una compuerta abierta entra al peor estado, no al promedio.
            if (!isset($medicion['valor_normalizado'])) {
                continue;
            }

            $suPeso = (float) ($catalogo[$codigo]->peso ?? 0);

            if ($suPeso <= 0.0) {
                continue;
            }

            $peso += $suPeso;
            $acumulado += $suPeso * (float) $medicion['valor_normalizado'];
        }

        // Compuerta cerrada: 0 y CRÍTICO sin promediar (§1.3 del catálogo).
        if ($cerradas !== []) {
            return [
                'bruto'               => 0.0,
                'publicado'           => 0.0,
                'estado'              => Escala::CRITICO,
                'compuertas_cerradas' => $cerradas,
            ];
        }

        $peorEstado = Escala::peor($estados);

        // Solo compuertas abiertas: hay estado, pero no hay número que publicar.
        if ($peso <= 0.0) {
            return ['bruto' => null, 'publicado' => null, 'estado' => $peorEstado];
        }

        $bruto = $acumulado / $peso;
        $publicado = Escala::aplicarTope($bruto, $peorEstado);

        return [
            'bruto'     => Escala::publicar($bruto),
            'publicado' => Escala::publicar($publicado),
            'estado'    => Escala::bandaPorSalud($publicado),
        ];
    }

    // ── Índice ──────────────────────────────────────────────────────────────

    /**
     * `ISBD_bruto = 0,30·IP + 0,35·IM + 0,35·IA`, topado por el peor estado
     * entre los tres.
     *
     * Promedia los valores **publicados** y no los brutos: si promediara los
     * brutos, un componente que el tope ya rescató del rojo volvería a entrar
     * como si no lo estuviera. Un componente sin datos reparte su peso entre
     * los otros; no cuenta como salud cero.
     *
     * @param array<string, array<string, mixed>> $componentes
     * @param array<string, array<string, mixed>> $mediciones
     * @return array<string, mixed>|null
     */
    private function indice(array $componentes, array $mediciones): ?array
    {
        $peso = 0.0;
        $acumulado = 0.0;
        $estados = [];

        foreach (self::PESOS_ISBD as $componente => $suPeso) {
            $indicador = $componentes[$componente] ?? null;

            if ($indicador === null || $indicador['publicado'] === null) {
                continue;
            }

            $peso += $suPeso;
            $acumulado += $suPeso * (float) $indicador['publicado'];
            $estados[] = (string) $indicador['estado'];
        }

        if ($peso <= 0.0) {
            return null;
        }

        $peorEstado = Escala::peor($estados);
        $bruto = $acumulado / $peso;
        $isbd = Escala::aplicarTope($bruto, $peorEstado);

        return [
            'isbd_bruto' => Escala::publicar($bruto),
            'isbd'       => Escala::publicar($isbd),
            'estado'     => Escala::bandaPorSalud($isbd),
            'causa'      => $this->causa($mediciones, $componentes, $peorEstado),
        ];
    }

    /**
     * Las métricas en el peor estado observado, para el invariante 5: el índice
     * nunca se muestra sin decir qué lo explica.
     *
     * @param array<string, array<string, mixed>> $mediciones
     * @param array<string, array<string, mixed>> $componentes
     * @return list<string>
     */
    private function causa(array $mediciones, array $componentes, ?string $peorEstado): array
    {
        if ($peorEstado === null) {
            return [];
        }

        $causa = [];

        foreach ($componentes as $componente => $indicador) {
            if (!isset(self::PESOS_ISBD[$componente]) || $indicador['estado'] !== $peorEstado) {
                continue;
            }

            foreach ($indicador['compuertas_cerradas'] ?? [] as $codigo) {
                $causa[] = (string) $codigo;
            }
        }

        foreach ($mediciones as $codigo => $medicion) {
            if (($medicion['estado'] ?? null) === $peorEstado && !in_array((string) $codigo, $causa, true)) {
                $causa[] = (string) $codigo;
            }
        }

        return $causa;
    }

    // ── Cabecera de la muestra ──────────────────────────────────────────────

    /**
     * Un contexto no declarado se considera caído: el agente debe anotar cómo
     * fue cada conexión, y el silencio no es éxito.
     *
     * @param array<string, array<string, mixed>> $contextos
     */
    private function contextoRespondio(array $contextos, string $ambito): bool
    {
        return ($contextos[$ambito]['estado'] ?? null) === 'OK';
    }

    /**
     * FALLIDA cuando ninguna conexión respondió; la muestra se persiste igual,
     * porque saltarla dejaría un hueco que después parece un periodo sano.
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
     * Resume los motivos de exclusión porque `fuera` no se persiste: sin esto,
     * de una muestra guardada sobrevive la cifra pero no el porqué.
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

    /** @param array<string, string> $fuera */
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

    // ── Catálogo ────────────────────────────────────────────────────────────

    /**
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
