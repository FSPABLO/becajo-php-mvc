<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Cargador automático de clases (PSR-4 simplificado).
 *
 * Traduce un nombre de clase con espacio de nombres a una ruta de archivo:
 *
 *     App\Controllers\HomeController  ->  app/Controllers/HomeController.php
 *
 * Gracias a esto no hay un solo `require` de clases en el proyecto: PHP las
 * carga sola la primera vez que se usan. Es lo mismo que hace Composer, escrito
 * a mano para no depender de él en esta entrega.
 */
final class Autoloader
{
    public function __construct(
        private readonly string $prefijo,
        private readonly string $directorioBase,
    ) {
    }

    public function registrar(): void
    {
        spl_autoload_register([$this, 'cargar']);
    }

    private function cargar(string $clase): void
    {
        if (!str_starts_with($clase, $this->prefijo)) {
            return;
        }

        $relativa = substr($clase, strlen($this->prefijo));
        $ruta = $this->directorioBase . '/' . str_replace('\\', '/', $relativa) . '.php';

        if (is_file($ruta)) {
            require_once $ruta;
        }
    }
}
