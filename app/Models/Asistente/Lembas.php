<?php

declare(strict_types=1);

namespace App\Models\Asistente;

use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Auditoria;
use App\Models\Entidades\Control;
use App\Models\Entidades\Usuario;

/**
 * Lembas, el asistente del módulo: la FRONTERA entre los datos y el modelo.
 *
 * REGLA DEL PROYECTO: ningún dato de auditoría sale hacia Anthropic. Todo lo que
 * viaja a la API se arma en esta clase —el prompt de sistema, las herramientas,
 * la descripción de la pantalla y la pregunta—, así que para comprobar que la
 * regla se cumple hay que leer UN archivo.
 *
 * Por eso Lembas es un ENRUTADOR para las auditorías, no un redactor:
 *
 *   - Herramientas PRIVADAS (resumen, riesgo, abrir un control…): el modelo
 *     elige cuál y con qué número; PHP la ejecuta con la comprobación de
 *     propiedad de siempre y el resultado se pinta como una ficha en el panel.
 *     Al modelo le vuelve solo «se mostró en pantalla», nunca el contenido.
 *   - Herramientas PÚBLICAS (el catálogo de 75 controles, que ya es público en
 *     /herramientas/instrumento-bd): el resultado sí vuelve al modelo, que
 *     redacta la explicación.
 *
 * Qué SÍ viaja, y por qué no es dato de auditoría: el texto de la pregunta con
 * los nombres de las empresas del usuario sustituidos por [empresa]; el tipo de
 * pantalla y el NÚMERO de la auditoría abierta, solo si es suya; los códigos de
 * control. Qué NO puede impedir esta clase: que alguien escriba un hallazgo en
 * el chat. El panel pide que no se haga y el prompt ordena no repetirlo.
 *
 * Ni el prompt de sistema ni la lista de herramientas llevan nada variable —ni
 * fecha, ni nombre, ni pantalla—: son el prefijo que se cachea, y son idénticos
 * para todos los usuarios de un mismo rol. Lo que cambia viaja en el mensaje.
 */
final class Lembas
{
    /** Llamadas a la API por pregunta, como mucho: catálogo + respuesta + margen. */
    private const MAX_VUELTAS = 3;

    /** Controles que devuelve una búsqueda en el catálogo. */
    private const MAX_CONTROLES = 5;

    /** Filas de una ficha de lista: el panel es una columna, no una tabla. */
    private const MAX_FILAS = 8;

    public const RESPONDIDA = 'RESPONDIDA';
    public const RECHAZADA = 'RECHAZADA';

    /**
     * Palabras que no sirven para buscar en el catálogo: aparecen en casi
     * cualquier pregunta y harían puntuar a los 75 controles por igual.
     */
    private const PALABRAS_VACIAS = [
        'que', 'qué', 'cual', 'cuales', 'como', 'para', 'por', 'con', 'sin', 'sobre',
        'del', 'las', 'los', 'una', 'uno', 'unos', 'unas', 'este', 'esta', 'estos',
        'control', 'controles', 'pide', 'piden', 'evidencia', 'explica', 'explicame',
        'the', 'and', 'what', 'which', 'does', 'about', 'for', 'with',
    ];

