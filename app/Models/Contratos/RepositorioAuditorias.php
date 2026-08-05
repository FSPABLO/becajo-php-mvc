<?php

declare(strict_types=1);

namespace App\Models\Contratos;

use App\Models\Entidades\Auditoria;
use App\Models\Entidades\EvaluacionControl;
use App\Models\Entidades\ResultadoRiesgo;
use App\Models\Entidades\Usuario;

/**
 * Contrato de la fuente de auditorías, usuarios e indicadores.
 *
 * Misma idea que RepositorioContenido y RepositorioInstrumento: los
 * controladores dependen de esta interfaz y no de Oracle. La diferencia es que
 * aquí no habrá una implementación "de arreglo" equivalente — una auditoría
 * tiene que persistir entre sesiones, y eso solo lo da una base de datos.
 *
 * Los métodos están agrupados en cuatro bloques según a qué sirven:
 * autenticación, auditorías, evaluación de controles e indicadores.
 */
interface RepositorioAuditorias
{
    // ── Usuarios y autenticación ─────────────────────────────────────────────

    /**
     * Verifica unas credenciales y devuelve el usuario, o null si no calzan.
     *
     * La comparación del hash ocurre dentro de la implementación: el hash
     * nunca sale del repositorio. Un usuario con activo = 0 se trata como
     * credenciales incorrectas, sin decir cuál de las dos cosas falló.
     */
    public function autenticar(string $correo, string $clave): ?Usuario;

    public function usuario(int $id): ?Usuario;

    /** @return list<Usuario> */
    public function usuariosPorRol(string $rol): array;

    /**
     * ¿Hay ya una cuenta con ese correo? Sin distinguir mayúsculas.
     *
     * Sirve para dar un mensaje claro en el formulario de registro. No
     * sustituye a la restricción uq_usuario_correo: entre esta comprobación y
     * el INSERT cabe otra petición con el mismo correo.
     */
    public function correoRegistrado(string $correo): bool;

    /**
     * Crea una cuenta y devuelve su identificador.
     *
     * Recibe el hash ya calculado, no la contraseña: el repositorio guarda lo
     * que le dan y nunca ve una contraseña en claro.
     */
    public function registrarUsuario(
        string $nombre,
        string $correo,
        string $hash,
        string $rol,
        string $organizacion,
    ): int;

    // ── Auditorías ───────────────────────────────────────────────────────────

    /**
     * Las auditorías de un auditor, de la más reciente a la más antigua.
     *
     * @return list<Auditoria>
     */
    public function auditoriasDe(int $idAuditor): array;

    public function auditoria(int $id): ?Auditoria;

    /** Crea una auditoría EN_PROGRESO y devuelve su identificador. */
    public function crearAuditoria(
        int $idAuditor,
        int $idAdministradorBd,
        string $areaEvaluada,
        string $fecha,
    ): int;

    public function actualizarAuditoria(
        int $id,
        int $idAdministradorBd,
        string $areaEvaluada,
        string $fecha,
    ): void;

    /** Marca la auditoría como FINALIZADA y sella la fecha de cierre. */
    public function finalizarAuditoria(int $id): void;

    /** Devuelve una auditoría finalizada al estado EN_PROGRESO. */
    public function reabrirAuditoria(int $id): void;

    // ── Evaluación de controles ──────────────────────────────────────────────

    /**
     * Todas las evaluaciones registradas en una auditoría.
     *
     * @return list<EvaluacionControl>
     */
    public function evaluaciones(int $idAuditoria): array;

    public function evaluacion(int $idAuditoria, string $codigoControl): ?EvaluacionControl;

    /**
     * Guarda la evaluación de un control, creándola o actualizándola.
     *
     * Una sola operación para ambos casos porque el auditor no distingue entre
     * "responder por primera vez" y "corregir lo que ya respondí": en la
     * pantalla es el mismo botón de guardar.
     */
    public function guardarEvaluacion(EvaluacionControl $evaluacion): void;

    public function eliminarEvaluacion(int $idAuditoria, string $codigoControl): void;

    /** Cuántos controles llevan estado asignado. Alimenta la barra de avance. */
    public function controlesEvaluados(int $idAuditoria): int;

    // ── Indicadores (pkg_indicadores) ────────────────────────────────────────

    /**
     * Recalcula y persiste la exposición al riesgo y el índice general.
     *
     * Se invoca al guardar avances y al finalizar. Es idempotente: el
     * procedimiento borra los resultados anteriores antes de insertar.
     */
    public function recalcularRiesgo(int $idAuditoria): void;

    /**
     * Cumplimiento global y madurez promedio de la auditoría completa.
     *
     * @return array<string, mixed>
     */
    public function resumen(int $idAuditoria): array;

    /**
     * Cumplimiento y madurez agrupados por dominio.
     *
     * @return list<array<string, mixed>>
     */
    public function cumplimientoPorDominio(int $idAuditoria): array;

    /**
     * Los controles peor calificados.
     *
     * @return list<array<string, mixed>>
     */
    public function menorMadurez(int $idAuditoria, int $cuantos = 5): array;

    /**
     * Los controles con mayor exposición al riesgo.
     *
     * @return list<array<string, mixed>>
     */
    public function mayorRiesgo(int $idAuditoria, int $cuantos = 5): array;

    /**
     * La exposición ya calculada para Confidencialidad, Integridad y
     * Disponibilidad.
     *
     * @return list<ResultadoRiesgo>
     */
    public function exposicionRiesgo(int $idAuditoria): array;
}
