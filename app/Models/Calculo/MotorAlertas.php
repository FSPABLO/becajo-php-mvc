<?php

declare(strict_types=1);

namespace App\Models\Calculo;

use App\Models\Contratos\RepositorioMonitor;
use App\Models\Entidades\Alerta;

/**
 * Decide qué alertas abrir, escalar y cerrar a partir de una muestra evaluada
 *
 * No escribe: devuelve decisiones y quien las persiste es
 * `RepositorioMonitorEscritura`. El motor de cálculo no toca la base, y una
 * alerta que se abre sin transacción de por medio es más fácil de probar.
 */
final class MotorAlertas
{
    public const ABRIR = 'abrir';
    public const ESCALAR = 'escalar';
    public const CERRAR = 'cerrar';

    /**
     * @param string $desde Banda a partir de la cual una métrica merece alerta.
     * @param list<array{origen: string, consecuencia: string}> $precedencias
     *   Conocimiento declarado del catálogo, no correlación calculada (§6.1).
     */
    public function __construct(
        private readonly string $desde = Escala::ADVERTENCIA,
        private readonly array $precedencias = [],
    ) {
    }

    /**
     * @param array<string, mixed> $evaluada
     * @return array{abrir: list<array<string, mixed>>, escalar: list<array<string, mixed>>, cerrar: list<array<string, mixed>>, episodio: array<string, mixed>|null}
     */
    public function decidir(array $evaluada, RepositorioMonitor $repositorio): array
    {
        $clave = (string) ($evaluada['instancia'] ?? '');
        $abiertas = $this->abiertasPorMetrica($repositorio, $clave);

        /** @var array<string, array<string, mixed>> $mediciones */
        $mediciones = $evaluada['mediciones'] ?? [];

        $abrir = [];
        $escalar = [];
        $cerrar = [];

        foreach ($mediciones as $codigo => $medicion) {
            $codigo = (string) $codigo;
            $nivel = $this->nivel($medicion);
            $abierta = $abiertas[$codigo] ?? null;

            if ($nivel === null) {
                if ($abierta !== null) {
                    $cerrar[] = [
                        'id_alerta' => $abierta->id,
                        'motivo'    => 'La métrica volvió a ' . (string) $medicion['estado'] . '.',
                    ];
                }

                continue;
            }

            $decision = [
                'codigo_metrica' => $codigo,
                'nivel'          => $nivel,
                'valor'          => $medicion['valor_crudo'] ?? null,
                'descripcion'    => $this->descripcion($codigo, $medicion, $nivel),
            ];

            if ($abierta === null) {
                $abrir[] = $decision;
                continue;
            }

            // El nivel escala dentro de la alerta; no se abre una segunda
            // (B-3). Si no cambió, la persistencia solo suma una ocurrencia.
            $escalar[] = ['id_alerta' => $abierta->id] + $decision;
        }

        // Una métrica que no se pudo medir no cierra su alerta: sin dato no es
        // «está bien». La alerta sigue abierta hasta que haya evidencia.
        return [
            self::ABRIR   => $abrir,
            self::ESCALAR => $escalar,
            self::CERRAR  => $cerrar,
            'episodio'    => $this->episodio($abrir),
        ];
    }

    /** El nivel de una medición, o null si no merece alerta.
     * @param array<string, mixed> $medicion
     */
    private function nivel(array $medicion): ?string
    {
        $estado = (string) ($medicion['estado'] ?? '');

        if ($estado !== '' && Escala::alMenosTanSevera($estado, $this->desde)) {
            return $estado;
        }

        if (($medicion['linea_base']['anomala'] ?? false) === true) {
            return Escala::ADVERTENCIA;
        }

        return null;
    }

    /** Lo mínimo para poder actuar sin ir a mirar a mano.
     * @param array<string, mixed> $medicion
     */
    private function descripcion(string $codigo, array $medicion, string $nivel): string
    {
        if (isset($medicion['abierta']) && $medicion['abierta'] === false) {
            $detalle = $medicion['detalle'] ?? [];
            $faltan = $detalle['ausentes'] ?? $detalle['invalidos'] ?? [];

            return $faltan === []
                ? "{$codigo}: compuerta cerrada."
                : "{$codigo}: compuerta cerrada, falta " . implode(', ', array_map(strval(...), $faltan)) . '.';
        }

        if ($nivel === Escala::ADVERTENCIA && ($medicion['linea_base']['anomala'] ?? false) === true) {
            return sprintf(
                '%s: %s desviaciones bajo su línea base (media %s), sin cruzar umbral.',
                $codigo,
                number_format(abs((float) $medicion['linea_base']['z']), 1, ',', ''),
                number_format((float) $medicion['linea_base']['media'], 1, ',', ''),
            );
        }

        return sprintf(
            '%s en %s: salud %s.',
            $codigo,
            $nivel,
            number_format((float) ($medicion['valor_normalizado'] ?? 0), 1, ',', ''),
        );
    }

    /** Las alertas que se abren en la misma muestra forman un episodio, y la que
     * no es consecuencia de ninguna otra presente se marca como causa probable.
     */
    private function episodio(array $abrir): ?array
    {
        if (count($abrir) < 2) {
            return null;
        }

        $codigos = array_map(static fn (array $a): string => (string) $a['codigo_metrica'], $abrir);
        $raices = [];

        foreach ($codigos as $codigo) {
            if (!$this->esConsecuenciaDeAlguna($codigo, $codigos)) {
                $raices[] = $codigo;
            }
        }

        return [
            'metricas' => $codigos,
            // Con más de una raíz no hay una causa que señalar sin inventarla.
            'causa'    => count($raices) === 1 ? $raices[0] : null,
        ];
    }

    /** @param list<string> $presentes */
    private function esConsecuenciaDeAlguna(string $codigo, array $presentes): bool
    {
        foreach ($this->precedencias as $precedencia) {
            if ($precedencia['consecuencia'] === $codigo
                && in_array($precedencia['origen'], $presentes, true)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, Alerta> */
    private function abiertasPorMetrica(RepositorioMonitor $repositorio, string $clave): array
    {
        $porMetrica = [];

        foreach ($repositorio->alertasAbiertas($clave) as $alerta) {
            $porMetrica[$alerta->codigoMetrica] = $alerta;
        }

        return $porMetrica;
    }
}
