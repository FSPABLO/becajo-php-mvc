<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Representa la petición HTTP entrante.
 *
 * Encapsula el acceso a $_SERVER, $_GET y $_POST para que el resto del sistema
 * no los toque directamente. También resuelve la "ruta base", que es lo que
 * permite que el sitio funcione tanto en la raíz del servidor como dentro de
 * una subcarpeta (por ejemplo http://localhost/becajo/public/).
 */
final class Peticion
{
    private string $metodo;
    private string $ruta;
    private string $rutaBase;

    /**
     * Parámetros tomados de la propia URL: en /auditorias/7 el enrutador deja
     * aquí ['id' => '7']. Los rellena Enrutador::despachar() antes de invocar
     * al controlador; nadie más debería escribirlos.
     *
     * @var array<string, string>
     */
    private array $parametros = [];

    public function __construct()
    {
        $this->metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Carpeta donde vive el front controller, vista desde el navegador.
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $this->rutaBase = rtrim($base, '/');

        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if ($this->rutaBase !== '' && str_starts_with($uri, $this->rutaBase)) {
            $uri = substr($uri, strlen($this->rutaBase));
        }

        $this->ruta = '/' . trim($uri, '/');
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    /** Ruta solicitada, ya sin la subcarpeta de instalación. Ej.: "/" */
    public function ruta(): string
    {
        return $this->ruta;
    }

    /** Prefijo para construir enlaces y rutas de recursos estáticos. */
    public function rutaBase(): string
    {
        return $this->rutaBase;
    }

    public function esPost(): bool
    {
        return $this->metodo === 'POST';
    }

    /**
     * ¿La envía un guion por fetch, o el navegador con un formulario?
     *
     * Se decide por una cabecera PROPIA y no por Accept ni por el
     * X-Requested-With de jQuery: Accept lo mandan también los navegadores y
     * su valor depende del día, mientras que esta cabecera solo puede
     * ponerla nuestro guion. La diferencia importa porque el mismo POST
     * responde JSON o redirige según quién pregunte, y equivocarse deja al
     * auditor mirando un JSON en pantalla.
     *
     * Una petición de otro origen no puede añadir cabeceras propias sin que
     * el navegador pida permiso antes (CORS), así que esto tampoco abre una
     * puerta: el token CSRF se sigue exigiendo igual.
     */
    public function esAsincrona(): bool
    {
        return ($_SERVER['HTTP_X_BECAJO_ASINCRONA'] ?? '') === '1';
    }

    /**
     * Lee una cookie del navegador.
     *
     * Existe por la misma razón que entrada(): que $_COOKIE quede encapsulado
     * aquí y no aparezca en un controlador ni, mucho menos, en una vista. La
     * usa el marco del módulo para saber si el auditor dejó plegada la barra
     * lateral, de modo que la página nazca ya plegada en lugar de plegarse de
     * un salto cuando arranca el guion.
     *
     * Lo que llega en una cookie es texto que escribió el cliente: quien la lea
     * debe compararla contra valores conocidos, nunca imprimirla tal cual.
     */
    public function cookie(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = $_COOKIE[$clave] ?? null;

        return is_string($valor) ? $valor : $porDefecto;
    }

    /**
     * Lee un dato enviado por el usuario, venga del formulario o de la URL.
     *
     * Devuelve SIEMPRE una cadena (o el valor por defecto): la conversión a
     * entero, fecha o booleano es responsabilidad de quien la use, que es el
     * único que sabe qué esperaba. Los espacios sobrantes se recortan porque
     * un campo con solo espacios es, a efectos prácticos, un campo vacío.
     */
    public function entrada(string $clave, ?string $porDefecto = null): ?string
    {
        $valor = $_POST[$clave] ?? $_GET[$clave] ?? null;

        if (!is_string($valor)) {
            return $porDefecto;
        }

        $valor = trim($valor);

        return $valor === '' ? $porDefecto : $valor;
    }

    /**
     * Lee un campo que llega repetido (casillas de verificación, listas
     * múltiples). Descarta lo que no sea texto para que un arreglo anidado
     * enviado a mano no se cuele hasta el modelo.
     *
     * @return list<string>
     */
    public function entradaLista(string $clave): array
    {
        $valor = $_POST[$clave] ?? $_GET[$clave] ?? [];

        if (!is_array($valor)) {
            return [];
        }

        return array_values(array_filter($valor, 'is_string'));
    }

    /** ¿El formulario envió esta casilla marcada? */
    public function marcada(string $clave): bool
    {
        return $this->entrada($clave) !== null;
    }

    /**
     * Lee un archivo subido, si de verdad llegó uno.
     *
     * Existe por la misma razón que entrada(): que $_FILES quede encapsulado
     * aquí y no aparezca en un controlador. Devuelve null en los dos casos que
     * significan "no adjuntó nada" —el campo no viajó, o viajó vacío
     * (UPLOAD_ERR_NO_FILE)—, porque para quien llama son lo mismo: el auditor
     * no puso archivo. Un error DISTINTO de ésos sí se devuelve, con su código
     * dentro, para que la validación pueda decir qué pasó en vez de tratarlo
     * como "no adjuntó nada" — que es como se pierde un archivo en silencio.
     *
     * `es_uploaded_file` es la comprobación que impide que un nombre de ruta
     * fabricado a mano convierta esto en una lectura de cualquier archivo del
     * servidor.
     *
     * @return array{nombre: string, tipo: string, tamano: int, ruta: string, error: int}|null
     */
    public function archivo(string $clave): ?array
    {
        $archivo = $_FILES[$clave] ?? null;

        if (!is_array($archivo) || !isset($archivo['error']) || is_array($archivo['error'])) {
            return null;
        }

        $error = (int) $archivo['error'];

        if ($error === \UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $ruta = is_string($archivo['tmp_name'] ?? null) ? $archivo['tmp_name'] : '';

        return [
            'nombre' => is_string($archivo['name'] ?? null) ? $archivo['name'] : '',
            // El tipo que declara el navegador NO se usa para decidir nada:
            // lo escribe el cliente. Viaja solo para poder decirlo en el
            // mensaje de error; quien manda es finfo sobre el contenido.
            'tipo'   => is_string($archivo['type'] ?? null) ? $archivo['type'] : '',
            'tamano' => (int) ($archivo['size'] ?? 0),
            'ruta'   => ($error === \UPLOAD_ERR_OK && $ruta !== '' && is_uploaded_file($ruta)) ? $ruta : '',
            'error'  => $error,
        ];
    }

    /**
     * ¿El cuerpo de la petición se pasó de post_max_size?
     *
     * Cuando eso ocurre PHP no devuelve un error: descarta el cuerpo entero y
     * deja $_POST y $_FILES VACÍOS, con la petición pareciendo un POST normal
     * sin campos. Sin esta comprobación, adjuntar un archivo demasiado grande
     * se manifiesta como "el token CSRF no coincide" —porque el token también
     * se perdió— y el auditor lee que su sesión caducó cuando lo que pasó es
     * que el archivo no cabía.
     *
     * Se mira Content-Length y no $_FILES porque a estas alturas ya no queda
     * rastro del archivo. Si el límite no se puede leer, se responde false: es
     * preferible seguir al camino normal que inventar un error.
     */
    public function excedioLimitePost(): bool
    {
        if (!$this->esPost() || $_POST !== [] || $_FILES !== []) {
            return false;
        }

        $limite = $this->bytesDeIni('post_max_size');
        $enviado = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        return $limite > 0 && $enviado > $limite;
    }

    /**
     * Tamaño máximo de archivo que este PHP acepta de verdad, en bytes.
     *
     * Es el MENOR de upload_max_filesize y post_max_size: de nada sirve
     * admitir un archivo de 6 MB si el cuerpo entero se corta en 4. Lo consulta
     * la validación para no prometer en pantalla un tope que el servidor no va
     * a respetar — ver docker/php.ini.
     */
    public function limiteSubidaBytes(): int
    {
        $porArchivo = $this->bytesDeIni('upload_max_filesize');
        $porPeticion = $this->bytesDeIni('post_max_size');

        $topes = array_filter([$porArchivo, $porPeticion], static fn (int $v): bool => $v > 0);

        return $topes === [] ? 0 : min($topes);
    }

    /**
     * Traduce un valor de php.ini con sufijo ("6M", "10G") a bytes.
     *
     * Los sufijos de PHP son potencias de 1024, no de 1000, y se acumulan: "1G"
     * es 1024 M, que es 1024 K, que es 1024 bytes. Un valor sin sufijo ya está
     * en bytes; 0 o vacío significan "sin límite" y se devuelven como 0.
     */
    private function bytesDeIni(string $directiva): int
    {
        $valor = trim((string) ini_get($directiva));

        if ($valor === '') {
            return 0;
        }

        $numero = (int) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g'     => $numero * 1024 * 1024 * 1024,
            'm'     => $numero * 1024 * 1024,
            'k'     => $numero * 1024,
            default => $numero,
        };
    }

    /** Parámetro tomado de la URL. En la ruta /auditorias/{id}, parametro('id'). */
    public function parametro(string $clave, ?string $porDefecto = null): ?string
    {
        return $this->parametros[$clave] ?? $porDefecto;
    }

    /**
     * La usa el enrutador tras hacer coincidir la ruta. No se llama desde los
     * controladores: para ellos los parámetros ya vienen puestos.
     *
     * @param array<string, string> $parametros
     */
    public function asignarParametros(array $parametros): void
    {
        $this->parametros = $parametros;
    }
}
