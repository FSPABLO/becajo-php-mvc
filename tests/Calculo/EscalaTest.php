<?php

declare(strict_types=1);

namespace Pruebas\Calculo;

use App\Models\Calculo\Escala;
use App\Models\Entidades\Metrica;
use App\Models\Entidades\Umbral;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * La primera prueba del motor, y la que sostiene a todas las demas.
 *
 * Las pruebas no usan proveedores de datos ni atributos: los casos van en
 * bucles explicitos dentro del metodo. Es a proposito. Un proveedor obliga a
 * elegir entre la anotacion antigua y el atributo nuevo segun la version de
 * PHPUnit instalada, y estas pruebas tienen que correr igual en la maquina de
 * cualquiera del equipo.
 */
final class EscalaTest extends TestCase
{
    /** El juego por omision: 50 / 70 / 85 / 95 sobre techo 100. */
    private function umbralPorOmision(): Umbral
    {
        return $this->umbral('M-PRO-01', 50.0, 70.0, 85.0, 95.0);
    }

    private function umbral(
        string $codigo,
        float $uOpt,
        float $uAdv,
        float $uDeg,
        float $uCrit,
    ): Umbral {
        return new Umbral(
            id: 1,
            codigoMetrica: $codigo,
            claveInstancia: null,
            uOpt: $uOpt,
            uAdv: $uAdv,
            uDeg: $uDeg,
            uCrit: $uCrit,
            validoDesde: '2026-01-01T00:00:00+00:00',
            validoHasta: null,
        );
    }

    /**
     * La tabla de verificacion del §5.2.1 del plan, fila por fila.
     *
     * El plan dice literalmente que esa tabla es el primer caso de prueba del
     * motor de calculo. Aqui esta, con las diez filas y sus dos columnas de
     * banda, que es lo que de verdad se esta comprobando: que la banda por
     * umbral y la banda por salud son la misma.
     */
    public function testTablaDeVerificacionDelPlan(): void
    {
        $umbral = $this->umbralPorOmision();

        $filas = [
            // [ u,   s esperada, banda esperada ]
            [0.0,   100.0, Escala::OPTIMO],
            [49.9,   90.0, Escala::OPTIMO],
            [50.0,   90.0, Escala::SALUDABLE],
            [62.0,   81.0, Escala::SALUDABLE],
            [70.0,   75.0, Escala::ADVERTENCIA],
            [76.0,   69.0, Escala::ADVERTENCIA],
            [85.0,   60.0, Escala::DEGRADADO],
            [95.0,   40.0, Escala::CRITICO],
            [96.0,   32.0, Escala::CRITICO],
            [100.0,   0.0, Escala::CRITICO],
        ];

        foreach ($filas as [$u, $sEsperada, $bandaEsperada]) {
            $s = Escala::normalizar($u, $umbral, 100.0, Metrica::MENOR_MEJOR);

            $this->assertSame(
                $sEsperada,
                Escala::publicar($s),
                "u = {$u} deberia normalizar a {$sEsperada}",
            );

            $this->assertSame(
                $bandaEsperada,
                Escala::bandaPorUtilizacion($u, $umbral, 100.0, Metrica::MENOR_MEJOR),
                "u = {$u} deberia caer en {$bandaEsperada} por umbral",
            );

            $this->assertSame(
                $bandaEsperada,
                Escala::bandaPorSalud($s),
                "u = {$u} deberia caer en {$bandaEsperada} por salud",
            );
        }
    }

    /**
     * La propiedad general, no solo las diez filas: para CUALQUIER
     * utilizacion, la banda por umbral y la banda por salud coinciden.
     *
     * Se barre con tres juegos de umbrales distintos, incluidos uno en
     * milisegundos y uno con techo 150, porque la normalizacion no exige
     * porcentajes (§1.4 del catalogo) y esa es justo la parte donde un
     * "100" escrito a mano en el codigo pasaria inadvertido.
     */
    public function testLaBandaDeLaSaludCoincideSiempreConLaDeLaUtilizacion(): void
    {
        $juegos = [
            ['M-ARC-01', $this->umbralPorOmision(), 100.0],
            ['M-PRO-04', $this->umbral('M-PRO-04', 10.0, 14.0, 17.0, 19.0), 20.0],
            ['M-MEM-02', $this->umbral('M-MEM-02', 70.0, 90.0, 100.0, 120.0), 150.0],
        ];

        foreach ($juegos as [$codigo, $umbral, $uMax]) {
            for ($paso = 0; $paso <= 1000; $paso++) {
                $u = round($uMax * $paso / 1000, 4);

                $porUmbral = Escala::bandaPorUtilizacion($u, $umbral, $uMax, Metrica::MENOR_MEJOR);
                $porSalud = Escala::bandaPorSalud(
                    Escala::normalizar($u, $umbral, $uMax, Metrica::MENOR_MEJOR)
                );

                $this->assertSame($porUmbral, $porSalud, "{$codigo} discrepa en u = {$u}");
            }
        }
    }

