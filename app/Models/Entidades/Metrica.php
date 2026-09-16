<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Entrada del catálogo de métricas, con su umbral general vigente.
 *
 * `sentido` distingue las tres familias de señal del §5.1 del plan: una
 * compuerta (ESTADO) no lleva peso ni umbral (§1.3 del catálogo). `entraIsbd`
 * en falso es la excepción declarada de CONSULTAS (§3.1 del plan): se
 * normaliza igual que una MENOR_MEJOR pero no se suma al índice — no hace
 * falta un cuarto valor de `sentido` para eso. `esIdentidad` es B-7: la
 * lectura cruda es una huella de texto, no una magnitud (hoy solo M-PRO-05).
 */
final class Metrica
{
    public const MENOR_MEJOR = 'MENOR_MEJOR';
    public const MAYOR_MEJOR = 'MAYOR_MEJOR';
    public const ESTADO = 'ESTADO';

    public const RAIZ = 'RAIZ';
    public const CONTENEDOR = 'CONTENEDOR';

    public function __construct(
        public readonly string $codigo,
        public readonly string $componente,
        public readonly string $nombre,
        public readonly ?string $unidad,
        public readonly string $vistaOrigen,
        public readonly string $sentido,
        public readonly string $ambito,
        public readonly ?int $peso,
        public readonly float $uMax,
        public readonly bool $acumulada,
        public readonly bool $esIdentidad,
        public readonly bool $entraIsbd,
        public readonly ?string $anclaIso,
        public readonly ?Umbral $umbral = null,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        $umbral = isset($fila['id_umbral']) ? Umbral::desdeFila($fila) : null;

        return new self(
            codigo:       (string) ($fila['codigo'] ?? ''),
            componente:   (string) ($fila['componente'] ?? ''),
            nombre:       (string) ($fila['nombre'] ?? ''),
            unidad:       self::textoONulo($fila['unidad'] ?? null),
            vistaOrigen:  (string) ($fila['vista_origen'] ?? ''),
            sentido:      (string) ($fila['sentido'] ?? ''),
            ambito:       (string) ($fila['ambito'] ?? ''),
            peso:         isset($fila['peso']) ? (int) $fila['peso'] : null,
            uMax:         (float) ($fila['u_max'] ?? 100),
            acumulada:    (int) ($fila['acumulada'] ?? 0) === 1,
            esIdentidad:  (int) ($fila['es_identidad'] ?? 0) === 1,
            entraIsbd:    (int) ($fila['entra_isbd'] ?? 1) === 1,
            anclaIso:     self::textoONulo($fila['ancla_iso'] ?? null),
            umbral:       $umbral,
        );
    }

    /** Familia ESTADO: no promedia, cierra el componente (§1.3 del catálogo). */
    public function esCompuerta(): bool
    {
        return $this->sentido === self::ESTADO;
    }

    private static function textoONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }
}
