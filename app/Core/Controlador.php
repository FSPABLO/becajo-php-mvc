<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Contratos\RepositorioCatalogo;
use App\Models\Contratos\RepositorioContenido;
use App\Models\Contratos\RepositorioInstrumento;
use App\Models\Entidades\Usuario;

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

    protected function catalogo(): RepositorioCatalogo
    {
        return $this->contenedor->catalogo();
    }

    protected function peticion(): Peticion
    {
        return $this->contenedor->peticion();
    }

    protected function sesion(): Sesion
    {
        return $this->contenedor->sesion();
    }

    protected function autenticacion(): Autenticacion
    {
        return $this->contenedor->autenticacion();
    }

    /**
     * Exige que haya alguien autenticado y devuelve quién es.
     *
     * Primera línea de toda acción del módulo de auditorías. Si no hay sesión,
     * corta aquí y manda al formulario de ingreso: la acción protegida ni
     * siquiera empieza a ejecutarse.
     */
    protected function exigirUsuario(): Usuario
    {
        $usuario = $this->autenticacion()->usuario();

        if ($usuario === null) {
            $this->sesion()->destello('error', 'Inicie sesión para continuar.');
            $this->redirigir('/ingresar');
        }

        return $usuario;
    }

    /**
     * Exige además el rol de administrador de base de datos.
     *
     * Lo usará el CRUD del catálogo maestro: que un auditor pueda responder
     * cuestionarios no significa que pueda reescribir los controles que todos
     * los demás evalúan.
     */
    protected function exigirAdministrador(): Usuario
    {
        $usuario = $this->exigirUsuario();

        if (!$usuario->esAdministrador()) {
            $this->sesion()->destello('error', 'No tiene permisos para esa sección.');
            $this->redirigir('/evaluacion');
        }

        return $usuario;
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
        $datos['mensajes'] = $datos['mensajes'] ?? $this->mensajesPendientes();

        echo $this->contenedor->vista()->renderizarConPlantilla($vista, $datos, $plantilla);
    }

    /**
     * Datos que el diseño principal necesita en toda página.
     *
     * @return array<string, mixed>
     */
    protected function contexto(): array
    {
        return [
            'empresa'      => $this->repositorio()->empresa(),
            'navegacion'   => $this->repositorio()->navegacion(),
            'herramientas' => $this->repositorio()->herramientas(),
        ];
    }

    /** @return array{titulo: string, descripcion: string} */
    protected function meta(string $titulo, string $descripcion = ''): array
    {
        return [
            'titulo'      => $titulo . ' | ' . $this->repositorio()->empresa()['nombre'],
            'descripcion' => $descripcion !== '' ? $descripcion
                : 'Módulo de evaluación de riesgo en la administración de bases '
                . 'de datos según ISO/IEC 27002.',
        ];
    }

    /**
     * Recoge los avisos que dejó la petición anterior antes de redirigir.
     *
     * Se leen aquí, en un solo sitio, para que toda vista los reciba sin que
     * cada controlador tenga que acordarse de pasarlos. Leerlos los consume:
     * es justo lo que se busca, porque ya se están mostrando.
     *
     * Si el visitante no traía sesión no puede haber nada pendiente, así que
     * ni siquiera se abre una. Es lo que evita que la portada mande una cookie
     * a quien solo pasaba por ahí.
     *
     * @return array{aviso: string|null, error: string|null}
     */
    private function mensajesPendientes(): array
    {
        if (!$this->sesion()->existePrevia()) {
            return ['aviso' => null, 'error' => null];
        }

        return [
            'aviso' => $this->sesion()->destello('aviso'),
            'error' => $this->sesion()->destello('error'),
        ];
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
