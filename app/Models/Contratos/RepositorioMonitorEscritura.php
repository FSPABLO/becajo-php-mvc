<?php

declare(strict_types=1);

namespace App\Models\Contratos;

/**
 * Contrato de escritura del monitor. Solo lo usa bin/monitor.php.
 *
 * Vive aparte de RepositorioMonitor por la misma razón que RepositorioCatalogo
 * vive aparte de RepositorioInstrumento: la implementación de arreglo no sabe
 * escribir, y el sitio no debe poder hacerlo.
 *
 * Fuente: documentacion/Parte II/contrato-repositorio-monitor.md §3.
 */
interface RepositorioMonitorEscritura
{
    // ── Persistencia de la muestra ───────────────────────────────────────────

    /**
     * Guarda una muestra evaluada completa —cabecera, mediciones e índice— y
     * devuelve su identificador.
     *
     * Recibe la estructura definida en el contrato de muestra, §5. Es todo o
     * nada: una muestra a medio guardar es peor que ninguna, porque parece
     * completa.
     *
     * Una muestra FALLIDA también se guarda. Que la instancia no respondiera a
     * esa hora es un dato; saltarla deja un hueco que después parece un periodo
     * sano.
     *
     * @param array<string, mixed> $muestraEvaluada
     */
    public function guardarMuestra(array $muestraEvaluada): int;

    // ── Ciclo de vida de las alertas ─────────────────────────────────────────

    /**
     * Abre una alerta si no hay ninguna abierta para (instancia, métrica); si la
     * hay, actualiza su nivel y sus ocurrencias.
     *
     * La clave NO incluye el nivel: un problema que empeora escala dentro de su
     * alerta en vez de abrir una segunda (§6 del plan).
     */
    public function registrarAlerta(
        string $clave,
        string $codigoMetrica,
        string $nivel,
        float $valor,
        float $umbral,
        string $vistaUtc,
    ): int;

    public function cerrarAlerta(
        int $idAlerta,
        string $motivo,
        ?string $accion,
        ?int $idResponsable,
    ): void;

    /**
     * Agrupa alertas concurrentes en un episodio y señala la causa probable
     * cuando la tabla de precedencias permite deducirla.
     *
     * @param list<int> $idsAlerta
     */
    public function agruparEnEpisodio(array $idsAlerta, ?int $idAlertaCausa): int;

    // ── Umbrales versionados ─────────────────────────────────────────────────

    /**
     * Cierra el juego de umbrales vigente y abre uno nuevo.
     *
     * Nunca hay UPDATE sobre los cuatro números: recalibrar no debe reescribir
     * la historia (§7.2).
     */
    public function versionarUmbral(
        string $codigoMetrica,
        ?string $clave,
        float $uOpt,
        float $uAdv,
        float $uDeg,
        float $uCrit,
        string $desdeUtc,
    ): int;

    // ── Mantenimiento (pkg_monitor) ──────────────────────────────────────────

    /** Consolida en resumen_hora. Debe correr ANTES de purgar y verificarse. */
    public function consolidarHora(string $hastaUtc): void;

    /** Purga según la política del §7.4. Si la consolidación falló, no purga. */
    public function purgar(string $hastaUtc): void;
}
