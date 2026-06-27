<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subchapter;

class SubchaptersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'law_id' => 1,
                'chapter_id' => 3,
                'subchapter_number' => 1,
                'subchapter_title' => 'SUBCAPÍTULO I: Tribunal de Contrataciones Públicas',
            ],
            [
                'law_id' => 1,
                'chapter_id' => 9,
                'subchapter_number' => 1,
                'subchapter_title' => 'SUBCAPÍTULO I: Conciliación',
            ],
            [
                'law_id' => 1,
                'chapter_id' => 9,
                'subchapter_number' => 2,
                'subchapter_title' => 'SUBCAPÍTULO II: Arbitraje',
            ],
        ];

        foreach ($data as $item) {
            Subchapter::create($item);
        }
    }
}
