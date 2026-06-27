<?php

namespace Database\Seeders;

use App\Models\Law;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LawsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Law::create([
            'name' => 'Ley General de Contrataciones Públicas',
        ]);

        Law::create([
            'name' => 'Reglamento de la Ley Nº 32069 - Ley General de Contrataciones Públicas',
        ]);
    }
}
