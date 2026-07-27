<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador: decide qué controlador atiende cada URL.
 *
 * Es la pieza que convierte "el usuario pidió /" en "ejecuta
 * HomeController::index()". Hoy el sitio tiene una sola ruta, pero la
 * estructura ya soporta agregar /servicios, /contacto, etc. sin tocar nada más.
 */
final class Enrutador
{
    /** @var array<string, array<string, callable-string|array{0:class-string,1:string}>> */
    private array $rutas = ['GET' => [], 'POST' => []];

    /** @param array{0: class-string, 1: string} $accion [Controlador::class, 'metodo'] */
    public function get(string $ruta, array $accion): void
    {
        $this->rutas['GET'][$ruta] = $accion;
    }

    /** @param array{0: class-string, 1: string} $accion */
    public function post(string $ruta, array $accion): void
    {
        $this->rutas['POST'][$ruta] = $accion;
    }

    public function despachar(Peticion $peticion, Contenedor $contenedor): void
    {
        $accion = $this->rutas[$peticion->metodo()][$peticion->ruta()] ?? null;

        if ($accion === null) {
            $this->responder404($peticion, $contenedor);
            return;
        }

        [$clase, $metodo] = $accion;

        /** @var Controlador $controlador */
        $controlador = new $clase($contenedor);
        $controlador->{$metodo}();
    }

    private function responder404(Peticion $peticion, Contenedor $contenedor): void
    {
        http_response_code(404);

        $vista = $contenedor->vista();
        echo $vista->renderizar('errores/404', [
            'ruta'    => $peticion->ruta(),
            'empresa' => $contenedor->repositorio()->empresa(),
        ]);
    }
}
