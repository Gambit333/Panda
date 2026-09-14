#!/usr/bin/env bash
#
# Provisiona un servidor Ubuntu 24.04 (Google Cloud e2-micro, siempre gratis)
# para ejecutar el sistema de nómina Laravel 12 conectado a Supabase (Postgres).
#
# Uso:
#   bash server-setup.sh
#
# Variables que puedes pasar al ejecutarlo (las de DB son OBLIGATORIAS):
#   REPO_URL            URL git del proyecto (ej. git@github.com:usuario/nomina.git)
#                       Si se deja vacía, el código debe copiarse a /var/www/nomina
#   SERVER_APP_KEY      APP_KEY de Laravel (si vacía, se genera)
#   SERVER_DB_HOST      host del pooler Supabase (ej. aws-0-us-west-2.pooler.supabase.com)
#   SERVER_DB_DATABASE  nombre de bd (postgres)
#   SERVER_DB_USERNAME  usuario pooler (ej. postgres.<project-ref>)
#   SERVER_DB_PASSWORD  contraseña
#
# Ejemplo:
#   SERVER_DB_HOST='aws-0-us-west-2.pooler.supabase.com' \
#   SERVER_DB_USERNAME='postgres.<project-ref>' \
#   SERVER_DB_PASSWORD='<tu-clave-de-supabase>' \
#   bash server-setup.sh

set -euo pipefail

APP_DIR="/var/www/nomina"
REPO_URL="${REPO_URL:-}"
SERVER_APP_KEY="${SERVER_APP_KEY:-}"
SERVER_DB_HOST="${SERVER_DB_HOST:-aws-0-<region>.pooler.supabase.com}"
SERVER_DB_DATABASE="${SERVER_DB_DATABASE:-postgres}"
SERVER_DB_USERNAME="${SERVER_DB_USERNAME:-}"
SERVER_DB_PASSWORD="${SERVER_DB_PASSWORD:-}"
SERVER_DB_PORT="${SERVER_DB_PORT:-6543}"

if [ -z "$SERVER_DB_HOST" ] || [ -z "$SERVER_DB_USERNAME" ] || [ -z "$SERVER_DB_PASSWORD" ]; then
    echo "[ERROR] Faltan credenciales DB (SERVER_DB_HOST, SERVER_DB_USERNAME, SERVER_DB_PASSWORD)."
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "[1/7] Actualizando sistema e instalando dependencias..."
apt-get update -y -qq
apt-get install -y -qq nginx php-fpm php-cli php-common php-pgsql php-mbstring \
    php-xml php-curl php-zip php-json php-bcmath php-intl php-sqlite3 \
    unzip curl git ca-certificates

echo "[2/7] Instalando Composer..."
if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "[3/7] Obteniendo el código del proyecto..."
if [ -n "$REPO_URL" ]; then
    if [ ! -d "$APP_DIR/.git" ]; then
        git clone "$REPO_URL" "$APP_DIR"
    else
        git -C "$APP_DIR" pull
    fi
else
    mkdir -p "$APP_DIR"
    echo "  (REPO_URL vacío) Copia tu proyecto a $APP_DIR, p. ej.:"
    echo "  rsync -av --exclude=.env --exclude=vendor --exclude=.git ./ usuario@IP:/var/www/nomina/"
fi

cd "$APP_DIR"

echo "[4/7] Configurando .env y dependencias..."
if [ ! -f .env ]; then
    cat > .env <<EOF
APP_NAME=Nomina
APP_ENV=production
APP_KEY=${SERVER_APP_KEY}
APP_DEBUG=false
APP_URL=http://localhost

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_ES
APP_MAINTENANCE_DRIVER=file

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_HOST=${SERVER_DB_HOST}
DB_PORT=${SERVER_DB_PORT}
DB_DATABASE=${SERVER_DB_DATABASE}
DB_USERNAME=${SERVER_DB_USERNAME}
DB_PASSWORD=${SERVER_DB_PASSWORD}
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@localhost"
MAIL_FROM_NAME="\${APP_NAME}"
EOF
    grep -q '^APP_KEY=.*[^\s]' .env || php artisan key:generate
    echo "  .env creado. Corrige APP_URL con tu dominio/IP cuando lo sepas."
fi

echo "[5/7] Instalando dependencias de PHP..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "  Permisos de almacenamiento..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan storage:link || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "[6/7] Configurando Nginx..."
PHP_FPM_USER=$(grep '^user' /etc/php/*/fpm/pool.d/www.conf | head -1 | awk '{print $3}')

cat > /etc/nginx/sites-available/nomina <<EOF
server {
    listen 80;
    server_name _;
    root ${APP_DIR}/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# unix socket correcto según la versión de php-fpm instalada
PHPVER=$(ls /etc/php | head -1)
sed -i "s#unix:/var/run/php/php-fpm.sock#unix:/var/run/php/php${PHPVER}-fpm.sock#" /etc/nginx/sites-available/nomina

ln -sf /etc/nginx/sites-available/nomina /etc/nginx/sites-enabled/nomina
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "[7/7] Arrancando servicios..."
systemctl enable php${PHPVER}-fpm
systemctl restart php${PHPVER}-fpm
systemctl reload nginx

echo ""
echo "================================================"
echo "  Instalación completada."
echo "  - App:            $APP_DIR"
echo "  - PHP-FPM:        php${PHPVER}"
echo "  - Revisa antes:   nano $APP_DIR/.env   (pon APP_URL)"
echo "  - Migra si faltan tablas: cd $APP_DIR && php artisan migrate --force"
echo "  - Tu IP pública:  $(curl -s ifconfig.me || echo '?')"
echo "  Nota: e2-micro: firewall GCP debe permitir 80/443."
echo "================================================"