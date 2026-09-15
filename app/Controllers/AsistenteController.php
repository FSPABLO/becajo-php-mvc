<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;
use App\Models\Asistente\ErrorAsistente;
use App\Models\Asistente\Lembas;
use App\Models\Entidades\Usuario;

/**
 * Lembas, el asistente: recibe la pregunta del panel y devuelve la respuesta
 * YA DIBUJADA.
 *
 * Solo responde a su guion (cabecera X-Becajo-Asincrona) y siempre en JSON con
 * `html`, también cuando algo falla: el globo de error lo arma el servidor igual
 * que el de una respuesta, y el guion solo lo inserta. Es el reparto de
 * guardarControl(): las reglas de cómo se ve algo viven en PHP, no copiadas en
 * JavaScript.
 *
 * El controlador se ocupa de la PUERTA —sesión, token, límites, sesión de PHP,
 * registro— y le deja a Lembas todo lo que decide qué viaja a la API. Esa
 * separación es la que permite auditar la regla de privacidad leyendo una sola
 * clase.
 */
final class AsistenteController extends Controlador
{
    /** Igual que el maxlength del campo: el navegador no es quien valida. */
    private const MAX_PREGUNTA = 2000;

    /** Turnos que se conservan: los que se reenvían al modelo y los que se repintan. */
    private const TURNOS_GUARDADOS = 6;

    private const CLAVE_HISTORIAL = 'lembas.historial';
    private const CLAVE_TRANSCRIPCION = 'lembas.transcripcion';

    public function consultar(): void
    {
        $usuario = $this->puerta();

        $pregunta = (string) ($this->peticion()->entrada('mensaje') ?? '');

        if ($pregunta === '') {
            $this->responderError('asistente.error_vacia', 422);
        }

        /*
         * Un navegador manda UTF-8, pero lo que llega es texto del cliente. Bytes
         * mal codificados harían fallar la codificación JSON de la petición a la
         * API mucho más adentro, y el usuario leería «error interno» por algo que
         * se puede decir aquí.
         */
        if (!mb_check_encoding($pregunta, 'UTF-8')) {
            $this->responderError('asistente.error_codificacion', 422);
        }

        if (mb_strlen($pregunta) > self::MAX_PREGUNTA) {
            $this->responderError('asistente.error_larga', 422);
        }

        /*
         * Los límites diarios, ANTES de hablar con la API. Son la segunda línea:
         * el tope de gasto de verdad está en la consola de Anthropic.
         */
        $limites = $this->contenedor->limitesAsistente();

        if ($this->auditorias()->consultasAsistenteHoy($usuario->id) >= $limites['usuario']) {
            $this->responderError('asistente.error_limite_usuario', 429, (string) $limites['usuario']);
        }

        if ($this->auditorias()->consultasAsistenteHoy() >= $limites['total']) {
            $this->responderError('asistente.error_limite_total', 429);
        }

        $ruta = (string) ($this->peticion()->entrada('ruta') ?? '/');
        $historial = $this->historialValido($this->sesion()->obtener(self::CLAVE_HISTORIAL, []));

        /*
         * Lo que viene puede tardar decenas de segundos. Se suelta la sesión
         * para que el auditor pueda seguir navegando en otra pestaña mientras
         * tanto, y se amplía el tiempo de ejecución, que en PHP es de 30 s: tres
         * vueltas a la API con reintentos lo pasarían.
         */
        $this->sesion()->liberar();
        set_time_limit(180);

        try {
            $respuesta = $this->contenedor->asistente()->responder($usuario, $pregunta, $ruta, $historial);
        } catch (ErrorAsistente $e) {
            // El detalle técnico va al registro; a la pantalla, solo el tipo.
            error_log(sprintf('Lembas [%s] usuario %d: %s', $e->tipo, $usuario->id, $e->getMessage()));

            $this->auditorias()->registrarConsultaAsistente($usuario->id, $ruta, [], 0, 0, 'ERROR');
            $this->responderError('asistente.error_' . $e->tipo, $e->tipo === ErrorAsistente::PETICION ? 500 : 502);
        } catch (\Throwable $e) {
            error_log(sprintf('Lembas [interno] usuario %d: %s', $usuario->id, $e->getMessage()));
            $this->responderError('asistente.error_interno', 500);
        }

        $this->auditorias()->registrarConsultaAsistente(
            $usuario->id,
            $respuesta['pantalla'],
            $respuesta['herramientas'],
            $respuesta['tokensEntrada'],
            $respuesta['tokensSalida'],
            $respuesta['resultado'],
        );

        $html = $this->turno($respuesta['texto'], $respuesta['fichas']);

        /*
         * Se relee la sesión antes de escribir: mientras esperaba a la API otra
         * pestaña pudo haber preguntado también, y escribir la copia vieja
         * borraría ese turno.
         */
        $this->guardarTurno($respuesta['historial'], $pregunta, $html);

        $this->json(['ok' => $respuesta['resultado'] === Lembas::RESPONDIDA, 'html' => $html]);
    }

