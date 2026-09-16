<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\Escala;
use App\Models\Calculo\LineaBase;
use App\Models\Calculo\MotorAlertas;
use App\Models\Calculo\MotorCalculoSalud;
use App\Models\RepositorioMonitorArreglo;
use PHPUnit\Framework\TestCase;
use Pruebas\Muestras;

/**
 * Línea base sobre ventana móvil (§5.5) y motor de alertas con ciclo de vida.
 */
final class MotorAlertasTest extends TestCase
{
    private function repositorio(): RepositorioMonitorArreglo
    {
        return RepositorioMonitorArreglo::desdeArchivo();
    }

    // ── Línea base ─────────────────────────────────────────────────────────

    /** Con pocas muestras cualquier desviación es ruido con aspecto de hallazgo. */
    public function testUnaVentanaCortaNoDiceNada(): void
    {
        $this->assertNull((new LineaBase())->evaluar([90.0, 91.0, 89.0], 10.0));
    }

    public function testUnaCaidaDeTresSigmasEsAnomala(): void
    {
        $ventana = [90.0, 91.0, 89.0, 90.0, 92.0, 88.0, 90.0, 91.0];

        $comparacion = (new LineaBase())->evaluar($ventana, 60.0);

        $this->assertNotNull($comparacion);
        $this->assertSame(90.1, $comparacion['media']);
        $this->assertTrue($comparacion['anomala']);
        $this->assertLessThan(-3.0, $comparacion['z']);
    }

    /** Una salud por encima de lo habitual es buena noticia, no una anomalía. */
    public function testUnaMejoraNoSeSenalaComoAnomalia(): void
    {
        $ventana = [60.0, 61.0, 59.0, 60.0, 62.0, 58.0, 60.0, 61.0];

        $comparacion = (new LineaBase())->evaluar($ventana, 100.0);

        $this->assertNotNull($comparacion);
        $this->assertFalse($comparacion['anomala']);
        $this->assertGreaterThan(0.0, $comparacion['z']);
    }

    /** Serie constante: sin dispersión no hay anomalía ni división por cero. */
    public function testUnaSerieConstanteNoProduceAnomalia(): void
    {
        $comparacion = (new LineaBase())->evaluar(array_fill(0, 10, 90.0), 40.0);

        $this->assertNotNull($comparacion);
        $this->assertSame(0.0, $comparacion['desviacion']);
        $this->assertFalse($comparacion['anomala']);
    }

    /** El motor adjunta la comparación a la medición cuando la ventana alcanza. */
    public function testElMotorAdjuntaLaLineaBaseALaMedicion(): void
    {
        $repositorio = $this->repositorio();

        for ($dia = 5; $dia <= 18; $dia++) {
            $repositorio->precargar([
                'instancia'  => 'FREEPDB1',
                'tomada_en'  => sprintf('2026-08-%02dT14:35:00+00:00', $dia),
                'resultado'  => 'OK',
                'mediciones' => ['M-PRO-01' => ['estado' => Escala::OPTIMO, 'valor_normalizado' => 93.2]],
            ]);
        }

        $evaluada = (new MotorCalculoSalud())->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $repositorio,
        );

