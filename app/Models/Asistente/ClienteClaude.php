<?php

declare(strict_types=1);

namespace App\Models\Asistente;

/**
 * Cliente HTTP de la API de mensajes de Anthropic, escrito a mano con curl.
 *
 * SIN SDK POR DECISIÓN DEL PROYECTO: el autoloader es propio y Composer solo
 * entra para las pruebas del motor de cálculo. El SDK oficial de PHP daría los
 * reintentos y los errores tipados hechos; aquí se escriben, y por eso esta
 * clase es el ÚNICO sitio que habla con api.anthropic.com — igual que
 * BaseDatos es el único que llama funciones oci_*.
 *
 * Qué resuelve y qué no:
 *
 *   - Reintenta lo que se arregla esperando: 429 (límite de la API), 529
 *     (saturada), 5xx y los fallos de CONEXIÓN. Respeta `retry-after` si
 *     viene, con un tope para no dejar al usuario mirando el panel.
 *   - NO reintenta un tiempo límite agotado: la petición pudo haberse
 *     procesado y cobrado, y repetirla duplicaría el gasto y la espera.
 *   - NO reintenta un 4xx: la misma petición fallaría igual.
 *
 * La petición lleva siempre `fallbacks: "default"`: si los filtros de
 * seguridad de Claude Opus 5 rechazan una pregunta —preguntar por privilegios
 * o vulnerabilidades de una base de datos puede caer en la categoría «cyber»—,
 * la API la reintenta ella misma con el modelo que recomienda para esa
 * categoría, en vez de devolver el rechazo.
 */
final class ClienteClaude
{
    private const URL = 'https://api.anthropic.com/v1/messages';
    private const VERSION = '2023-06-01';

    /** El modo "default" de fallbacks va con esta cabecera, y solo con esta. */
    private const BETAS = 'server-side-fallback-2026-07-01';

    /** 408 y 409 los reintenta también el SDK oficial; se copia su criterio. */
    private const REINTENTABLES = [408, 409, 429, 500, 502, 503, 504, 529];

    /** Espera máxima entre reintentos, en segundos, diga lo que diga retry-after. */
    private const ESPERA_MAXIMA = 8;

    public function __construct(
        private readonly string $clave,
        private readonly string $modelo,
        private readonly string $esfuerzo,
        private readonly int $maxTokens,
        private readonly int $tiempoLimite = 45,
        private readonly int $reintentos = 2,
    ) {
    }

    public function modelo(): string
    {
        return $this->modelo;
    }

