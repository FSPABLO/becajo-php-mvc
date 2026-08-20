<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 correoUsuario viaja siempre, aunque idUsuario sea null o la cuenta ya no
 * exista: es la copia que sobrevive al usuario, no una referencia que se
 * pierde si la cuenta se borra o se desactiva.
 */
final class RegistroBitacora
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $idUsuario,
        public readonly string $correoUsuario,
        public readonly string $accion,
        public readonly ?string $entidad,
        public readonly ?string $idEntidad,
        public readonly ?string $detalle,
        public readonly ?string $direccionIp,
        public readonly string $fechaHora,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id:            (int) $fila['id_bitacora'],
            idUsuario:     isset($fila['id_usuario']) ? (int) $fila['id_usuario'] : null,
            correoUsuario: (string) $fila['correo_usuario'],
            accion:        (string) $fila['accion'],
            entidad:       $fila['entidad'] !== null ? (string) $fila['entidad'] : null,
            idEntidad:     $fila['id_entidad'] !== null ? (string) $fila['id_entidad'] : null,
            detalle:       $fila['detalle'] !== null ? (string) $fila['detalle'] : null,
            direccionIp:   $fila['direccion_ip'] !== null ? (string) $fila['direccion_ip'] : null,
            fechaHora:     (string) $fila['fecha_hora'],
        );
    }
}
