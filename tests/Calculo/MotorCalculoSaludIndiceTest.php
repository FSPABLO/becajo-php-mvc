<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\Escala;
use App\Models\Calculo\MotorCalculoSalud;
use App\Models\RepositorioMonitorArreglo;
use PHPUnit\Framework\TestCase;
use Pruebas\Muestras;

/**
 * El ISBD: pesos 30/35/35 sobre los componentes publicados, segundo nivel del
 * tope, reparto de peso y los invariantes 4 y 5 (§5.3 y §5.4 del plan).
 */
final class MotorCalculoSaludIndiceTest extends TestCase
{
    /** Las nueve métricas del catálogo real que la muestra de ejemplo evalúa. */
    private const EVALUABLES = [
        'M-ARC-01', 'M-ARC-02', 'M-ARC-03',
        'M-MEM-01', 'M-MEM-02', 'M-MEM-03',
        'M-PRO-01', 'M-PRO-02', 'M-PRO-03',
    ];

    private function motor(float $piso = 80.0): MotorCalculoSalud
    {
        return new MotorCalculoSalud($piso);
    }

    /** @param list<string> $codigos */
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

    // ── El caso trabajado del §5.4 ─────────────────────────────────────────

    /**
     * ```
     * ISBD_bruto = 0,30×40,0 + 0,35×75,0 + 0,35×91,8 = 70,4
     * peor estado entre componentes: CRÍTICO (IP)     → tope 40
     * ISBD = mín(70,4 ; 40) = 40,0  →  CRÍTICO
     * ```
     *
     * 70,4 se publicaría como ADVERTENCIA, es decir, como una base que aguanta
     * el fin de semana, y la base está a cuatro sesiones de rechazar
     * conexiones. Ahí está la diferencia entre un tablero que avisa y uno que
     * tranquiliza.
     */
    public function testElIsbdDelCasoTrabajado(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestraDelCaso(), $this->repositorioDelCaso());
        $indice = $evaluada['indice'];

