<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\MotorCalculoSalud;
use App\Models\RepositorioMonitorArreglo;
use PHPUnit\Framework\TestCase;
use Pruebas\Muestras;

/**
 * Cobertura, resultado de la muestra y contexto caído.
 *
 * Cubre cuatro de los siete casos límite del §6 del contrato: cero filas en
 * una métrica, un contexto caído, la instancia que no responde y un componente
 * entero sin datos.
 */
final class MotorCalculoSaludCoberturaTest extends TestCase
{
    /** Las nueve métricas que la muestra de ejemplo sí puede evaluar hoy. */
    private const EVALUABLES = [
        'M-ARC-01', 'M-ARC-02', 'M-ARC-03',
        'M-MEM-01', 'M-MEM-02', 'M-MEM-03',
        'M-PRO-01', 'M-PRO-02', 'M-PRO-03',
    ];

    private function motor(float $piso = 80.0): MotorCalculoSalud
    {
        return new MotorCalculoSalud($piso);
    }

    private function repositorio(): RepositorioMonitorArreglo
    {
        return RepositorioMonitorArreglo::desdeArchivo();
    }

    /** Un catálogo recortado a las métricas indicadas.
     *
     * @param list<string> $codigos
     */
    private function repositorioCon(array $codigos): RepositorioMonitorArreglo
    {
        /** @var array<string, mixed> $catalogo */
        $catalogo = require Muestras::raiz() . '/config/monitor-catalogo.php';

        $catalogo['metricas'] = array_values(array_filter(
            $catalogo['metricas'],
            static fn (array $fila): bool => in_array($fila['codigo'], $codigos, true),
        ));

        return new RepositorioMonitorArreglo($catalogo);
    }

    // ── Cobertura ──────────────────────────────────────────────────────────

    /**
     * Con todas las métricas del catálogo recolectadas, cobertura 100 y OK.
     *
     * Es el caso sano y el que fija la referencia: si esta prueba no diera
     * 100, ninguna de las siguientes significaría nada.
     */
    public function testCoberturaCompletaDaResultadoOK(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorioCon(self::EVALUABLES),
        );

