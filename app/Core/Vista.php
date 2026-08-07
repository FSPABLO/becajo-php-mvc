<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Motor de vistas.
 *
 * Ejecuta un archivo de plantilla capturando su salida en memoria en lugar de
 * enviarla directo al navegador. Eso permite renderizar primero el contenido y
 * después inyectarlo dentro del diseño principal.
 *
 * Las variables se pasan con extract(), de modo que dentro de la vista se
 * escribe $servicios y no $datos['servicios'].
 */
final class Vista
{
    public function __construct(
        private readonly string $directorioVistas,
        private readonly string $rutaBase,
        private readonly ?Idioma $idioma = null,
    ) {
    }

    public function t(string $clave, string ...$argumentos): string
    {
        $texto = $this->idioma?->t($clave) ?? $clave;

        return $argumentos === [] ? $texto : vsprintf($texto, $argumentos);
    }

    public function idiomaActual(): string
    {
        return $this->idioma?->actual() ?? 'es';
    }

    /**
     * Cómo obtener el token contra CSRF, si el módulo de auditorías existe.
     *
     * Se guarda la FORMA de conseguirlo y no el token: pedirlo abre la sesión,
     * y el sitio público no tiene por qué abrir ninguna. Así solo las páginas
     * que de verdad pintan un formulario pagan ese coste.
     *
     * @var (\Closure(): string)|null
     */
    private ?\Closure $generadorToken = null;

    /** La llama public/index.php cuando hay base de datos configurada. */
    public function proveerToken(\Closure $generador): void
    {
        $this->generadorToken = $generador;
    }

    /**
     * Campo oculto que debe llevar TODO formulario que envíe por POST.
     *
     *     <form method="post" action="...">
     *         <?= $vista->campoToken() ?>
     *
     * Sin él, el controlador rechaza el envío. Falla ruidosamente si nadie
     * proveyó el generador, porque un formulario que se pinta sin token es un
     * formulario que el usuario rellenará para nada.
     */
    public function campoToken(): string
    {
        if ($this->generadorToken === null) {
            throw new \RuntimeException(
                'No hay token disponible: esta página necesita el módulo de '
                . 'auditorías (config/base_datos.php).'
            );
        }

        return '<input type="hidden" name="_token" value="'
             . e(($this->generadorToken)()) . '">';
    }

    /**
     * Renderiza una vista suelta (sin diseño envolvente).
     *
     * Las variables internas van con prefijo __ a propósito. extract() vuelca
     * las claves de $datos como variables locales, así que cualquier nombre
     * "normal" usado aquí (como $ruta o $vista) chocaría con un dato de la
     * vista. Ese choque ya causó un error real: el parámetro se llamaba $vista
     * y, por EXTR_SKIP, impedía inyectar el objeto Vista en las plantillas.
     *
     * @param array<string, mixed> $__datos
     */
    public function renderizar(string $__vista, array $__datos = []): string
    {
        $__archivo = $this->directorioVistas . '/' . $__vista . '.php';

        if (!is_file($__archivo)) {
            throw new \RuntimeException("Vista no encontrada: {$__vista} ({$__archivo})");
        }

        extract($__datos, EXTR_SKIP);

        // Se asignan DESPUÉS de extract() para garantizar que siempre existan
        // y que ningún dato de la vista pueda suplantarlas.
        $vista = $this;
        $rutaBase = $this->rutaBase;

        ob_start();
        require $__archivo;

        return (string) ob_get_clean();
    }

    /**
     * Renderiza una vista y la coloca dentro de un diseño de app/Views/layouts.
     *
     * @param array<string, mixed> $datos
     */
    public function renderizarConPlantilla(string $nombreVista, array $datos, string $plantilla): string
    {
        $datos['contenido'] = $this->renderizar($nombreVista, $datos);

        return $this->renderizar('layouts/' . $plantilla, $datos);
    }

    /**
     * Renderiza un componente reutilizable de app/Views/components.
     *
     * @param array<string, mixed> $datos
     */
    public function componente(string $nombre, array $datos = []): string
    {
        return $this->renderizar('components/' . $nombre, $datos);
    }

    /** Construye una URL respetando la subcarpeta donde está instalado el sitio. */
    public function url(string $ruta = ''): string
    {
        return $this->rutaBase . '/' . ltrim($ruta, '/');
    }

    /**
     * Resuelve un destino de navegación, venga de la portada o de otra página.
     *
     * El menú del sitio mezcla anclas de la portada ("#servicios") con rutas
     * propias ("/herramientas/..."). Un ancla suelta solo funciona si el
     * visitante ya está en la portada; anteponerle la raíz la vuelve válida
     * desde cualquier página sin recargar cuando ya se está en ella.
     */
    public function destino(string $ruta): string
    {
        return str_starts_with($ruta, '#')
            ? $this->url() . $ruta
            : $this->url($ruta);
    }
}
