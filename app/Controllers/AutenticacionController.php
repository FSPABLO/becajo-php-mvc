<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/**
 * Entrada, salida y alta de cuentas.
 *
 * Solo hay acciones POST. Los formularios (GET /ingresar y GET /registrarse)
 * los construirá Persona 4; hasta entonces no se declaran esas rutas, porque
 * una ruta que apunta a una vista inexistente falla en cuanto alguien la abre.
 *
 * Toda acción termina en un redireccionamiento y deja el resultado en un
 * mensaje de sesión (destello). Es el patrón PRG: si el usuario recarga
 * después de enviar, el navegador repite el GET del destino y no el POST, así
 * que no se reintenta el alta ni el ingreso.
 *
 * Los errores de validación NO viajan en la URL: van en la sesión. Un correo o
 * un mensaje de error en la barra de direcciones acaba en el historial, en los
 * registros del servidor y en la cabecera Referer de la siguiente petición.
 */
final class AutenticacionController extends Controlador
{
    /** Destino tras entrar o registrarse. Provisional hasta que exista el panel. */
    private const DESTINO = '/evaluacion';

    public function ingresar(): void
    {
        $auth = $this->autenticacion();

        if (!$auth->tokenValido($this->peticion()->entrada('_token'))) {
            $this->sesion()->destello('error', 'La sesión expiró. Intente de nuevo.');
            $this->redirigir('/ingresar');
        }

        $correo = $this->peticion()->entrada('correo', '');
        $clave = $this->peticion()->entrada('clave', '');

        $usuario = $auth->ingresar((string) $correo, (string) $clave);

        if ($usuario === null) {
            // Mismo mensaje para credenciales incorrectas y cuenta desactivada.
            $this->sesion()->destello('error', 'Correo o contraseña incorrectos.');
            $this->sesion()->destello('correo', (string) $correo);
            $this->redirigir('/ingresar');
        }

        $this->sesion()->destello('aviso', 'Bienvenido, ' . $usuario->nombre . '.');
        $this->redirigir(self::DESTINO);
    }

    public function registrar(): void
    {
        $auth = $this->autenticacion();
        $peticion = $this->peticion();

        if (!$auth->tokenValido($peticion->entrada('_token'))) {
            $this->sesion()->destello('error', 'La sesión expiró. Intente de nuevo.');
            $this->redirigir('/registrarse');
        }

        $resultado = $auth->registrar(
            nombre:       (string) $peticion->entrada('nombre', ''),
            correo:       (string) $peticion->entrada('correo', ''),
            clave:        (string) $peticion->entrada('clave', ''),
            confirmacion: (string) $peticion->entrada('confirmacion', ''),
            organizacion: (string) $peticion->entrada('organizacion', ''),
        );

        if ($resultado['errores'] !== []) {
            // Se devuelven todos los errores juntos, no el primero: obligar a
            // corregir de a un campo por envío es maltratar al usuario.
            $this->sesion()->poner('registro.errores', $resultado['errores']);
            $this->sesion()->poner('registro.valores', [
                'nombre'       => (string) $peticion->entrada('nombre', ''),
                'correo'       => (string) $peticion->entrada('correo', ''),
                'organizacion' => (string) $peticion->entrada('organizacion', ''),
            ]);
            $this->redirigir('/registrarse');
        }

        $usuario = $resultado['usuario'];

        if ($usuario === null) {
            $this->sesion()->destello('error', 'No se pudo crear la cuenta. Intente de nuevo.');
            $this->redirigir('/registrarse');
        }

        // Se entra directamente tras registrarse: obligar a repetir el correo
        // y la contraseña recién escritos no aporta nada.
        $auth->iniciarSesion($usuario);

        $this->sesion()->destello('aviso', 'Cuenta creada. Bienvenido, ' . $usuario->nombre . '.');
        $this->redirigir(self::DESTINO);
    }

    public function salir(): void
    {
        // Cerrar sesión también se protege con token. Si no, bastaría una
        // imagen apuntando a esta ruta en cualquier página para echar al
        // usuario; es molesto más que peligroso, pero se evita igual.
        if ($this->autenticacion()->tokenValido($this->peticion()->entrada('_token'))) {
            $this->autenticacion()->cerrar();
        }

        $this->redirigir('/');
    }
}
