<?php

declare(strict_types=1);

namespace Pruebas\Modelos;

use App\Models\Entidades\Metrica;
use App\Models\Entidades\Muestra;
use App\Models\RepositorioMonitorArreglo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pruebas\Muestras;

/**
 * Dos grupos de pruebas con propósitos distintos. Las primeras verifican que
 * el catálogo del arreglo y el del script de Oracle dicen lo mismo, que es el
 * riesgo real de tener el dato en dos sitios. Las segundas verifican que los
 * cuatro métodos que el motor usa para mirar hacia atrás se comportan como
 * promete el contrato, sobre todo cuando no hay nada hacia atrás que mirar.
 */
final class RepositorioMonitorArregloTest extends TestCase
{
    private const RAIZ = __DIR__ . '/../..';

    private function repositorio(): RepositorioMonitorArreglo
    {
        return RepositorioMonitorArreglo::desdeArchivo();
    }

    /** El texto del script semilla de Oracle, para comparar contra él. */
    private function scriptSemilla(): string
    {
        $ruta = self::RAIZ . '/Scripts/07_datos_semilla_monitor.sql';
        $contenido = file_get_contents($ruta);

        $this->assertIsString($contenido, "No se pudo leer {$ruta}");

        return $contenido;
    }

    // ── El catálogo dice lo mismo que el script ─────────────────────────────

    public function testElCatalogoTraeLasQuinceMetricas(): void
    {
        $metricas = $this->repositorio()->metricas();

        $this->assertCount(15, $metricas);

        $porComponente = [];

        foreach ($metricas as $metrica) {
            $porComponente[$metrica->componente] = ($porComponente[$metrica->componente] ?? 0) + 1;
        }

        $this->assertSame(
            ['ARCHIVOS' => 5, 'CONSULTAS' => 1, 'MEMORIA' => 3, 'PROCESOS' => 6],
            $porComponente,
        );
    }

    /**
     * La prueba que justifica tener el dato en dos sitios.
     *
     * El catálogo vive en `config/monitor-catalogo.php` y en
     * `Scripts/07_datos_semilla_monitor.sql`, y nada en el lenguaje impide que
     * alguien agregue una métrica en uno y se olvide del otro. Esto lo
     * detecta el mismo día.
     */
    public function testLosCodigosCoincidenConElScriptSemilla(): void
    {
        preg_match_all(
            "/INSERT INTO metrica \(.*?VALUES \('([A-Z]-[A-Z]{3}-\d{2})'/",
            $this->scriptSemilla(),
            $coincidencias,
        );

        $enElScript = $coincidencias[1];
        sort($enElScript);

        $enElArreglo = array_map(
            static fn (Metrica $m): string => $m->codigo,
            $this->repositorio()->metricas(),
        );
        sort($enElArreglo);

        $this->assertNotEmpty($enElScript, 'No se reconoció ningún INSERT de métrica en el script');
        $this->assertSame($enElScript, $enElArreglo);
    }

    public function testLosUmbralesCoincidenConElScriptSemilla(): void
    {
        preg_match_all(
            "/INSERT INTO umbral \(.*?VALUES \('([A-Z]-[A-Z]{3}-\d{2})', *(-?[\d.]+), *(-?[\d.]+), *(-?[\d.]+), *(-?[\d.]+)\)/",
            $this->scriptSemilla(),
            $coincidencias,
            PREG_SET_ORDER,
        );

        $enElScript = [];

        foreach ($coincidencias as $fila) {
            $enElScript[$fila[1]] = [(float) $fila[2], (float) $fila[3], (float) $fila[4], (float) $fila[5]];
        }

        $this->assertCount(11, $enElScript, 'Se esperaban 11 umbrales en el script');

        foreach ($this->repositorio()->metricas() as $metrica) {
            if ($metrica->esCompuerta()) {
                $this->assertNull(
                    $metrica->umbral,
                    "{$metrica->codigo} es compuerta y no debería llevar umbral",
                );
                continue;
            }

            $this->assertNotNull($metrica->umbral, "{$metrica->codigo} debería llevar umbral");
            $this->assertArrayHasKey($metrica->codigo, $enElScript);

            $this->assertSame(
                $enElScript[$metrica->codigo],
                [
                    $metrica->umbral->uOpt,
                    $metrica->umbral->uAdv,
                    $metrica->umbral->uDeg,
                    $metrica->umbral->uCrit,
                ],
                "Los umbrales de {$metrica->codigo} no coinciden con el script",
            );
        }
    }

