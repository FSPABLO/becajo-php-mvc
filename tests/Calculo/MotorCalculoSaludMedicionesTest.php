<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\Escala;
use App\Models\Calculo\MotorCalculoSalud;
use App\Models\RepositorioMonitorArreglo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * El bloque `mediciones` del §5 del contrato de muestra.
 *
 * La prueba central compara celda por celda contra el ejemplo del contrato,
 * que trae lecturas reales.
 */
final class MotorCalculoSaludMedicionesTest extends TestCase
{
    private const RAIZ = __DIR__ . '/../..';

    private function motor(): MotorCalculoSalud
    {
        return new MotorCalculoSalud();
    }

    private function repositorio(): RepositorioMonitorArreglo
    {
        return RepositorioMonitorArreglo::desdeArchivo();
    }

    /** @return array<string, mixed> */
    private function muestra(string $nombre): array
    {
        /** @var array<string, array<string, mixed>> $muestras */
        $muestras = require self::RAIZ . '/config/monitor-muestras.php';

        return $muestras[$nombre];
    }

    // ── El ejemplo del contrato ────────────────────────────────────────────

    /** Las seis proporciones evaluables del ejemplo, con sus cifras exactas. */
    public function testLasProporcionesDelEjemploDelContrato(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestra('completa'), $this->repositorio());

        $esperadas = [
            // codigo   => [crudo, normalizado, estado, umbral_id]
            'M-PRO-01' => [34.16,  93.2, Escala::OPTIMO,    1],
            'M-PRO-02' => [43.00,  91.4, Escala::OPTIMO,    2],
            'M-MEM-01' => [100.00, 100.0, Escala::OPTIMO,   5],
            'M-MEM-02' => [75.64,  85.8, Escala::SALUDABLE, 6],
            'M-MEM-03' => [18.80,  91.0, Escala::OPTIMO,    7],
            'M-ARC-01' => [0.03,   100.0, Escala::OPTIMO,   8],
        ];

