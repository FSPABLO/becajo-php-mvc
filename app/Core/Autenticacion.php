<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Entidades\Usuario;

/**
 * Quién está dentro del sistema y cómo entró.
 *
 * La lógica vive aquí y no en el controlador a propósito: el controlador
 * termina toda acción con un redireccionamiento (y por tanto con un exit), lo
 * que lo vuelve imposible de probar sin levantar un navegador. Esta clase, en
 * cambio, recibe datos y devuelve datos, así que se puede verificar contra la
 * base de datos real sin una sola vista.
 *
 * En sesión se guarda ÚNICAMENTE el identificador del usuario, no sus datos.
 * Así, si a un auditor se le retira el rol o se le desactiva la cuenta, deja de
 * tener acceso en la siguiente petición en vez de conservarlo hasta que cierre
 * el navegador. El precio es una consulta por petición, que se memoriza.
 */
final class Autenticacion
{
    private const CLAVE_USUARIO = 'auth.usuario';
    private const CLAVE_TOKEN = 'auth.token';

    /** Longitud mínima de contraseña. */
    public const MINIMO_CLAVE = 8;

    private ?Usuario $memoria = null;
    private bool $consultado = false;

    public function __construct(
        private readonly Sesion $sesion,
        private readonly RepositorioAuditorias $repositorio,
    ) {
    }

    // ── Entrada y salida ─────────────────────────────────────────────────────

    /**
     * Verifica las credenciales y, si son correctas, abre la sesión.
     *
     * Devuelve null en cualquier caso de fallo, sin distinguir entre correo
     * inexistente, clave incorrecta y cuenta desactivada: decirle a quien lo
     * intenta cuál de las tres cosas falló es regalarle la mitad del trabajo.
     */
    public function ingresar(string $correo, string $clave): ?Usuario
    {
        $usuario = $this->repositorio->autenticar($correo, $clave);

        if ($usuario === null) {
            return null;
        }

        $this->iniciarSesion($usuario);

        return $usuario;
    }

    /**
     * Deja al usuario dentro del sistema.
     *
     * Se renueva el identificador de sesión ANTES de guardar nada: si alguien
     * consiguió fijar el identificador mientras la víctima aún no había
     * entrado, ese identificador queda inservible al autenticarse.
     */
    public function iniciarSesion(Usuario $usuario): void
    {
        $this->sesion->regenerar();
        $this->sesion->poner(self::CLAVE_USUARIO, $usuario->id);

        $this->memoria = $usuario;
        $this->consultado = true;
    }

    public function cerrar(): void
    {
        $this->sesion->destruir();

        $this->memoria = null;
        $this->consultado = true;
    }

    // ── Quién está dentro ────────────────────────────────────────────────────

    public function usuario(): ?Usuario
    {
        if ($this->consultado) {
            return $this->memoria;
        }

        $this->consultado = true;
        $id = $this->sesion->obtener(self::CLAVE_USUARIO);

        if (!is_int($id)) {
            return $this->memoria = null;
        }

        $usuario = $this->repositorio->usuario($id);

        // La cuenta pudo desactivarse o borrarse mientras la sesión seguía
        // abierta. En ese caso la sesión ya no vale nada: se descarta.
        if ($usuario === null || !$usuario->activo) {
            $this->sesion->olvidar(self::CLAVE_USUARIO);

            return $this->memoria = null;
        }

        return $this->memoria = $usuario;
    }

    public function hayUsuario(): bool
    {
        return $this->usuario() !== null;
    }

    public function esAdministrador(): bool
    {
        return $this->usuario()?->esAdministrador() ?? false;
    }

    // ── Registro ─────────────────────────────────────────────────────────────

