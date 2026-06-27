<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Title;

class TitlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'law_id' => 1,
                'title' => 'TÍTULO I: DISPOSICIONES GENERALES',
            ],
            [
                'law_id' => 1,
                'title' => 'TÍTULO II: ACTORES INVOLUCRADOS EN EL PROCESO DE CONTRATACIÓN PÚBLICA',
            ],
            [
                'law_id' => 1,
                'title' => 'TÍTULO III: FASES DEL PROCESO DE CONTRATACIÓN PÚBLICA',
            ],
            [
                'law_id' => 1,
                'title' => 'TÍTULO IV: VÍAS DE SOLUCIÓN DE CONTROVERSIAS',
            ],
            [
                'law_id' => 1,
                'title' => 'TÍTULO V: INFRACCIONES Y SANCIONES',
            ],
            [
                'law_id' => 1,
                'title' => 'DISPOSICIONES COMPLEMENTARIAS FINALES',
            ],
            [
                'law_id' => 1,
                'title' => 'DISPOSICIONES COMPLEMENTARIAS Y TRANSITORIAS',
            ],
            [
                'law_id' => 1,
                'title' => 'DISPOSICIONES COMPLEMENTARIAS MODIFICATORIAS',
            ],
            [
                'law_id' => 1,
                'title' => 'DISPOSICIÓN COMPLEMENTARIA DEROGATORIA',
            ],
            [
                'law_id' => 1,
                'title' => 'TÍTULO VI: DISPOSICIONES ESPECIALES V2',
            ],
            // Título para el reglamento (law_id = 2) con solo artículos
            [
                'law_id' => 2,
                'title' => 'TÍTULO I: DISPOSICIONES GENERALES V2',
            ],
        ];

        foreach ($data as $item) {
            Title::create($item);
        }
    }
}
