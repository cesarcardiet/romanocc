#!/usr/bin/env bash
set -euo pipefail

# Uso en VPS:
#   bash scripts/vps-setup-firebase.sh /tmp/firebase-key.json
#
# O desde tu PC (PowerShell), sube el JSON y ejecuta:
#   scp "C:\Users\cesar\Downloads\romanocc-9a797-firebase-adminsdk-fbsvc-6a04fa5e51.json" root@66.94.102.53:/tmp/firebase-key.json
#   ssh root@66.94.102.53 "cd /var/www/romanocc && bash scripts/vps-setup-firebase.sh /tmp/firebase-key.json"

APP_DIR="${APP_DIR:-/var/www/romanocc}"
KEY_SRC="${1:-}"

if [[ -z "$KEY_SRC" || ! -f "$KEY_SRC" ]]; then
  echo "ERROR: Indica la ruta al JSON de la cuenta de servicio."
  echo "Ejemplo: bash scripts/vps-setup-firebase.sh /tmp/firebase-key.json"
  exit 1
fi

cd "$APP_DIR"

TARGET_DIR="storage/app/firebase"
TARGET_FILE="$TARGET_DIR/service-account-key.json"

mkdir -p "$TARGET_DIR"
cp "$KEY_SRC" "$TARGET_FILE"
chmod 600 "$TARGET_FILE"
chown www-data:www-data "$TARGET_FILE"

set_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

set_env "SKIP_FIREBASE_BOOT" "false"
set_env "FIREBASE_PROJECT_ID" "romanocc-9a797"
set_env "FIREBASE_PROJECT" "romanocc-9a797"
set_env "FIREBASE_SENDER_ID" "475003090229"

php artisan config:cache

echo ""
echo "Firebase configurado en $TARGET_FILE"
echo "Proyecto: romanocc-9a797"
echo ""
echo "Probar (sustituye USER_ID por un usuario de la app):"
echo "  php artisan notification:debug USER_ID"
