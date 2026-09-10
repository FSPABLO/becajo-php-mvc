<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * El archivo adjunto de la evidencia de un control: la captura de pantalla, el
 * PDF de la política, el log exportado.
 *
 * NO sustituye a `EvaluacionControl::$evidenciaVerificada`, que sigue siendo la
 * descripción escrita por el auditor y la que exige ISO/IEC 27007 cuando la
 * respuesta es "Sí". El archivo es opcional y la acompaña: un adjunto sin una
 * línea que diga qué se miró en él obliga a abrirlo para saberlo.
 *
 * El CONTENIDO no vive aquí. Esta entidad es la ficha —qué archivo hay, cómo se
 * llama, cuánto pesa— y la pintan las 75 tarjetas del panel; arrastrar el
 * binario de cada una para escribir un nombre y un tamaño sería traerse varios
 * megabytes por pantalla. Los bytes se piden aparte, y solo cuando alguien
 * abre el archivo (RepositorioAuditorias::contenidoArchivoEvidencia()).
 */
final class ArchivoEvidencia
{
    /**
     * Los tipos que se aceptan, y con qué extensión se ofrecen al descargar.
     *
     * La lista está DUPLICADA a propósito en ck_evidarch_tipo (Scripts/01): la
     * validación en PHP existe para dar un mensaje en el campo correcto, y la
     * restricción de la base es la última línea de defensa. Es el mismo criterio
     * que el resto del esquema.
     *
     * @var array<string, string>
     */
    public const TIPOS = [
        'image/png'       => 'png',
        'image/jpeg'      => 'jpg',
        'image/webp'      => 'webp',
        'image/gif'       => 'gif',
        'application/pdf' => 'pdf',
    ];

    /** 5 MB. El mismo número que ck_evidarch_tamano, y por la misma razón. */
    public const MAXIMO_BYTES = 5 * 1024 * 1024;

    public function __construct(
        public readonly string $codigoControl,
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
            codigoControl: (string) ($fila['codigo_control'] ?? ''),
            nombre:        (string) ($fila['nombre'] ?? ''),
            tipoMime:      (string) ($fila['tipo_mime'] ?? ''),
            tamanoBytes:   (int) ($fila['tamano_bytes'] ?? 0),
            fechaCarga:    isset($fila['fecha_carga']) ? (string) $fila['fecha_carga'] : null,
            id:            (int) ($fila['id_evidencia_archivo'] ?? 0),
        );
    }

    public function esImagen(): bool
    {
        return str_starts_with($this->tipoMime, 'image/');
    }

    public function esPdf(): bool
    {
        return $this->tipoMime === 'application/pdf';
    }

    /**
     * El tamaño en la unidad que se lea de un vistazo, con formato español
     * (§2.3 del sistema visual: coma decimal).
     *
     * Un decimal en MB y ninguno en KB: "1,4 MB" dice algo, "312,0 KB" no dice
     * más que "312 KB" y ocupa tres caracteres más en una línea que ya lleva el
     * nombre del archivo al lado.
     */
    public function tamanoLegible(): string
    {
        if ($this->tamanoBytes >= 1024 * 1024) {
            return number_format($this->tamanoBytes / (1024 * 1024), 1, ',', '') . ' MB';
        }

        return number_format(max(1, (int) round($this->tamanoBytes / 1024)), 0, ',', '') . ' KB';
    }
}
