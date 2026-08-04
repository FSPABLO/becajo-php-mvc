<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Contratos\RepositorioContenido;
use App\Models\Contratos\RepositorioInstrumento;

/**
 * Clase base de todos los controladores.
 *
 * Un controlador NO consulta datos por su cuenta ni imprime HTML: pide datos al
 * modelo y se los pasa a una vista. Si algún día un controlador empieza a
 * contener SQL o etiquetas HTML, es señal de que la separación se rompió.
 */
abstract class Controlador
{
    public function __construct(protected readonly Contenedor $contenedor)
    {
    }

    protected function repositorio(): RepositorioContenido
    {
        return $this->contenedor->repositorio();
    }

    protected function instrumento(): RepositorioInstrumento
    {
        return $this->contenedor->instrumento();
    }

    protected function auditorias(): RepositorioAuditorias
    {
        return $this->contenedor->auditorias();
    }

    protected function peticion(): Peticion
    {
        return $this->contenedor->peticion();
    }

    protected function sesion(): Sesion
    {
        return $this->contenedor->sesion();
    }

    /** Atajo para leer un parámetro de la URL: /auditorias/{id} -> parametro('id'). */
    protected function parametro(string $clave, ?string $porDefecto = null): ?string
    {
        return $this->peticion()->parametro($clave, $porDefecto);
    }

    /**
     * Renderiza una vista dentro del diseño principal y la envía al navegador.
     *
     * @param array<string, mixed> $datos
     */
    protected function ver(string $vista, array $datos = [], string $plantilla = 'principal'): void
    {
        echo $this->contenedor->vista()->renderizarConPlantilla($vista, $datos, $plantilla);
    }

    /**
     * Envía al navegador a otra ruta del sitio y corta la ejecución.
     *
     * Todo POST que modifica datos termina aquí (patrón PRG: post, redirect,
     * get). Sin el redireccionamiento, recargar la página después de guardar
     * volvería a enviar el formulario y duplicaría el registro.
     *
     * La ruta se antepone con la ruta base para que los enlaces sigan siendo
     * correctos si el sitio vive en una subcarpeta.
     */
    protected function redirigir(string $ruta): never
    {
        header('Location: ' . $this->peticion()->rutaBase() . $ruta);

        exit;
    }

    /**
     * Responde 404 desde un controlador.
     *
     * El enrutador ya cubre la URL que no existe; esto cubre el caso distinto
     * de una URL bien formada que apunta a un registro inexistente
     * (/auditorias/9999).
     */
    protected function noEncontrado(): never
    {
        http_response_code(404);

        // Sin plantilla envolvente: errores/404 ya es un documento HTML
        // completo, igual que cuando lo sirve el enrutador.
        echo $this->contenedor->vista()->renderizar('errores/404', [
            'ruta'    => $this->peticion()->ruta(),
            'empresa' => $this->repositorio()->empresa(),
        ]);

        exit;
    }
}
