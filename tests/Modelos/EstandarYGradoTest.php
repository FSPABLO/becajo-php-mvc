<?php

declare(strict_types=1);

namespace Pruebas\Modelos;

use App\Models\Entidades\Estandar;
use App\Models\Entidades\EvaluacionControl;
use PHPUnit\Framework\TestCase;

/**
 * La captura COBIT deriva el estado del grado de logro. Si esa traducción
 * cambia, cambian el cumplimiento y las remediaciones sin que nadie lo note.
 */
final class EstandarYGradoTest extends TestCase
{
    public function testLyFCuentanComoLogrado(): void
    {
        self::assertSame(EvaluacionControl::SI, EvaluacionControl::estadoDeGrado('L'));
        self::assertSame(EvaluacionControl::SI, EvaluacionControl::estadoDeGrado('F'));
    }

    public function testNyPCuentanComoNoLogrado(): void
    {
        self::assertSame(EvaluacionControl::NO, EvaluacionControl::estadoDeGrado('N'));
        self::assertSame(EvaluacionControl::NO, EvaluacionControl::estadoDeGrado('P'));
    }

    public function testNoAplicaPasaTalCual(): void
    {
        self::assertSame(EvaluacionControl::NO_APLICA, EvaluacionControl::estadoDeGrado('NA'));
    }

    public function testElModoSaleDelDato(): void
    {
        $cobit = Estandar::desdeArreglo(['codigo' => 'COBIT2019', 'modo_evaluacion' => 'OBJETIVO']);
        $iso = Estandar::desdeArreglo(['codigo' => 'ISO27002']);

        self::assertTrue($cobit->evaluaPorObjetivo());
        self::assertFalse($iso->evaluaPorObjetivo());
    }

    public function testElGradoSeLeeDeLaFila(): void
    {
        $evaluacion = EvaluacionControl::desdeFila(['id_auditoria' => 1, 'codigo_control' => 'CB-001', 'grado_logro' => 'P']);

        self::assertSame('P', $evaluacion->gradoLogro);
    }
}
