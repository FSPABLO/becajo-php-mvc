<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un juego de cuatro umbrales con su vigencia.
 *
 * Fuente única de u_opt/u_adv/u_deg/u_crit (B-8, Anexo A del plan): `metrica`
 * no guarda una copia. `claveInstancia` nula es el umbral general de la
 * métrica; con valor, la anulación para esa instancia. `validoHasta` nulo es
 * el juego vigente — nunca se hace UPDATE sobre los cuatro números (§7.2).
 */
final class Umbral
{
    public function __construct(
        public readonly int $id,
        public readonly string $codigoMetrica,
        public readonly ?string $claveInstancia,
        public readonly float $uOpt,
        public readonly float $uAdv,
        public readonly float $uDeg,
        public readonly float $uCrit,
        public readonly string $validoDesde,
        public readonly ?string $validoHasta,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id:             (int) ($fila['id_umbral'] ?? 0),
            codigoMetrica:  (string) ($fila['codigo_metrica'] ?? ''),
            claveInstancia: self::textoONulo($fila['clave_instancia'] ?? null),
            uOpt:           (float) ($fila['u_opt'] ?? 0),
            uAdv:           (float) ($fila['u_adv'] ?? 0),
            uDeg:           (float) ($fila['u_deg'] ?? 0),
            uCrit:          (float) ($fila['u_crit'] ?? 0),
            validoDesde:    (string) ($fila['valido_desde'] ?? ''),
            validoHasta:    self::textoONulo($fila['valido_hasta'] ?? null),
        );
    }

    public function vigente(): bool
    {
        return $this->validoHasta === null;
    }

    private static function textoONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }
}
