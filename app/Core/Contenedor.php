<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Contratos\RepositorioAuditorias;
use App\Models\Contratos\RepositorioCatalogo;
use App\Models\Contratos\RepositorioContenido;
use App\Models\Contratos\RepositorioInstrumento;

/**
 * Contenedor de dependencias.
 *
 * Guarda los objetos compartidos (la petición, el motor de vistas, los
 * repositorios) y se los entrega a quien los necesite. Su valor real está en
 * los métodos repositorio() e instrumento(): el resto del sistema pide "el
 * repositorio" sin saber si detrás hay un arreglo de PHP o una consulta a
 * Oracle.
 *
 * Cambiar la fuente de datos es cambiar public/index.php, y nada más.
 */
final class Contenedor
{
    public function __construct(
        private readonly Peticion $peticion,
        private readonly Vista $vista,
        private readonly RepositorioContenido $repositorio,
        private readonly RepositorioInstrumento $instrumento,
        private readonly Sesion $sesion,
        private readonly Idioma $idioma,
        /**
         * Opcional a propósito: el módulo de auditorías es el único que exige
         * una base de datos real. Cuando no hay config/base_datos.php llega
         * como null, y el sitio público (portada, instrumento) sigue
         * funcionando igual. Ver el mensaje de auditorias() más abajo.
         */
        private readonly ?RepositorioAuditorias $auditorias = null,
    ) {
    }

    /**
     * Se construye al pedirla y no en el constructor porque depende del
     * repositorio de auditorías, que puede no existir.
     */
    private ?Autenticacion $autenticacion = null;

    public function peticion(): Peticion
    {
        return $this->peticion;
    }

    public function vista(): Vista
    {
        return $this->vista;
    }

    public function repositorio(): RepositorioContenido
    {
        return $this->repositorio;
    }

    public function instrumento(): RepositorioInstrumento
    {
        return $this->instrumento;
    }

    public function sesion(): Sesion
    {
        return $this->sesion;
    }

    public function idioma(): Idioma
    {
        return $this->idioma;
    }

    /**
     * Repositorio de auditorías, usuarios e indicadores.
     *
     * Falla con un mensaje explícito en vez de devolver null: si alguien llega
     * al módulo de auditorías sin base de datos configurada, es mejor leer qué
     * falta que perseguir un "call to a member function on null".
     */
    public function auditorias(): RepositorioAuditorias
    {
        if ($this->auditorias === null) {
            throw new \RuntimeException(
                'El módulo de auditorías necesita una conexión a Oracle. Copie '
                . 'config/base_datos.ejemplo.php a config/base_datos.php y ejecute '
                . 'los scripts de la carpeta Scripts/.'
            );
        }

        return $this->auditorias;
    }

    public function autenticacion(): Autenticacion
    {
        return $this->autenticacion ??= new Autenticacion($this->sesion, $this->auditorias());
    }

    /**
     * El catálogo, con sus operaciones de escritura.
     *
     * Es el mismo objeto que devuelve instrumento(), pero visto por su otra
     * cara: solo la implementación sobre Oracle sabe escribir, y la que lee
     * config/instrumento-bd.php no. De ahí la comprobación en vez de un
     * conversor de tipo a secas.
     */
    public function catalogo(): RepositorioCatalogo
    {
        if (!$this->instrumento instanceof RepositorioCatalogo) {
            throw new \RuntimeException(
                'El catálogo solo se puede administrar con el instrumento en Oracle. '
                . 'Revise "instrumento_en_oracle" en config/base_datos.php.'
            );
        }

        return $this->instrumento;
    }

    public function hayCatalogo(): bool
    {
        return $this->instrumento instanceof RepositorioCatalogo;
    }

    /** ¿Hay base de datos configurada? Lo usan las vistas para ocultar el menú. */
    public function hayAuditorias(): bool
    {
        return $this->auditorias !== null;
    }
}
