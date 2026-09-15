<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * La fotografía de perfil de un usuario.
 *
 * Es la FICHA —qué imagen hay, cómo se llama, cuánto pesa—, no la imagen. Los
 * bytes se piden aparte y solo cuando alguien abre la foto
 * (`RepositorioAuditorias::contenidoFotoUsuario()`), por la misma razón por la
 * que `ArchivoEvidencia` está partida igual: `usuario` se consulta en CADA
 * petición y un BLOB en esa lectura sería la foto viajando en cada clic.
 *
 * Es opcional siempre. Sin foto, el avatar cae en las iniciales del nombre, que
 * es lo que el producto ya pintaba antes de que esto existiera.
 */
final class FotoPerfil
{
    /**
     * Los tipos que se aceptan, y con qué extensión se sirven.
     *
     * Solo imágenes: un avatar en PDF no es un avatar. La lista está DUPLICADA
     * en `ck_usufoto_tipo` (Scripts/01) a propósito, con el mismo criterio que
     * el resto del esquema: la validación en PHP da el mensaje en el campo, la
     * restricción de la base es la última línea de defensa.
     *
     * @var array<string, string>
     */
    public const TIPOS = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /** 2 MB. El mismo número que ck_usufoto_tamano, y por la misma razón. */
    public const MAXIMO_BYTES = 2 * 1024 * 1024;

    public function __construct(
        public readonly int $idUsuario,
        public readonly string $nombre,
        public readonly string $tipoMime,
        public readonly int $tamanoBytes,
        public readonly ?string $fechaCarga = null,
        public readonly int $id = 0,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        return new self(
            idUsuario:   (int) ($fila['id_usuario'] ?? 0),
            nombre:      (string) ($fila['nombre'] ?? ''),
            tipoMime:    (string) ($fila['tipo_mime'] ?? ''),
            tamanoBytes: (int) ($fila['tamano_bytes'] ?? 0),
            fechaCarga:  isset($fila['fecha_carga']) ? (string) $fila['fecha_carga'] : null,
            id:          (int) ($fila['id_usuario_foto'] ?? 0),
        );
    }

    /**
     * El tamaño en la unidad que se lea de un vistazo, con formato español
     * (§2.3 del sistema visual: coma decimal). Mismo criterio que
     * ArchivoEvidencia: un decimal en MB y ninguno en KB.
     */
    public function tamanoLegible(): string
    {
        if ($this->tamanoBytes >= 1024 * 1024) {
            return number_format($this->tamanoBytes / (1024 * 1024), 1, ',', '') . ' MB';
        }

        return number_format(max(1, (int) round($this->tamanoBytes / 1024)), 0, ',', '') . ' KB';
    }
}
