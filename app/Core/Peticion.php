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
