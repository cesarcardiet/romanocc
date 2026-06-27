<?php

namespace App\Services;

use App\Models\Title;
use Illuminate\Support\Collection;

class LawStructureService
{
    /**
     * Formatear un título con su estructura de capítulos y artículos
     * Maneja tanto capítulos reales como capítulos virtuales
     */
    public function formatTitleWithChapters(Title $title, bool $includeContent = false): array
    {
        $chapters = collect();

        // Si el título tiene capítulos reales, procesarlos
        if ($title->chapters->isNotEmpty()) {
            $chapters = $title->chapters->map(function ($chapter) use ($includeContent) {
                return $this->formatChapter($chapter, $includeContent);
            });
        } else {
            // Si no tiene capítulos, crear un capítulo virtual con los artículos del título
            $chapters = $this->createVirtualChapter($title, $includeContent);
        }

        return [
            'title' => (string) $title->title,
            'chapters' => $chapters->values(),
        ];
    }

    /**
     * Formatear un capítulo con sus artículos y subcapítulos
     */
    private function formatChapter($chapter, bool $includeContent = false): array
    {
        // Artículos directos del capítulo
        $articles = $chapter->articles->map(function ($article) use ($includeContent) {
            return $this->formatArticle($article, $includeContent);
        })->sortBy(function ($article) {
            return (int) $article['number'];
        })->values();

        // Procesar subcapítulos
        $subchapters = $chapter->subchapters->map(function ($subchapter) use ($includeContent) {
            return $this->formatSubchapter($subchapter, $includeContent);
        });

        return [
            'chapter' => $chapter->chapter_title ?: "CAPÍTULO " . $chapter->chapter_number,
            'articles' => $articles->toArray(),
            'subchapters' => $subchapters->toArray(),
        ];
    }

    /**
     * Formatear un subcapítulo con sus artículos
     */
    private function formatSubchapter($subchapter, bool $includeContent = false): array
    {
        $subchapterArticles = $subchapter->articles->map(function ($article) use ($includeContent) {
            return $this->formatArticle($article, $includeContent);
        })->sortBy(function ($article) {
            return (int) $article['number'];
        })->values();

        return [
            'subchapter' => $subchapter->subchapter_title ?: "SUBCAPÍTULO " . $subchapter->subchapter_number,
            'articles' => $subchapterArticles->toArray(),
        ];
    }

    /**
     * Formatear un artículo individual
     * Cuando $includeContent es true: incluye content, has_attachments y attachments_count
     */
    private function formatArticle($article, bool $includeContent = false): array
    {
        $articleData = [
            'id' => $article->id,
            'number' => $article->article_number,
            'title' => $article->article_title,
        ];

        if ($includeContent) {
            $articleData['content'] = $article->article_content ?? '';

            // Usar atributos withCount para evitar N+1 (se cargan en LawController::index)
            $resolutions = (int) ($article->resolutions_count ?? 0);
            $videos = (int) ($article->videos_count ?? 0);
            $files = (int) ($article->files_count ?? 0);
            $opinions = (int) ($article->opinions_count ?? 0);

            $hasAttachments = ($resolutions + $videos + $files + $opinions) > 0;
            $articleData['has_attachments'] = $hasAttachments;
            $articleData['attachments_count'] = $hasAttachments
                ? [
                    'resolutions' => (int) $resolutions,
                    'videos' => (int) $videos,
                    'files' => (int) $files,
                    'opinions' => (int) $opinions,
                ]
                : null;
        }

        return $articleData;
    }

    /**
     * Crear un capítulo virtual para títulos sin capítulos
     */
    private function createVirtualChapter(Title $title, bool $includeContent = false): Collection
    {
        if ($title->articles->isEmpty()) {
            return collect();
        }

        $articles = $title->articles->map(function ($article) use ($includeContent) {
            return $this->formatArticle($article, $includeContent);
        })->sortBy(function ($article) {
            return (int) $article['number'];
        })->values();

        return collect([[
            'chapter' => 'ARTÍCULOS',
            'articles' => $articles->toArray(),
        ]]);
    }

    /**
     * Formatear capítulo para el método show (incluye contenido siempre)
     */
    public function formatChapterForShow($chapter): array
    {
        $articles = collect();

        // Artículos directos del capítulo
        $articles = $articles->merge($chapter->articles->map(function ($article) {
            return [
                'number' => $article->article_number,
                'title' => $article->article_title,
                'content' => $article->article_content,
            ];
        }));

        // Artículos de subcapítulos
        foreach ($chapter->subchapters as $subchapter) {
            foreach ($subchapter->articles as $article) {
                $articles->push([
                    'number' => $article->article_number,
                    'title' => $article->article_title,
                    'content' => $article->article_content,
                ]);
            }
        }

        // Ordenar por número de forma numérica
        $articles = $articles->sortBy(function ($article) {
            return (int) $article['number'];
        })->values();

        return [
            'chapter' => $chapter->chapter_title ?: "CAPÍTULO " . $chapter->chapter_number,
            'articles' => $articles->toArray(),
        ];
    }

