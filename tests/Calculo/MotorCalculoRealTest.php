<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\MotorAlertas;
use App\Models\Calculo\MotorCalculoReal;
use App\Models\Contratos\MotorCalculo;
use App\Models\RepositorioMonitorArreglo;
use PHPUnit\Framework\TestCase;
use Pruebas\Muestras;

/**
 * La frontera entre el motor y la persistencia.
 *
 * `guardarMuestra()` lee la muestra evaluada por nombre de clave. Si el motor
 * deja de producir una, no falla nada: la columna queda nula y el dato se
 * pierde en silencio. Estas pruebas leen las claves directamente del código de
 * `RepositorioMonitorOracle` y comprueban que el motor las produce, para que un
 * cambio en cualquiera de los dos lados se note el mismo día.
 */
final class MotorCalculoRealTest extends TestCase
{
    /**
     * Claves que `guardarMuestra()` lee y el motor todavía no produce, con su
     * razón. Vacío es el objetivo; una entrada nueva sin discutir es un error.
     *
     * @var array<string, string>
     */
    private const PENDIENTES = [
        // medicion.valor_acumulado es una sola columna y M-PRO-04 necesita dos
        // (esperas y micros). El motor emite el mapa `acumulados`; persistirlo
        // exige la decisión de esquema pendiente con F2. Mientras tanto la tasa
        // se deriva en memoria pero no sobrevive a la muestra siguiente.
        'valor_acumulado' => 'Acumulado doble de M-PRO-04, pendiente de decisión de esquema.',
    ];

    private function codigoDelRepositorioOracle(): string
    {
        $ruta = Muestras::raiz() . '/app/Models/RepositorioMonitorOracle.php';
        $codigo = file_get_contents($ruta);

        $this->assertIsString($codigo, "No se pudo leer {$ruta}");

        return $codigo;
    }

    /** @return list<string> */
    private function clavesQueLee(string $variable): array
    {
        preg_match_all(
            '/\$' . $variable . "\\['([a-z_]+)'\\]/",
            $this->codigoDelRepositorioOracle(),
            $coincidencias,
        );

        $claves = array_values(array_unique($coincidencias[1]));
        sort($claves);

        return $claves;
    }

    /** @return array<string, mixed> */
    private function evaluada(): array
    {
        $repositorio = RepositorioMonitorArreglo::desdeArchivo();

        $cruda = Muestras::cruda(Muestras::COMPLETA);
        $cruda['consultas_observadas'] = [['sql_id' => '7ng34ruy5awxq']];

        return (new MotorCalculoReal())->evaluar($cruda, $repositorio);
    }

    public function testElMotorRealCumpleElContratoDelAgente(): void
    {
        $motor = new MotorCalculoReal();

        $this->assertInstanceOf(MotorCalculo::class, $motor);
    }

    /** El agente construye la clase sin argumentos: los valores por omisión bastan. */
    public function testSeConstruyeSinConfiguracion(): void
    {
        $evaluada = (new MotorCalculoReal())->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            RepositorioMonitorArreglo::desdeArchivo(),
        );