        $this->assertSame(100.0, $evaluada['cobertura_pct']);
        $this->assertSame('OK', $evaluada['resultado']);
        $this->assertNull($evaluada['mensaje']);
        $this->assertCount(9, $evaluada['mediciones']);
    }

    /**
     * Contra el catálogo real de quince, la muestra de ejemplo queda PARCIAL.
     *
     * Nueve mediciones sobre catorce planificadas. Las catorce son quince menos
     * `M-PRO-04`, que es una tasa en su primera muestra y sale del denominador
     * sin ser un error.
     */
    public function testLaMuestraDeEjemploNoLlegaAlPisoContraElCatalogoDeQuince(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorio(),
        );

        $this->assertSame(64.3, $evaluada['cobertura_pct']);
        $this->assertSame('PARCIAL', $evaluada['resultado']);
        $this->assertStringContainsString('Muestra incompleta', (string) $evaluada['mensaje']);
        $this->assertStringContainsString('64,3', (string) $evaluada['mensaje']);
    }

    /** El piso es configurable: con 50 %, esa misma muestra pasa a OK. */
    public function testElPisoDeCoberturaEsConfigurable(): void
    {
        $evaluada = $this->motor(50.0)->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorio(),
        );

        $this->assertSame(64.3, $evaluada['cobertura_pct']);
        $this->assertSame('OK', $evaluada['resultado']);
        $this->assertNull($evaluada['mensaje']);
    }

    // ── Qué baja la cobertura y qué no ─────────────────────────────────────

    /**
     * `VACIA` baja la cobertura: la métrica debía dar datos y no los dio.*/
    public function testUnaLecturaVaciaBajaLaCobertura(): void
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['lecturas']['M-PRO-01'] = ['estado' => 'VACIA', 'mensaje' => '0 filas'];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioCon(self::EVALUABLES));

        $this->assertCount(8, $evaluada['mediciones']);
        $this->assertSame(88.9, $evaluada['cobertura_pct']);   // 8 de 9
        $this->assertSame('OK', $evaluada['resultado']);       // aún sobre el piso
    }

    /** `NO_APLICA` no baja la cobertura: sale también de las planificadas.  */
    public function testNoAplicaSaleDeLasPlanificadasYNoPenaliza(): void
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['lecturas']['M-PRO-01'] = ['estado' => 'NO_APLICA', 'mensaje' => 'sin CDB'];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioCon(self::EVALUABLES));

        $this->assertCount(8, $evaluada['mediciones']);
        $this->assertSame(100.0, $evaluada['cobertura_pct']);  // 8 de 8
        $this->assertSame('OK', $evaluada['resultado']);
    }

    /** Una tasa en su primera muestra tampoco penaliza. */
    public function testUnaTasaSinMuestraAnteriorSaleDelDenominador(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorioCon([...self::EVALUABLES, 'M-PRO-04']),
        );

        $this->assertSame(MotorCalculoSalud::SIN_DERIVAR, $evaluada['fuera']['M-PRO-04']);
        $this->assertSame(100.0, $evaluada['cobertura_pct']);
        $this->assertSame('OK', $evaluada['resultado']);
    }

    // ── Contexto caído ─────────────────────────────────────────────────────

    /**
     * El caso más probable de los siete: se cae una conexión y con ella todas
     * las métricas de su ámbito.
     *
     * Diez métricas de ámbito RAIZ salen de golpe. Quedan dos evaluadas sobre
     * quince planificadas, muy por debajo del piso, y la muestra es PARCIAL.
     */
    public function testUnContextoCaidoSacaTodasLasMetricasDeSuAmbito(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::CONTEXTO_RAIZ_CAIDO),
            $this->repositorio(),
        );

        $this->assertCount(2, $evaluada['mediciones']);
        $this->assertSame(['M-ARC-01', 'M-ARC-02'], array_keys($evaluada['mediciones']));
        $this->assertSame(13.3, $evaluada['cobertura_pct']);
        $this->assertSame('PARCIAL', $evaluada['resultado']);
        $this->assertStringContainsString('CONTEXTO_CAIDO 10', (string) $evaluada['mensaje']);

        $deRaiz = array_filter(
            $evaluada['fuera'],
            static fn (string $m): bool => $m === MotorCalculoSalud::CONTEXTO_CAIDO,
        );

        $this->assertCount(10, $deRaiz);
    }

    /**
     * El contexto manda sobre la lectura.
     *
     * Si la conexión no respondió, un valor que igual venga en la muestra no es
     * fiable: viene de una conexión que el propio agente declaró caída.
     */
    public function testElContextoCaidoMandaSobreUnaLecturaConValor(): void
    {
        $cruda = Muestras::cruda(Muestras::CONTEXTO_RAIZ_CAIDO);
        $cruda['lecturas']['M-PRO-01'] = ['estado' => 'OK', 'valor' => 34.16];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());

        $this->assertArrayNotHasKey('M-PRO-01', $evaluada['mediciones']);
        $this->assertSame(MotorCalculoSalud::CONTEXTO_CAIDO, $evaluada['fuera']['M-PRO-01']);
    }

    /** Un contexto que no se declaró se considera caído. El silencio no es éxito. */
    public function testUnContextoNoDeclaradoSeConsideraCaido(): void
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        unset($cruda['contextos']['CONTENEDOR']);

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());

        $this->assertSame(MotorCalculoSalud::CONTEXTO_CAIDO, $evaluada['fuera']['M-ARC-01']);
        $this->assertArrayHasKey('M-PRO-01', $evaluada['mediciones']);
    }

    // ── La instancia no respondió ──────────────────────────────────────────

    /**
     * Ninguna conexión abrió: FALLIDA, cobertura 0 y el motivo del agente.
     *
     * La muestra se persiste igual. Que la instancia no respondiera a esa hora
     * es un dato; saltarla dejaría un hueco que después parece un periodo sano.
     */
    public function testLaInstanciaQueNoRespondeDaMuestraFallida(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::FALLIDA),
            $this->repositorio(),
        );

        $this->assertSame('FALLIDA', $evaluada['resultado']);
        $this->assertSame(0.0, $evaluada['cobertura_pct']);
        $this->assertSame([], $evaluada['mediciones']);
        $this->assertCount(15, $evaluada['fuera']);
        $this->assertStringContainsString('ORA-12170', (string) $evaluada['mensaje']);
    }

    // ── Un componente entero sin datos ─────────────────────────────────────

    /**
     * Séptimo caso límite, visto desde la cobertura.
     *
     * Con MEMORIA entera fuera, la cobertura baja pero el motor no inventa un
     * cero para ese componente. Qué hacer con su peso —repartirlo entre los
     * otros dos— es cosa del paso que arma el ISBD; aquí lo que importa es que
     * las tres métricas quedan anotadas y ninguna produjo medición.
     */
    public function testUnComponenteEnteroSinDatosNoValeCero(): void
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);

        foreach (['M-MEM-01', 'M-MEM-02', 'M-MEM-03'] as $codigo) {
            $cruda['lecturas'][$codigo] = ['estado' => 'ERROR', 'mensaje' => 'ORA-00942'];
        }

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioCon(self::EVALUABLES));

        foreach (['M-MEM-01', 'M-MEM-02', 'M-MEM-03'] as $codigo) {
            $this->assertArrayNotHasKey($codigo, $evaluada['mediciones']);
            $this->assertSame('ERROR', $evaluada['fuera'][$codigo]);
        }

        $this->assertSame(66.7, $evaluada['cobertura_pct']);   // 6 de 9
        $this->assertSame('PARCIAL', $evaluada['resultado']);
    }

    // ── B-11: la evidencia de C-066 viaja intacta ──────────────────────────

    /**
     * `consultas_observadas` pasa de la muestra cruda a la evaluada sin tocarse.
     *
     * `guardarMuestra()` la busca en la muestra **evaluada** para insertarla en
     * `consulta_observada`. Si el motor no la reenvía, no falla nada: la tabla
     * queda vacía, el bucle de inserción recorre un arreglo inexistente y la
     * evidencia de C-066 se pierde en silencio. Ese silencio es exactamente lo
     * que esta prueba impide.
     */
    public function testLaEvidenciaDeConsultasViajaIntactaALaMuestraEvaluada(): void
    {
        $observadas = [
            [
                'sql_id'                          => '7ng34ruy5awxq',
                'plan_hash_value'                 => 1388734953,
                'ejecuciones'                     => 412,
                'cpu_ms_por_ejecucion'            => 1840.5,
                'transcurrido_ms_por_ejecucion'   => 2310.9,
                'lecturas_logicas_por_ejecucion'  => 98211,
            ],
        ];

        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['consultas_observadas'] = $observadas;

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorio());

        $this->assertSame($observadas, $evaluada['consultas_observadas']);
    }

    /**
     * Sin evidencia recolectada, la clave no aparece.
     *
     * Copiarla siempre como arreglo vacío borraría la diferencia entre «no hubo
     * consultas costosas» y «este recolector no reporta eso», que no son lo
     * mismo para quien después audita el control.
     */
    public function testSinEvidenciaLaClaveNoSeInventa(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorio(),
        );

        $this->assertArrayNotHasKey('consultas_observadas', $evaluada);
    }

    // ── El mensaje conserva el desglose que la tabla no guarda ─────────────

    /**
     * El mensaje resume los motivos, no solo la cifra.
     *
     * `fuera` no se persiste: de una muestra guardada sobrevive
     * `cobertura_pct` y nada más. Sin este resumen nadie puede saber, tres
     * meses después, si aquel 64 % fue por lecturas vacías, por silencio del
     * recolector o por una conexión caída.
     */
    public function testElMensajeResumeLosMotivosDeExclusion(): void
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['lecturas']['M-PRO-01'] = ['estado' => 'VACIA', 'mensaje' => '0 filas'];
        $cruda['lecturas']['M-PRO-02'] = ['estado' => 'ERROR', 'mensaje' => 'ORA-00942'];

        $mensaje = (string) $this->motor()->evaluar($cruda, $this->repositorio())['mensaje'];

        $this->assertStringContainsString('SIN_LECTURA 5', $mensaje);
        $this->assertStringContainsString('VACIA 1', $mensaje);
        $this->assertStringContainsString('ERROR 1', $mensaje);

        // SIN_DERIVAR salió del denominador, así que no explica la cobertura.
        $this->assertStringNotContainsString('SIN_DERIVAR', $mensaje);
    }
}
