<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleFile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ArticleFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener algunos artículos existentes para asociar archivos
        $articles = Article::limit(3)->get();
        
        if ($articles->isEmpty()) {
            $this->command->info('No hay artículos disponibles para asociar archivos. Ejecuta primero los seeders de artículos.');
            return;
        }

        // Crear archivos de ejemplo para cada artículo
        foreach ($articles as $article) {
            // Simular algunos archivos de ejemplo
            $exampleFiles = [
                'article-files/documento_ejemplo.pdf',
                'article-files/imagen_referencia.jpg',
                'article-files/hoja_calculo.xlsx'
            ];

            // Crear 1-2 archivos aleatorios por artículo
            $filesToCreate = array_slice($exampleFiles, 0, rand(1, 2));
            
            foreach ($filesToCreate as $filePath) {
                ArticleFile::create([
                    'article_id' => $article->id,
                    'file_path' => $filePath
                ]);
            }
        }

        $this->command->info('Archivos de ejemplo creados exitosamente para los artículos.');
    }
}