    /**
     * Las metricas «mayor es mejor» pasan por la misma tabla sobre `techo − v`.
     *
     * Los dos valores son lecturas reales de `becajo-oracle` citadas en el
     * contrato de muestra, con los umbrales ya transformados que siembra
     * `07_datos_semilla_monitor.sql`.
     */
    public function testMayorEsMejorUsaLaMismaTablaInvertida(): void
    {
        $aciertos = $this->umbral('M-MEM-01', 5.0, 10.0, 20.0, 30.0);
        $libre = $this->umbral('M-MEM-03', 90.0, 95.0, 98.0, 99.0);

        $this->assertSame(
            100.0,
            Escala::publicar(Escala::normalizar(100.0, $aciertos, 100.0, Metrica::MAYOR_MEJOR)),
        );

        $s = Escala::normalizar(18.80, $libre, 100.0, Metrica::MAYOR_MEJOR);

        $this->assertSame(91.0, Escala::publicar($s));
        $this->assertSame(Escala::OPTIMO, Escala::bandaPorSalud($s));

        // Y la direccion es la correcta: menos memoria libre, menos salud.
        $peor = Escala::normalizar(0.5, $libre, 100.0, Metrica::MAYOR_MEJOR);
        $this->assertLessThan($s, $peor);
        $this->assertSame(Escala::CRITICO, Escala::bandaPorSalud($peor));
    }

    /** La salud nunca crece cuando crece la utilizacion. */
    public function testLaNormalizacionEsMonotonaDecreciente(): void
    {
        $umbral = $this->umbralPorOmision();
        $anterior = 101.0;

        for ($paso = 0; $paso <= 1000; $paso++) {
            $s = Escala::normalizar($paso / 10, $umbral, 100.0, Metrica::MENOR_MEJOR);

            $this->assertLessThanOrEqual($anterior, $s);
            $this->assertGreaterThanOrEqual(0.0, $s);
            $this->assertLessThanOrEqual(100.0, $s);

            $anterior = $s;
        }
    }

    /** Fuera del rango declarado la medida se satura, no se sale de la escala. */
    public function testLosValoresFueraDelRangoSeSaturan(): void
    {
        $umbral = $this->umbralPorOmision();

        $this->assertSame(100.0, Escala::publicar(
            Escala::normalizar(-3.0, $umbral, 100.0, Metrica::MENOR_MEJOR)
        ));

        $this->assertSame(0.0, Escala::publicar(
            Escala::normalizar(250.0, $umbral, 100.0, Metrica::MENOR_MEJOR)
        ));
    }

    /** Los topes del §5.4 son las fronteras exactas, no un punto por debajo. */
    public function testTopesDelEslabonMasDebil(): void
    {
        $this->assertNull(Escala::tope(Escala::OPTIMO));
        $this->assertSame(90.0, Escala::tope(Escala::SALUDABLE));
        $this->assertSame(75.0, Escala::tope(Escala::ADVERTENCIA));
        $this->assertSame(60.0, Escala::tope(Escala::DEGRADADO));
        $this->assertSame(40.0, Escala::tope(Escala::CRITICO));

        // Un indicador topado en 40,0 pertenece a CRITICO.
        $this->assertSame(Escala::CRITICO, Escala::bandaPorSalud(40.0));
        $this->assertSame(Escala::SALUDABLE, Escala::bandaPorSalud(90.0));
    }

    /** El tope baja la nota; nunca la sube. */
    public function testAplicarTopeSoloPuedeBajar(): void
    {
        $this->assertSame(40.0, Escala::aplicarTope(70.4, Escala::CRITICO));
        $this->assertSame(75.0, Escala::aplicarTope(76.4, Escala::ADVERTENCIA));
        $this->assertSame(91.8, Escala::aplicarTope(91.8, Escala::OPTIMO));

        // Ya por debajo del tope: el tope no lo rescata.
        $this->assertSame(12.0, Escala::aplicarTope(12.0, Escala::ADVERTENCIA));

        // Sin peor estado (componente sin metricas) no hay tope que aplicar.
        $this->assertSame(88.0, Escala::aplicarTope(88.0, null));
    }

    public function testPeorBanda(): void
    {
        $this->assertSame(
            Escala::CRITICO,
            Escala::peor([Escala::OPTIMO, Escala::CRITICO, Escala::SALUDABLE]),
        );

        $this->assertSame(
            Escala::SALUDABLE,
            Escala::peor([Escala::OPTIMO, Escala::SALUDABLE]),
        );

        $this->assertNull(Escala::peor([]));
    }

    /** Un juego de umbrales desordenado revienta al usarse, no publica cifras. */
    public function testUmbralesIncoherentesLanzanExcepcion(): void
    {
        $torcido = $this->umbral('M-XXX-99', 70.0, 50.0, 85.0, 95.0);

        $this->expectException(InvalidArgumentException::class);

        Escala::normalizar(60.0, $torcido, 100.0, Metrica::MENOR_MEJOR);
    }

    /** El techo tambien es dato: el mismo valor crudo no vale lo mismo con otro techo. */
    public function testElTechoSeDeclaraNoSeSupone(): void
    {
        $umbral = $this->umbral('M-PRO-04', 10.0, 14.0, 17.0, 19.0);

        // 1,24 ms sobre techo 20: la lectura de calibracion del catalogo.
        $s = Escala::normalizar(1.24, $umbral, 20.0, Metrica::MENOR_MEJOR);

        $this->assertSame(98.8, Escala::publicar($s));
        $this->assertSame(Escala::OPTIMO, Escala::bandaPorSalud($s));
    }
}
