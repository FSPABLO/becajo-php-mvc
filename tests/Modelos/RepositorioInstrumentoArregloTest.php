<?php

declare(strict_types=1);

namespace Pruebas\Modelos;

use App\Models\Entidades\Estandar;
use App\Models\RepositorioInstrumentoArreglo;
use PHPUnit\Framework\TestCase;

/**
 * El arreglo solo describe ISO. Con Oracle, la escala de ISO sale de la
 * semilla de 14_multinorma.sql: si los dos textos se separan, las pantallas
 * cambian según qué repositorio esté activo.
 */
final class RepositorioInstrumentoArregloTest extends TestCase
{
    private const RAIZ = __DIR__ . '/../..';

    private function repositorio(): RepositorioInstrumentoArreglo
    {
        return new RepositorioInstrumentoArreglo(self::RAIZ . '/config/instrumento-bd.php');
    }

    public function testPorOmisionEntregaElCatalogoIso(): void
    {
        $repositorio = $this->repositorio();

        self::assertCount(7, $repositorio->dominios());
        self::assertCount(25, $repositorio->procesos());
        self::assertCount(75, $repositorio->controles());
        self::assertEquals($repositorio->controles(), $repositorio->controles(Estandar::ISO));
    }

    public function testOtraNormaNoRecibeElCatalogoIso(): void
    {
        $repositorio = $this->repositorio();

        self::assertSame([], $repositorio->dominios('COBIT2019'));
        self::assertSame([], $repositorio->procesos('COBIT2019'));
        self::assertSame([], $repositorio->controles('COBIT2019'));
        self::assertSame([], $repositorio->escala('COBIT2019'));
    }

    public function testDeclaraSoloIso(): void
    {
        $estandares = $this->repositorio()->estandares();

        self::assertCount(1, $estandares);
        self::assertSame(Estandar::ISO, $estandares[0]->codigo);
    }

    public function testLaEscalaIsoCoincideConLaSemillaDeOracle(): void
    {
        $sql = (string) file_get_contents(self::RAIZ . '/Scripts/14_multinorma.sql');
        preg_match_all(
            "/INSERT INTO nivel_madurez VALUES \\('ISO27002', (\\d), '([^']+)',\\s*'([^']+)'\\);/u",
            $sql,
            $coincidencias,
            PREG_SET_ORDER,
        );

        $semilla = array_map(
            static fn (array $m): array => ['nivel' => (int) $m[1], 'nombre' => $m[2], 'descripcion' => $m[3]],
            $coincidencias,
        );

        self::assertCount(6, $semilla);
        self::assertSame($this->repositorio()->escala(), $semilla);
    }
}
