# Becajo — imagen de Apache + PHP
#
# Se parte de la imagen oficial php:8.3-apache: ya trae Apache 2 y el módulo
# de PHP integrado (mod_php), que es justo la combinación que pedía WAMP,
# solo que sobre Linux en lugar de Windows.
FROM php:8.3-apache

# 1. mod_rewrite: lo usa public/.htaccess para mandar toda petición al
#    front controller (public/index.php). Sin esto, las URLs "bonitas"
#    no funcionarían y solo cargaría la portada.
RUN a2enmod rewrite

# 2. DocumentRoot -> /var/www/html/public
#
#    Por defecto Apache sirve desde /var/www/html, que en este proyecto
#    contendría TODO: app/, config/, README... El único cambio real de
#    seguridad de esta imagen es apuntar la raíz web a la subcarpeta public/,
#    de modo que app/ y config/ (con las credenciales del día que se conecte
#    Oracle) queden fuera del alcance del navegador.
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf

# 3. AllowOverride All: permite que public/.htaccess tenga efecto.
#    Por defecto Apache lo ignora salvo que se le autorice explícitamente.
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# 4. Puerto dinámico.
#
#    Localmente Apache siempre usa el 80 (docker-compose.yml lo publica en
#    8080). En un servicio de hosting como Render, la plataforma asigna el
#    puerto en tiempo de ejecución mediante $PORT, y puede ser distinto en
#    cada despliegue. docker/iniciar.sh ajusta Apache a ese puerto justo
#    antes de arrancarlo.
COPY docker/iniciar.sh /usr/local/bin/iniciar.sh
RUN chmod +x /usr/local/bin/iniciar.sh

CMD ["/usr/local/bin/iniciar.sh"]
