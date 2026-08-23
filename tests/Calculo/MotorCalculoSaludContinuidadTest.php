<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\Escala;
use App\Models\Calculo\MotorCalculoSalud;
use App\Models\RepositorioMonitorArreglo;
use PHPUnit\Framework\TestCase;
use Pruebas\Muestras;

/**
 * Lo que depende del tiempo: tasas, identidad e histéresis
 */
final class MotorCalculoSaludContinuidadTest extends TestCase
{
    private function motor(): MotorCalculoSalud
    {
        return new MotorCalculoSalud();
    }

    private function repositorio(): RepositorioMonitorArreglo
    {
        return RepositorioMonitorArreglo::desdeArchivo();
    }

    /** @return array<string, mixed> */
    private function cruda(string $tomadaEn, int $esperas, int $micros): array
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['tomada_en'] = $tomadaEn;
        $cruda['lecturas']['M-PRO-04'] = [
            'estado'     => 'OK',
            'acumulados' => ['esperas' => $esperas, 'micros' => $micros],
        ];

        return $cruda;
    }

    /** Deja una muestra evaluada en el historial. */
    private function precargarTasa(
        RepositorioMonitorArreglo $repositorio,
        string $tomadaEn,
        int $esperas,
        int $micros,
    ): void {
        $repositorio->precargar([
            'instancia'  => 'FREEPDB1',
            'tomada_en'  => $tomadaEn,
            'resultado'  => 'OK',
            'mediciones' => [
                'M-PRO-04' => [
                    'estado'     => Escala::OPTIMO,
                    'acumulados' => ['esperas' => $esperas, 'micros' => $micros],
                ],
            ],
        ]);
    }

    // ── Tasas ──────────────────────────────────────────────────────────────

    /** Primera muestra: no es un error, es que falta el punto de comparación. */
    public function testLaPrimeraMuestraDeUnaTasaSaleDelDenominador(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorio(),
        );

        $this->assertArrayNotHasKey('M-PRO-04', $evaluada['mediciones']);
        $this->assertSame(MotorCalculoSalud::SIN_DERIVAR, $evaluada['fuera']['M-PRO-04']);
    }

    /**
     * `(Δmicros / Δesperas) / 1000` da milisegundos por escritura de redo.
     *
     * 1 000 000 microsegundos repartidos en 500 escrituras son 2 ms, que sobre
     * el techo de 20 ms y los umbrales 10/14/17/19 normaliza a 98,0.
     */
    public function testLaTasaSeDerivaDeLaDiferenciaEntreMuestras(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarTasa($repositorio, '2026-08-19T14:30:00+00:00', 8000, 10000000);

        $evaluada = $this->motor()->evaluar(
            $this->cruda('2026-08-19T14:35:00+00:00', 8500, 11000000),
            $repositorio,
        );

        $medicion = $evaluada['mediciones']['M-PRO-04'];

        $this->assertSame(2.0, $medicion['valor_crudo']);
        $this->assertSame(98.0, $medicion['valor_normalizado']);
        $this->assertSame(Escala::OPTIMO, $medicion['estado']);
        $this->assertSame(['esperas' => 8500, 'micros' => 11000000], $medicion['acumulados']);
    }

    /** La media de toda la vida de la instancia no es la tasa del momento. */
    public function testLaTasaNoEsElAcumuladoDividido(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarTasa($repositorio, '2026-08-19T14:30:00+00:00', 8000, 10000000);

        // Acumulado: 8500 esperas y 11 s → 1,29 ms de media histórica.
        // Diferencia: 500 esperas y 1 s → 2,0 ms ahora.
        $evaluada = $this->motor()->evaluar(
            $this->cruda('2026-08-19T14:35:00+00:00', 8500, 11000000),
            $repositorio,
        );

        $this->assertSame(2.0, $evaluada['mediciones']['M-PRO-04']['valor_crudo']);
    }

    /** Un acumulado que baja significa reinicio: se descarta el tramo. */
    public function testUnAcumuladoQueBajaDescartaElTramo(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarTasa($repositorio, '2026-08-19T14:30:00+00:00', 8000, 10000000);

        $evaluada = $this->motor()->evaluar(
            $this->cruda('2026-08-19T14:35:00+00:00', 120, 150000),
            $repositorio,
        );

        $this->assertArrayNotHasKey('M-PRO-04', $evaluada['mediciones']);
        $this->assertSame(MotorCalculoSalud::REINICIO, $evaluada['fuera']['M-PRO-04']);
    }

    /** Sin escrituras entre muestras la tasa es indefinida, no cero. */
    public function testSinActividadNoSeInventaUnaTasaDeCero(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarTasa($repositorio, '2026-08-19T14:30:00+00:00', 8000, 10000000);

        $evaluada = $this->motor()->evaluar(
            $this->cruda('2026-08-19T14:35:00+00:00', 8000, 10000000),
            $repositorio,
        );

        $this->assertArrayNotHasKey('M-PRO-04', $evaluada['mediciones']);
        $this->assertSame(MotorCalculoSalud::SIN_ACTIVIDAD, $evaluada['fuera']['M-PRO-04']);
    }

    /** Reinicio y primera muestra salen del denominador: no penalizan cobertura. */
    public function testLosMotivosDeTasaNoBajanLaCobertura(): void
    {
        $repositorio = $this->repositorio();
        $sinHistorial = $this->motor()->evaluar(Muestras::cruda(Muestras::COMPLETA), $repositorio);

        $this->precargarTasa($repositorio, '2026-08-19T14:30:00+00:00', 8000, 10000000);
        $conReinicio = $this->motor()->evaluar(
            $this->cruda('2026-08-19T14:35:00+00:00', 10, 200),
            $repositorio,
        );

        $this->assertSame($sinHistorial['cobertura_pct'], $conReinicio['cobertura_pct']);
    }

    // ── Identidad ──────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function crudaConHuella(string $tomadaEn, string $huella): array
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['tomada_en'] = $tomadaEn;
        $cruda['lecturas']['M-PRO-05'] = ['estado' => 'OK', 'identidad' => $huella];

        return $cruda;
    }

    private function precargarHuella(RepositorioMonitorArreglo $repositorio, string $huella): void
    {
        $repositorio->precargar([
            'instancia'  => 'FREEPDB1',
            'tomada_en'  => '2026-08-19T14:30:00+00:00',
            'resultado'  => 'OK',
            'mediciones' => ['M-PRO-05' => ['estado' => Escala::OPTIMO, 'huella' => $huella]],
        ]);
    }

    /** Misma huella: los procesos de fondo no se reiniciaron. */
    public function testUnaHuellaIgualDejaLaCompuertaAbierta(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarHuella($repositorio, 'CKPT:1238,DBW0:1236,LGWR:1237,PMON:1234,SMON:1235');

        $evaluada = $this->motor()->evaluar(
            $this->crudaConHuella('2026-08-19T14:35:00+00:00', 'CKPT:1238,DBW0:1236,LGWR:1237,PMON:1234,SMON:1235'),
            $repositorio,
        );

        $medicion = $evaluada['mediciones']['M-PRO-05'];

        $this->assertTrue($medicion['abierta']);
        $this->assertSame(Escala::OPTIMO, $medicion['estado']);
        $this->assertArrayNotHasKey('valor_normalizado', $medicion);
    }

    /**
     * Un `spid` distinto es un proceso que cayó y volvió entre muestras, algo
     * que M-PRO-03 no ve porque solo mira una foto.
     */
    public function testUnaHuellaDistintaCierraLaCompuertaYGuardaElCambio(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarHuella($repositorio, 'CKPT:1238,DBW0:1236,LGWR:1237,PMON:1234,SMON:1235');

        $evaluada = $this->motor()->evaluar(
            $this->crudaConHuella('2026-08-19T14:35:00+00:00', 'CKPT:1238,DBW0:1236,LGWR:1237,PMON:1234,SMON:9999'),
            $repositorio,
        );

        $medicion = $evaluada['mediciones']['M-PRO-05'];

        $this->assertFalse($medicion['abierta']);
        $this->assertSame(Escala::CRITICO, $medicion['estado']);
        $this->assertStringContainsString('SMON:1235', $medicion['detalle']['anterior']);
        $this->assertStringContainsString('SMON:9999', $medicion['detalle']['actual']);
        $this->assertSame(Escala::CRITICO, $evaluada['componentes']['PROCESOS']['estado']);
    }

    /** La huella actual se guarda para que la muestra siguiente pueda comparar. */
    public function testLaHuellaSeGuardaParaLaProximaMuestra(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarHuella($repositorio, 'PMON:1000');

        $evaluada = $this->motor()->evaluar(
            $this->crudaConHuella('2026-08-19T14:35:00+00:00', 'PMON:1000'),
            $repositorio,
        );

        $this->assertSame('PMON:1000', $evaluada['mediciones']['M-PRO-05']['huella']);
    }

    // ── Histéresis ─────────────────────────────────────────────────────────

    /** @param list<string> $estados */
    private function conHistorial(array $estados): RepositorioMonitorArreglo
    {
        $repositorio = $this->repositorio();
        $minuto = 0;

        foreach ($estados as $estado) {
            $repositorio->precargar([
                'instancia'  => 'FREEPDB1',
                'tomada_en'  => sprintf('2026-08-19T14:%02d:00+00:00', $minuto++),
                'resultado'  => 'OK',
                'mediciones' => ['M-PRO-01' => ['estado' => $estado, 'valor_normalizado' => 90.0]],
            ]);
        }

        return $repositorio;
    }

    /** @return array<string, mixed> */
    private function crudaConSesiones(float $valor): array
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['tomada_en'] = '2026-08-19T15:00:00+00:00';
        $cruda['lecturas']['M-PRO-01'] = ['estado' => 'OK', 'valor' => $valor];

        return $cruda;
    }

    /**
     * Un pico aislado no cambia el estado publicado.
     *
     * Con dos muestras ÓPTIMO detrás, una sola lectura en DEGRADADO no reúne
     * las dos de tres que exige subir de severidad. Una alerta que parpadea
     * entrena al operador a ignorarla.
     */
    public function testUnPicoAisladoNoCambiaElEstado(): void
    {
        $repositorio = $this->conHistorial([Escala::OPTIMO, Escala::OPTIMO]);

        $medicion = $this->motor()
            ->evaluar($this->crudaConSesiones(90.0), $repositorio)['mediciones']['M-PRO-01'];

        $this->assertSame(Escala::OPTIMO, $medicion['estado']);
        $this->assertSame(Escala::DEGRADADO, $medicion['estado_observado']);
    }

    /** El valor no se suaviza: lo que se sostiene es la etiqueta. */
    public function testLaHisteresisNoTocaElValorMedido(): void
    {
        $repositorio = $this->conHistorial([Escala::OPTIMO, Escala::OPTIMO]);

        $medicion = $this->motor()
            ->evaluar($this->crudaConSesiones(90.0), $repositorio)['mediciones']['M-PRO-01'];

        $this->assertSame(90.0, $medicion['valor_crudo']);
        $this->assertSame(50.0, $medicion['valor_normalizado']);
    }

    /** Dos de tres sostienen el empeoramiento y el estado sí cambia. */
    public function testDosDeTresSostienenUnEmpeoramiento(): void
    {
        $repositorio = $this->conHistorial([Escala::DEGRADADO, Escala::OPTIMO]);

        $medicion = $this->motor()
            ->evaluar($this->crudaConSesiones(90.0), $repositorio)['mediciones']['M-PRO-01'];

        $this->assertSame(Escala::DEGRADADO, $medicion['estado']);
        $this->assertArrayNotHasKey('estado_observado', $medicion);
    }

    /**
     * Una mejora se publica de inmediato: limitación declarada de esta versión.
     *
     * El plan pide toda la ventana también para bajar, pero `medicion` guarda
     * el estado publicado y no el observado, así que la historia no distingue
     * una métrica que estuvo mal de una que pareció estarlo.
     */
    public function testUnaMejoraSePublicaDeInmediato(): void
    {
        $repositorio = $this->conHistorial([Escala::DEGRADADO, Escala::DEGRADADO]);

        $medicion = $this->motor()
            ->evaluar($this->crudaConSesiones(10.0), $repositorio)['mediciones']['M-PRO-01'];

        $this->assertSame(Escala::OPTIMO, $medicion['estado']);
        $this->assertArrayNotHasKey('estado_observado', $medicion);
    }

    /** Sin historial no hay nada que suavizar: se publica lo observado. */
    public function testSinHistorialSePublicaLoObservado(): void
    {
        $medicion = $this->motor()
            ->evaluar($this->crudaConSesiones(90.0), $this->repositorio())['mediciones']['M-PRO-01'];

        $this->assertSame(Escala::DEGRADADO, $medicion['estado']);
        $this->assertArrayNotHasKey('estado_observado', $medicion);
    }

    /**
     * Un empeoramiento suprimido no dispara el tope del componente.
     *
     * Sin historial, M-PRO-01 en DEGRADADO topa PROCESOS en 60,0. Con dos
     * muestras ÓPTIMO detrás el pico no se sostiene, el tope no actúa y el
     * componente publica su promedio. El número baja igual, que es lo correcto:
     * la histéresis suaviza la etiqueta, no la medida.
     */
    public function testUnEmpeoramientoSuprimidoNoDisparaElTope(): void
    {
        $sinHistorial = $this->motor()
            ->evaluar($this->crudaConSesiones(90.0), $this->repositorio());

        $conHistorial = $this->motor()
            ->evaluar($this->crudaConSesiones(90.0), $this->conHistorial([Escala::OPTIMO, Escala::OPTIMO]));

        $this->assertSame(60.0, $sinHistorial['componentes']['PROCESOS']['publicado']);

        $procesos = $conHistorial['componentes']['PROCESOS'];

        $this->assertSame($procesos['bruto'], $procesos['publicado']);
        $this->assertGreaterThan(60.0, $procesos['publicado']);
    }

    /**
     * Las compuertas no se suavizan: un proceso de fondo que se reinició es un
     * hecho, no una lectura cerca de un umbral.
     */
    public function testLasCompuertasNoPasanPorLaHisteresis(): void
    {
        $repositorio = $this->repositorio();
        $this->precargarHuella($repositorio, 'PMON:1000');

        $evaluada = $this->motor()->evaluar(
            $this->crudaConHuella('2026-08-19T14:35:00+00:00', 'PMON:9999'),
            $repositorio,
        );

        $this->assertSame(Escala::CRITICO, $evaluada['mediciones']['M-PRO-05']['estado']);
        $this->assertArrayNotHasKey('estado_observado', $evaluada['mediciones']['M-PRO-05']);
    }
}
