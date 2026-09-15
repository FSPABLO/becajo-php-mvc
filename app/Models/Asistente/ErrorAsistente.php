<?php

declare(strict_types=1);

namespace App\Models\Asistente;

/**
 * Algo salió mal al hablar con la API de Anthropic.
 *
 * El TIPO es lo único que decide qué ve el usuario; el mensaje de la excepción
 * es el detalle técnico, y ese va al registro de errores y nunca a la pantalla.
 * Separarlos no es cosmética: el cuerpo de un error de la API puede traer texto
 * que no le dice nada a un auditor («invalid_request_error: messages.3...») y
 * que tampoco hace falta que vea.
 *
 * Ningún mensaje que se construya aquí puede llevar la clave: el cliente arma
 * el detalle con el estado HTTP y el cuerpo de la RESPUESTA, que la API
 * devuelve sin repetir la credencial.
 */
final class ErrorAsistente extends \RuntimeException
{
    /** No se pudo conectar, o la API tardó más que el tiempo límite. */
    public const RED = 'red';

    /** 429 / 529 / 5xx tras agotar los reintentos: la API está saturada. */
    public const SATURADO = 'saturado';

    /** 401 / 403: la clave falta, está mal escrita o fue revocada. */
    public const CREDENCIAL = 'credencial';

    /** La cuenta se quedó sin saldo o tocó su tope de gasto. */
    public const SALDO = 'saldo';

    /** Cualquier otro 4xx: la petición está mal formada. Es un fallo nuestro. */
    public const PETICION = 'peticion';

    public function __construct(
        public readonly string $tipo,
        string $detalle,
        public readonly int $estadoHttp = 0,
    ) {
        parent::__construct($detalle);
    }
}
