<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\Escala;
use App\Models\Calculo\MotorCalculoSalud;
use App\Models\RepositorioMonitorArreglo;
use PHPUnit\Framework\TestCase;
use Pruebas\Muestras;

/**
 * De métricas a componente: promedio ponderado, compuertas y el primer nivel
 * del tope del eslabón más débil
 */
final class MotorCalculoSaludComponentesTest extends TestCase
{
    private function motor(): MotorCalculoSalud
    {
        return new MotorCalculoSalud();
    }

    /**
     * El catálogo del caso trabajado: seis métricas propias con pesos
     * 2 y 1 y umbrales por omisión. Se verifica el modelo de cálculo, no la
     * calibración de ninguna métrica real.
     */
    private function repositorioDelCasoTrabajado(): RepositorioMonitorArreglo
    {
        return new RepositorioMonitorArreglo([
            'instancias' => [$this->instancia()],
            'metricas'   => [
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
    private function muestraDelCasoTrabajado(): array
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

    // ── El caso trabajado ─────────────────────────────────────────

    /**
     * Se comprueban antes que los componentes: si una difiere, todo lo demás
     * difiere por arrastre y el fallo no diría dónde está el problema.
     */
    public function testLasSeisMetricasDelCasoTrabajado(): void
    {
        $evaluada = $this->motor()->evaluar(
            $this->muestraDelCasoTrabajado(),
            $this->repositorioDelCasoTrabajado(),
        );

        $esperadas = [
            'X-PRO-01' => [32.0, Escala::CRITICO],
            'X-PRO-02' => [81.0, Escala::SALUDABLE],
            'X-MEM-01' => [69.0, Escala::ADVERTENCIA],
            'X-MEM-02' => [91.2, Escala::OPTIMO],
            'X-ARC-01' => [91.8, Escala::OPTIMO],
        ];

        foreach ($esperadas as $codigo => [$salud, $estado]) {
            $this->assertSame($salud, $evaluada['mediciones'][$codigo]['valor_normalizado'], $codigo);
            $this->assertSame($estado, $evaluada['mediciones'][$codigo]['estado'], $codigo);
        }
    }

    /**
     * Los tres componentes del caso trabajado, brutos y publicados.
     *
     * ```
     * IP = (2×32,0 + 1×81,0) / 3 = 48,3   peor CRÍTICO     → mín(48,3; 40) = 40,0
     * IM = (2×69,0 + 1×91,2) / 3 = 76,4   peor ADVERTENCIA → mín(76,4; 75) = 75,0
     * IA = 91,8 (la compuerta no promedia) peor ÓPTIMO     → 91,8
     * ```
     *
     * IP bruto es 48,3, que la escala llamaría DEGRADADO, y una base a cuatro
     * sesiones de rechazar conexiones no está degradada.
     */
    public function testLosComponentesDelCasoTrabajado(): void
    {
        $evaluada = $this->motor()->evaluar(
            $this->muestraDelCasoTrabajado(),
            $this->repositorioDelCasoTrabajado(),
        );

        $componentes = $evaluada['componentes'];

        $this->assertSame(48.3, $componentes['PROCESOS']['bruto']);
        $this->assertSame(40.0, $componentes['PROCESOS']['publicado']);
        $this->assertSame(Escala::CRITICO, $componentes['PROCESOS']['estado']);

        $this->assertSame(76.4, $componentes['MEMORIA']['bruto']);
        $this->assertSame(75.0, $componentes['MEMORIA']['publicado']);
        $this->assertSame(Escala::ADVERTENCIA, $componentes['MEMORIA']['estado']);

        $this->assertSame(91.8, $componentes['ARCHIVOS']['bruto']);
        $this->assertSame(91.8, $componentes['ARCHIVOS']['publicado']);
        $this->assertSame(Escala::OPTIMO, $componentes['ARCHIVOS']['estado']);
    }

    /**
     * Un indicador topado en 40,0 pertenece a CRÍTICO porque las bandas de
     * salud están cerradas por arriba.
     */
    public function testElTopeDejaElIndicadorEnLaBandaDeSuPeorMetrica(): void
    {
        $evaluada = $this->motor()->evaluar(
            $this->muestraDelCasoTrabajado(),
            $this->repositorioDelCasoTrabajado(),
        );

        foreach (['PROCESOS' => Escala::CRITICO, 'MEMORIA' => Escala::ADVERTENCIA] as $componente => $banda) {
            $this->assertSame(
                $banda,
                Escala::bandaPorSalud($evaluada['componentes'][$componente]['publicado']),
                $componente,
            );
        }
    }

    // ── Compuertas ─────────────────────────────────────────────────────────

    /**
     * Sexto caso límite del §6. Sin esta regla, un datafile fuera de línea
     * promediado con una proporción al 91,8 daría un componente en torno a 45.
     */
    public function testUnaCompuertaCerradaMandaElComponenteACeroYCritico(): void
    {
        $cruda = $this->muestraDelCasoTrabajado();
        $cruda['lecturas']['X-ARC-02'] = [
            'estado'  => 'OK',
            'abierta' => false,
            'detalle' => ['invalidos' => ['users01.dbf']],
        ];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioDelCasoTrabajado());
        $archivos = $evaluada['componentes']['ARCHIVOS'];

        $this->assertSame(0.0, $archivos['bruto']);
        $this->assertSame(0.0, $archivos['publicado']);
        $this->assertSame(Escala::CRITICO, $archivos['estado']);
        $this->assertSame(['X-ARC-02'], $archivos['compuertas_cerradas']);
    }

    /**
     * ARCHIVOS vale 91,8 por su única proporción, no porque la compuerta
     * abierta sume un cien: quitándola, el indicador sale idéntico.
     */
    public function testUnaCompuertaAbiertaNoInflaElPromedio(): void
    {
        $conCompuerta = $this->motor()->evaluar(
            $this->muestraDelCasoTrabajado(),
            $this->repositorioDelCasoTrabajado(),
        );

        $cruda = $this->muestraDelCasoTrabajado();
        unset($cruda['lecturas']['X-ARC-02']);

        $sinCompuerta = $this->motor()->evaluar($cruda, $this->repositorioDelCasoTrabajado());

        $this->assertSame(
            $conCompuerta['componentes']['ARCHIVOS']['publicado'],
            $sinCompuerta['componentes']['ARCHIVOS']['publicado'],
        );
    }

    /**
     * Tiene estado pero no número: publicar un cien sería inventar salud a
     * partir de la ausencia de falla.
     */
    public function testUnComponenteDeSoloCompuertasNoPublicaNumero(): void
    {
        $cruda = $this->muestraDelCasoTrabajado();
        unset($cruda['lecturas']['X-ARC-01']);

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioDelCasoTrabajado());
        $archivos = $evaluada['componentes']['ARCHIVOS'];

        $this->assertNull($archivos['bruto']);
        $this->assertNull($archivos['publicado']);
        $this->assertSame(Escala::OPTIMO, $archivos['estado']);
    }

    // ── Sin dato no es cero, también aquí ──────────────────────────────────

    /**
     * Su ausencia es la señal, distinta de un cero: no se puede afirmar que la
     * memoria esté mal cuando lo que pasó es que no se pudo medir.
     */
    public function testUnComponenteSinMedicionesNoAparece(): void
    {
        $cruda = $this->muestraDelCasoTrabajado();
        $cruda['lecturas']['X-MEM-01'] = ['estado' => 'ERROR', 'mensaje' => 'ORA-00942'];
        $cruda['lecturas']['X-MEM-02'] = ['estado' => 'ERROR', 'mensaje' => 'ORA-00942'];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioDelCasoTrabajado());

        $this->assertArrayNotHasKey('MEMORIA', $evaluada['componentes']);
        $this->assertArrayHasKey('PROCESOS', $evaluada['componentes']);
        $this->assertArrayHasKey('ARCHIVOS', $evaluada['componentes']);
    }

    /** El promedio solo pesa lo recolectado: una métrica fuera no arrastra. */
    public function testUnaMetricaFueraNoEntraAlPromedioComoCero(): void
    {
        $cruda = $this->muestraDelCasoTrabajado();
        $cruda['lecturas']['X-PRO-01'] = ['estado' => 'VACIA', 'mensaje' => '0 filas'];

        $evaluada = $this->motor()->evaluar($cruda, $this->repositorioDelCasoTrabajado());
        $procesos = $evaluada['componentes']['PROCESOS'];

        // Queda solo X-PRO-02, que vale 81,0 y es SALUDABLE.
        $this->assertSame(81.0, $procesos['bruto']);
        $this->assertSame(81.0, $procesos['publicado']);
        $this->assertSame(Escala::SALUDABLE, $procesos['estado']);
    }

    // ── El catálogo real ───────────────────────────────────────────────────

    /**
     * PROCESOS da 92,5 y no los 94,3 del documento porque le falta `M-PRO-04`,
     * una tasa que sale del denominador en la primera muestra. El invariante 3
     * no permite suplirla con un cero.
     */
    public function testLosComponentesDelEjemploDelContrato(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            RepositorioMonitorArreglo::desdeArchivo(),
        );

        $componentes = $evaluada['componentes'];

        $this->assertSame(91.3, $componentes['MEMORIA']['bruto']);
        $this->assertSame(90.0, $componentes['MEMORIA']['publicado']);
        $this->assertSame(Escala::SALUDABLE, $componentes['MEMORIA']['estado']);

        $this->assertSame(100.0, $componentes['ARCHIVOS']['bruto']);
        $this->assertSame(100.0, $componentes['ARCHIVOS']['publicado']);
        $this->assertSame(Escala::OPTIMO, $componentes['ARCHIVOS']['estado']);

        $this->assertSame(92.5, $componentes['PROCESOS']['bruto']);
    }

