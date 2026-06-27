<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\InformationApp;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class ProductionBootstrapSeeder extends Seeder
{
    /**
     * Datos mínimos para producción: app-info, PDFs y admin.
     * Idempotente: se puede ejecutar varias veces sin duplicar registros.
     */
    public function run(): void
    {
        $this->seedInformationApp();
        $this->seedAdminUser();
    }

    private function seedInformationApp(): void
    {
        $targetDir = storage_path('app/public/information_apps');
        File::ensureDirectoryExists($targetDir);

        $files = [
            'terms-and-conditions.pdf' => base_path('public/terms-and-conditions.pdf'),
            'privacy-policy.pdf' => base_path('public/privacy-policy.pdf'),
        ];

        foreach ($files as $filename => $source) {
            $destination = $targetDir . DIRECTORY_SEPARATOR . $filename;
            if (File::exists($source) && ! File::exists($destination)) {
                File::copy($source, $destination);
            }
        }

        InformationApp::updateOrCreate(
            ['id' => 1],
            [
                'url_terminos_y_condiciones' => 'information_apps/terms-and-conditions.pdf',
                'url_politica_de_privacidad' => 'information_apps/privacy-policy.pdf',
            ]
        );
    }

    private function seedAdminUser(): void
    {
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
    }
}
