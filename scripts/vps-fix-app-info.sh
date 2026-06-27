#!/usr/bin/env bash
set -euo pipefail

cd /var/www/romanocc

mkdir -p storage/app/public/information_apps
cp -f public/terms-and-conditions.pdf public/privacy-policy.pdf storage/app/public/information_apps/

php artisan storage:link 2>/dev/null || true

php artisan db:seed --class=ProductionBootstrapSeeder --force 2>/dev/null || php <<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\InformationApp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

InformationApp::updateOrCreate(
    ['id' => 1],
    [
        'url_terminos_y_condiciones' => 'information_apps/terms-and-conditions.pdf',
        'url_politica_de_privacidad' => 'information_apps/privacy-policy.pdf',
    ]
);

User::updateOrCreate(
    ['email' => 'admin@romanocc.com'],
    [
        'name' => 'Administrador',
        'password' => Hash::make('12345678'),
        'type' => UserType::ADMIN,
        'status' => UserStatus::ACTIVE,
        'email_verified_at' => now(),
    ]
);

echo "Bootstrap OK\n";
PHP

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo ""
echo "Verificación:"
curl -s http://127.0.0.1/api/app-info | head -c 500
echo ""
