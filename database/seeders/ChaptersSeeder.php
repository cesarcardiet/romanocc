<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Chapter;

class ChaptersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'law_id' => 1,
                'title_id' => 2,
                'chapter_number' => 1,
                'chapter_title' => 'CAPÍTULO I: ACTORES DEL PROCESO DE CONTRATACIÓN PÚBLICA',
            ],
            [
                'law_id' => 1,
                'title_id' => 2,
                'chapter_number' => 2,
                'chapter_title' => 'CAPÍTULO II: ORGANISMO ESPECIALIZADO PARA LAS CONTRATACIONES PÚBLICAS EFICIENTES (OECE)',
            ],
            [
                'law_id' => 1,
                'title_id' => 2,
                'chapter_number' => 3,
                'chapter_title' => 'CAPÍTULO III: CENTRAL DE COMPRAS PÚBLICAS - PERÚ COMPRAS',
            ],
            [
                'law_id' => 1,
                'title_id' => 2,
                'chapter_number' => 4,
                'chapter_title' => 'CAPÍTULO IV: ENTIDADES CONTRATANTES',
            ],
            [
                'law_id' => 1,
                'title_id' => 2,  
                'chapter_number' => 5,
                'chapter_title' => 'CAPÍTULO V: PROVEEDORES DE BIENES, SERVICIOS Y OBRAS',
            ],
            [
                'law_id' => 1,
                'title_id' => 3,
                'chapter_number' => 1,
                'chapter_title' => 'CAPÍTULO I: FASE DE ACTUACIONES PREPARATORIAS',
            ],
            [
                'law_id' => 1,
                'title_id' => 3,
                'chapter_number' => 2,
                'chapter_title' => 'CAPÍTULO II: FASE DE SELECCIÓN',
            ],
            [
                'law_id' => 1,
                'title_id' => 3,
                'chapter_number' => 3,
                'chapter_title' => 'CAPÍTULO III: FASE DE EJECUCIÓN CONTRACTUAL',
            ],
            [
                'law_id' => 1,
                'title_id' => 4,
                'chapter_number' => 1,
                'chapter_title' => 'CAPÍTULO I: VÍAS DE SOLUCIÓN DE CONTROVERSIAS',
            ],
            [
                'law_id' => 1,
                'title_id' => 4,
                'chapter_number' => 2,
                'chapter_title' => 'CAPÍTULO II: REGISTROS',
            ],
            [
                'law_id' => 1,
                'title_id' => 5,
                'chapter_number' => 1,
                'chapter_title' => 'CAPÍTULO I: INFRACCIONES Y SANCIONES ADMINISTRATIVAS A PROVEEDORES',
            ],
            [
                'law_id' => 1,
                'title_id' => 5,
                'chapter_number' => 2,
                'chapter_title' => 'CAPÍTULO II: INFRACCIONES Y SANCIONES ADMINISTRATIVAS A INSTITUCIONES ARBITRALES Y CENTROS DE ADMINISTRACIÓN DE JUNTAS DE PREVENCIÓN Y RESOLUCIÓN DE DISPUTAS',
            ],
        ];

        foreach ($data as $item) {
            Chapter::create($item);
        }
    }
}
