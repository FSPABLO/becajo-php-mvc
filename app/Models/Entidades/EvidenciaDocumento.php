<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un documento de respaldo que sustenta una o varias evaluaciones de control.
 *
 * Vive independiente de EvaluacionControl, el mismo documento
 * (un correo, una captura, un export de configuración) puede sustentar varias
 * preguntas de control distintas dentro de una misma auditoría. La relación
 * muchos a muchos la resuelve la tabla evidencia_control; esta entidad
 * representa solo el documento en sí.
 */
final class EvidenciaDocumento
{
    /** Según ck_evidencia_formato. */
    public const FORMATOS = ['PDF', 'DOCX', 'XLSX', 'PNG', 'JPG', 'CSV', 'TXT', 'LOG'];

    public function __construct(
        public readonly int $id,
        public readonly int $idAuditoria,
        public readonly string $nombreDocumento,
        public readonly string $formato,
        public readonly string $version,
        public readonly string $responsable,
        public readonly string $fechaDocumento,
        // Cuántos OTROS controles (además de este) ya usan el mismo
        // documento. Solo lo llena evidenciasDeControl(); en
        // evidenciasDeAuditoria()
        public readonly int $otrosControles = 0,
        public readonly string $controlesVinculados = '',
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id:                 (int) $fila['id_evidencia'],
            idAuditoria:        (int) $fila['id_auditoria'],
            nombreDocumento:    (string) $fila['nombre_documento'],
            formato:            (string) $fila['formato'],
            version:            (string) $fila['version'],
            responsable:        (string) $fila['responsable'],
            fechaDocumento:     (string) $fila['fecha_documento'],
            otrosControles:     (int) ($fila['otros_controles'] ?? 0),
            controlesVinculados: (string) ($fila['controles_vinculados'] ?? ''),
        );
    }

    /** Resumen de una línea, para la tarjeta vinculada y el selector de "vincular existente". */
    public function etiqueta(): string
    {
        return $this->nombreDocumento . ' (' . $this->formato . ', v' . $this->version
             . ', ' . $this->fechaDocumento . ')';
    }
}
