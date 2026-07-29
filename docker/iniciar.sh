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

PUERTO="${PORT:-80}"

sed -ri "s/^Listen .*/Listen ${PUERTO}/" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PUERTO}>/" /etc/apache2/sites-enabled/000-default.conf

exec apache2-foreground
