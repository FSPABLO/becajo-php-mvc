<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Una métrica dentro de una muestra: crudo, normalizado, estado.
 *
 * `valorAcumulado` (metrica.acumulada) y `huella` (metrica.es_identidad, B-7)
 * son nulos salvo para las métricas de su propia familia — es el mismo
 * patrón para tasas y para identidad, ver contrato-muestra.md §3.1.
 */
final class Medicion
{
    public function __construct(
        public readonly int $id,
        public readonly int $idMuestra,
        public readonly string $codigoMetrica,
        public readonly ?float $valorCrudo,
        public readonly ?float $valorNormalizado,
        public readonly ?string $estado,
        public readonly ?int $idUmbral,
        public readonly ?float $valorAcumulado,
        public readonly ?string $huella,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id:                (int) ($fila['id_medicion'] ?? 0),
            idMuestra:         (int) ($fila['id_muestra'] ?? 0),
            codigoMetrica:     (string) ($fila['codigo_metrica'] ?? ''),
            valorCrudo:        isset($fila['valor_crudo']) ? (float) $fila['valor_crudo'] : null,
            valorNormalizado:  isset($fila['valor_normalizado']) ? (float) $fila['valor_normalizado'] : null,
            estado:            self::textoONulo($fila['estado'] ?? null),
            idUmbral:          isset($fila['id_umbral']) ? (int) $fila['id_umbral'] : null,
            valorAcumulado:    isset($fila['valor_acumulado']) ? (float) $fila['valor_acumulado'] : null,
            huella:            self::textoONulo($fila['huella'] ?? null),
        );
    }

    private static function textoONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }
}
