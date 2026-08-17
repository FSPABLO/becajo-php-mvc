<?php

declare(strict_types=1);

/**
 * Plantilla de configuración para el motor PostgreSQL.
 *
 * Ruta ALTERNATIVA a base_datos.ejemplo.php (Oracle) — misma idea, misma
 * ubicación final:
 *
 *     cp config/base_datos.postgres.ejemplo.php config/base_datos.php
 *
 * Pensada para Azure: Azure App Service (contenedor) + Azure Database for
 * PostgreSQL flexible server. En local, docker-compose.postgres.yml trae su
 * propio Postgres en un contenedor con esos mismos nombres de variable.
 *
 * La diferencia con la plantilla de Oracle no es solo el motor: la forma de
 * 'cadena' cambia. Oracle usa Easy Connect (host:puerto/servicio); PDO_PGSQL
 * usa pares clave=valor (host=... port=... dbname=...), que es lo que separa
 * BaseDatosPostgres::conexion() al construir el DSN "pgsql:...".
 */

return [

    'motor' => env('BD_MOTOR', 'postgres'),

    // DSN de PDO_PGSQL, sin el prefijo "pgsql:" (BaseDatosPostgres lo agrega).
    // "postgres" es el nombre del servicio en docker-compose.postgres.yml;
    // en Azure se reemplaza por el host del flexible server
    // (algo así como midb.postgres.database.azure.com) vía la variable de
    // entorno BD_CADENA, sin tocar este archivo.
    'cadena' => env(
        'BD_CADENA',
        'host=postgres port=5432 dbname=becajo sslmode=prefer'
    ),

    'usuario' => env('BD_USUARIO', 'becajo'),

    'clave' => env('BD_CLAVE', 'becajo'),

    /**
     * ¿El catálogo del instrumento se lee de Postgres o del arreglo de PHP?
     * Mismo significado que en la plantilla de Oracle — ver
     * RepositorioInstrumentoPostgres.
     */
    'instrumento_en_oracle' => env('BD_INSTRUMENTO', 'si') === 'si',
];
