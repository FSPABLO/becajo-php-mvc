<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/**
 * El formulario de contacto de la portada. No depende de Oracle: cualquiera
 * puede escribir sin necesidad de cuenta, así que no pasa por Autenticacion.
 */
final class ContactoController extends Controlador
{
    public function enviar(): void
    {
        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->sesion()->poner('contacto.errores', $errores);
            $this->sesion()->poner('contacto.valores', $datos);
            $this->redirigir('/#contacto');
        }

        $this->guardar($datos);

        $this->sesion()->destello('aviso', 'Mensaje recibido. Le contactaremos pronto.');
        $this->redirigir('/#contacto');
    }

    /** @return array{nombre: string, correo: string, motor: string, mensaje: string} */
    private function leerFormulario(): array
    {
        $peticion = $this->peticion();

        return [
            'nombre'  => trim((string) $peticion->entrada('nombre', '')),
            'correo'  => trim((string) $peticion->entrada('correo', '')),
            'motor'   => trim((string) $peticion->entrada('motor', '')),
            'mensaje' => trim((string) $peticion->entrada('mensaje', '')),
        ];
    }

    /**
     * @param array{nombre: string, correo: string, motor: string, mensaje: string} $datos
     * @return array<string, string>
     */
    private function validar(array $datos): array
    {
        $errores = [];

        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'Indique su nombre.';
        }

        if ($datos['correo'] === '' || filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) === false) {
            $errores['correo'] = 'Escriba un correo válido.';
        }

        if ($datos['mensaje'] === '') {
            $errores['mensaje'] = 'Cuéntenos brevemente qué necesita.';
        } elseif (mb_strlen($datos['mensaje']) > 2000) {
            $errores['mensaje'] = 'El mensaje es demasiado largo.';
        }

        return $errores;
    }

    /**
     * Se guarda en un archivo y no en Oracle a propósito: este formulario es
     * de la portada pública, no del módulo de auditorías, y el sitio tiene que
     * seguir funcionando aunque nadie haya levantado la base de datos todavía.
     *
     * @param array{nombre: string, correo: string, motor: string, mensaje: string} $datos
     */
    private function guardar(array $datos): void
    {
        $carpeta = \RAIZ . '/storage';

        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        $linea = json_encode([
            'fecha'   => date('c'),
            'nombre'  => $datos['nombre'],
            'correo'  => $datos['correo'],
            'motor'   => $datos['motor'],
            'mensaje' => $datos['mensaje'],
        ], JSON_UNESCAPED_UNICODE) . \PHP_EOL;

        file_put_contents($carpeta . '/contactos.log', $linea, FILE_APPEND | LOCK_EX);
    }
}