        $this->assertArrayHasKey('linea_base', $evaluada['mediciones']['M-PRO-01']);
        $this->assertSame(93.2, $evaluada['mediciones']['M-PRO-01']['linea_base']['media']);
    }

    /** Sin historial no hay línea base: la clave no aparece. */
    public function testSinVentanaNoHayLineaBase(): void
    {
        $evaluada = (new MotorCalculoSalud())->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorio(),
        );

        $this->assertArrayNotHasKey('linea_base', $evaluada['mediciones']['M-PRO-01']);
    }

    // ── Apertura y niveles ─────────────────────────────────────────────────

    private function evaluada(array $mediciones): array
    {
        return ['instancia' => 'FREEPDB1', 'mediciones' => $mediciones];
    }

    /** @return array<string, mixed> */
    private function medicion(string $estado, float $salud = 50.0): array
    {
        return ['valor_crudo' => 88.0, 'valor_normalizado' => $salud, 'estado' => $estado];
    }

    /** Por debajo de ADVERTENCIA no se alerta: el tablero ya muestra el número. */
    public function testUnaMetricaSaludableNoAbreAlerta(): void
    {
        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-PRO-01' => $this->medicion(Escala::SALUDABLE, 85.0)]),
            $this->repositorio(),
        );

        $this->assertSame([], $decisiones[MotorAlertas::ABRIR]);
    }

    public function testUnaMetricaEnAdvertenciaAbreAlertaConSuNivel(): void
    {
        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-PRO-01' => $this->medicion(Escala::ADVERTENCIA, 70.0)]),
            $this->repositorio(),
        );

        $this->assertCount(1, $decisiones[MotorAlertas::ABRIR]);
        $this->assertSame('M-PRO-01', $decisiones[MotorAlertas::ABRIR][0]['codigo_metrica']);
        $this->assertSame(Escala::ADVERTENCIA, $decisiones[MotorAlertas::ABRIR][0]['nivel']);
    }

    /** La descripción dice qué falta, no solo que algo falta. */
    public function testUnaCompuertaCerradaDescribeQueFalta(): void
    {
        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-PRO-03' => [
                'abierta' => false,
                'estado'  => Escala::CRITICO,
                'detalle' => ['ausentes' => ['SMON']],
            ]]),
            $this->repositorio(),
        );

        $this->assertStringContainsString('SMON', $decisiones[MotorAlertas::ABRIR][0]['descripcion']);
    }

    /** Una anomalía de línea base alerta aunque el umbral no se haya cruzado. */
    public function testUnaAnomaliaDeLineaBaseAlertaSinCruzarUmbral(): void
    {
        $medicion = $this->medicion(Escala::OPTIMO, 95.0);
        $medicion['linea_base'] = ['media' => 99.0, 'desviacion' => 0.5, 'z' => -8.0, 'anomala' => true];

        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-MEM-01' => $medicion]),
            $this->repositorio(),
        );

        $this->assertCount(1, $decisiones[MotorAlertas::ABRIR]);
        $this->assertSame(Escala::ADVERTENCIA, $decisiones[MotorAlertas::ABRIR][0]['nivel']);
        $this->assertStringContainsString('línea base', $decisiones[MotorAlertas::ABRIR][0]['descripcion']);
    }

    // ── Deduplicación y ciclo de vida ──────────────────────────────────────

    /**
     * Un problema que empeora escala dentro de su alerta, no abre una segunda.
     *
     * Un tablespace que pasa de ADVERTENCIA a DEGRADADO y a CRÍTICO dejaría
     * tres alertas abiertas para un solo problema, que es el ruido que el §6
     * quiere evitar.
     */
    public function testUnProblemaQueEmpeoraEscalaDentroDeSuAlerta(): void
    {
        $repositorio = $this->repositorio();
        $id = $repositorio->precargarAlerta('FREEPDB1', 'M-ARC-01', Escala::ADVERTENCIA);

        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-ARC-01' => $this->medicion(Escala::CRITICO, 20.0)]),
            $repositorio,
        );

        $this->assertSame([], $decisiones[MotorAlertas::ABRIR]);
        $this->assertCount(1, $decisiones[MotorAlertas::ESCALAR]);
        $this->assertSame($id, $decisiones[MotorAlertas::ESCALAR][0]['id_alerta']);
        $this->assertSame(Escala::CRITICO, $decisiones[MotorAlertas::ESCALAR][0]['nivel']);
    }

    /** El mismo nivel tampoco duplica: la persistencia suma una ocurrencia. */
    public function testElMismoNivelNoAbreUnaSegundaAlerta(): void
    {
        $repositorio = $this->repositorio();
        $repositorio->precargarAlerta('FREEPDB1', 'M-ARC-01', Escala::DEGRADADO);

        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-ARC-01' => $this->medicion(Escala::DEGRADADO, 50.0)]),
            $repositorio,
        );

        $this->assertSame([], $decisiones[MotorAlertas::ABRIR]);
        $this->assertCount(1, $decisiones[MotorAlertas::ESCALAR]);
    }

    /** Cierre automático cuando la métrica vuelve a normal. */
    public function testUnaMetricaQueVuelveANormalCierraSuAlerta(): void
    {
        $repositorio = $this->repositorio();
        $id = $repositorio->precargarAlerta('FREEPDB1', 'M-ARC-01', Escala::DEGRADADO);

        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-ARC-01' => $this->medicion(Escala::OPTIMO, 98.0)]),
            $repositorio,
        );

        $this->assertCount(1, $decisiones[MotorAlertas::CERRAR]);
        $this->assertSame($id, $decisiones[MotorAlertas::CERRAR][0]['id_alerta']);
    }

    public function testUnaCondicionSostenidaGeneraUnaSolaAlerta(): void
    {
        $repositorio = $this->repositorio();
        $motor = new MotorAlertas();
        $medicion = $this->evaluada(['M-ARC-01' => $this->medicion(Escala::DEGRADADO, 50.0)]);

        $primera = $motor->decidir($medicion, $repositorio);

        $this->assertCount(1, $primera[MotorAlertas::ABRIR]);

        // La persistencia abre la alerta; de ahí en adelante ninguna muestra
        // debería querer abrir otra.
        $repositorio->precargarAlerta('FREEPDB1', 'M-ARC-01', Escala::DEGRADADO);

        for ($muestra = 2; $muestra <= 5; $muestra++) {
            $decisiones = $motor->decidir($medicion, $repositorio);

            $this->assertSame([], $decisiones[MotorAlertas::ABRIR], "muestra {$muestra}");
            $this->assertCount(1, $decisiones[MotorAlertas::ESCALAR], "muestra {$muestra}");
            $this->assertSame([], $decisiones[MotorAlertas::CERRAR], "muestra {$muestra}");
        }

        $this->assertCount(1, $repositorio->alertasAbiertas('FREEPDB1'));
    }

    /**
     * Una métrica que no se pudo medir no cierra su alerta.
     *
     * Sin dato no es «está bien». La alerta sigue abierta hasta que haya
     * evidencia de que el problema pasó.
     */
    public function testUnaMetricaSinDatoNoCierraSuAlerta(): void
    {
        $repositorio = $this->repositorio();
        $repositorio->precargarAlerta('FREEPDB1', 'M-ARC-01', Escala::CRITICO);

        $decisiones = (new MotorAlertas())->decidir($this->evaluada([]), $repositorio);

        $this->assertSame([], $decisiones[MotorAlertas::CERRAR]);
        $this->assertSame([], $decisiones[MotorAlertas::ABRIR]);
    }

    /** Las alertas de otra instancia no interfieren. */
    public function testLasAlertasDeOtraInstanciaNoInterfieren(): void
    {
        $repositorio = $this->repositorio();
        $repositorio->precargarAlerta('OTRA', 'M-ARC-01', Escala::CRITICO);

        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada(['M-ARC-01' => $this->medicion(Escala::CRITICO, 20.0)]),
            $repositorio,
        );

        $this->assertCount(1, $decisiones[MotorAlertas::ABRIR]);
        $this->assertSame([], $decisiones[MotorAlertas::ESCALAR]);
    }

    // ── Episodios ──────────────────────────────────────────────────────────

    /** @return list<array{origen: string, consecuencia: string}> */
    private function precedencias(): array
    {
        return [
            ['origen' => 'M-PRO-02', 'consecuencia' => 'M-PRO-01'],
            ['origen' => 'M-ARC-01', 'consecuencia' => 'M-ARC-02'],
        ];
    }

    /** Una sola alerta no es un episodio. */
    public function testUnaAlertaSolaNoFormaEpisodio(): void
    {
        $decisiones = (new MotorAlertas(Escala::ADVERTENCIA, $this->precedencias()))->decidir(
            $this->evaluada(['M-PRO-01' => $this->medicion(Escala::CRITICO, 20.0)]),
            $this->repositorio(),
        );

        $this->assertNull($decisiones['episodio']);
    }

    /**
     * Dentro de un episodio, la alerta que no es consecuencia de ninguna otra
     * presente se marca como causa probable.
     *
     * Agotados los procesos no hay dónde alojar sesiones nuevas, así que
     * `M-PRO-02` explica a `M-PRO-01` y no al revés.
     */
    public function testElEpisodioSenalaLaCausaProbable(): void
    {
        $decisiones = (new MotorAlertas(Escala::ADVERTENCIA, $this->precedencias()))->decidir(
            $this->evaluada([
                'M-PRO-01' => $this->medicion(Escala::CRITICO, 20.0),
                'M-PRO-02' => $this->medicion(Escala::DEGRADADO, 50.0),
            ]),
            $this->repositorio(),
        );

        $episodio = $decisiones['episodio'];

        $this->assertSame(['M-PRO-01', 'M-PRO-02'], $episodio['metricas']);
        $this->assertSame('M-PRO-02', $episodio['causa']);
    }

    /**
     * Con dos raíces no hay una causa que señalar sin inventarla.
     *
     * La versión 1 no infiere causalidad de los datos: presentar ruido como
     * causa en una herramienta de auditoría es peor que no ofrecer la función.
     */
    public function testSinUnaRaizUnicaNoSeSenalaCausa(): void
    {
        $decisiones = (new MotorAlertas(Escala::ADVERTENCIA, $this->precedencias()))->decidir(
            $this->evaluada([
                'M-PRO-02' => $this->medicion(Escala::CRITICO, 20.0),
                'M-ARC-01' => $this->medicion(Escala::DEGRADADO, 50.0),
            ]),
            $this->repositorio(),
        );

        $this->assertNotNull($decisiones['episodio']);
        $this->assertNull($decisiones['episodio']['causa']);
    }

    /** Sin precedencias declaradas no se deduce causa alguna. */
    public function testSinPrecedenciasNoHayCausa(): void
    {
        $decisiones = (new MotorAlertas())->decidir(
            $this->evaluada([
                'M-PRO-01' => $this->medicion(Escala::CRITICO, 20.0),
                'M-PRO-02' => $this->medicion(Escala::DEGRADADO, 50.0),
            ]),
            $this->repositorio(),
        );

        $this->assertNull($decisiones['episodio']['causa']);
    }
}

