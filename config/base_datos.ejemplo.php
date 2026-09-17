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
 * la base levantada.
 *
 * Los valores reales se leen del entorno (docker-compose.yml los define para
 * desarrollo local; en Azure/producción el hosting inyecta las variables).
 * Los segundos argumentos de env() son solo un valor por defecto cómodo.
 *
 * DOS MOTORES
 * ------------
 * La clave 'motor' decide qué mitad de la capa de datos construye
 * public/index.php: 'oracle' (valor por omisión, para no romper una
 * config/base_datos.php vieja sin esa clave) o 'postgres'. Es la única
 * decisión que hay que tomar aquí — Contenedor, los controladores y las
 * vistas reciben la interfaz (RepositorioAuditorias / RepositorioCatalogo) y
 * no se enteran de cuál es.
 *
 * Para desplegar en Azure Database for PostgreSQL, use el bloque 'postgres'
 * de abajo. El formato de 'cadena' cambia por completo entre los dos
 * motores: Oracle usa Easy Connect (host:puerto/servicio) y Postgres usa
 * pares clave=valor de libpq (host=... port=... dbname=... sslmode=...).
 */

return [

    'motor' => env('BD_MOTOR', 'postgres'),

    // ── PostgreSQL (Azure Database for PostgreSQL) ──────────────────────────
    //
    // Formato de conexión de PDO_PGSQL/libpq: pares clave=valor separados por
    // espacio. sslmode=require es el mínimo recomendado por Azure para una
    // conexión desde fuera de su red virtual.
    //
    //   'cadena' => env('BD_CADENA', 'host=mi-servidor.postgres.database.azure.com port=5432 dbname=rivendel sslmode=require'),
    //
    // Con docker compose en local, el host es el nombre del servicio:
    'cadena' => env('BD_CADENA', 'host=localhost port=5432 dbname=rivendel sslmode=disable'),

    // ── Oracle (Instant Client / oci8) ───────────────────────────────────────
    //
    // Cadena Easy Connect: host:puerto/servicio. Con docker compose, "oracle"
    // es el nombre del servicio en la red interna. Se usa solo cuando
    // 'motor' => 'oracle'.
    //
    //   'cadena' => env('BD_CADENA', 'oracle:1521/FREEPDB1'),

    'usuario' => env('BD_USUARIO', 'becajo'),

    'clave' => env('BD_CLAVE', 'becajo'),

    // AL32UTF8 en Oracle, UTF8 en Postgres. Fijarlo aquí evita que los
    // acentos del catálogo (75 controles en español) lleguen corruptos.
    // Bajo 'motor' => 'postgres' esta clave no se usa: BaseDatosPostgres
    // deja que PDO_PGSQL negocie el charset de la conexión con el servidor.
    'charset' => env('BD_CHARSET', 'UTF8'),

    /**
     * ¿El catálogo del instrumento se lee de la base o del arreglo de PHP?
     * Mismo nombre de clave en los dos motores: significa "el catálogo vive
     * en la base", sin importar cuál.
     *
     * true  -> RepositorioInstrumentoOracle / RepositorioInstrumentoPostgres
     *          (tablas dominio/proceso/control). Requiere haber ejecutado
     *          Scripts/01 y Scripts/02 (Oracle) o Scripts/postgres/01 y 02
     *          (Postgres).
     * false -> RepositorioInstrumentoArreglo (config/instrumento-bd.php).
     *          Útil para trabajar en las vistas sin depender de la base.
     *
     * Ojo: aunque esté en true, meta(), escala(), marco() y referencias()
     * se siguen leyendo del archivo PHP en los dos motores, porque ese
     * esquema no tiene tablas para esas cuatro secciones. Ver
     * RepositorioInstrumentoOracle / RepositorioInstrumentoPostgres.
     */
    'instrumento_en_oracle' => env('BD_INSTRUMENTO', 'si') === 'si',
];