    /** «Nueva conversación»: olvida el historial y la transcripción. */
    public function olvidar(): void
    {
        $this->puerta(exigirServicio: false);

        $this->sesion()->olvidar(self::CLAVE_HISTORIAL);
        $this->sesion()->olvidar(self::CLAVE_TRANSCRIPCION);

        $this->json(['ok' => true]);
    }

    // ── Apoyo ────────────────────────────────────────────────────────────────

    /**
     * Las comprobaciones de entrada de las dos acciones.
     *
     * No usa exigirUsuario() ni exigirToken(): esos REDIRIGEN, y un fetch que
     * sigue una redirección recibe la página de ingreso en vez de JSON. Aquí
     * la misma regla responde con un globo que lo explica.
     */
    private function puerta(bool $exigirServicio = true): Usuario
    {
        header('Cache-Control: no-store');

        if (!$this->peticion()->esAsincrona()) {
            $this->redirigir('/evaluacion');
        }

        $usuario = $this->contenedor->hayAuditorias() ? $this->autenticacion()->usuario() : null;

        if ($usuario === null) {
            $this->responderError('asistente.error_sesion', 401);
        }

        if (!$this->autenticacion()->tokenValido($this->peticion()->entrada('_token'))) {
            $this->responderError('asistente.error_sesion', 419);
        }

        if ($exigirServicio && !$this->contenedor->hayAsistente()) {
            $this->responderError('asistente.error_apagado', 503);
        }

        return $usuario;
    }

    /**
     * Un turno de Lembas dibujado: texto y fichas.
     *
     * @param list<array{tipo: string, datos: array<string, mixed>}> $fichas
     */
    private function turno(string $texto, array $fichas): string
    {
        return $this->contenedor->vista()->renderizar('partials/panel/asistente/turno', [
            'texto'  => $texto,
            'fichas' => $fichas,
        ]);
    }

    private function responderError(string $clave, int $codigo, string ...$argumentos): never
    {
        $this->json([
            'ok'   => false,
            'html' => $this->turno('', [['tipo' => 'aviso', 'datos' => ['clave' => $clave, 'args' => $argumentos, 'error' => true]]]),
        ], $codigo);
    }

    /**
     * Añade el turno a la sesión y recorta lo viejo.
     *
     * Dos listas con dos usos: el HISTORIAL es lo que se reenvía al modelo
     * (texto tal como se envió), y la TRANSCRIPCIÓN es lo que se repinta en el
     * panel al cambiar de pantalla (la pregunta original y el HTML de la
     * respuesta). No son la misma cosa: la pregunta que vio el modelo lleva la
     * pantalla delante y los nombres de empresa sustituidos.
     *
     * Se recorta por el PRINCIPIO, de turno en turno completo: quitar un mensaje
     * suelto dejaría una conversación que empieza por la respuesta.
     *
     * @param list<array{role: string, content: string}> $turno
     */
    private function guardarTurno(array $turno, string $pregunta, string $html): void
    {
        $historial = $this->historialValido($this->sesion()->obtener(self::CLAVE_HISTORIAL, []));
        $historial = array_slice([...$historial, ...$turno], -2 * self::TURNOS_GUARDADOS);

        $transcripcion = $this->sesion()->obtener(self::CLAVE_TRANSCRIPCION, []);
        $transcripcion = is_array($transcripcion) ? $transcripcion : [];
        $transcripcion[] = ['pregunta' => $pregunta, 'html' => $html];
        $transcripcion = array_slice(array_values($transcripcion), -self::TURNOS_GUARDADOS);

        $this->sesion()->poner(self::CLAVE_HISTORIAL, $historial);
        $this->sesion()->poner(self::CLAVE_TRANSCRIPCION, $transcripcion);
    }

    /**
     * El historial de la sesión, solo si tiene la forma que espera la API:
     * pares usuario/asistente de texto, empezando por el usuario.
     *
     * @return list<array{role: string, content: string}>
     */
    private function historialValido(mixed $historial): array
    {
        if (!is_array($historial)) {
            return [];
        }

        $valido = [];

        foreach (array_values($historial) as $i => $mensaje) {
            $rolEsperado = $i % 2 === 0 ? 'user' : 'assistant';

            if (!is_array($mensaje) || ($mensaje['role'] ?? null) !== $rolEsperado || !is_string($mensaje['content'] ?? null)) {
                return [];
            }

            $valido[] = ['role' => $rolEsperado, 'content' => $mensaje['content']];
        }

        // Un número impar de mensajes terminaría en una pregunta sin respuesta.
        return count($valido) % 2 === 0 ? $valido : [];
    }
}
