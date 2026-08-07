#!/bin/bash
#
# Levanta Becajo de cero: revisa si hay Docker, lo instala si falta,
# y prepara los contenedores y la base de datos.
#
# Uso: ./instalar.sh

set -e

verde() { echo -e "\033[0;32m$1\033[0m"; }
amarillo() { echo -e "\033[0;33m$1\033[0m"; }
rojo() { echo -e "\033[0;31m$1\033[0m"; }

echo "== Instalador de Becajo =="
echo

# ── 1. ¿Hay Docker? ──────────────────────────────────────────────────────

if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
    verde "Docker ya está instalado y corriendo."
else
    amarillo "No se encontró Docker corriendo en este equipo."
    read -p "¿Quiere que lo instale ahora? (s/n) " respuesta

    if [ "$respuesta" != "s" ] && [ "$respuesta" != "S" ]; then
        rojo "Sin Docker no se puede continuar. Instálelo manualmente y vuelva a correr este script."
        exit 1
    fi

    SO="$(uname -s)"

    if [ "$SO" = "Darwin" ]; then
        if command -v brew >/dev/null 2>&1; then
            echo "Instalando Docker Desktop con Homebrew..."
            brew install --cask docker
            amarillo "Abra Docker Desktop desde Aplicaciones, acepte los permisos que pida,"
            amarillo "espere a que diga \"Docker Desktop is running\" y corra este script de nuevo."
        else
            rojo "No encontré Homebrew. Descargue Docker Desktop manualmente desde:"
            echo "  https://www.docker.com/products/docker-desktop/"
            amarillo "Después de instalarlo y abrirlo, corra este script de nuevo."
        fi
        exit 0
    elif [ "$SO" = "Linux" ]; then
        echo "Instalando Docker con el script oficial..."
        curl -fsSL https://get.docker.com | sh
        sudo usermod -aG docker "$USER"
        amarillo "Cierre sesión y vuelva a entrar (o reinicie la terminal) para que el permiso de"
        amarillo "grupo tome efecto, y corra este script de nuevo."
        exit 0
    else
        rojo "Sistema operativo no reconocido ($SO). Instale Docker manualmente:"
        echo "  https://docs.docker.com/get-docker/"
        exit 1
    fi
fi

# ── 2. Archivo de conexión a Oracle ──────────────────────────────────────

if [ ! -f config/base_datos.php ]; then
    cp config/base_datos.ejemplo.php config/base_datos.php
    verde "Creado config/base_datos.php a partir de la plantilla."
else
    verde "config/base_datos.php ya existe."
fi

# ── 3. Contenedores ──────────────────────────────────────────────────────

echo "Levantando los contenedores (la primera vez Oracle tarda unos minutos en arrancar)..."
docker compose up --build -d

echo "Esperando a que Oracle esté listo..."
until [ "$(docker inspect -f '{{.State.Health.Status}}' becajo-oracle 2>/dev/null)" = "healthy" ]; do
    sleep 5
    echo "  ...todavía iniciando"
done
verde "Oracle está listo."

# ── 4. Esquema y datos ───────────────────────────────────────────────────

YA_CARGADO=$(docker exec becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 <<'SQL'
set heading off feedback off
select count(*) from user_tables where table_name = 'DOMINIO';
exit;
SQL
)

if echo "$YA_CARGADO" | grep -q "1"; then
    verde "El esquema ya estaba cargado, no lo vuelvo a correr."
else
    echo "Cargando esquema y datos de prueba..."
    docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/01_esquema.sql > /dev/null
    docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/02_datos_semilla.sql > /dev/null
    docker exec -i becajo-oracle sqlplus -s becajo/becajo@FREEPDB1 < Scripts/03_procedimientos_indicadores.sql > /dev/null
    verde "Esquema y datos de prueba cargados."
fi

echo
verde "Listo. El sitio está en http://localhost:8080"
echo "Cuentas de prueba:"
echo "  Auditor:   ana.alfaro@consultora.example / auditor2026"
echo "  Admin BD:  luis.rojas@empresa.example / adminbd2026"
