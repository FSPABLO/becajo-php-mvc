<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Una base Oracle vigilada.
 *
 * `clave` es la llave natural ('FREEPDB1'), la misma que viaja en toda la
 * interfaz de RepositorioMonitor y en la muestra cruda (contrato-muestra.md
 * §3) — no un id numérico. `servicioRaiz` y `servicioContenedor` existen
 * porque el agente abre dos conexiones por muestra (§10.1 del plan): la raíz,
 * donde vive V$RESOURCE_LIMIT de verdad, y el PDB auditado.
 */
final class Instancia
{
    public function __construct(
        public readonly string $clave,
        public readonly string $nombre,
        public readonly string $motor,
        public readonly string $host,
        public readonly ?int $puerto,
        public readonly string $servicioRaiz,
        public readonly string $servicioContenedor,
        public readonly string $entorno,
        public readonly string $criticidad,
        public readonly bool $activa,
        /** B-5: distingue los datos de Scripts/08_datos_demo_monitor.sql de una recolección real. */
        public readonly bool $demostrativa,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            clave:               (string) ($fila['clave'] ?? ''),
            nombre:               (string) ($fila['nombre'] ?? ''),
            motor:                (string) ($fila['motor'] ?? ''),
            host:                 (string) ($fila['host'] ?? ''),
            puerto:               isset($fila['puerto']) ? (int) $fila['puerto'] : null,
            servicioRaiz:         (string) ($fila['servicio_raiz'] ?? ''),
            servicioContenedor:   (string) ($fila['servicio_contenedor'] ?? ''),
            entorno:              (string) ($fila['entorno'] ?? ''),
            criticidad:           (string) ($fila['criticidad'] ?? 'MEDIA'),
            activa:               (int) ($fila['activa'] ?? 1) === 1,
            demostrativa:         (int) ($fila['demostrativa'] ?? 0) === 1,
        );
    }
}
