<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controlador;

/** Cambia el idioma de la interfaz y vuelve a donde estaba el visitante. */
final class IdiomaController extends Controlador
{
    public function cambiar(): void
    {
        $codigo = (string) $this->peticion()->entrada('codigo', '');

        $this->idioma()->cambiar($codigo);

        $destino = (string) $this->peticion()->entrada('destino', '/');

        // Solo se sigue una ruta interna: un valor ajeno en "destino" no debe
        // poder mandar a nadie fuera del sitio.
        if (!str_starts_with($destino, '/')) {
            $destino = '/';
        }

        $this->redirigir($destino);
    }
}
