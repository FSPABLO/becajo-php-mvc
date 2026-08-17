#!/bin/sh
#
# Script de arranque del contenedor.
#
# En tu Mac, Apache escucha siempre en el puerto 80 (docker-compose.yml lo
# publica en 8080). En un servicio de hosting como Render, el puerto lo asigna
# la plataforma en tiempo de ejecución mediante la variable de entorno $PORT
# — cada despliegue puede tocarle un número distinto. Este script ajusta la
# configuración de Apache a ese puerto justo antes de arrancarlo, usando 80
# como respaldo cuando la variable no exista (tu entorno local).
set -e

# config/base_datos.php no se sube al repo (trae la forma de leer las
# credenciales, no las credenciales en sí), así que en un despliegue nuevo
# hay que crearlo aquí. Los valores reales los pone el hosting (Render,
# Azure...) como variables de entorno (BD_CADENA, BD_USUARIO, BD_CLAVE); este
# archivo solo activa el módulo de auditorías.
#
# BD_MOTOR decide qué plantilla copiar: "postgres" trae la de Azure Database
# for PostgreSQL, cualquier otro valor (o ausente) mantiene el respaldo de
# siempre a Oracle, para no cambiar el comportamiento de Render.
if [ ! -f /var/www/html/config/base_datos.php ]; then
    if [ "${BD_MOTOR:-oracle}" = "postgres" ]; then
        cp /var/www/html/config/base_datos.postgres.ejemplo.php /var/www/html/config/base_datos.php
    else
        cp /var/www/html/config/base_datos.ejemplo.php /var/www/html/config/base_datos.php
    fi
fi

PUERTO="${PORT:-80}"

sed -ri "s/^Listen .*/Listen ${PUERTO}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PUERTO}>/" /etc/apache2/sites-enabled/000-default.conf

exec apache2-foreground