    /**
     * MEMORIA bruta vale 91,3, que sería ÓPTIMO. Una sola métrica al 75,6 % de
     * su objetivo impide que el componente se anuncie como óptimo.
     */
    public function testLaFilaSaludableDelTopeNoEsDecorativa(): void
    {
        $evaluada = $this->motor()->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            RepositorioMonitorArreglo::desdeArchivo(),
        );

        $memoria = $evaluada['componentes']['MEMORIA'];

        $this->assertSame(Escala::OPTIMO, Escala::bandaPorSalud($memoria['bruto']));
        $this->assertSame(Escala::SALUDABLE, $memoria['estado']);
        $this->assertLessThan($memoria['bruto'], $memoria['publicado']);
    }

    /** CONSULTAS se calcula igual, aunque después no sume al índice. */
    public function testConsultasSeCalculaComoCualquierOtroComponente(): void
    {
        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['lecturas']['M-CON-01'] = ['estado' => 'OK', 'valor' => 2.0];

        $evaluada = $this->motor()->evaluar($cruda, RepositorioMonitorArreglo::desdeArchivo());

        $this->assertArrayHasKey('CONSULTAS', $evaluada['componentes']);
        $this->assertSame(Escala::ADVERTENCIA, $evaluada['componentes']['CONSULTAS']['estado']);
    }

    // ── Ayudantes ──────────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function instancia(): array
    {
        return [
            'clave' => 'PRODCORE1', 'nombre' => 'PRODCORE1', 'motor' => 'Oracle',
            'host' => 'oracle', 'puerto' => 1521, 'servicio_raiz' => 'FREE',
            'servicio_contenedor' => 'PRODCORE1', 'entorno' => 'Producción',
            'criticidad' => 'ALTA', 'activa' => 1, 'demostrativa' => 0,
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
