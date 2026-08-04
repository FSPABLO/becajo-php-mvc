<?php

declare(strict_types=1);

namespace App\Models\Contratos;

use App\Models\Entidades\Control;
use App\Models\Entidades\Dominio;
use App\Models\Entidades\Proceso;

/**
 * Administración del catálogo maestro: dominios, procesos y controles.
 *
 * Extiende el contrato de lectura en vez de sustituirlo, así que quien
 * administre el catálogo también puede consultarlo. La separación importa
 * porque RepositorioInstrumentoArreglo —el que lee config/instrumento-bd.php—
 * implementa solo la parte de lectura: un archivo PHP del repositorio no se
 * edita desde una pantalla, y obligarle a declarar métodos de escritura que
 * lanzarían excepciones sería mentir sobre lo que sabe hacer.
 *
 * LAS CLAVES NO SE CAMBIAN. Una vez creado, un dominio conserva su clave, un
 * proceso su número y un control su código. Renombrarlos exigiría arrastrar el
 * cambio por las tablas que los referencian —incluidas las evaluaciones ya
 * respondidas de auditorías cerradas—, y una auditoría entregada al cliente no
 * puede cambiar de contenido a posteriori. Para corregir una clave se borra y
 * se vuelve a crear, cosa que solo se permite si nadie la usa todavía.
 */
interface RepositorioCatalogo extends RepositorioInstrumento
{
    // ── Dominios ─────────────────────────────────────────────────────────────

    public function dominio(string $clave): ?Dominio;

    public function crearDominio(Dominio $dominio): void;

    /** Actualiza todo menos la clave, que identifica la fila. */
    public function actualizarDominio(Dominio $dominio): void;

    public function eliminarDominio(string $clave): void;

    // ── Procesos ─────────────────────────────────────────────────────────────

    public function proceso(int $numero): ?Proceso;

    public function crearProceso(Proceso $proceso): void;

    public function actualizarProceso(Proceso $proceso): void;

    public function eliminarProceso(int $numero): void;

    // ── Controles ────────────────────────────────────────────────────────────

    public function control(string $codigo): ?Control;

    public function crearControl(Control $control): void;

    public function actualizarControl(Control $control): void;

    public function eliminarControl(string $codigo): void;

    // ── Comprobaciones antes de borrar ───────────────────────────────────────

    /**
     * Cuántos procesos cuelgan de un dominio.
     *
     * Las llaves foráneas ya impedirían el borrado, pero devolverían un
     * ORA-02292 en pantalla. Con estos recuentos se explica en castellano qué
     * hay que quitar primero.
     */
    public function procesosDeDominio(string $clave): int;

    public function controlesDeProceso(int $numero): int;

    /**
     * Cuántas evaluaciones de auditoría responden a un control.
     *
     * Mientras haya una sola, el control no se borra: se llevaría por delante
     * la respuesta de una auditoría ya realizada.
     */
    public function evaluacionesDeControl(string $codigo): int;

    /**
     * Lo mismo para todos los controles a la vez: código => nº de evaluaciones.
     *
     * El índice del catálogo necesita saber, control por control, si se puede
     * borrar. Preguntarlo de uno en uno serían 75 consultas para pintar una
     * tabla; una agrupación las resuelve todas.
     *
     * @return array<string, int>
     */
    public function evaluacionesPorControl(): array;

    /** El siguiente valor libre de 'orden' dentro de una lista. */
    public function siguienteOrdenDominio(): int;

    public function siguienteOrdenProceso(): int;
}