    /**
     * Envía una petición a /v1/messages y devuelve la respuesta decodificada.
     *
     * $sistema y $herramientas tienen que llegar IDÉNTICOS byte a byte en cada
     * llamada de un mismo rol: son el prefijo que se cachea, y cualquier
     * diferencia —una fecha, un orden distinto— lo invalida en silencio.
     *
     * @param list<array<string, mixed>> $sistema      Bloques de texto del prompt de sistema.
     * @param list<array<string, mixed>> $herramientas Definiciones de herramientas.
     * @param list<array<string, mixed>> $mensajes     La conversación.
     * @return array<string, mixed>
     *
     * @throws ErrorAsistente
     */
    public function mensaje(array $sistema, array $herramientas, array $mensajes): array
    {
        $cuerpo = json_encode([
            'model'         => $this->modelo,
            'max_tokens'    => $this->maxTokens,
            'system'        => $sistema,
            'tools'         => $herramientas,
            'messages'      => $mensajes,
            'output_config' => ['effort' => $this->esfuerzo],
            'fallbacks'     => 'default',
            /*
             * Caché automática: marca el último bloque de la conversación, así
             * que la vuelta siguiente —con los resultados de las herramientas,
             * o la pregunta siguiente— relee del caché todo lo anterior. El
             * prefijo fijo (herramientas + sistema) lleva además su propia marca
             * en Lembas, para no depender de que la conversación coincida.
             */
            'cache_control' => ['type' => 'ephemeral'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $intento = 0;

        while (true) {
            $respuesta = $this->enviar($cuerpo);

            if ($respuesta['errorRed'] === null && $respuesta['estado'] === 200) {
                return $this->decodificar($respuesta['cuerpo']);
            }

            $reintentable = $respuesta['errorRed'] !== null
                ? $respuesta['reintentarRed']
                : in_array($respuesta['estado'], self::REINTENTABLES, true);

            if (!$reintentable || $intento >= $this->reintentos) {
                throw $this->errorDe($respuesta);
            }

            $intento++;

            /*
             * Espera exponencial con algo de azar (1 s, 2 s…), salvo que la API
             * diga cuánto esperar. El azar evita que dos pestañas que chocaron
             * contra el mismo límite vuelvan a chocar en el mismo instante.
             */
            $segundos = $respuesta['reintentarEn'] ?? (2 ** ($intento - 1)) + (mt_rand(0, 500) / 1000);
            usleep((int) (min((float) $segundos, self::ESPERA_MAXIMA) * 1_000_000));
        }
    }

    /**
     * Una sola llamada HTTP, sin reintentos.
     *
     * @return array{estado: int, cuerpo: string, errorRed: string|null, reintentarRed: bool, reintentarEn: float|null}
     */
    private function enviar(string $cuerpo): array
    {
        $reintentarEn = null;

        $ch = curl_init(self::URL);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $this->tiempoLimite,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->clave,
                'anthropic-version: ' . self::VERSION,
                'anthropic-beta: ' . self::BETAS,
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS     => $cuerpo,
            // Solo interesa retry-after; el resto de cabeceras se ignora.
            CURLOPT_HEADERFUNCTION => static function ($ch, string $linea) use (&$reintentarEn): int {
                if (stripos($linea, 'retry-after:') === 0) {
                    $valor = trim(substr($linea, strlen('retry-after:')));

                    if (is_numeric($valor)) {
                        $reintentarEn = (float) $valor;
                    }
                }

                return strlen($linea);
            },
        ]);

        $respuesta = curl_exec($ch);
        $numeroError = curl_errno($ch);
        $estado = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $mensajeError = curl_error($ch);
        curl_close($ch);

        if ($numeroError !== 0 || !is_string($respuesta)) {
            return [
                'estado'        => 0,
                'cuerpo'        => '',
                'errorRed'      => sprintf('curl %d: %s', $numeroError, $mensajeError),
                // 28 es CURLE_OPERATION_TIMEDOUT: ver la cabecera de la clase.
                'reintentarRed' => $numeroError !== 28,
                'reintentarEn'  => null,
            ];
        }

        return [
            'estado'        => $estado,
            'cuerpo'        => $respuesta,
            'errorRed'      => null,
            'reintentarRed' => false,
            'reintentarEn'  => $reintentarEn,
        ];
    }

    /** @return array<string, mixed> */
    private function decodificar(string $cuerpo): array
    {
        try {
            $datos = json_decode($cuerpo, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ErrorAsistente(ErrorAsistente::PETICION, 'Respuesta de la API que no es JSON: ' . $e->getMessage(), 200);
        }

        if (!is_array($datos) || !isset($datos['content']) || !is_array($datos['content'])) {
            throw new ErrorAsistente(ErrorAsistente::PETICION, 'Respuesta de la API sin "content".', 200);
        }

        /*
         * El contenido se decodifica OTRA VEZ como objetos, para devolverlo a la
         * API tal cual en la vuelta siguiente. Con arreglos asociativos un
         * objeto vacío —el `input: {}` de una herramienta sin parámetros— vuelve
         * a codificarse como `[]`, y la API rechaza la petición. Se lee con los
         * arreglos y se reenvía con los objetos, índice por índice.
         */
        $objetos = json_decode($cuerpo, false);
        $datos['contenido_original'] = is_object($objetos) && is_array($objetos->content ?? null)
            ? $objetos->content
            : [];

        return $datos;
    }

    /** @param array{estado: int, cuerpo: string, errorRed: string|null} $respuesta */
    private function errorDe(array $respuesta): ErrorAsistente
    {
        if ($respuesta['errorRed'] !== null) {
            return new ErrorAsistente(ErrorAsistente::RED, $respuesta['errorRed']);
        }

        $estado = $respuesta['estado'];
        $datos = json_decode($respuesta['cuerpo'], true);
        $tipoApi = is_array($datos) ? (string) ($datos['error']['type'] ?? '') : '';
        $mensajeApi = is_array($datos) ? (string) ($datos['error']['message'] ?? '') : '';
        $detalle = sprintf('HTTP %d %s: %s', $estado, $tipoApi, mb_substr($mensajeApi, 0, 300));

        $tipo = match (true) {
            $estado === 401, $estado === 403                   => ErrorAsistente::CREDENCIAL,
            // El saldo agotado llega como un 400 corriente; solo el texto lo distingue.
            $estado === 400 && stripos($mensajeApi, 'credit balance') !== false,
            stripos($mensajeApi, 'spend limit') !== false,
            stripos($mensajeApi, 'usage limit') !== false      => ErrorAsistente::SALDO,
            in_array($estado, self::REINTENTABLES, true)       => ErrorAsistente::SATURADO,
            default                                             => ErrorAsistente::PETICION,
        };

        return new ErrorAsistente($tipo, $detalle, $estado);
    }
}