        foreach ($esperadas as $codigo => [$crudo, $normalizado, $estado, $idUmbral]) {
            $this->assertArrayHasKey($codigo, $evaluada['mediciones'], "Falta {$codigo}");

            $medicion = $evaluada['mediciones'][$codigo];

            $this->assertSame($crudo, $medicion['valor_crudo'], "{$codigo}: valor crudo");
            $this->assertSame($normalizado, $medicion['valor_normalizado'], "{$codigo}: normalizado");
            $this->assertSame($estado, $medicion['estado'], "{$codigo}: estado");
            $this->assertSame($idUmbral, $medicion['umbral_id'], "{$codigo}: umbral vigente");
        }
    }

    /** Las tres compuertas del ejemplo: estado, sin valor normalizado. */
    public function testLasCompuertasDelEjemploNoLlevanValorNormalizado(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestra('completa'), $this->repositorio());

        foreach (['M-PRO-03', 'M-ARC-02', 'M-ARC-03'] as $codigo) {
            $medicion = $evaluada['mediciones'][$codigo];

            $this->assertTrue($medicion['abierta'], "{$codigo}: debería estar abierta");
            $this->assertSame(Escala::OPTIMO, $medicion['estado']);
            $this->assertArrayNotHasKey('valor_normalizado', $medicion);
            $this->assertArrayNotHasKey('valor_crudo', $medicion);
            $this->assertArrayNotHasKey('umbral_id', $medicion);
        }
    }

    /** Nueve mediciones evaluadas y ninguna lectura desconocida. */
    public function testElEjemploProduceNueveMediciones(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestra('completa'), $this->repositorio());

        $this->assertCount(9, $evaluada['mediciones']);
        $this->assertSame([], $evaluada['ignoradas']);
        $this->assertSame('FREEPDB1', $evaluada['instancia']);
        $this->assertSame('2026-08-19T14:35:02+00:00', $evaluada['tomada_en']);
        $this->assertSame(842, $evaluada['duracion_ms']);
    }

    /**
     * Las seis que faltan salen anotadas, no desaparecen.
     *
     * Las otras cinco porque no vienen en la muestra —son las de la
     * v1 del catálogo, todavía sin lectura de calibración—. En los seis casos
     * el motor sabe decir por qué, que es lo que después baja la cobertura en
     * vez de pasar inadvertido.
     */
    public function testLasMetricasNoEvaluadasQuedanAnotadasConSuMotivo(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestra('completa'), $this->repositorio());

        $this->assertSame(
            [
                'M-ARC-04' => MotorCalculoSalud::SIN_LECTURA,
                'M-ARC-05' => MotorCalculoSalud::SIN_LECTURA,
                'M-CON-01' => MotorCalculoSalud::SIN_LECTURA,
                'M-PRO-04' => MotorCalculoSalud::SIN_DERIVAR,
                'M-PRO-05' => MotorCalculoSalud::SIN_LECTURA,
                'M-PRO-06' => MotorCalculoSalud::SIN_LECTURA,
            ],
            $evaluada['fuera'],
        );
    }

    /** El orden de salida es el del catálogo, no el de la muestra. */
    public function testElOrdenDeLasMedicionesEsElDelCatalogo(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestra('completa'), $this->repositorio());

        $this->assertSame(
            ['M-ARC-01', 'M-ARC-02', 'M-ARC-03', 'M-MEM-01', 'M-MEM-02', 'M-MEM-03',
             'M-PRO-01', 'M-PRO-02', 'M-PRO-03'],
            array_keys($evaluada['mediciones']),
        );
    }

    // ── Invariante 3: sin dato no es cero ──────────────────────────────────

    /**Una lectura VACIA, ERROR o NO_APLICA no produce medición. */
    public function testUnaLecturaSinDatosNoProduceMedicionNiVale0(): void
    {
        foreach (['VACIA', 'ERROR', 'NO_APLICA'] as $estado) {
            $cruda = $this->muestra('completa');
            $cruda['lecturas']['M-PRO-01'] = [
                'estado'  => $estado,
                'mensaje' => 'v$resource_limit devolvió 0 filas',
            ];

            $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());

            $this->assertArrayNotHasKey('M-PRO-01', $evaluada['mediciones'], $estado);
            $this->assertSame($estado, $evaluada['fuera']['M-PRO-01']);
        }
    }

    /** Una lectura que declara OK pero no trae su campo tampoco vale 0. */
    public function testUnaLecturaIncompletaSaleFuera(): void
    {
        $cruda = $this->muestra('completa');
        $cruda['lecturas']['M-PRO-01'] = ['estado' => 'OK'];          // sin valor
        $cruda['lecturas']['M-PRO-03'] = ['estado' => 'OK', 'detalle' => []]; // sin abierta

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());

        $this->assertArrayNotHasKey('M-PRO-01', $evaluada['mediciones']);
        $this->assertArrayNotHasKey('M-PRO-03', $evaluada['mediciones']);
        $this->assertSame(MotorCalculoSalud::LECTURA_INCOMPLETA, $evaluada['fuera']['M-PRO-01']);
        $this->assertSame(MotorCalculoSalud::LECTURA_INCOMPLETA, $evaluada['fuera']['M-PRO-03']);
    }

    /** La muestra fallida no produce ninguna medición y no revienta. */
    public function testLaMuestraFallidaNoProduceMediciones(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestra('fallida'), $this->repositorio());

        $this->assertSame([], $evaluada['mediciones']);
        $this->assertCount(15, $evaluada['fuera']);
    }

    // ── Compuertas cerradas ────────────────────────────────────────────────

    /** Una compuerta cerrada es CRÍTICO y conserva su detalle. */
    public function testUnaCompuertaCerradaEsCriticaYConservaSuDetalle(): void
    {
        $cruda = $this->muestra('completa');
        $cruda['lecturas']['M-PRO-03'] = [
            'estado'  => 'OK',
            'abierta' => false,
            'detalle' => [
                'esperados' => ['PMON', 'SMON', 'DBW0', 'LGWR', 'CKPT'],
                'ausentes'  => ['SMON'],
            ],
        ];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());
        $medicion = $evaluada['mediciones']['M-PRO-03'];

        $this->assertFalse($medicion['abierta']);
        $this->assertSame(Escala::CRITICO, $medicion['estado']);
        $this->assertSame(['SMON'], $medicion['detalle']['ausentes']);
    }

    // ── Casos del contrato que no son del ejemplo ──────────────────────────

    /** Una lectura que el catálogo no conoce se ignora y se registra.
     * Es lo que pasa cuando el agente va por delante del catálogo, y no debe
     * detener la evaluación de las demás (§8 del contrato).
     */
    public function testUnaLecturaDesconocidaSeIgnoraYSeRegistra(): void
    {
        $cruda = $this->muestra('completa');
        $cruda['lecturas']['M-XXX-99'] = ['estado' => 'OK', 'valor' => 42.0];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());

        $this->assertSame(['M-XXX-99'], $evaluada['ignoradas']);
        $this->assertArrayNotHasKey('M-XXX-99', $evaluada['mediciones']);
        $this->assertCount(9, $evaluada['mediciones']);
    }

    /**
     * «Mayor es mejor» guarda el crudo tal como se midió.
     *
     * La transformación `techo − v` vive dentro de la normalización. Si se
     * filtrara al dato persistido, el tablero mostraría 81,2 en vez de 18,8 %
     * libre, que es lo que un DBA reconoce.
     */
    public function testMayorEsMejorPersisteElCrudoSinTransformar(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestra('completa'), $this->repositorio());

        $this->assertSame(18.80, $evaluada['mediciones']['M-MEM-03']['valor_crudo']);
        $this->assertSame(91.0, $evaluada['mediciones']['M-MEM-03']['valor_normalizado']);

        $this->assertSame(100.00, $evaluada['mediciones']['M-MEM-01']['valor_crudo']);
        $this->assertSame(100.0, $evaluada['mediciones']['M-MEM-01']['valor_normalizado']);
    }

    /** El techo declarado se respeta: M-MEM-02 llega a 150, no se satura en 100. */
    public function testElTechoDeclaradoSeAplicaAlNormalizar(): void
    {
        $cruda = $this->muestra('completa');
        $cruda['lecturas']['M-MEM-02'] = ['estado' => 'OK', 'valor' => 130.0];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());
        $medicion = $evaluada['mediciones']['M-MEM-02'];

        // 130 cae en el tramo [120, 150] -> entre 40 y 0.
        $this->assertSame(26.7, $medicion['valor_normalizado']);
        $this->assertSame(Escala::CRITICO, $medicion['estado']);
    }

    /** Una proporción sin umbral vigente es un catálogo roto, no un cero. */
    public function testUnaProporcionSinUmbralLanzaExcepcion(): void
    {
        $repositorio = new RepositorioMonitorArreglo([
            'instancias' => [['clave' => 'FREEPDB1', 'nombre' => 'FREEPDB1', 'motor' => 'Oracle',
                              'host' => 'oracle', 'puerto' => 1521, 'servicio_raiz' => 'FREE',
                              'servicio_contenedor' => 'FREEPDB1', 'entorno' => 'Aplicación',
                              'criticidad' => 'MEDIA', 'activa' => 1, 'demostrativa' => 0]],
            'metricas' => [[
                'codigo' => 'M-PRO-01', 'componente' => 'PROCESOS', 'nombre' => 'Sin umbral',
                'unidad' => '%', 'vista_origen' => 'v$resource_limit', 'sentido' => 'MENOR_MEJOR',
                'ambito' => 'RAIZ', 'peso' => 3, 'u_max' => 100, 'acumulada' => 0,
                'es_identidad' => 0, 'entra_isbd' => 1, 'ancla_iso' => 'A.8.6',
            ]],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->motor()->evaluar($this->muestra('completa'), $repositorio);
    }
}
