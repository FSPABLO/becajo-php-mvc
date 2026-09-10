<?php

declare(strict_types=1);

namespace App\Models\Entidades;

/**
 * Una auditoría: la evaluación completa de una organización en una fecha.
 *
 * El estado es lo que permite el "guardar y continuar después" que pide el
 * enunciado: una auditoría EN_PROGRESO puede retomarse tantas veces como haga
 * falta, y solo al pasar a FINALIZADA se congelan sus resultados.
 *
 * Los nombres del auditor, del administrador de BD y de la organización llegan
 * resueltos desde la consulta (un JOIN con usuario y otro con la vista
 * v_auditoria_entrevistado) y no como una segunda consulta por fila: la lista
 * "Mis auditorías" los necesita todos y pedirlos de a uno sería el problema N+1
 * de manual.
 *
 * $idAdministradorBd es NULO cuando al entrevistado se le escribió a mano: esa
 * persona no tiene cuenta en el sistema. Su nombre y su empresa llegan igual en
 * $nombreAdministradorBd y $organizacion —la vista resuelve de cuál de los dos
 * orígenes salen—, así que quien solo quiera MOSTRARLOS no tiene que saber nada
 * de esto. El id es para enlazar a la cuenta, y por eso puede faltar.
 */
final class Auditoria
{
    public const EN_PROGRESO = 'EN_PROGRESO';
    public const FINALIZADA = 'FINALIZADA';

    public function __construct(
        public readonly int $id,
        public readonly int $idAuditor,
        public readonly ?int $idAdministradorBd,
        public readonly string $areaEvaluada,
        public readonly string $fecha,
        public readonly string $estado,
        public readonly ?float $indiceGeneralRiesgo,
        public readonly string $nombreAuditor = '',
        public readonly string $nombreAdministradorBd = '',
        public readonly string $organizacion = '',
        public readonly ?string $fechaFinalizacion = null,
    ) {
    }

    /** @param array<string, mixed> $fila */
    public static function desdeFila(array $fila): self
    {
        $indice = $fila['indice_general_riesgo'] ?? null;

        return new self(
            id:                    (int) ($fila['id_auditoria'] ?? 0),
            idAuditor:             (int) ($fila['id_auditor'] ?? 0),
            // Sin cast a int: 0 no es «ninguna cuenta», es la cuenta 0, y
            // la vista ya distingue el entrevistado escrito a mano.
            idAdministradorBd:     isset($fila['id_administrador_bd'])
                ? (int) $fila['id_administrador_bd']
                : null,
            areaEvaluada:          (string) ($fila['area_evaluada'] ?? ''),
            fecha:                 (string) ($fila['fecha'] ?? ''),
            estado:                (string) ($fila['estado'] ?? self::EN_PROGRESO),
            // Null no es cero: una auditoría sin calcular todavía no tiene
            // índice, y mostrar 0.00 la haría parecer de riesgo máximo.
            indiceGeneralRiesgo:   $indice === null ? null : (float) $indice,
            nombreAuditor:         (string) ($fila['nombre_auditor'] ?? ''),
            nombreAdministradorBd: (string) ($fila['nombre_administrador_bd'] ?? ''),
            organizacion:          (string) ($fila['organizacion'] ?? ''),
            fechaFinalizacion:     isset($fila['fecha_finalizacion'])
                ? (string) $fila['fecha_finalizacion']
                : null,
        );
    }

    public function estaFinalizada(): bool
    {
        return $this->estado === self::FINALIZADA;
    }

    /** ¿Al entrevistado se le escribió a mano, sin cuenta en el sistema? */
    public function entrevistadoEscritoAMano(): bool
    {
        return $this->idAdministradorBd === null;
    }
}
