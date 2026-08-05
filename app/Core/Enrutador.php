<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador: decide qué controlador atiende cada URL.
 *
 * Es la pieza que convierte "el usuario pidió /" en "ejecuta
 * HomeController::index()".
 *
 * Además de rutas fijas admite parámetros entre llaves:
 *
 *     $enrutador->get('/auditorias/{id}', [AuditoriaController::class, 'mostrar']);
 *
 * Al atender /auditorias/7, el controlador lee el valor con
 * $this->parametro('id'). Sin esto no hay CRUD posible: toda operación sobre
 * un registro concreto necesita decir *cuál* en la propia URL.
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
        $rutas = $this->rutas[$peticion->metodo()] ?? [];
        $ruta = $peticion->ruta();

        // Coincidencia exacta primero: es el caso más común y el más barato.
        // Además evita que /evaluacion/catalogo se lo quede /evaluacion/{id}
        // solo por estar declarada antes en config/rutas.php.
        $accion = $rutas[$ruta] ?? null;
        $parametros = [];

        if ($accion === null) {
            [$accion, $parametros] = $this->buscarPorPatron($rutas, $ruta);
        }

        if ($accion === null) {
            $this->responder404($peticion, $contenedor);
            return;
        }

        $peticion->asignarParametros($parametros);

        [$clase, $metodo] = $accion;

        /** @var Controlador $controlador */
        $controlador = new $clase($contenedor);
        $controlador->{$metodo}();
    }

    /**
     * Recorre las rutas con llaves buscando la primera que calce.
     *
     * @param array<string, array{0: class-string, 1: string}> $rutas
     * @return array{0: array{0: class-string, 1: string}|null, 1: array<string, string>}
     */
    private function buscarPorPatron(array $rutas, string $ruta): array
    {
        foreach ($rutas as $patron => $accion) {
            if (!str_contains($patron, '{')) {
                continue;
            }

            if (preg_match($this->expresion($patron), $ruta, $coincidencias) !== 1) {
                continue;
            }

            // preg_match devuelve los grupos dos veces: por número y por
            // nombre. Solo interesan los nombrados.
            $parametros = array_filter(
                $coincidencias,
                static fn (int|string $clave): bool => is_string($clave),
                \ARRAY_FILTER_USE_KEY,
            );

            return [$accion, $parametros];
        }

        return [null, []];
    }

    /**
     * Traduce '/auditorias/{id}/controles/{codigo}' a una expresión regular.
     *
     * El patrón se escapa antes de sustituir las llaves: así un punto o un
     * signo de interrogación en la ruta se comparan como caracteres literales
     * y no como comodines. Cada parámetro acepta cualquier cosa menos una
     * barra, para que un segmento no se trague el resto de la URL.
     */
    private function expresion(string $patron): string
    {
        $escapado = preg_quote($patron, '#');

        $regla = preg_replace(
            '#\\\\\{(\w+)\\\\\}#',
            '(?P<$1>[^/]+)',
            $escapado,
        );

        return '#^' . $regla . '$#';
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