    /**
     * Las dos métricas «mayor es mejor» llevan los umbrales transformados.
     *
     * Si alguien los «corrigiera» al valor del catálogo (95, 90, 80, 70), la
     * normalización quedaría al revés y la métrica mejoraría al empeorar. Es el
     * error más caro posible en este archivo y conviene que tenga nombre.
     */
    public function testLosUmbralesDeMayorEsMejorVanTransformados(): void
    {
        $porCodigo = [];

        foreach ($this->repositorio()->metricas() as $metrica) {
            $porCodigo[$metrica->codigo] = $metrica;
        }

        $this->assertSame(Metrica::MAYOR_MEJOR, $porCodigo['M-MEM-01']->sentido);
        $this->assertSame(5.0, $porCodigo['M-MEM-01']->umbral->uOpt);
        $this->assertSame(30.0, $porCodigo['M-MEM-01']->umbral->uCrit);

        $this->assertSame(Metrica::MAYOR_MEJOR, $porCodigo['M-MEM-03']->sentido);
        $this->assertSame(90.0, $porCodigo['M-MEM-03']->umbral->uOpt);
        $this->assertSame(99.0, $porCodigo['M-MEM-03']->umbral->uCrit);
    }

    /** El techo se declara y no siempre vale 100 (§1.5 del catálogo). */
    public function testLosTechosDeclaradosSobrevivenAlArreglo(): void
    {
        $techos = [];

        foreach ($this->repositorio()->metricas() as $metrica) {
            $techos[$metrica->codigo] = $metrica->uMax;
        }

        $this->assertSame(150.0, $techos['M-MEM-02']);
        $this->assertSame(20.0, $techos['M-PRO-04']);
        $this->assertSame(100.0, $techos['M-ARC-01']);
    }

    public function testCONSULTASNoEntraAlIsbd(): void
    {
        foreach ($this->repositorio()->metricas() as $metrica) {
            $this->assertSame(
                $metrica->componente !== 'CONSULTAS',
                $metrica->entraIsbd,
                "{$metrica->codigo} tiene entra_isbd al revés",
            );
        }
    }

    public function testLaInstanciaPropiaEstaVigilada(): void
    {
        $repositorio = $this->repositorio();

        $this->assertCount(1, $repositorio->instancias());

        $instancia = $repositorio->instancia('FREEPDB1');

        $this->assertNotNull($instancia);
        $this->assertSame('FREE', $instancia->servicioRaiz);
        $this->assertSame('FREEPDB1', $instancia->servicioContenedor);
        $this->assertTrue($instancia->activa);
        $this->assertFalse($instancia->demostrativa);

        $this->assertNull($repositorio->instancia('NO-EXISTE'));
    }

    public function testLasSeisPrecedenciasDeclaradas(): void
    {
        $precedencias = $this->repositorio()->precedencias();

        $this->assertCount(6, $precedencias);
        $this->assertContains(
            ['origen' => 'M-PRO-02', 'consecuencia' => 'M-PRO-01'],
            $precedencias,
        );
    }

    // ── Lo que el motor necesita para mirar hacia atrás ─────────────────────

    /**
     * Sin historial, los cuatro métodos responden vacío. No lanzan, no
     * devuelven ceros y no son un error: es el estado del primer día, y el
     * motor tiene que saber distinguirlo de una lectura fallida.
     */
    public function testSinHistorialTodoRespondeVacio(): void
    {
        $repositorio = $this->repositorio();

        $this->assertSame([], $repositorio->acumuladosAnteriores('FREEPDB1'));
        $this->assertSame([], $repositorio->huellasAnteriores('FREEPDB1'));
        $this->assertSame([], $repositorio->estadosRecientes('FREEPDB1', 'M-PRO-01', 3));
        $this->assertSame([], $repositorio->ventanaLineaBase('FREEPDB1', 'M-PRO-01', 14, 14));
        $this->assertSame([], $repositorio->ultimasMuestras());
        $this->assertNull($repositorio->ultimaMuestra('FREEPDB1'));
    }

    public function testPrecargarRechazaUnaInstanciaNoVigilada(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->repositorio()->precargar([
            'instancia' => 'PRODCORE1',
            'tomada_en' => '2026-08-19T14:35:02+00:00',
        ]);
    }

