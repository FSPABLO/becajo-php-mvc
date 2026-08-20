<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/**
 * Controlador de la página principal.
 *
 * Note lo corto que es. Un controlador solo hace tres cosas:
 *   1. Pide los datos al modelo (el repositorio).
 *   2. Los arma en un arreglo.
 *   3. Se los entrega a la vista.
 *
 * No consulta la base de datos directamente, no calcula reglas de negocio y no
 * imprime HTML. Si empieza a crecer, es señal de que algo debe moverse al
 * modelo o a la vista.
 */
final class HomeController extends Controlador
{
    public function index(): void
    {
        $repositorio = $this->repositorio();

        /*
         * El banner enseña el TAMAÑO y la ESTRUCTURA del instrumento, y los saca
         * de aquí en vez de llevarlos escritos en config/contenido.php. Es la
         * diferencia entre una cifra que se desincroniza en cuanto alguien
         * añade un control y una que no puede: el «75» del banner ES el número
         * de controles que hay, no una copia suya hecha a mano.
         *
         * Sirve igual con el instrumento en arreglo que en Oracle: el
         * contenedor decide cuál, y el controlador solo pide el contrato.
         */
        $instrumento = $this->instrumento();

        /*
         * Contexto ya trae 'usuarioActual' (null si no hay sesión o si no hay
         * módulo de auditorías). Se reutiliza aquí en vez de volver a
         * preguntarle a Autenticacion, porque la portada no necesita saber
         * CÓMO se decide quién está conectado — solo el resultado.
         *
         * Con sesión, la portada deja de ser un sitio de visitante: se le
         * añade una franja con su propia cartera, para que "estoy conectado"
         * se note apenas llega y no haya que ir a buscarlo al panel.
         */
        $contexto = $this->contexto();
        $auditoriasAuditor = $contexto['usuarioActual'] !== null
            ? $this->auditorias()->auditoriasDe($contexto['usuarioActual']->id)
            : [];

        $this->ver('home/index', [
            ...$contexto,
            'auditoriasAuditor'    => $auditoriasAuditor,
            'meta'                  => $repositorio->meta(),
            'hero'                  => $repositorio->hero(),
            'dominios'              => $instrumento->dominios(),
            'procesos'              => $instrumento->procesos(),
            'controles'             => $instrumento->controles(),
            'retos'                 => $repositorio->retos(),
            'encabezadoServicios'   => $repositorio->encabezadoServicios(),
            'servicios'             => $repositorio->servicios(),
            'metricas'              => $repositorio->metricas(),
            'motores'               => $repositorio->motores(),
            'stack'                 => $repositorio->stack(),
            'caso'                  => $repositorio->caso(),
            'encabezadoTestimonios' => $repositorio->encabezadoTestimonios(),
            'testimonios'           => $repositorio->testimonios(),
            'equipo'                => $repositorio->equipo(),
            'planes'                => $repositorio->planes(),
            'contacto'              => $repositorio->contacto(),
            'erroresContacto'       => $this->leerYOlvidar('contacto.errores'),
            'valoresContacto'       => $this->leerYOlvidar('contacto.valores'),
        ]);
    }

    /** @return array<string, string> */
    private function leerYOlvidar(string $clave): array
    {
        $valor = $this->sesion()->obtener($clave, []);
        $this->sesion()->olvidar($clave);

        return is_array($valor) ? $valor : [];
    }
}
