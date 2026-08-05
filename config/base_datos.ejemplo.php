<?php

declare(strict_types=1);

/**
 * Plantilla de configuración de la base de datos.
 *
 * CÓPIESE a config/base_datos.php, que está en .gitignore y por tanto nunca
 * viaja al repositorio. Este archivo (el .ejemplo) sí se versiona: sirve para
 * que cualquiera del equipo sepa qué claves existen sin tener que adivinarlas.
 *
 *     cp config/base_datos.ejemplo.php config/base_datos.php
 *
 * La presencia de config/base_datos.php es lo que ACTIVA la conexión: si el
 * archivo no existe, public/index.php sigue funcionando con los arreglos de
 * PHP y el sitio público carga igual. Así nadie queda bloqueado por no tener
 * Oracle levantado.
 *
 * Los valores reales se leen del entorno (docker-compose.yml los define). Los
 * segundos argumentos de env() son solo un valor por defecto cómodo para
 * desarrollo local; en producción el hosting inyecta las variables.
 */

return [

    // Cadena Easy Connect: host:puerto/servicio.
    // Con docker compose, "oracle" es el nombre del servicio en la red interna.
    'cadena' => env('BD_CADENA', 'oracle:1521/FREEPDB1'),

    'usuario' => env('BD_USUARIO', 'becajo'),

    'clave' => env('BD_CLAVE', 'becajo'),

    // AL32UTF8 es el juego de caracteres de la base. Fijarlo aquí evita que
    // los acentos del catálogo (75 controles en español) lleguen corruptos.
    'charset' => env('BD_CHARSET', 'AL32UTF8'),

    /**
     * ¿El catálogo del instrumento se lee de Oracle o del arreglo de PHP?
     *
     * true  -> RepositorioInstrumentoOracle (tablas dominio/proceso/control).
     *          Requiere haber ejecutado Scripts/01 y Scripts/02.
     * false -> RepositorioInstrumentoArreglo (config/instrumento-bd.php).
     *          Útil para trabajar en las vistas sin depender de la base.
     *
     * Ojo: aunque esté en true, meta(), escala(), marco() y referencias()
     * se siguen leyendo del archivo PHP, porque el esquema de Persona 2 no
     * tiene tablas para esas cuatro secciones. Ver RepositorioInstrumentoOracle.
     */
    'instrumento_en_oracle' => env('BD_INSTRUMENTO', 'si') === 'si',
];