    public function testUltimaMuestraDevuelveLaMasRecienteConSuIndice(): void
    {
        $repositorio = $this->repositorio();

        // A propósito fuera de orden: el repositorio ordena por tomada_en, no
        // por el orden en que se precargaron.
        $repositorio->precargar($this->muestraEvaluada('2026-08-19T15:00:00+00:00', 88.0));
        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:00:00+00:00', 95.0));

        $muestra = $repositorio->ultimaMuestra('FREEPDB1');

        $this->assertNotNull($muestra);
        $this->assertSame('2026-08-19T15:00:00+00:00', $muestra->tomadaEn);
        $this->assertSame(88.0, $muestra->isbd);
        $this->assertSame('SALUDABLE', $muestra->estado);
        $this->assertSame(['M-PRO-01'], $muestra->causa);
        $this->assertTrue($muestra->tieneIndice());
        $this->assertCount(1, $repositorio->ultimasMuestras());
    }

    public function testEstadosRecientesVaDelMasNuevoAlMasViejoYRespetaElLimite(): void
    {
        $repositorio = $this->repositorio();

        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:00:00+00:00', 95.0, 'OPTIMO'));
        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:05:00+00:00', 80.0, 'SALUDABLE'));
        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:10:00+00:00', 70.0, 'ADVERTENCIA'));
        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:15:00+00:00', 50.0, 'DEGRADADO'));

        $this->assertSame(
            ['DEGRADADO', 'ADVERTENCIA', 'SALUDABLE'],
            $repositorio->estadosRecientes('FREEPDB1', 'M-PRO-01', 3),
        );

        // Menos muestras que las pedidas: devuelve las que hay, no rellena.
        $this->assertCount(4, $repositorio->estadosRecientes('FREEPDB1', 'M-PRO-01', 10));
    }

    public function testAcumuladosYHuellasSalenDeLaMuestraInmediatamenteAnterior(): void
    {
        $repositorio = $this->repositorio();

        $repositorio->precargar($this->muestraConContinuidad('2026-08-19T14:00:00+00:00', 8000, 'PMON:1000'));
        $repositorio->precargar($this->muestraConContinuidad('2026-08-19T14:05:00+00:00', 8412, 'PMON:1234'));

        $this->assertSame(
            ['M-PRO-04.esperas' => 8412.0, 'M-PRO-04.micros' => 10430880.0],
            $repositorio->acumuladosAnteriores('FREEPDB1'),
        );

        $this->assertSame(
            ['M-PRO-05' => 'PMON:1234'],
            $repositorio->huellasAnteriores('FREEPDB1'),
        );
    }

    /**
     * La ventana de línea base filtra por tramo horario y por antigüedad.
     *
     * Las tres muestras de las 14 h de días distintos entran; la de las 9 h
     * queda fuera por tramo y la de hace un mes por antigüedad.
     */
    public function testVentanaLineaBaseFiltraPorTramoHorarioYPorDias(): void
    {
        $repositorio = $this->repositorio();

        $repositorio->precargar($this->muestraEvaluada('2026-07-01T14:05:00+00:00', 60.0)); // vieja
        $repositorio->precargar($this->muestraEvaluada('2026-08-17T09:05:00+00:00', 70.0)); // otro tramo
        $repositorio->precargar($this->muestraEvaluada('2026-08-17T14:05:00+00:00', 91.0));
        $repositorio->precargar($this->muestraEvaluada('2026-08-18T14:05:00+00:00', 92.0));
        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:05:00+00:00', 93.0));

        $ventana = $repositorio->ventanaLineaBase('FREEPDB1', 'M-PRO-01', 14, 14);

        $this->assertSame([91.0, 92.0, 93.0], $ventana);
    }

    public function testSerieIndiceOmiteLasMuestrasSinIndicePublicado(): void
    {
        $repositorio = $this->repositorio();

        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:00:00+00:00', 95.0));
        $repositorio->precargar([
            'instancia'     => 'FREEPDB1',
            'tomada_en'     => '2026-08-19T14:05:00+00:00',
            'resultado'     => Muestra::PARCIAL,
            'cobertura_pct' => 20.0,
            'mediciones'    => [],
        ]);
        $repositorio->precargar($this->muestraEvaluada('2026-08-19T14:10:00+00:00', 90.0));

        $serie = $repositorio->serieIndice(
            'FREEPDB1',
            '2026-08-19T00:00:00+00:00',
            '2026-08-20T00:00:00+00:00',
        );

        $this->assertCount(2, $serie);
        $this->assertSame(95.0, $serie[0]['isbd']);
        $this->assertSame(90.0, $serie[1]['isbd']);
    }

