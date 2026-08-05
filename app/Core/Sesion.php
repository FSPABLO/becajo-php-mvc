<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Envoltorio de $_SESSION.
 *
 * Único punto del proyecto que toca la variable superglobal, igual que
 * Peticion con $_SERVER o BaseDatos con las funciones oci_*. Mantener el
 * acceso concentrado aquí es lo que permitirá, si algún día hace falta,
 * guardar las sesiones en la base de datos sin salir a buscar $_SESSION por
 * todo el código.
 *
 * A propósito NO sabe nada de auditores ni de roles: eso es asunto de la capa
 * de autenticación, que se construye encima de esta clase guardando y leyendo
 * claves. Aquí solo hay "poner", "obtener" y "olvidar".
 */
final class Sesion
{
    private bool $iniciada = false;

    /**
     * Abre la sesión si no estaba abierta.
     *
     * Es idempotente y perezoso: llamarlo dos veces no hace daño, y mientras
     * nadie lea ni escriba nada no se envía la cookie de sesión. Así el sitio
     * público sigue sirviéndose sin cookies.
     */
    public function iniciar(): void
    {
        if ($this->iniciada || session_status() === \PHP_SESSION_ACTIVE) {
            $this->iniciada = true;
            return;
        }

        // Las cookies de sesión no tienen por qué ser legibles desde
        // JavaScript ni viajar a otros sitios: httponly y SameSite cierran
        // por defecto dos vías clásicas de robo de sesión.
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        $this->iniciada = true;
    }

    /**
     * ¿Este visitante ya traía una sesión?
     *
     * Permite leer mensajes pendientes sin abrir sesión a quien nunca la tuvo.
     * Sin esta comprobación, mostrar avisos en el diseño principal obligaría a
     * llamar a iniciar() en toda página, y el sitio público —que no necesita
     * sesión para nada— acabaría enviando una cookie a cada visitante.
     */
    public function existePrevia(): bool
    {
        return session_status() === \PHP_SESSION_ACTIVE
            || isset($_COOKIE[session_name()]);
    }

    public function poner(string $clave, mixed $valor): void
    {
        $this->iniciar();

        $_SESSION[$clave] = $valor;
    }

    public function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        $this->iniciar();

        return $_SESSION[$clave] ?? $porDefecto;
    }

    public function tiene(string $clave): bool
    {
        $this->iniciar();

        return isset($_SESSION[$clave]);
    }

    public function olvidar(string $clave): void
    {
        $this->iniciar();

        unset($_SESSION[$clave]);
    }

    /**
     * Guarda un valor para la SIGUIENTE petición y lo borra al leerlo.
     *
     * Es lo que permite mostrar "Auditoría creada" después de un POST que
     * redirige: el mensaje sobrevive al redireccionamiento y desaparece solo,
     * sin quedar pegado si el usuario recarga la página.
     */
    public function destello(string $clave, ?string $valor = null): ?string
    {
        if ($valor !== null) {
            $this->poner('_destello.' . $clave, $valor);

            return null;
        }

        $guardado = $this->obtener('_destello.' . $clave);
        $this->olvidar('_destello.' . $clave);

        return is_string($guardado) ? $guardado : null;
    }

    /**
     * Renueva el identificador de sesión conservando su contenido.
     *
     * Se llama justo después de un inicio de sesión correcto. Sin esto, un
     * atacante que haya conseguido fijar el identificador ANTES del login
     * seguiría dentro de la sesión ya autenticada (fijación de sesión).
     */
    public function regenerar(): void
    {
        $this->iniciar();

        // Solo tiene sentido si la sesión llegó a abrirse de verdad. Sin esta
        // guarda, un contexto donde session_start() no prosperó (CLI, cabeceras
        // ya enviadas) genera un aviso en vez de no hacer nada.
        if (session_status() === \PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** Cierra la sesión y descarta su contenido. */
    public function destruir(): void
    {
        $this->iniciar();

        $_SESSION = [];

        if (session_status() === \PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        $this->iniciada = false;
    }
}
