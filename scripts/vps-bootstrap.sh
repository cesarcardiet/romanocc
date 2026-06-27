#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/romanocc"
REPO="https://github.com/cesarcardiet/romanocc.git"
DB_NAME="romanocc"
DB_USER="romanocc"
DB_PASS="${DB_PASS:-Romanocc2026!}"

export DEBIAN_FRONTEND=noninteractive

echo "=== 1. Paquetes ==="
apt-get update
apt-get install -y nginx mysql-server git unzip curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl

if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "=== 2. MySQL ==="
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "=== 3. Código ==="
mkdir -p /var/www
if [ -d "$APP_DIR/.git" ]; then
  cd "$APP_DIR" && git pull origin main
else
  git clone "$REPO" "$APP_DIR"
fi

cd "$APP_DIR"
composer install --no-dev --optimize-autoloader --no-interaction

if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --force
fi

grep -q "^APP_URL=" .env && sed -i "s|^APP_URL=.*|APP_URL=http://66.94.102.53|" .env || echo "APP_URL=http://66.94.102.53" >> .env
grep -q "^APP_ENV=" .env && sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env || echo "APP_ENV=production" >> .env
grep -q "^APP_DEBUG=" .env && sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env || echo "APP_DEBUG=false" >> .env
grep -q "^DB_CONNECTION=" .env && sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env || echo "DB_CONNECTION=mysql" >> .env
grep -q "^DB_HOST=" .env && sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" .env || echo "DB_HOST=127.0.0.1" >> .env
grep -q "^DB_DATABASE=" .env && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" .env || echo "DB_DATABASE=${DB_NAME}" >> .env
grep -q "^DB_USERNAME=" .env && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" .env || echo "DB_USERNAME=${DB_USER}" >> .env
grep -q "^DB_PASSWORD=" .env && sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env || echo "DB_PASSWORD=${DB_PASS}" >> .env
grep -q "^FILESYSTEM_DISK=" .env && sed -i "s|^FILESYSTEM_DISK=.*|FILESYSTEM_DISK=local|" .env || echo "FILESYSTEM_DISK=local" >> .env
grep -q "^FILAMENT_UPLOAD_DISK=" .env && sed -i "s|^FILAMENT_UPLOAD_DISK=.*|FILAMENT_UPLOAD_DISK=public|" .env || echo "FILAMENT_UPLOAD_DISK=public" >> .env
grep -q "^SKIP_FIREBASE_BOOT=" .env && sed -i "s|^SKIP_FIREBASE_BOOT=.*|SKIP_FIREBASE_BOOT=true|" .env || echo "SKIP_FIREBASE_BOOT=true" >> .env

echo "=== 4. Laravel ==="
php artisan migrate --force
php artisan legal:import-base --force
php artisan storage:link 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== 5. Nginx ==="
cat > /etc/nginx/sites-available/romanocc <<'NGINX'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name 66.94.102.53 dashboard.romanocc.com;
    root /var/www/romanocc/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/romanocc /etc/nginx/sites-enabled/romanocc
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
systemctl enable php8.3-fpm nginx mysql

echo ""
echo "LISTO"
echo "  Panel: http://66.94.102.53/login"
echo "  API:   http://66.94.102.53/api/app-info"
echo "  DB user: ${DB_USER} / ${DB_PASS}"
