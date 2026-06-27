<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class CleanArticleContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-article-content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia las etiquetas [cite...] de los artículos existentes';

    public function handle(): void
    {
        $articles = Article::where('article_content', 'like', '%[cite%')->get();
        
        $count = 0;
        foreach ($articles as $article) {
            $original = $article->article_content;
            
            // Reutiliza la lógica del mutator
            $cleaned = preg_replace('/\[cite[^\]]*\]/i', '', $original);
            $cleaned = preg_replace('/\s+/', ' ', $cleaned);
            $cleaned = trim($cleaned);
            
            if ($original !== $cleaned) {
                $article->article_content = $cleaned;
                $article->saveQuietly(); // Evita disparar eventos
                $count++;
            }
        }
        
        $this->info("Se limpiaron {$count} artículos.");
    }
}