    /**
     * Las herramientas. `privada` decide si el resultado vuelve al modelo;
     * `admin` si solo se ofrecen al rol ADMIN_BD. El orden es fijo: es parte
     * del prefijo cacheado.
     *
     * Las descripciones dicen CUÁNDO usarlas y no solo qué hacen, y repiten
     * «tú no ves el resultado» en cada privada: es lo que evita que el modelo
     * prometa un análisis que no puede hacer.
     */
    private const HERRAMIENTAS = [
        'listar_mis_auditorias' => [
            'privada'     => true,
            'admin'       => false,
            'descripcion' => 'Muestra en pantalla la lista de auditorías que condujo el usuario: número, fecha, área evaluada y estado. Úsala cuando pida ver sus auditorías, cuando tenga que elegir una, o cuando haga falta el número de una auditoría y la pantalla actual no lo indique. Tú no ves el resultado.',
            'propiedades' => [],
        ],
        'mostrar_resumen_auditoria' => [
            'privada'     => true,
            'admin'       => false,
            'descripcion' => 'Muestra en pantalla el resumen de una auditoría del usuario: estado, cumplimiento general, madurez promedio, índice general de riesgo y cumplimiento por dominio. Úsala cuando pida el resumen, el estado, el avance o los resultados de una auditoría. Tú no ves el resultado.',
            'propiedades' => [
                'id_auditoria' => ['type' => 'integer', 'description' => 'Número de la auditoría.'],
            ],
        ],
        'mostrar_controles_mayor_riesgo' => [
            'privada'     => true,
            'admin'       => false,
            'descripcion' => 'Muestra en pantalla los controles con mayor nivel de riesgo de una auditoría del usuario, con su impacto, probabilidad y dimensiones afectadas. Úsala cuando pregunte por los riesgos, los hallazgos más graves o qué atender primero. Tú no ves el resultado.',
            'propiedades' => [
                'id_auditoria' => ['type' => 'integer', 'description' => 'Número de la auditoría.'],
            ],
        ],
        'abrir_control_para_llenar' => [
            'privada'     => true,
            'admin'       => false,
            'descripcion' => 'Muestra en pantalla un acceso directo al formulario de un control dentro de una auditoría del usuario, junto con lo que pide ese control, para que el usuario lo responda. Úsala cuando quiera llenar, responder, evaluar o registrar un control. Las respuestas, hallazgos y evidencias los escribe el usuario en el formulario: tú no los registras ni los ves.',
            'propiedades' => [
                'id_auditoria'   => ['type' => 'integer', 'description' => 'Número de la auditoría.'],
                'codigo_control' => ['type' => 'string', 'description' => 'Código del control, por ejemplo C-014.'],
            ],
        ],
        'mostrar_remediaciones_vencidas' => [
            'privada'     => true,
            'admin'       => true,
            'descripcion' => 'Muestra en pantalla las remediaciones vencidas de todas las auditorías: control, fecha límite y responsable. Úsala cuando pregunte por remediaciones atrasadas, vencidas o pendientes de seguimiento. Tú no ves el resultado.',
            'propiedades' => [],
        ],
        'buscar_controles_catalogo' => [
            'privada'     => false,
            'admin'       => false,
            'descripcion' => 'Busca en el catálogo público de 75 controles del instrumento (basado en ISO/IEC 27002) y te devuelve hasta cinco controles con su código, referencia ISO, dominio, proceso, enunciado, pregunta de auditoría y evidencia esperada. Úsala SIEMPRE antes de explicar qué pide un control, qué evidencia se espera o qué control cubre un tema, y para averiguar el código de un control cuando el usuario lo describe con palabras. Acepta un código (C-014) o palabras clave (respaldos, privilegios, cifrado).',
            'propiedades' => [
                'consulta' => ['type' => 'string', 'description' => 'Código del control o palabras clave del tema.'],
            ],
        ],
    ];

    /** Qué recibe el modelo en lugar del contenido de una herramienta privada. */
    private const RESULTADO_PRIVADO = 'Resultado mostrado al usuario en pantalla. Tú no tienes acceso a su contenido: no lo describas, no lo resumas y no inventes cifras sobre él.';

    public function __construct(
        private readonly ClienteClaude $cliente,
        private readonly RepositorioAuditorias $auditorias,
        private readonly RepositorioInstrumento $instrumento,
    ) {
    }

