<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Ciclo de vida completo de una alerta (§6 del plan).
 *
 * A lo sumo una ABIERTA por (instancia, métrica) — no por nivel (B-3, Anexo A
 * del plan): el nivel escala dentro de esta misma fila (`nivelActual` /
 * `nivelMaximo`) en vez de abrir una segunda alerta cuando el problema
 * empeora.
 */
final class Alerta
{
    public const ABIERTA = 'ABIERTA';
    public const RECONOCIDA = 'RECONOCIDA';
    public const CERRADA = 'CERRADA';

    public function __construct(
        public readonly int $id,
        public readonly string $claveInstancia,
        public readonly string $codigoMetrica,
        public readonly string $nivelActual,
        public readonly string $nivelMaximo,
        public readonly string $estadoAtencion,
        public readonly ?float $valor,
        public readonly ?float $umbral,
        public readonly ?string $descripcion,
        public readonly int $ocurrencias,
        public readonly string $vistaPrimeraVez,
        public readonly string $vistaPorUltimaVez,
        /** Referencia informal a usuario.id_usuario de la parte 1; sin FK formal (esquema separado). */
        public readonly ?int $responsable,
        public readonly ?string $fechaReconocimiento,
        public readonly ?string $fechaCierre,
        public readonly ?string $motivoCierre,
        public readonly ?string $accion,
        public readonly ?int $idEpisodio,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id:                   (int) ($fila['id_alerta'] ?? 0),
            claveInstancia:       (string) ($fila['clave_instancia'] ?? ''),
            codigoMetrica:        (string) ($fila['codigo_metrica'] ?? ''),
            nivelActual:          (string) ($fila['nivel_actual'] ?? ''),
            nivelMaximo:          (string) ($fila['nivel_maximo'] ?? ''),
            estadoAtencion:       (string) ($fila['estado_atencion'] ?? self::ABIERTA),
            valor:                isset($fila['valor']) ? (float) $fila['valor'] : null,
            umbral:               isset($fila['umbral']) ? (float) $fila['umbral'] : null,
            descripcion:          self::textoONulo($fila['descripcion'] ?? null),
            ocurrencias:          (int) ($fila['ocurrencias'] ?? 1),
            vistaPrimeraVez:      (string) ($fila['vista_primera_vez'] ?? ''),
            vistaPorUltimaVez:    (string) ($fila['vista_por_ultima_vez'] ?? ''),
            responsable:          isset($fila['responsable']) ? (int) $fila['responsable'] : null,
            fechaReconocimiento:  self::textoONulo($fila['fecha_reconocimiento'] ?? null),
            fechaCierre:          self::textoONulo($fila['fecha_cierre'] ?? null),
            motivoCierre:         self::textoONulo($fila['motivo_cierre'] ?? null),
            accion:               self::textoONulo($fila['accion'] ?? null),
            idEpisodio:           isset($fila['id_episodio']) ? (int) $fila['id_episodio'] : null,
        );
    }

    public function estaAbierta(): bool
    {
        return $this->estadoAtencion === self::ABIERTA;
    }

    private static function textoONulo(mixed $valor): ?string
    {
        return ($valor === null || $valor === '') ? null : (string) $valor;
    }
}