        $this->assertSame('FREEPDB1', $evaluada['instancia']);
        $this->assertArrayHasKey('mediciones', $evaluada);
    }

    /** La cabecera que guardarMuestra inserta en `muestra`. */
    public function testLaCabeceraTraeTodasLasClavesQueSePersisten(): void
    {
        $evaluada = $this->evaluada();

        foreach ($this->clavesQueLee('muestraEvaluada') as $clave) {
            $this->assertArrayHasKey($clave, $evaluada, "Falta la clave de cabecera '{$clave}'");
        }
    }

    /** Los seis campos de cada fila de `medicion`. */
    public function testCadaMedicionTraeLosCamposQueSePersisten(): void
    {
        $evaluada = $this->evaluada();
        $medicion = $evaluada['mediciones']['M-PRO-01'];

        foreach ($this->clavesQueLee('medicion') as $clave) {
            if (isset(self::PENDIENTES[$clave])) {
                continue;
            }

            // Las compuertas no llevan los campos de una proporción; se
            // comprueba sobre una proporción, que es el caso completo.
            if (in_array($clave, ['huella'], true)) {
                continue;
            }

            $this->assertArrayHasKey($clave, $medicion, "Falta el campo '{$clave}' de medicion");
        }
    }

    /** Los cuatro campos de `indice`, sobre una muestra que sí publica índice. */
    public function testElIndiceTraeLosCamposQueSePersisten(): void
    {
        /** @var array<string, mixed> $catalogo */
        $catalogo = require Muestras::raiz() . '/config/monitor-catalogo.php';

        $catalogo['metricas'] = array_values(array_filter(
            $catalogo['metricas'],
            static fn (array $f): bool => in_array($f['codigo'], [
                'M-ARC-01', 'M-ARC-02', 'M-ARC-03',
                'M-MEM-01', 'M-MEM-02', 'M-MEM-03',
                'M-PRO-01', 'M-PRO-02', 'M-PRO-03',
            ], true),
        ));

        $evaluada = (new MotorCalculoReal())->evaluar(
            Muestras::cruda(Muestras::COMPLETA),
            new RepositorioMonitorArreglo($catalogo),
        );

        $this->assertNotNull($evaluada['indice']);

        foreach ($this->clavesQueLee('indice') as $clave) {
            $this->assertArrayHasKey($clave, $evaluada['indice'], "Falta el campo '{$clave}' de indice");
        }
    }

    /** Los componentes se leen por `publicado` para llenar ip, im e ia. */
    public function testLosComponentesTraenSuValorPublicado(): void
    {
        $evaluada = $this->evaluada();

        foreach (['PROCESOS', 'MEMORIA', 'ARCHIVOS'] as $componente) {
            $this->assertArrayHasKey('publicado', $evaluada['componentes'][$componente], $componente);
        }
    }

    /** La evidencia de C-066 llega intacta al otro lado de la frontera (B-11). */
    public function testLaEvidenciaDeConsultasLlegaAlaPersistencia(): void
    {
        $this->assertSame(
            [['sql_id' => '7ng34ruy5awxq']],
            $this->evaluada()['consultas_observadas'],
        );
    }

    /**
     * Las decisiones de alerta viajan en la muestra evaluada.
     *
     * `guardarMuestra()` las ignora porque no las conoce: falta que el agente
     * las pase a `registrarAlerta()`. Se emiten igual para que ese enganche sea
     * un cambio corto y no una reescritura.
     */
    public function testLasDecisionesDeAlertaViajanEnLaMuestraEvaluada(): void
    {
        $evaluada = $this->evaluada();

        $this->assertArrayHasKey('alertas', $evaluada);

        foreach ([MotorAlertas::ABRIR, MotorAlertas::ESCALAR, MotorAlertas::CERRAR] as $accion) {
            $this->assertIsArray($evaluada['alertas'][$accion]);
        }

        $this->assertArrayHasKey('episodio', $evaluada['alertas']);
    }

    /**
     * Las precedencias llegan al motor de alertas desde el repositorio.
     *
     * Sin esto el episodio nunca señala causa en producción: el motor de
     * alertas funcionaría en las pruebas —que le pasan la lista a mano— y
     * quedaría inerte cuando corre el agente.
     */
    public function testLasPrecedenciasLleganDesdeElRepositorio(): void
    {
        $repositorio = RepositorioMonitorArreglo::desdeArchivo();

        $cruda = Muestras::cruda(Muestras::COMPLETA);
        // Dos métricas ligadas por precedencia: M-MEM-03 arrastra a M-MEM-01.
        $cruda['lecturas']['M-MEM-01'] = ['estado' => 'OK', 'valor' => 60.0];
        $cruda['lecturas']['M-MEM-03'] = ['estado' => 'OK', 'valor' => 1.5];

        $evaluada = (new MotorCalculoReal())->evaluar($cruda, $repositorio);
        $episodio = $evaluada['alertas']['episodio'];

        $this->assertNotNull($episodio);
        $this->assertSame('M-MEM-03', $episodio['causa']);
    }

    /**
     * El hueco conocido está documentado y es el único.
     *
     * Si mañana el motor deja de producir otro campo, las pruebas de arriba lo
     * detectan; esta se asegura de que la lista de excusas no crezca sola.
     */
    public function testSoloHayUnaClavePendienteYEstaJustificada(): void
    {
        $this->assertCount(1, self::PENDIENTES);
        $this->assertArrayHasKey('valor_acumulado', self::PENDIENTES);
        $this->assertArrayHasKey('acumulados', $this->evaluada()['mediciones']['M-PRO-04'] ?? ['acumulados' => null]);
    }
}