    /**
     * Da de alta una cuenta nueva.
     *
     * Devuelve ['usuario' => Usuario|null, 'errores' => array<string, string>].
     * Los errores van indexados por campo para que la vista pueda pintarlos
     * junto a su casilla cuando Persona 4 construya el formulario.
     *
     * El ROL NO se acepta desde fuera: toda cuenta creada por esta vía nace
     * como AUDITOR. Si el rol viniera del formulario, cualquiera podría
     * registrarse como ADMIN_BD —que es el superadministrador del sistema— y
     * quedarse con el catálogo maestro. Las cuentas ADMIN_BD se crean a mano
     * en la base.
     *
     * @return array{usuario: Usuario|null, errores: array<string, string>}
     */
    public function registrar(
        string $nombre,
        string $correo,
        string $clave,
        string $confirmacion,
        string $organizacion,
    ): array {
        $errores = $this->validarRegistro($nombre, $correo, $clave, $confirmacion, $organizacion);

        if ($errores !== []) {
            return ['usuario' => null, 'errores' => $errores];
        }

        $id = $this->repositorio->registrarUsuario(
            nombre: $nombre,
            correo: mb_strtolower($correo),
            // PASSWORD_DEFAULT deja que PHP elija el algoritmo vigente; la
            // columna es VARCHAR2(255) justamente para admitir hashes más
            // largos si el día de mañana cambia a algo distinto de bcrypt.
            hash: password_hash($clave, PASSWORD_DEFAULT),
            rol: Usuario::ROL_AUDITOR,
            organizacion: $organizacion,
        );

        return ['usuario' => $this->repositorio->usuario($id), 'errores' => []];
    }

    /**
     * Comprueba los datos de registro.
     *
     * @return array<string, string>
     */
    private function validarRegistro(
        string $nombre,
        string $correo,
        string $clave,
        string $confirmacion,
        string $organizacion,
    ): array {
        $errores = [];

        if (trim($nombre) === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 150) {
            $errores['nombre'] = 'El nombre no puede pasar de 150 caracteres.';
        }

        if (trim($correo) === '') {
            $errores['correo'] = 'El correo es obligatorio.';
        } elseif (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errores['correo'] = 'El correo no tiene un formato válido.';
        } elseif (mb_strlen($correo) > 150) {
            $errores['correo'] = 'El correo no puede pasar de 150 caracteres.';
        } elseif ($this->repositorio->correoRegistrado($correo)) {
            // Se comprueba aquí para poder dar un mensaje claro, pero la
            // restricción uq_usuario_correo de la base es la que manda: entre
            // esta consulta y el INSERT cabe otro registro con el mismo correo.
            $errores['correo'] = 'Ya existe una cuenta con ese correo.';
        }

        if (mb_strlen($clave) < self::MINIMO_CLAVE) {
            $errores['clave'] = 'La contraseña debe tener al menos ' . self::MINIMO_CLAVE . ' caracteres.';
        } elseif ($clave !== $confirmacion) {
            $errores['confirmacion'] = 'La confirmación no coincide con la contraseña.';
        }

        if (trim($organizacion) === '') {
            $errores['organizacion'] = 'La organización es obligatoria.';
        } elseif (mb_strlen($organizacion) > 200) {
            $errores['organizacion'] = 'La organización no puede pasar de 200 caracteres.';
        }

        return $errores;
    }

    // ── Protección contra CSRF ───────────────────────────────────────────────

    /**
     * Token de la sesión, creado la primera vez que se pide.
     *
     * Va incrustado como campo oculto en cada formulario. Sin él, bastaría con
     * que un auditor autenticado visitara una página ajena para que esa página
     * enviara peticiones a este sitio en su nombre —crear auditorías, borrar
     * controles— aprovechando que el navegador adjunta la cookie de sesión
     * sola. El atacante no puede leer el token, así que no puede falsificarlo.
     */
    public function token(): string
    {
        $token = $this->sesion->obtener(self::CLAVE_TOKEN);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->sesion->poner(self::CLAVE_TOKEN, $token);
        }

        return $token;
    }

    /**
     * Compara el token recibido con el de la sesión.
     *
     * hash_equals y no === : compara en tiempo constante, sin delatar cuántos
     * caracteres iniciales acertó quien lo intenta.
     */
    public function tokenValido(?string $recibido): bool
    {
        $esperado = $this->sesion->obtener(self::CLAVE_TOKEN);

        if (!is_string($esperado) || $esperado === '' || $recibido === null) {
            return false;
        }

        return hash_equals($esperado, $recibido);
    }
}
