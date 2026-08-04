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

# 4. Extensión oci8: el puente entre PHP y Oracle.
#
#    php:8.3-apache no trae ningún driver de Oracle, y a diferencia de MySQL
#    no se instala con docker-php-ext-install: oci8 se compila contra las
#    bibliotecas del Oracle Instant Client, que hay que descargar aparte.
#
#    Son dos paquetes: "basiclite" (las bibliotecas en tiempo de ejecución;
#    la variante lite pesa la mitad que basic porque omite los archivos de
#    idioma que no usamos) y "sdk" (las cabeceras .h que necesita el
#    compilador). El enlace simbólico sin número de versión evita tener que
#    tocar LD_LIBRARY_PATH si mañana se sube el Instant Client.
#
#    Este paso va ANTES del COPY del proyecto a propósito: Docker cachea por
#    capas, y así cambiar una vista no obliga a recompilar la extensión.
ENV ORACLE_HOME=/opt/oracle/instantclient
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient

#    Sobre libaio: Instant Client enlaza contra libaio.so.1. En Debian 13
#    (trixie), que es la base actual de php:8.3-apache, ese paquete pasó a
#    llamarse libaio1t64 por la migración a time_t de 64 bits, y además solo
#    instala libaio.so.1t64 — el nombre sin sufijo ya no existe. De ahí las dos
#    líneas siguientes: se intenta el nombre nuevo con respaldo al viejo (por
#    si alguien construye sobre una base anterior) y se crea el enlace que el
#    cliente espera encontrar.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip curl \
    && { apt-get install -y --no-install-recommends libaio1t64 \
         || apt-get install -y --no-install-recommends libaio1; } \
    && if [ ! -e /usr/lib/x86_64-linux-gnu/libaio.so.1 ]; then \
           ln -s libaio.so.1t64 /usr/lib/x86_64-linux-gnu/libaio.so.1; \
       fi \
    && ldconfig \
    && mkdir -p /opt/oracle \
    && cd /opt/oracle \
    && curl -fsSLO https://download.oracle.com/otn_software/linux/instantclient/2113000/instantclient-basiclite-linux.x64-21.13.0.0.0dbru.zip \
    && curl -fsSLO https://download.oracle.com/otn_software/linux/instantclient/2113000/instantclient-sdk-linux.x64-21.13.0.0.0dbru.zip \
    && unzip -q 'instantclient-*.zip' \
    && rm -f instantclient-*.zip \
    && ln -s /opt/oracle/instantclient_21_13 /opt/oracle/instantclient \
    && echo 'instantclient,/opt/oracle/instantclient' | pecl install oci8 \
    && docker-php-ext-enable oci8 \
    && php -m | grep -q '^oci8$' \
    && apt-get purge -y --auto-remove unzip curl \
    && rm -rf /var/lib/apt/lists/*

# 5. Copiar el proyecto dentro de la imagen.
#
#    En tu máquina, docker-compose.yml monta la carpeta del proyecto como
#    volumen, así que este paso "no se nota": el contenido del volumen tapa
#    lo que sea que haya aquí dentro. Pero un servicio de hosting como Render
#    NO monta ningún volumen: solo construye la imagen y la ejecuta tal cual.
#    Sin este COPY, el contenedor arranca con /var/www/html/public vacío
#    (el error "DocumentRoot does not exist" que se vio en el log de Render).
#    .dockerignore excluye lo que no debe viajar dentro de la imagen (.git, etc).
COPY . /var/www/html

# 6. Puerto dinámico.
#
#    Localmente Apache siempre usa el 80 (docker-compose.yml lo publica en
#    8080). En un servicio de hosting como Render, la plataforma asigna el
#    puerto en tiempo de ejecución mediante $PORT, y puede ser distinto en
#    cada despliegue. docker/iniciar.sh ajusta Apache a ese puerto justo
#    antes de arrancarlo.
COPY docker/iniciar.sh /usr/local/bin/iniciar.sh
RUN chmod +x /usr/local/bin/iniciar.sh

CMD ["/usr/local/bin/iniciar.sh"]
