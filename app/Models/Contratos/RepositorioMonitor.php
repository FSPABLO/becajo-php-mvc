<?php

declare(strict_types=1);

namespace App\Models\Contratos;

use App\Models\Entidades\Alerta;
use App\Models\Entidades\Episodio;
use App\Models\Entidades\Instancia;
use App\Models\Entidades\Medicion;
use App\Models\Entidades\Metrica;
use App\Models\Entidades\Muestra;

/**
 * Contrato de lectura del monitor de salud.
 *
 * Lo usan dos consumidores con necesidades distintas y por eso los métodos van
 * en bloques: el sitio, que pinta lo ya calculado, y el motor de cálculo, que
 * necesita mirar hacia atrás para derivar tasas, sostener la histéresis y
 * comparar contra la línea base.
 *
 * Ninguno de estos métodos calcula nada. Las series y los resúmenes salen de
 * pkg_monitor, igual que los indicadores de la parte 1 salen de
 * pkg_indicadores: una cifra nueva en pantalla es un procedimiento nuevo, no un
 * SELECT en PHP.
 *
 * Fuente: documentacion/Parte II/contrato-repositorio-monitor.md §2.
 */
interface RepositorioMonitor
{
    // ── Instancias vigiladas ─────────────────────────────────────────────────

    /** @return list<Instancia> */
    public function instancias(): array;

    public function instancia(string $clave): ?Instancia;

    // ── Estado actual ────────────────────────────────────────────────────────

    /**
     * La última muestra evaluada de cada instancia, con su índice ya resuelto.
     *
     * Una sola llamada para la pantalla que las lista todas. La alternativa
     * —una consulta por instancia— crece con la cartera y es la forma habitual
     * de que un tablero se vuelva lento sin que nadie sepa por qué.
     *
     * @return list<Muestra>
     */
    public function ultimasMuestras(): array;

    public function ultimaMuestra(string $clave): ?Muestra;

    /**
     * El detalle de una muestra: una fila por métrica, con su valor crudo, su
     * normalizado y el estado con que se publicó.
     *
     * @return list<Medicion>
     */
    public function mediciones(int $idMuestra): array;

    // ── Series históricas ────────────────────────────────────────────────────

    /**
     * Evolución del ISBD entre dos instantes. Los tramos sin muestras NO vienen
     * en el resultado: rellenar huecos es cosa de quien dibuja, porque solo la
     * vista sabe si corta la línea o une los extremos.
     *
     * Misma decisión que evolucionAuditor() en RepositorioAuditorias.
     *
     * @return list<array<string, mixed>>
     */
    public function serieIndice(string $clave, string $desdeUtc, string $hastaUtc): array;

    /**
     * Evolución de una métrica. Por encima de la retención en crudo (30 días)
     * la implementación responde desde resumen_hora, no desde medicion.
     *
     * @return list<array<string, mixed>>
     */
    public function serieMetrica(
        string $clave,
        string $codigoMetrica,
        string $desdeUtc,
        string $hastaUtc,
    ): array;

    // ── Alertas y episodios ──────────────────────────────────────────────────

    /**
     * Alertas abiertas, de una instancia o de todas.
     *
     * @return list<Alerta>
     */
    public function alertasAbiertas(?string $clave = null): array;

    /**
     * Episodios: alertas concurrentes agrupadas por causa común (§6.1 del plan).
     *
     * @return list<Episodio>
     */
    public function episodios(string $clave, string $desdeUtc, string $hastaUtc): array;

    // ── Catálogo ─────────────────────────────────────────────────────────────

    /**
     * El catálogo de métricas con los umbrales VIGENTES ahora.
     *
     * Para explicar una medición histórica no sirve: hay que usar el umbral_id
     * que esa medición guardó, porque los umbrales se versionan (§7.2).
     *
     * @return list<Metrica>
     */
    public function metricas(): array;

    // ── Lo que necesita el motor de cálculo ──────────────────────────────────

    /**
     * Los valores acumulados de la muestra anterior, por código de métrica.
     *
     * Es lo que permite derivar las tasas: v$system_event entrega totales desde
     * el arranque de la instancia y la tasa exige dos lecturas. Devuelve un
     * arreglo vacío si no hay muestra previa, y en ese caso las métricas de tasa
     * salen del denominador — no son un error.
     *
     * @return array<string, float>
     */
    public function acumuladosAnteriores(string $clave): array;

    /**
     * La huella de identidad de la muestra anterior, por código de métrica —
     * el equivalente de acumuladosAnteriores() para métricas que comparan una
     * identidad (`spid`) en vez de una magnitud. La usa M-PRO-05 para detectar
     * un reinicio de proceso de fondo. Arreglo vacío si no hay muestra previa,
     * y en ese caso la métrica sale del denominador — no es un error
     * (B-7, Anexo A del plan).
     *
     * @return array<string, string>
     */
    public function huellasAnteriores(string $clave): array;

    /**
     * Los últimos estados publicados de una métrica, del más reciente al más
     * antiguo. Alimenta la histéresis: k de las últimas n muestras (§5.5).
     *
     * @return list<string>
     */
    public function estadosRecientes(string $clave, string $codigoMetrica, int $cuantas): array;

    /**
     * Los valores normalizados de una métrica en el mismo tramo horario de los
     * últimos N días, para la línea base del §5.5 — el paso 6 de la guía.
     *
     * @return list<float>
     */
    public function ventanaLineaBase(
        string $clave,
        string $codigoMetrica,
        int $dias,
        int $tramoHora,
    ): array;
}
