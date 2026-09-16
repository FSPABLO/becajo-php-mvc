#!/bin/sh
#
# Programador del agente de monitoreo (parte 2, §4.1 del plan).
#
# No hay cron en la imagen base, y agregarlo habría significado un segundo
# proceso corriendo dentro del mismo contenedor -mala práctica: un
# contenedor hace una sola cosa. Un bucle con sleep cumple lo mismo con
# menos piezas móviles, y es justo lo que pide el plan: "un servicio más en
# docker-compose.yml, que se versiona y se puede demostrar; el programador
# de tareas de Windows no cumple ninguna de las dos cosas".
set -e

# Mismo criterio que docker/iniciar.sh: sin estos dos archivos (gitignored)
# nadie del equipo queda bloqueado por no haberlos copiado a mano. Las
# credenciales reales las ponen las variables de entorno de abajo.
if [ ! -f /var/www/html/config/base_datos.php ]; then
    cp /var/www/html/config/base_datos.ejemplo.php /var/www/html/config/base_datos.php
fi

if [ ! -f /var/www/html/config/monitor.php ]; then
    cp /var/www/html/config/monitor.ejemplo.php /var/www/html/config/monitor.php
fi

INTERVALO_MINUTOS="${BD_MONITOR_INTERVALO_MINUTOS:-5}"
INTERVALO_SEGUNDOS=$((INTERVALO_MINUTOS * 60))

echo "Agente de monitoreo arrancado. Intervalo: ${INTERVALO_MINUTOS} min."

while true; do
    php /var/www/html/bin/monitor.php
    sleep "$INTERVALO_SEGUNDOS"
done