        $this->assertSame(70.4, $indice['isbd_bruto']);
        $this->assertSame(40.0, $indice['isbd']);
        $this->assertSame(Escala::CRITICO, $indice['estado']);
        $this->assertSame(['X-PRO-01'], $indice['causa']);
    }

    /**
     * El tope actúa dos veces y por motivos distintos: dentro de IP porque una
     * métrica en crítico no se promedia con una sana, y sobre el índice porque
     * un componente en crítico no se promedia con dos buenos.
     *
     * La diferencia `isbd_bruto − isbd` mide cuánto tuvo que intervenir la
     * regla. Aquí vale 30,4 puntos.
     */
    public function testElTopeActuaEnLosDosNiveles(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestraDelCaso(), $this->repositorioDelCaso());

        $this->assertSame(48.3, $evaluada['componentes']['PROCESOS']['bruto']);
        $this->assertSame(40.0, $evaluada['componentes']['PROCESOS']['publicado']);

        $indice = $evaluada['indice'];

        $this->assertEqualsWithDelta(30.4, $indice['isbd_bruto'] - $indice['isbd'], 0.01);
    }

    /** El ISBD promedia los publicados, no los brutos. */
    public function testElIsbdPromediaLosPublicados(): void
    {
        $evaluada = $this->motor()->evaluar($this->muestraDelCaso(), $this->repositorioDelCaso());

        $conBrutos = 0.30 * 48.3 + 0.35 * 76.4 + 0.35 * 91.8;

        $this->assertNotEqualsWithDelta($conBrutos, $evaluada['indice']['isbd_bruto'], 0.05);
        $this->assertSame(70.4, $evaluada['indice']['isbd_bruto']);
    }

    // ── El ejemplo del contrato ────────────────────────────────────────────

    /**
     * La misma conclusión del §5.1 del contrato: 90,0 SALUDABLE por M-MEM-02.
     *
     * El bruto es 94,3 y no los 94,8 del documento porque a PROCESOS le falta
     * `M-PRO-04`, que es una tasa y sale del denominador en la primera muestra.
     * El estado publicado y la causa coinciden igual, que es lo que el caso
     * pretende demostrar.
     */
    public function testElIndiceDelEjemploDelContrato(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            $this->repositorioCon(self::EVALUABLES),
        );

        $indice = $evaluada['indice'];

        $this->assertSame(94.3, $indice['isbd_bruto']);
        $this->assertSame(90.0, $indice['isbd']);
        $this->assertSame(Escala::SALUDABLE, $indice['estado']);
        $this->assertSame(['M-MEM-02'], $indice['causa']);
    }

    /** CONSULTAS se calcula y se muestra, pero no entra al promedio del ISBD. */
    public function testConsultasNoAlteraElIsbd(): void
    {
        $codigos = [...self::EVALUABLES, 'M-CON-01'];

        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $sinConsultas = $this->motor()->evaluar($cruda, $this->repositorioCon(self::EVALUABLES));

        $cruda['lecturas']['M-CON-01'] = ['estado' => 'OK', 'valor' => 12.0];
        $conConsultas = $this->motor()->evaluar($cruda, $this->repositorioCon($codigos));

        $this->assertSame(Escala::CRITICO, $conConsultas['componentes']['CONSULTAS']['estado']);
        $this->assertSame($sinConsultas['indice']['isbd'], $conConsultas['indice']['isbd']);
        $this->assertSame($sinConsultas['indice']['estado'], $conConsultas['indice']['estado']);
    }

    // ── Invariante 3 en el nivel del índice ────────────────────────────────

    /**
     * Un componente sin datos reparte su peso entre los otros dos; no cuenta
     * como salud cero.
     *
     * Sin MEMORIA, los pesos 0,30 y 0,35 se renormalizan sobre 0,65, así que
     * el ISBD queda entre IP e IA. Si el componente entrara como cero, el
     * índice caería por debajo de los dos.
     */
    public function testUnComponenteSinDatosRepartesuPeso(): void
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);

        foreach (['M-MEM-01', 'M-MEM-02', 'M-MEM-03'] as $codigo) {
            unset($cruda['lecturas'][$codigo]);
        }

        $codigos = ['M-ARC-01', 'M-ARC-02', 'M-ARC-03', 'M-PRO-01', 'M-PRO-02', 'M-PRO-03'];
        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioCon($codigos));

        $ip = $evaluada['componentes']['PROCESOS']['publicado'];
        $ia = $evaluada['componentes']['ARCHIVOS']['publicado'];
        $isbd = $evaluada['indice']['isbd'];

        $this->assertArrayNotHasKey('MEMORIA', $evaluada['componentes']);
        $this->assertGreaterThan($ip, $isbd);
        $this->assertLessThan($ia, $isbd);

        // 0,30·92,5 + 0,35·100 repartido sobre 0,65.
        $this->assertSame(96.5, $isbd);
    }

    // ── Invariante 4: bajo el piso no se publica índice ────────────────────

    /**
     * Una muestra PARCIAL no publica índice. Un número calculado sobre la
     * mitad de la evidencia es peor que ninguno, porque parece uno bueno.
     */
    public function testUnaMuestraParcialNoPublicaIndice(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            RepositorioMonitorArreglo::desdeArchivo(),
        );

        $this->assertSame('PARCIAL', $evaluada['resultado']);
        $this->assertNull($evaluada['indice']);
        $this->assertNotSame([], $evaluada['componentes']);
    }

    /** Y una FALLIDA tampoco, aunque se persista igual. */
    public function testUnaMuestraFallidaNoPublicaIndice(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::FALLIDA),
            RepositorioMonitorArreglo::desdeArchivo(),
        );

        $this->assertSame('FALLIDA', $evaluada['resultado']);
        $this->assertNull($evaluada['indice']);
        $this->assertSame([], $evaluada['componentes']);
    }

    // ── Criterios de aceptación del frente ─────────────────────────────────

    /**
     * Ningún componente en CRÍTICO puede convivir con un índice publicado por
     * encima de 40. Se barre sobre las combinaciones de una compuerta cerrada
     * en cada componente.
     */
    public function testNingunComponenteCriticoConvieneConUnIndiceSobre40(): void
    {
        foreach (['X-ARC-02'] as $compuerta) {
            $cruda = $this->muestraDelCaso();
            $cruda['lecturas'][$compuerta] = ['estado' => 'OK', 'abierta' => false, 'detalle' => []];

            $evaluada = $this->motor()->evaluar($cruda, $this->repositorioDelCaso());

            $this->assertLessThanOrEqual(40.0, $evaluada['indice']['isbd']);
            $this->assertSame(Escala::CRITICO, $evaluada['indice']['estado']);
            $this->assertContains($compuerta, $evaluada['indice']['causa']);
        }
    }

    public function testUnComponenteCriticoTopaUnIndiceQueSeriaSaludable(): void
        {
            $cruda = $this->muestraDelCaso();
            $cruda['lecturas']['X-MEM-01'] = ['estado' => 'OK', 'valor' => 10.0];
            $cruda['lecturas']['X-MEM-02'] = ['estado' => 'OK', 'valor' => 10.0];
            $cruda['lecturas']['X-ARC-01'] = ['estado' => 'OK', 'valor' => 10.0];

            $evaluada = $this->motor()->evaluar($cruda, $this->repositorioDelCaso());
            $indice = $evaluada['indice'];

            $this->assertSame(Escala::CRITICO, $evaluada['componentes']['PROCESOS']['estado']);
            $this->assertSame(40.0, $evaluada['componentes']['PROCESOS']['publicado']);

            // Sin el tope, 80,6 se publicaría como SALUDABLE.
            $this->assertSame(80.6, $indice['isbd_bruto']);
            $this->assertSame(Escala::SALUDABLE, Escala::bandaPorSalud($indice['isbd_bruto']));

            $this->assertSame(40.0, $indice['isbd']);
            $this->assertSame(Escala::CRITICO, $indice['estado']);
            $this->assertSame(['X-PRO-01'], $indice['causa']);
        }

    /** El índice nunca se muestra sin estado y sin causa (invariante 5). */
    public function testElIndiceNuncaViajaSinEstadoNiCausa(): void
    {
        $muestras = [
            [$this->muestraDelCaso(), $this->repositorioDelCaso()],
            [Muestras::cruda(Muestras::COMPLETA), $this->repositorioCon(self::EVALUABLES)],
        ];

        foreach ($muestras as [$cruda, $repositorio]) {
            $indice = $this->motor()->evaluar($cruda, $repositorio)['indice'];

            $this->assertNotNull($indice['estado']);
            $this->assertNotSame([], $indice['causa']);
            $this->assertContains($indice['estado'], Escala::BANDAS);
        }
    }

    // ── El caso trabajado, catálogo y muestra ──────────────────────────────

    private function repositorioDelCaso(): RepositorioMonitorArreglo
    {
        return new RepositorioMonitorArreglo([
            'instancias' => [[
                'clave' => 'PRODCORE1', 'nombre' => 'PRODCORE1', 'motor' => 'Oracle',
                'host' => 'oracle', 'puerto' => 1521, 'servicio_raiz' => 'FREE',
                'servicio_contenedor' => 'PRODCORE1', 'entorno' => 'Producción',
                'criticidad' => 'ALTA', 'activa' => 1, 'demostrativa' => 0,
            ]],
            'metricas' => [
                $this->fila('X-PRO-01', 'PROCESOS', 'MENOR_MEJOR', 2),
                $this->fila('X-PRO-02', 'PROCESOS', 'MENOR_MEJOR', 1),
                $this->fila('X-MEM-01', 'MEMORIA', 'MENOR_MEJOR', 2),
                $this->fila('X-MEM-02', 'MEMORIA', 'MENOR_MEJOR', 1),
                $this->fila('X-ARC-01', 'ARCHIVOS', 'MENOR_MEJOR', 2),
                $this->fila('X-ARC-02', 'ARCHIVOS', 'ESTADO', null),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function muestraDelCaso(): array
    {
        return [
            'instancia' => 'PRODCORE1',
            'tomada_en' => '2026-08-19T14:35:02+00:00',
            'contextos' => [
                'RAIZ'       => ['estado' => 'OK'],
                'CONTENEDOR' => ['estado' => 'OK'],
            ],
            'lecturas' => [
                'X-PRO-01' => ['estado' => 'OK', 'valor' => 96.0],
                'X-PRO-02' => ['estado' => 'OK', 'valor' => 62.0],
                'X-MEM-01' => ['estado' => 'OK', 'valor' => 76.0],
                'X-MEM-02' => ['estado' => 'OK', 'valor' => 44.0],
                'X-ARC-01' => ['estado' => 'OK', 'valor' => 41.0],
                'X-ARC-02' => ['estado' => 'OK', 'abierta' => true, 'detalle' => []],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function fila(string $codigo, string $componente, string $sentido, ?int $peso): array
    {
        $fila = [
            'codigo' => $codigo, 'componente' => $componente, 'nombre' => $codigo,
            'unidad' => '%', 'vista_origen' => 'v$prueba', 'sentido' => $sentido,
            'ambito' => 'RAIZ', 'peso' => $peso, 'u_max' => 100, 'acumulada' => 0,
            'es_identidad' => 0, 'entra_isbd' => 1, 'ancla_iso' => 'A.8.6',
        ];

        if ($sentido === 'ESTADO') {
            return $fila;
        }

        return $fila + [
            'id_umbral' => 1, 'codigo_metrica' => $codigo, 'clave_instancia' => null,
            'u_opt' => 50, 'u_adv' => 70, 'u_deg' => 85, 'u_crit' => 95,
            'valido_desde' => '2026-01-01 00:00:00', 'valido_hasta' => null,
        ];
    }
}