    /**
     * Crear capítulo virtual para el método show
     */
    public function createVirtualChapterForShow(Title $title): Collection
    {
        if ($title->articles->isEmpty()) {
            return collect();
        }

        $articles = $title->articles->map(function ($article) {
            return [
                'number' => $article->article_number,
                'title' => $article->article_title,
                'content' => $article->article_content,
            ];
        })->sortBy(function ($article) {
            return (int) $article['number'];
        })->values();

        return collect([[
            'chapter' => 'ARTÍCULOS',
            'articles' => $articles->toArray(),
        ]]);
    }

    /**
     * Formatear título para búsqueda (incluye fragmentos de contenido)
     */
    public function formatTitleForSearch(Title $title, string $query): array
    {
        $formattedChapters = [];
        
        foreach ($title->chapters as $chapter) {
            $chapterArticles = collect();
            $formattedSubchapters = [];
            
            // Artículos directos del capítulo
            foreach ($chapter->articles as $article) {
                $contentFragment = $this->extractTextFragment($article->article_content, $query);
                if (!empty($contentFragment)) {
                    $chapterArticles->push([
                        'number' => $article->article_number,
                        'title' => $article->article_title,
                        'content' => $article->article_content,
                        'content_fragment' => $contentFragment,
                    ]);
                }
            }

            // Procesar subcapítulos
            foreach ($chapter->subchapters as $subchapter) {
                $subchapterArticles = collect();
                
                foreach ($subchapter->articles as $article) {
                    $contentFragment = $this->extractTextFragment($article->article_content, $query);
                    if (!empty($contentFragment)) {
                        $subchapterArticles->push([
                            'number' => $article->article_number,
                            'title' => $article->article_title,
                            'content' => $article->article_content,
                            'content_fragment' => $contentFragment,
                        ]);
                    }
                }
                
                // Solo incluir subcapítulos que tengan artículos con coincidencias
                if ($subchapterArticles->count() > 0) {
                    $formattedSubchapters[] = [
                        'subchapter' => $subchapter->subchapter_title ?: "SUBCAPÍTULO " . $subchapter->subchapter_number,
                        'articles' => $subchapterArticles->sortBy(function ($article) {
                            return (int) $article['number'];
                        })->values()->toArray(),
                    ];
                }
            }

            // Solo incluir capítulos que tengan artículos o subcapítulos con coincidencias
            if ($chapterArticles->count() > 0 || count($formattedSubchapters) > 0) {
                $formattedChapters[] = [
                    'chapter' => $chapter->chapter_title ?: "CAPÍTULO " . $chapter->chapter_number,
                        'articles' => $chapterArticles->sortBy(function ($article) {
                            return (int) $article['number'];
                        })->values()->toArray(),
                    'subchapters' => $formattedSubchapters,
                ];
            }
        }

        return [
            'title' => (string) $title->title,
            'chapters' => $formattedChapters,
        ];
    }

    /**
     * Extraer fragmento de texto que contiene la búsqueda
     */
    private function extractTextFragment($content, $query, $maxWords = 30): string
    {
        $lowerContent = strtolower($content);
        $lowerQuery = strtolower($query);
        $normalizedContent = strtolower($this->normalizeText($content));
        $normalizedQuery = strtolower($this->normalizeText($query));
        
        // Buscar primero la coincidencia exacta
        $position = strpos($lowerContent, $lowerQuery);
        
        // Si no encuentra coincidencia exacta, buscar con texto normalizado
        if ($position === false) {
            $position = strpos($normalizedContent, $normalizedQuery);
        }
        
        if ($position === false) {
            return '';
        }
        
        // Calcular posición de inicio y fin del fragmento
        $contextSize = 150;
        $start = max(0, $position - $contextSize);
        $end = min(strlen($content), $position + strlen($query) + $contextSize);
        
        $fragment = substr($content, $start, $end - $start);
        
        // Asegurar que el fragmento comience y termine en palabras completas
        $words = explode(' ', $fragment);
        if (count($words) > $maxWords) {
            $words = array_slice($words, 0, $maxWords);
        }
        
        $fragment = implode(' ', $words);
        
        // Agregar puntos suspensivos si es necesario
        if ($start > 0) {
            $fragment = '...' . $fragment;
        }
        if ($end < strlen($content)) {
            $fragment = $fragment . '...';
        }
        
        return trim($fragment);
    }

    /**
     * Normalizar texto removiendo tildes y caracteres especiales
     */
    private function normalizeText($text): string
    {
        $replacements = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N',
            'ü' => 'u', 'Ü' => 'U'
        ];
        
        return strtr($text, $replacements);
    }
}
