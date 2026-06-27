<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Law;
use App\Models\Chapter;
use App\Models\Article;
use Illuminate\Support\Collection;

class Title extends Model
{
    protected $fillable = ['law_id', 'title', 'sort_order'];

    public function scopeOrdered($query)
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('titles', 'sort_order')) {
            return $query->orderBy('sort_order')->orderBy('id');
        }

        return $query->orderBy('id');
    }

    public function law()
    {
        return $this->belongsTo(Law::class, 'law_id', 'id');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class, 'title_id', 'id');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'title_id', 'id');
    }

    /**
     * Obtener artículos organizados por capítulos (reales o virtuales)
     */
    public function getOrganizedArticles(): Collection
    {
        if ($this->chapters->isNotEmpty()) {
            return $this->chapters;
        }
        
        // Crear capítulo virtual si no hay capítulos reales
        return collect([[
            'chapter' => 'ARTÍCULOS',
            'articles' => $this->articles->sortBy('article_number')
        ]]);
    }

    /**
     * Verificar si el título tiene capítulos reales
     */
    public function hasRealChapters(): bool
    {
        return $this->chapters->isNotEmpty();
    }

    /**
     * Obtener todos los artículos del título (incluyendo los de capítulos y subcapítulos)
     */
    public function getAllArticles(): Collection
    {
        $allArticles = collect();

        // Artículos directos del título
        $allArticles = $allArticles->merge($this->articles);

        // Artículos de capítulos
        foreach ($this->chapters as $chapter) {
            $allArticles = $allArticles->merge($chapter->articles);
            
            // Artículos de subcapítulos
            foreach ($chapter->subchapters as $subchapter) {
                $allArticles = $allArticles->merge($subchapter->articles);
            }
        }

        return $allArticles->sortBy('article_number');
    }
}
