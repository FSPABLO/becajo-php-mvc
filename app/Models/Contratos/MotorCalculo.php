<?php

declare(strict_types=1);

namespace App\Models\Contratos;

/**
 * Contrato entre el recolector (bin/monitor.php, F2) y el motor de cálculo
 * (F3): convierte una muestra cruda en una muestra evaluada.
 *
 * No es uno de los tres contratos originales de la Fase 0 —esos fijaron el
 * modelo de datos, no cómo se llaman entre sí el agente y el motor—, así que
 * queda documentado como decisión B-10 en el Anexo A del plan. Un solo
 * método: el recolector entrega la muestra cruda (contrato-muestra.md §3) y
 * recibe la muestra evaluada (§5), lista para RepositorioMonitorEscritura::guardarMuestra().
 *
 * El motor recibe también RepositorioMonitor porque necesita mirar hacia
 * atrás: acumuladosAnteriores()/huellasAnteriores() para derivar tasas e
 * identidad, estadosRecientes() para la histéresis, ventanaLineaBase() para
 * la línea base del §5.5.
 */
interface MotorCalculo
{
    /**
     * @param array<string, mixed> $muestraCruda
     * @return array<string, mixed>
     */
    public function evaluar(array $muestraCruda, RepositorioMonitor $repositorio): array;
}
