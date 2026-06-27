<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LawsSeeder::class,
            TitlesSeeder::class,
            ChaptersSeeder::class,
            SubchaptersSeeder::class,
            ArticlesSeeder::class,
            InformationAppSeeder::class,
        ]);

        User::create([
            'name' => 'Administrador',
            'email' => 'admin@romanocc.com',
            'password' => Hash::make('12345678'),
            'type' => UserType::ADMIN,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
        ]);
    }
}
