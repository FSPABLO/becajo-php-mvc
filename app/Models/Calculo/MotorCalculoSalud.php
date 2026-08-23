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

    /** Tasa o identidad sin muestra anterior con la que comparar. */
    public const SIN_DERIVAR = 'SIN_DERIVAR';

    /** El acumulado bajó: la instancia se reinició y el tramo se descarta. */
    public const REINICIO = 'REINICIO';

    /** No hubo actividad entre muestras: el denominador de la tasa es cero. */
    public const SIN_ACTIVIDAD = 'SIN_ACTIVIDAD';

    /** La conexión de su ámbito no respondió. Manda sobre lo que diga la lectura. */
    public const CONTEXTO_CAIDO = 'CONTEXTO_CAIDO';

    /**
     * Cómo se deriva la tasa de cada métrica acumulada.
     *
     * @var array<string, array{numerador: string, denominador: string, factor: float}>
     */
    private const DERIVACIONES = [
        // (Δtime_waited_micro / Δtotal_waits) / 1000 → milisegundos por escritura.
        'M-PRO-04' => ['numerador' => 'micros', 'denominador' => 'esperas', 'factor' => 0.001],
    ];

    /** Motivos que sacan a la métrica también de las planificadas: nunca iba a
     * dar datos, o el dato llegó y lo que falta es el punto de comparación.
     *
     * @var list<string>
     */
    private const FUERA_DEL_DENOMINADOR = [
        'NO_APLICA',
        self::SIN_DERIVAR,
        self::REINICIO,
        self::SIN_ACTIVIDAD,
    ];

    /**
     * Pesos del ISBD. CONSULTAS no aparece: se mide y alerta,
     * pero no suma (§3.1).
     *
     * @var array<string, float>
     */
    private const PESOS_ISBD = [
        'PROCESOS' => 0.30,
        'MEMORIA'  => 0.35,
        'ARCHIVOS' => 0.35,
    ];

    /**
     * @param float $pisoCobertura Bajo este porcentaje la muestra es PARCIAL y
     *   no publica índice (invariante 4).
     * @param int $ventana Muestras que mira la histéresis, incluida la actual.
     * @param int $paraSubir Cuántas de esa ventana deben sostener un empeoramiento.
     */
    public function __construct(
        private readonly float $pisoCobertura = 80.0,
        private readonly int $ventana = 3,
        private readonly int $paraSubir = 2,
        private readonly int $diasLineaBase = 14,
        private readonly ?LineaBase $lineaBase = null,
    ) {
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
        $clave = (string) ($muestraCruda['instancia'] ?? '');
        $acumuladosPrevios = $repositorio->acumuladosAnteriores($clave);
        $huellasPrevias = $repositorio->huellasAnteriores($clave);

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

            if ($metrica->esIdentidad) {
                $medicion = $this->evaluarIdentidad($metrica, $lectura, $huellasPrevias);
            } elseif ($metrica->acumulada) {
                $medicion = $this->evaluarTasa($metrica, $lectura, $acumuladosPrevios);
            } elseif ($metrica->esCompuerta()) {
                $medicion = $this->evaluarCompuerta($lectura);
            } else {
                $medicion = $this->evaluarProporcion($metrica, $lectura);
            }

            if (is_string($medicion)) {
                $fuera[$codigo] = $medicion;
                continue;
            }

            if ($medicion === null) {
                $fuera[$codigo] = self::LECTURA_INCOMPLETA;
                continue;
            }

            $medicion = $this->conHisteresis($repositorio, $clave, $codigo, $medicion);
            $mediciones[$codigo] = $this->conLineaBase(
                $repositorio,
                $clave,
                $codigo,
                $medicion,
                (string) ($muestraCruda['tomada_en'] ?? ''),
            );
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

        return $this->normalizada($metrica, (float) $lectura['valor']);
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
     * Tasa: se deriva de la diferencia contra la muestra anterior.
     *
     * El recolector entrega solo el acumulado desde el arranque de la
     * instancia, así que la primera muestra no puede producir tasa y un
     * acumulado que bajó significa que los contadores se reiniciaron: una
     * resta negativa nunca es una tasa.
     *
     * Devuelve un motivo (string) cuando no hay tasa que publicar.
     */
    private function evaluarTasa(Metrica $metrica, array $lectura, array $previos): array|string|null
    {
        if (!isset($lectura['acumulados']) || !is_array($lectura['acumulados'])) {
            return null;
        }

        /** @var array<string, int|float> $acumulados */
        $acumulados = $lectura['acumulados'];
        $derivacion = self::DERIVACIONES[$metrica->codigo] ?? null;

        $deltas = [];

        foreach ($acumulados as $parte => $valor) {
            $anterior = $previos[$metrica->codigo . '.' . $parte] ?? $previos[$metrica->codigo] ?? null;

            if ($anterior === null) {
                return self::SIN_DERIVAR;
            }

            $delta = (float) $valor - $anterior;

            if ($delta < 0.0) {
                return self::REINICIO;
            }

            $deltas[(string) $parte] = $delta;
        }

        if ($derivacion === null) {
            $crudo = array_sum($deltas);
        } else {
            $denominador = $deltas[$derivacion['denominador']] ?? 0.0;

            if ($denominador <= 0.0) {
                return self::SIN_ACTIVIDAD;
            }

            $crudo = ($deltas[$derivacion['numerador']] ?? 0.0) / $denominador * $derivacion['factor'];
        }

        $medicion = $this->normalizada($metrica, $crudo);
        $medicion['acumulados'] = $acumulados;

        return $medicion;
    }

    /** Identidad: compara una huella de texto contra la de la muestra anterior
     *  (B-7). No mide una magnitud, produce una compuerta.  */
    private function evaluarIdentidad(Metrica $metrica, array $lectura, array $previas): array|string|null
    {
        if (!isset($lectura['identidad']) || !is_string($lectura['identidad'])) {
            return null;
        }

        $actual = $lectura['identidad'];
        $anterior = $previas[$metrica->codigo] ?? null;

        if ($anterior === null) {
            return self::SIN_DERIVAR;
        }

        $estable = $anterior === $actual;

        return [
            'abierta' => $estable,
            'estado'  => $estable ? Escala::OPTIMO : Escala::CRITICO,
            'huella'  => $actual,
            'detalle' => $estable ? [] : ['anterior' => $anterior, 'actual' => $actual],
        ];
    }

    /**
     * Histéresis: un empeoramiento se publica solo si `k` de las últimas `n`
     * muestras lo sostienen (§5.5 del plan).
     *
     * Actúa sobre el estado publicado, no sobre el valor: la cifra sigue siendo
     * la que se midió y lo que se sostiene es la etiqueta. Sin historial no hay
     * nada que suavizar.
     *
     * **Limitación declarada.** Una mejora se publica de inmediato. El plan
     * pide `n` de `n` también para bajar, pero `medicion` guarda el estado
     * publicado y no el observado: una vez suprimido un empeoramiento, la
     * historia ya no distingue entre una métrica que estuvo mal y una que
     * pareció estarlo. Sostener una mejora exige una columna nueva.
     */
    private function conHisteresis(
        RepositorioMonitor $repositorio,
        string $clave,
        string $codigo,
        array $medicion,
    ): array {
        if (isset($medicion['abierta'])) {
            return $medicion;
        }

        $observado = (string) $medicion['estado'];
        $anteriores = $repositorio->estadosRecientes($clave, $codigo, $this->ventana - 1);

        if ($anteriores === []) {
            return $medicion;
        }

        $ultimo = $anteriores[0];

        if ($ultimo === $observado || !Escala::empeora($observado, $ultimo)) {
            return $medicion;
        }

        $sostienen = 0;

        foreach ([$observado, ...$anteriores] as $estado) {
            if (Escala::alMenosTanSevera($estado, $observado)) {
                $sostienen++;
            }
        }

        if ($sostienen >= $this->paraSubir) {
            return $medicion;
        }

        // El empeoramiento no está sostenido: se mantiene el estado anterior y
        // se conserva lo observado para que la cifra siga explicándose.
        $medicion['estado_observado'] = $observado;
        $medicion['estado'] = $ultimo;

        return $medicion;
    }

    /**
     * Compara la salud contra la línea base de la métrica en su mismo tramo
     * horario. Solo agrega la clave cuando la ventana alcanza.
     *
     * @param array<string, mixed> $medicion
     * @return array<string, mixed>
     */
    private function conLineaBase(
        RepositorioMonitor $repositorio,
        string $clave,
        string $codigo,
        array $medicion,
        string $tomadaEnUtc,
    ): array {
        if (!isset($medicion['valor_normalizado'])) {
            return $medicion;
        }

        $instante = strtotime($tomadaEnUtc);

        if ($instante === false) {
            return $medicion;
        }

        $ventana = $repositorio->ventanaLineaBase(
            $clave,
            $codigo,
            $this->diasLineaBase,
            (int) gmdate('G', $instante),
        );

        $comparacion = ($this->lineaBase ?? new LineaBase())
            ->evaluar($ventana, (float) $medicion['valor_normalizado']);

        if ($comparacion !== null) {
            $medicion['linea_base'] = $comparacion;
        }

        return $medicion;
    }

    private function normalizada(Metrica $metrica, float $crudo): array
    {
        if ($metrica->umbral === null) {
            throw new InvalidArgumentException(
                "{$metrica->codigo} no es compuerta y no tiene umbral vigente: el catálogo está incompleto."
            );
        }

        $salud = Escala::normalizar($crudo, $metrica->umbral, $metrica->uMax, $metrica->sentido);

        return [
            'valor_crudo'       => $crudo,
            'valor_normalizado' => Escala::publicar($salud),
            'estado'            => Escala::bandaPorSalud($salud),
            'umbral_id'         => $metrica->umbral->id,
        ];
    }

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