    public function testMedicionesDevuelveElDetalleDeUnaMuestra(): void
    {
        $repositorio = $this->repositorio();
        $id = $repositorio->precargar(
            $this->muestraEvaluada('2026-08-19T14:00:00+00:00', 95.0, 'OPTIMO', 93.2)
        );

        $mediciones = $repositorio->mediciones($id);

        $this->assertCount(1, $mediciones);
        $this->assertSame('M-PRO-01', $mediciones[0]->codigoMetrica);
        $this->assertSame(34.16, $mediciones[0]->valorCrudo);
        $this->assertSame(93.2, $mediciones[0]->valorNormalizado);
        $this->assertSame(1, $mediciones[0]->idUmbral);

        $this->assertSame([], $repositorio->mediciones(9999));
    }

    // ── Las muestras crudas de ejemplo ──────────────────────────────────────

    public function testLasMuestrasDeEjemploUsanCodigosDelCatalogo(): void
    {
        $muestras = Muestras::todas();

        $conocidos = array_map(
            static fn (Metrica $m): string => $m->codigo,
            $this->repositorio()->metricas(),
        );

        foreach ($muestras as $nombre => $muestra) {
            $this->assertSame('FREEPDB1', $muestra['instancia'], "{$nombre}: instancia desconocida");
            $this->assertArrayHasKey('contextos', $muestra, "{$nombre}: falta contextos");

            foreach (array_keys($muestra['lecturas']) as $codigo) {
                $this->assertContains($codigo, $conocidos, "{$nombre}: {$codigo} no está en el catálogo");
            }
        }
    }

    public function testLaMuestraCompletaEsLaDelContrato(): void
    {
        $muestras = Muestras::todas();
        $completa = $muestras['completa'];

        $this->assertCount(10, $completa['lecturas']);

        // Una de cada tipo de lectura del §3.1.
        $this->assertSame(34.16, $completa['lecturas']['M-PRO-01']['valor']);
        $this->assertArrayHasKey('acumulados', $completa['lecturas']['M-PRO-04']);
        $this->assertTrue($completa['lecturas']['M-PRO-03']['abierta']);

        // La cabecera declara cómo fue cada conexión.
        $this->assertSame('OK', $completa['contextos']['RAIZ']['estado']);
        $this->assertSame('OK', $completa['contextos']['CONTENEDOR']['estado']);
    }

    public function testLaMuestraFallidaNoTraeLecturas(): void
    {
        $muestras = Muestras::todas();

        $this->assertSame([], $muestras['fallida']['lecturas']);
        $this->assertSame('ERROR', $muestras['fallida']['contextos']['RAIZ']['estado']);
        $this->assertSame('ERROR', $muestras['fallida']['contextos']['CONTENEDOR']['estado']);

        // Y la de contexto caído sí trae las de la conexión que sobrevivió.
        $this->assertSame('ERROR', $muestras['contexto-raiz-caido']['contextos']['RAIZ']['estado']);
        $this->assertSame('OK', $muestras['contexto-raiz-caido']['contextos']['CONTENEDOR']['estado']);
        $this->assertCount(2, $muestras['contexto-raiz-caido']['lecturas']);
    }

    // ── Ayudantes ───────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function muestraEvaluada(
        string $tomadaEn,
        float $isbd,
        string $estado = 'SALUDABLE',
        ?float $normalizado = null,
    ): array {
        $normalizado ??= $isbd;

        return [
            'instancia'     => 'FREEPDB1',
            'tomada_en'     => $tomadaEn,
            'resultado'     => Muestra::OK,
            'cobertura_pct' => 100.0,
            'mediciones'    => [
                'M-PRO-01' => [
                    'valor_crudo'       => 34.16,
                    'valor_normalizado' => $normalizado,
                    'estado'            => $estado,
                    'umbral_id'         => 1,
                ],
            ],
            'componentes' => [
                'PROCESOS' => ['bruto' => $isbd, 'publicado' => $isbd, 'estado' => $estado],
            ],
            'indice' => [
                'isbd_bruto' => $isbd,
                'isbd'       => $isbd,
                'estado'     => $estado,
                'causa'      => ['M-PRO-01'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function muestraConContinuidad(string $tomadaEn, int $esperas, string $huella): array
    {
        return [
            'instancia'  => 'FREEPDB1',
            'tomada_en'  => $tomadaEn,
            'resultado'  => Muestra::OK,
            'mediciones' => [
                'M-PRO-04' => [
                    'valor_crudo' => 1.24,
                    'estado'      => 'OPTIMO',
                    'acumulados'  => ['esperas' => $esperas, 'micros' => 10430880],
                ],
                'M-PRO-05' => [
                    'estado' => 'OPTIMO',
                    'huella' => $huella,
                ],
            ],
        ];
    }
}

