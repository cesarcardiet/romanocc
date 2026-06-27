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
        InformationApp::updateOrCreate(
            ['id' => 1], // Buscar por ID
            [
                'url_terminos_y_condiciones' => 'terms-and-conditions.pdf',
                'url_politica_de_privacidad' => 'privacy-policy.pdf',
            ]
        );
    }
}
