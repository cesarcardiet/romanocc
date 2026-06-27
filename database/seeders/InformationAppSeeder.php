<?php

namespace Database\Seeders;

use App\Models\InformationApp;
use Illuminate\Database\Seeder;

class InformationAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear o actualizar la información de la aplicación
        $targetDir = storage_path('app/public/information_apps');
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $files = [
            'terms-and-conditions.pdf' => base_path('public/terms-and-conditions.pdf'),
            'privacy-policy.pdf' => base_path('public/privacy-policy.pdf'),
        ];

        foreach ($files as $filename => $source) {
            $destination = $targetDir . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($source) && ! file_exists($destination)) {
                copy($source, $destination);
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
}
