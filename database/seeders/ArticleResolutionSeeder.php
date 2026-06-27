<?php

namespace Database\Seeders;

use App\Models\ArticleResolution;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleResolutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener un artículo y usuario para la prueba
        $article = Article::first();
        $user = User::first();

        if ($article && $user) {
            ArticleResolution::create([
                'article_id' => $article->id,
                'user_id' => $user->id,
                'name' => '01K3MP3KR852FPA595V508DWFQ.pdf',
                'url' => null, // Enlace opcional
                'url_pdf' => '01K3MP3KR852FPA595V508DWFQ.pdf', // Archivo PDF
            ]);
        }
    }
}