    /**
     * Responde una pregunta.
     *
     * $historial son los turnos anteriores TAL COMO se enviaron —texto plano,
     * sin bloques de razonamiento—, para que el prefijo de la conversación sea
     * idéntico byte a byte y el caché acierte. Entre preguntas no se reenvía
     * nada más: ni resultados de herramientas ni razonamiento.
     *
     * @param list<array{role: string, content: string}> $historial
     * @return array{
     *     texto: string,
     *     fichas: list<array{tipo: string, datos: array<string, mixed>}>,
     *     herramientas: list<string>,
     *     tokensEntrada: int,
     *     tokensSalida: int,
     *     resultado: string,
     *     pantalla: string,
     *     historial: list<array{role: string, content: string}>
     * }
     *
     * @throws ErrorAsistente
     */
    public function responder(Usuario $usuario, string $pregunta, string $ruta, array $historial): array
    {
        $esAdministrador = $usuario->esAdministrador();
        $pantalla = $this->describirPantalla($ruta, $usuario);

        $mensajeUsuario = '[Pantalla actual: ' . $pantalla . ']' . "\n\n" . $this->protegerNombres($pregunta, $usuario);

        $sistema = [[
            'type'          => 'text',
            'text'          => $this->promptSistema($esAdministrador),
            'cache_control' => ['type' => 'ephemeral'],
        ]];
        $herramientas = $this->definicionesPara($esAdministrador);

        $mensajes = [...$historial, ['role' => 'user', 'content' => $mensajeUsuario]];

        $textos = [];
        $fichas = [];
        $marcas = [];
        $usadas = [];
        $tokensEntrada = 0;
        $tokensSalida = 0;
        $resultado = self::RESPONDIDA;

        for ($vuelta = 1; $vuelta <= self::MAX_VUELTAS; $vuelta++) {
            $respuesta = $this->cliente->mensaje($sistema, $herramientas, $mensajes);

            $consumo = is_array($respuesta['usage'] ?? null) ? $respuesta['usage'] : [];
            $tokensEntrada += (int) ($consumo['input_tokens'] ?? 0)
                + (int) ($consumo['cache_creation_input_tokens'] ?? 0)
                + (int) ($consumo['cache_read_input_tokens'] ?? 0);
            $tokensSalida += (int) ($consumo['output_tokens'] ?? 0);

            /*
             * Un rechazo que sobrevivió al respaldo automático (fallbacks). Se
             * comprueba ANTES de leer el contenido: puede no traer nada útil.
             */
            if (($respuesta['stop_reason'] ?? '') === 'refusal') {
                $resultado = self::RECHAZADA;
                $fichas[] = ['tipo' => 'aviso', 'datos' => ['clave' => 'asistente.aviso_rechazo', 'args' => []]];
                break;
            }

            [$bloques, $originales] = $this->bloquesVigentes($respuesta);

            $llamadas = [];

            foreach ($bloques as $bloque) {
                if (($bloque['type'] ?? '') === 'text' && trim((string) ($bloque['text'] ?? '')) !== '') {
                    $textos[] = trim((string) $bloque['text']);
                } elseif (($bloque['type'] ?? '') === 'tool_use') {
                    $llamadas[] = $bloque;
                }
            }

            if (($respuesta['stop_reason'] ?? '') === 'max_tokens') {
                $fichas[] = ['tipo' => 'aviso', 'datos' => ['clave' => 'asistente.aviso_cortada', 'args' => []]];
            }

            if ($llamadas === []) {
                break;
            }

            $resultados = [];
            $hayQueSeguir = false;

            foreach ($llamadas as $llamada) {
                $nombre = (string) ($llamada['name'] ?? '');
                $entrada = is_array($llamada['input'] ?? null) ? $llamada['input'] : [];
                $usadas[] = $nombre;

                $definicion = self::HERRAMIENTAS[$nombre] ?? null;

                /*
                 * La herramienta no existe o no es de este rol. No debería
                 * pasar —solo se le ofrecen las suyas—, pero el permiso se
                 * comprueba al EJECUTAR y no solo al ofrecer, igual que
                 * auditoriaPropia() no se fía de que el enlace venga del menú.
                 */
                if ($definicion === null || ($definicion['admin'] && !$esAdministrador)) {
                    $resultados[] = $this->resultadoHerramienta($llamada, 'Herramienta no disponible para este usuario.', true);
                    $hayQueSeguir = true;
                    continue;
                }

                if ($definicion['privada']) {
                    ['ficha' => $ficha, 'marca' => $marca] = $this->ejecutarPrivada($nombre, $entrada, $usuario);
                    $fichas[] = $ficha;
                    $marcas[] = $marca;
                    $resultados[] = $this->resultadoHerramienta($llamada, self::RESULTADO_PRIVADO);
                    continue;
                }

                $resultados[] = $this->resultadoHerramienta(
                    $llamada,
                    json_encode($this->buscarEnCatalogo((string) ($entrada['consulta'] ?? '')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                );
                $hayQueSeguir = true;
            }

            /*
             * Si todo lo pedido fueron herramientas privadas, la respuesta ya
             * está en las fichas y no se vuelve a llamar a la API: al modelo no
             * le queda nada que decir sobre datos que no ve, y otra vuelta solo
             * gastaría tokens —o, peor, lo invitaría a comentar lo que no vio—.
             */
            if (!$hayQueSeguir) {
                break;
            }

            if ($vuelta === self::MAX_VUELTAS) {
                $fichas[] = ['tipo' => 'aviso', 'datos' => ['clave' => 'asistente.aviso_incompleta', 'args' => []]];
                break;
            }

            $mensajes[] = ['role' => 'assistant', 'content' => $originales];
            $mensajes[] = ['role' => 'user', 'content' => $resultados];
        }

        $texto = implode("\n\n", $textos);

        /*
         * Lo que queda en el historial del turno: el texto del modelo y, por
         * cada ficha, una marca con el número y el tipo —nunca su contenido—,
         * para que «¿y la 153?» se entienda en la pregunta siguiente.
         */
        $recuerdo = trim($texto . ($marcas === [] ? '' : "\n\n[Se mostró en pantalla: " . implode('; ', $marcas) . ']'));

        return [
            'texto'         => $texto,
            'fichas'        => $fichas,
            'herramientas'  => $usadas,
            'tokensEntrada' => $tokensEntrada,
            'tokensSalida'  => $tokensSalida,
            'resultado'     => $resultado,
            'pantalla'      => $pantalla,
            'historial'     => [
                ['role' => 'user', 'content' => $mensajeUsuario],
                ['role' => 'assistant', 'content' => $recuerdo !== '' ? $recuerdo : '(sin respuesta)'],
            ],
        ];
    }

    // ── Lo que viaja a la API ────────────────────────────────────────────────

    /**
     * El prompt de sistema. Fijo por rol: ver la cabecera de la clase.
     */
    private function promptSistema(bool $esAdministrador): string
    {
        $rol = $esAdministrador ? 'un administrador de bases de datos' : 'un auditor';
        $capacidadAdmin = $esAdministrador
            ? "\n- Mostrar las remediaciones vencidas de todas las auditorías."
            : '';

        return <<<PROMPT
Eres Lembas, el asistente del módulo de auditoría de bases de datos de Rivendel. El módulo evalúa la administración de bases de datos con un instrumento de 75 controles basado en ISO/IEC 27002, repartidos en 7 dominios y 25 procesos. Quien te escribe es {$rol} que ya inició sesión.

Regla de privacidad, que manda sobre todo lo demás:
Tú nunca ves datos de auditorías. Cuando la persona pide información de sus auditorías, eliges la herramienta que corresponde y el sistema le muestra el resultado directamente en pantalla; a ti solo te llega la confirmación de que se mostró. Por eso:
- No inventes, supongas ni resumas cifras, estados, hallazgos, nombres de empresas ni resultados de auditorías.
- Antes de usar una herramienta que muestra datos, escribe como mucho una frase breve que anuncie lo que vas a mostrar. No comentes el resultado.
- Si la persona escribe en el chat hallazgos, evidencias o respuestas de un control, no los repitas ni los analices: recuérdale que se registran en el formulario del control y ofrécele abrirlo.
- Los nombres de empresas llegan sustituidos por [empresa]. No intentes adivinarlos.

Qué puedes hacer:
- Mostrar sus auditorías, el resumen de una auditoría y los controles de mayor riesgo de una auditoría.{$capacidadAdmin}
- Ayudar a llenar una auditoría con abrir_control_para_llenar. La ficha que se muestra ya incluye lo que pide el control y un botón al formulario: no repitas su contenido; di en una frase que puede llenarlo desde ese botón. La persona escribe las respuestas en el formulario; tú no registras respuestas. Si ya tienes el código, abre el control directamente; búscalo en el catálogo solo si lo describe con palabras.
- Explicar el catálogo público de controles: qué pide un control, qué evidencia se espera, a qué dominio pertenece. Para eso usa buscar_controles_catalogo y responde solo con lo que devuelva, citando el código del control (por ejemplo C-014).

Contexto:
- Cada mensaje empieza con la pantalla en la que está la persona. Si pregunta por «esta auditoría» y la pantalla indica una, usa ese número. Si no hay ninguna y hace falta, muestra sus auditorías para que elija.
- El monitor de salud es una maqueta con cifras sintéticas y no tienes herramientas sobre él; dilo si preguntan por él.
- Si piden algo fuera de estas capacidades, dilo en una frase y ofrece lo que sí puedes hacer.

Forma de responder:
- Responde en el idioma en que te escriban. En español, trata a la persona de usted.
- Sé breve: una a tres frases, salvo cuando expliques un control.
- Escribe texto plano. No uses Markdown: ni asteriscos, ni almohadillas, ni tablas. Para enumerar, usa guiones al inicio de línea.
PROMPT;
    }

    /**
     * Las definiciones de herramientas de un rol, en el formato de la API.
     *
     * @return list<array<string, mixed>>
     */
    private function definicionesPara(bool $esAdministrador): array
    {
        $definiciones = [];

        foreach (self::HERRAMIENTAS as $nombre => $herramienta) {
            if ($herramienta['admin'] && !$esAdministrador) {
                continue;
            }

            $definiciones[] = [
                'name'         => $nombre,
                'description'  => $herramienta['descripcion'],
                // strict: la API garantiza que la entrada cumple el esquema, así
                // que un id que llega es un entero y no «la ciento cincuenta y dos».
                'strict'       => true,
                'input_schema' => [
                    'type'                 => 'object',
                    // Un objeto vacío tiene que viajar como {} y no como [].
                    'properties'           => $herramienta['propiedades'] === [] ? new \stdClass() : $herramienta['propiedades'],
                    'required'             => array_keys($herramienta['propiedades']),
                    'additionalProperties' => false,
                ],
            ];
        }

        return $definiciones;
    }

    /**
     * Qué pantalla está mirando, dicho sin datos.
     *
     * $ruta llega del navegador: es una PISTA, no una autorización. El número
     * de una auditoría solo se menciona si es del usuario; si no, la pantalla
     * se describe sin número, igual que auditoriaPropia() responde 404 y no 403.
     */
    private function describirPantalla(string $ruta, Usuario $usuario): string
    {
        $ruta = '/' . trim(mb_substr($ruta, 0, 300), '/');

        if (preg_match('#^/evaluacion/(\d+)/controles/(C-\d{3})(?:/|$)#', $ruta, $m) === 1) {
            return $this->auditoriaPropia((int) $m[1], $usuario) !== null
                ? sprintf('el formulario del control %s de la auditoría %d', $m[2], (int) $m[1])
                : 'una auditoría';
        }

        if (preg_match('#^/evaluacion/(\d+)(?:/(resultados|remediaciones|reporte))?$#', $ruta, $m) === 1) {
            $id = (int) $m[1];

            if ($this->auditoriaPropia($id, $usuario) === null) {
                return 'una auditoría';
            }

            return match ($m[2] ?? '') {
                'resultados'    => sprintf('los resultados de la auditoría %d', $id),
                'remediaciones' => sprintf('las remediaciones de la auditoría %d', $id),
                'reporte'       => sprintf('el reporte ejecutivo de la auditoría %d', $id),
                default         => sprintf('la auditoría %d, donde se responden sus 75 controles', $id),
            };
        }

        return match (true) {
            $ruta === '/evaluacion'                        => 'la lista de sus auditorías',
            $ruta === '/evaluacion/nueva'                  => 'el formulario para crear una auditoría',
            str_starts_with($ruta, '/evaluacion/comparar') => 'el histórico por empresa',
            str_starts_with($ruta, '/remediaciones')       => 'las remediaciones vencidas',
            str_starts_with($ruta, '/catalogo')            => 'el catálogo maestro de controles',
            str_starts_with($ruta, '/monitoreo')           => 'el monitor de salud, que es una maqueta',
            str_starts_with($ruta, '/perfil')              => 'su perfil',
            default                                        => 'otra pantalla del módulo',
        };
    }

    /**
     * Sustituye por [empresa] los nombres de las empresas que el usuario auditó.
     *
     * Sin tildes y sin mayúsculas en los dos lados: «cooperativa de ejemplo»
     * tiene que caer igual que «Cooperativa de Ejemplo». Los nombres largos van
     * primero para que uno corto contenido en otro no deje media razón social
     * a la vista. No reconoce una empresa nombrada a medias («la cooperativa»):
     * de eso se encarga el aviso del panel, no esta función.
     */
    private function protegerNombres(string $texto, Usuario $usuario): string
    {
        $nombres = [];

        foreach ($this->auditorias->auditoriasDe($usuario->id) as $auditoria) {
            $nombre = trim($auditoria->organizacion);

            if (mb_strlen($nombre) >= 3) {
                $nombres[$nombre] = true;
            }
        }

        $nombres = array_keys($nombres);
        usort($nombres, static fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($nombres as $nombre) {
            $texto = preg_replace('/' . $this->patronSinTildes($nombre) . '/iu', '[empresa]', $texto) ?? $texto;
        }

        return $texto;
    }

    private function patronSinTildes(string $nombre): string
    {
        $variantes = [
            'a' => '[aáàä]', 'e' => '[eéèë]', 'i' => '[iíìï]', 'o' => '[oóòö]',
            'u' => '[uúùü]', 'n' => '[nñ]', 'c' => '[cç]',
        ];

        $patron = '';

        foreach (mb_str_split(normalizarBusqueda($nombre)) as $letra) {
            $patron .= $variantes[$letra] ?? (preg_match('/\s/u', $letra) === 1 ? '\s+' : preg_quote($letra, '/'));
        }

        return $patron;
    }

    // ── Lectura de la respuesta ──────────────────────────────────────────────

    /**
     * Los bloques que cuentan, y su versión original para reenviarlos.
     *
     * Si la API cambió de modelo a mitad de respuesta (fallbacks), lo anterior
     * al último bloque `fallback` es de un intento declinado: de ahí solo vale
     * el texto. El razonamiento y las llamadas a herramientas de ese intento
     * no se ejecutan ni se reenvían, y el bloque `fallback` es un marcador que
     * se descarta.
     *
     * @param array<string, mixed> $respuesta
     * @return array{0: list<array<string, mixed>>, 1: list<object>}
     */
    private function bloquesVigentes(array $respuesta): array
    {
        $contenido = $respuesta['content'];
        $originales = $respuesta['contenido_original'] ?? [];

        $ultimoFallback = -1;

        foreach ($contenido as $i => $bloque) {
            if (($bloque['type'] ?? '') === 'fallback') {
                $ultimoFallback = $i;
            }
        }

        $vigentes = [];
        $vigentesOriginales = [];

        foreach ($contenido as $i => $bloque) {
            $tipo = (string) ($bloque['type'] ?? '');

            if ($tipo === 'fallback' || ($i < $ultimoFallback && $tipo !== 'text')) {
                continue;
            }

            $vigentes[] = $bloque;

            if (isset($originales[$i])) {
                $vigentesOriginales[] = $originales[$i];
            }
        }

        return [$vigentes, $vigentesOriginales];
    }

    /**
     * @param array<string, mixed> $uso
     * @return array<string, mixed>
     */
    private function resultadoHerramienta(array $uso, string $contenido, bool $esError = false): array
    {
        $resultado = [
            'type'        => 'tool_result',
            'tool_use_id' => (string) ($uso['id'] ?? ''),
            'content'     => $contenido,
        ];

        if ($esError) {
            $resultado['is_error'] = true;
        }

        return $resultado;
    }

    // ── Herramientas privadas: se ejecutan y se PINTAN ───────────────────────

    /**
     * Ejecuta una herramienta privada y devuelve la ficha que se pinta y la
     * marca que recuerda el historial. La marca lleva el número y el tipo de
     * lo mostrado, nunca su contenido.
     *
     * @param array<string, mixed> $entrada
     * @return array{ficha: array{tipo: string, datos: array<string, mixed>}, marca: string}
     */
    private function ejecutarPrivada(string $nombre, array $entrada, Usuario $usuario): array
    {
        if ($nombre === 'listar_mis_auditorias') {
            $auditorias = $this->auditorias->auditoriasDe($usuario->id);

            return [
                'ficha' => ['tipo' => 'auditorias', 'datos' => [
                    'auditorias' => array_slice($auditorias, 0, self::MAX_FILAS),
                    'total'      => count($auditorias),
                ]],
                'marca' => 'lista de sus auditorías',
            ];
        }

        if ($nombre === 'mostrar_remediaciones_vencidas') {
            $vencidas = $this->auditorias->remediacionesVencidas();

            return [
                'ficha' => ['tipo' => 'vencidas', 'datos' => [
                    'remediaciones' => array_slice($vencidas, 0, self::MAX_FILAS),
                    'total'         => count($vencidas),
                ]],
                'marca' => 'remediaciones vencidas',
            ];
        }

        $id = (int) ($entrada['id_auditoria'] ?? 0);
        $auditoria = $this->auditoriaPropia($id, $usuario);

        /*
         * El mismo aviso para «no existe» y para «es de otro»: distinguirlos
         * confirmaría que esa auditoría existe.
         */
        if ($auditoria === null) {
            return [
                'ficha' => $this->aviso('asistente.aviso_auditoria_no_encontrada', (string) max(0, $id)),
                'marca' => sprintf('aviso de que la auditoría %d no está entre las suyas', max(0, $id)),
            ];
        }

        if ($nombre === 'mostrar_resumen_auditoria') {
            return [
                'ficha' => ['tipo' => 'resumen', 'datos' => [
                    'auditoria' => $auditoria,
                    'resumen'   => $this->auditorias->resumen($auditoria->id),
                    'dominios'  => $this->auditorias->cumplimientoPorDominio($auditoria->id),
                ]],
                'marca' => sprintf('resumen de la auditoría %d', $auditoria->id),
            ];
        }

        if ($nombre === 'mostrar_controles_mayor_riesgo') {
            return [
                'ficha' => ['tipo' => 'riesgo', 'datos' => [
                    'auditoria' => $auditoria,
                    'controles' => $this->auditorias->mayorRiesgo($auditoria->id, 5),
                ]],
                'marca' => sprintf('controles de mayor riesgo de la auditoría %d', $auditoria->id),
            ];
        }

        // abrir_control_para_llenar
        $codigo = strtoupper(trim((string) ($entrada['codigo_control'] ?? '')));
        $control = preg_match('/^C-\d{3}$/', $codigo) === 1 ? $this->control($codigo) : null;

        if ($control === null) {
            return [
                'ficha' => $this->aviso('asistente.aviso_control_no_encontrado', $codigo !== '' ? mb_substr($codigo, 0, 20) : '—'),
                'marca' => 'aviso de que ese código de control no existe',
            ];
        }

        return [
            'ficha' => ['tipo' => 'control', 'datos' => [
                'auditoria'  => $auditoria,
                'control'    => $control,
                'evaluacion' => $this->auditorias->evaluacion($auditoria->id, $control->id),
            ]],
            'marca' => sprintf('acceso al formulario del control %s de la auditoría %d', $control->id, $auditoria->id),
        ];
    }

    /** @return array{tipo: string, datos: array<string, mixed>} */
    private function aviso(string $clave, string ...$argumentos): array
    {
        return ['tipo' => 'aviso', 'datos' => ['clave' => $clave, 'args' => $argumentos]];
    }

    /** La misma regla que AuditoriaController::auditoriaPropia(). */
    private function auditoriaPropia(int $id, Usuario $usuario): ?Auditoria
    {
        if ($id <= 0) {
            return null;
        }

        $auditoria = $this->auditorias->auditoria($id);

        return $auditoria !== null && $auditoria->idAuditor === $usuario->id ? $auditoria : null;
    }

    private function control(string $codigo): ?Control
    {
        foreach ($this->instrumento->controles() as $control) {
            if ($control->id === $codigo) {
                return $control;
            }
        }

        return null;
    }

    // ── Herramienta pública: el catálogo vuelve al modelo ────────────────────

    /**
     * Busca en el catálogo de controles, que es público.
     *
     * Puntúa por coincidencias y no pretende ser un buscador: un código exacto
     * gana siempre, luego la referencia ISO, y después las palabras que
     * aparecen, con más peso en el enunciado que en el resto. Las palabras en
     * plural se buscan también sin la «s» final, para que «respaldos»
     * encuentre «respaldo».
     *
     * @return array<string, mixed>
     */
    private function buscarEnCatalogo(string $consulta): array
    {
        $consulta = mb_substr($consulta, 0, 200);
        $normal = normalizarBusqueda($consulta);

        $dominios = [];
        foreach ($this->instrumento->dominios() as $dominio) {
            $dominios[$dominio->clave] = $dominio->nombre;
        }

        $procesos = [];
        foreach ($this->instrumento->procesos() as $proceso) {
            $procesos[$proceso->numero] = $proceso;
        }

        $palabras = [];
        foreach (preg_split('/[^\p{L}\p{N}]+/u', $normal) ?: [] as $palabra) {
            if (mb_strlen($palabra) < 3 || in_array($palabra, self::PALABRAS_VACIAS, true)) {
                continue;
            }

            $palabras[] = mb_strlen($palabra) > 4 && str_ends_with($palabra, 's')
                ? mb_substr($palabra, 0, -1)
                : $palabra;
        }

        $candidatos = [];

        foreach ($this->instrumento->controles() as $control) {
            $proceso = $procesos[$control->proceso] ?? null;
            $nombreDominio = $proceso !== null ? ($dominios[$proceso->dominio] ?? '') : '';

            $puntos = 0;

            if (str_contains($normal, normalizarBusqueda($control->id))) {
                $puntos += 100;
            }

            if ($control->iso !== '' && str_contains($normal, normalizarBusqueda($control->iso))) {
                $puntos += 50;
            }

            $enunciado = normalizarBusqueda($control->enunciado);
            $resto = normalizarBusqueda(implode(' ', [
                $control->pregunta, $control->evidencia, $proceso?->nombre ?? '', $nombreDominio,
            ]));

            foreach ($palabras as $palabra) {
                if (str_contains($enunciado, $palabra)) {
                    $puntos += 3;
                } elseif (str_contains($resto, $palabra)) {
                    $puntos += 1;
                }
            }

            if ($puntos > 0) {
                $candidatos[] = [
                    'puntos'  => $puntos,
                    'control' => [
                        'codigo'             => $control->id,
                        'referencia_iso'     => $control->iso,
                        'dominio'            => $nombreDominio,
                        'proceso'            => $proceso?->nombre ?? '',
                        'peso'               => $control->peso,
                        'enunciado'          => $control->enunciado,
                        'pregunta_auditoria' => $control->pregunta,
                        'evidencia_esperada' => $control->evidencia,
                    ],
                ];
            }
        }

        usort($candidatos, static fn (array $a, array $b): int => $b['puntos'] <=> $a['puntos']);

        return [
            'consulta'  => $consulta,
            'controles' => array_map(
                static fn (array $candidato): array => $candidato['control'],
                array_slice($candidatos, 0, self::MAX_CONTROLES),
            ),
            'nota'      => $candidatos === []
                ? 'Ningún control del catálogo coincide. Dilo y sugiere otras palabras.'
                : 'Responde solo con esta información.',
        ];
    }
}
