<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Un usuario del sistema: auditor o administrador de bases de datos.
 *
 * La contraseña NO viaja en esta entidad, ni siquiera cifrada. El hash se lee
 * dentro del repositorio, se compara allí mismo y se descarta; así no hay
 * forma de que termine impreso en una vista o en un volcado de depuración.
 *
 * "organizacion" es la empresa a la que pertenece el usuario. Es un campo de
 * texto y no una tabla aparte: el equipo descartó la tabla ORGANIZACION en la
 * reunión del 2/8 (ver el comentario de Scripts/01_esquema.sql). La empresa
 * auditada se deduce de la organización del administrador de BD entrevistado.
 */
final class Usuario
{
    public const ROL_AUDITOR = 'AUDITOR';
    public const ROL_ADMIN_BD = 'ADMIN_BD';

    public function __construct(
        public readonly int $id,
        public readonly string $nombre,
        public readonly string $correo,
        public readonly string $rol,
        public readonly string $organizacion,
        public readonly bool $activo,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            id:           (int) ($fila['id_usuario'] ?? 0),
            nombre:       (string) ($fila['nombre'] ?? ''),
            correo:       (string) ($fila['correo'] ?? ''),
            rol:          (string) ($fila['rol'] ?? self::ROL_AUDITOR),
            organizacion: (string) ($fila['organizacion'] ?? ''),
            activo:       (int) ($fila['activo'] ?? 0) === 1,
        );
    }

    public function esAuditor(): bool
    {
        return $this->rol === self::ROL_AUDITOR;
    }

    /**
     * El rol ADMIN_BD es además el superadministrador del sistema: es quien
     * podrá dar de alta controles en el catálogo maestro (Bloque 5).
     */
    public function esAdministrador(): bool
    {
        return $this->rol === self::ROL_ADMIN_BD;
    }
}
