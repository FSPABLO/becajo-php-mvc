<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Cabecera de una recolección, con su índice ya resuelto.
 *
 * "Muestra trae su índice dentro. No se separan en dos objetos porque no
 * existe una muestra sin índice ni un índice sin muestra, y tenerlos juntos
 * evita que la vista tenga que pedir dos cosas para pintar una tarjeta"
 * (contrato-repositorio-monitor.md §4). En el esquema siguen siendo dos
 * tablas —una muestra FALLIDA o PARCIAL bajo el piso de cobertura no genera
 * fila en `indice`—, así que aquí `isbd` puede ser null: es la señal de que
 * no hay nada que publicar, no un error de lectura.
 */
final class Muestra
{
    public const OK = 'OK';
    public const PARCIAL = 'PARCIAL';
    public const FALLIDA = 'FALLIDA';

    public function __construct(
        public readonly int $id,
        public readonly string $claveInstancia,
        public readonly string $tomadaEn,
        public readonly ?int $duracionMs,
        public readonly string $resultado,
        public readonly ?float $coberturaPct,
        public readonly ?string $mensaje,
        public readonly ?float $ip = null,
        public readonly ?float $im = null,
        public readonly ?float $ia = null,
        public readonly ?float $isbdBruto = null,
        public readonly ?float $isbd = null,
        public readonly ?string $estado = null,
        /**
         * Las métricas en el peor estado que explican el índice publicado
         * (invariante 5 del plan: "el ISBD nunca se muestra sin causa").
         *
         * @var list<string>
         */
        public readonly array $causa = [],
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        $causaTexto = (string) ($fila['causa'] ?? '');

        return new self(
            id:              (int) ($fila['id_muestra'] ?? 0),
            claveInstancia:  (string) ($fila['clave_instancia'] ?? ''),
            tomadaEn:        (string) ($fila['tomada_en'] ?? ''),
            duracionMs:      isset($fila['duracion_ms']) ? (int) $fila['duracion_ms'] : null,
            resultado:       (string) ($fila['resultado'] ?? ''),
            coberturaPct:    isset($fila['cobertura_pct']) ? (float) $fila['cobertura_pct'] : null,
            mensaje:         self::textoONulo($fila['mensaje'] ?? null),
            ip:              isset($fila['ip']) ? (float) $fila['ip'] : null,
            im:              isset($fila['im']) ? (float) $fila['im'] : null,
            ia:              isset($fila['ia']) ? (float) $fila['ia'] : null,
            isbdBruto:       isset($fila['isbd_bruto']) ? (float) $fila['isbd_bruto'] : null,
            isbd:            isset($fila['isbd']) ? (float) $fila['isbd'] : null,
            estado:          self::textoONulo($fila['estado'] ?? null),
            causa:           $causaTexto === '' ? [] : explode(',', $causaTexto),
        );
    }

    public function tieneIndice(): bool
    {
        return $this->isbd !== null;
    }

    private static function textoONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }
}
