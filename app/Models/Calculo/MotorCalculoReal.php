<?php

declare(strict_types=1);

namespace App\Models\Calculo;

use App\Models\Contratos\MotorCalculo;
use App\Models\Contratos\RepositorioMonitor;

/**
 * El motor que arma `bin/monitor.php` (B-10 del Anexo A).
 *
 * Es la raíz de composición del frente 3: el único sitio donde se eligen el
 * piso de cobertura, la ventana de histéresis y los parámetros de la línea
 * base. Las clases de `App\Models\Calculo` no traen configuración; la reciben.
 *
 * `bin/monitor.php` la busca por nombre y degrada si no existe (B-10). Vive
 * junto al resto del frente 3 y no en una capa de servicios propia: una capa
 * nueva para una sola clase sería arquitectura decidida por un `string` de
 * otro archivo.
 *
 * Las decisiones de alerta viajan en la muestra evaluada bajo `alertas`.
 * `guardarMuestra()` ignora las claves que no conoce, así que **no se
 * persisten todavía**: falta que el agente las pase a `registrarAlerta()`,
 * `cerrarAlerta()` y `agruparEnEpisodio()`. Se emiten igual para que el
 * enganche sea un cambio de tres líneas y no una reescritura.
 */
final class MotorCalculoReal implements MotorCalculo
{
    private readonly MotorCalculoSalud $salud;

    /** @param array<string, mixed> $configMonitor */
    public function __construct(private readonly array $configMonitor = [])
    {
        $this->salud = new MotorCalculoSalud(
            pisoCobertura: (float) ($configMonitor['piso_cobertura_pct'] ?? 80.0),
            ventana: (int) ($configMonitor['histeresis_ventana'] ?? 3),
            paraSubir: (int) ($configMonitor['histeresis_para_subir'] ?? 2),
            diasLineaBase: (int) ($configMonitor['linea_base_dias'] ?? 14),
            lineaBase: new LineaBase(
                minimoMuestras: (int) ($configMonitor['linea_base_minimo'] ?? 8),
                sigmas: (float) ($configMonitor['linea_base_sigmas'] ?? 3.0),
            ),
        );
    }

    /**
     * @param array<string, mixed> $muestraCruda
     * @return array<string, mixed>
     */
    public function evaluar(array $muestraCruda, RepositorioMonitor $repositorio): array
    {
        $evaluada = $this->salud->evaluar($muestraCruda, $repositorio);

        // Las precedencias salen del repositorio y no de una constante: son
        // dato del catálogo, igual que los umbrales (B-8). Construir el motor
        // de alertas aquí y no en el constructor es lo que permite leerlas.
        $alertas = new MotorAlertas(
            (string) ($this->configMonitor['alertar_desde'] ?? Escala::ADVERTENCIA),
            $repositorio->precedencias(),
        );

        $evaluada['alertas'] = $alertas->decidir($evaluada, $repositorio);

        return $evaluada;
    }
}

