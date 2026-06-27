<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Chapter;
use App\Models\Law;
use App\Models\Subchapter;
use App\Models\Title;

class LawHierarchyService
{
    public function findLaw(int $id, ?string $type = null): ?Law
    {
        $query = Law::with($this->hierarchyRelations());

        if ($type) {
            $query->where('type', $type);
        }

        return $query->find($id);
    }

    public function buildIndex(Law $law): array
    {
        return $this->build($law, false);
    }

    public function buildWithContent(Law $law): array
    {
        return $this->build($law, true);
    }

    private function build(Law $law, bool $includeContent): array
    {
        $data = [
            'id' => $law->id,
            'name' => $law->name,
            'type' => $law->type,
            'titles' => LegalOrderService::sortTitles($law->titles)
                ->map(fn (Title $title) => $this->formatTitle($title, $includeContent))
                ->values()
                ->all(),
        ];

        return LegalOrderService::sortFormattedHierarchy($data);
    }

    private function hierarchyRelations(): array
    {
        $articleLoader = fn ($query) => $query->withCount(['opinions', 'resolutions', 'videos', 'files']);

        return [
            'titles' => function ($query) use ($articleLoader) {
                $query->with([
                    'articles' => $articleLoader,
                    'chapters' => function ($chaptersQuery) use ($articleLoader) {
                        $chaptersQuery->with([
                            'articles' => $articleLoader,
                            'subchapters' => function ($subchaptersQuery) use ($articleLoader) {
                                $subchaptersQuery->with(['articles' => $articleLoader]);
                            },
                        ]);
                    },
                ]);
            },
        ];
    }

    private function formatTitle(Title $title, bool $includeContent): array
    {
        $data = [
            'id' => $title->id,
            'name' => (string) $title->title,
        ];

        $titleDirectArticles = LegalOrderService::sortArticles(
            $title->articles->filter(fn (Article $article) => $article->chapter_id === null)
        );

        if ($title->chapters->isNotEmpty()) {
            $data['chapters'] = LegalOrderService::sortChapters($title->chapters)
                ->map(fn (Chapter $chapter) => $this->formatChapter($chapter, $includeContent))
                ->values()
                ->all();

            if ($titleDirectArticles->isNotEmpty()) {
                $data['articles'] = $this->formatArticles($titleDirectArticles, $includeContent);
            }
        } else {
            $data['articles'] = $this->formatArticles(
                LegalOrderService::sortArticles($title->articles),
                $includeContent
            );
        }

        return $data;
    }

    private function formatChapter(Chapter $chapter, bool $includeContent): array
    {
        $data = [
            'id' => $chapter->id,
            'name' => $chapter->chapter_title ?: 'CAPÍTULO ' . $chapter->chapter_number,
            'articles' => $this->formatArticles(
                LegalOrderService::sortArticles($chapter->articles),
                $includeContent
            ),
        ];

        if ($chapter->subchapters->isNotEmpty()) {
            $data['subchapters'] = LegalOrderService::sortSubchapters($chapter->subchapters)
                ->map(fn (Subchapter $subchapter) => $this->formatSubchapter($subchapter, $includeContent))
                ->values()
                ->all();
        }

        return $data;
    }

    private function formatSubchapter(Subchapter $subchapter, bool $includeContent): array
    {
        return [
            'id' => $subchapter->id,
            'name' => $subchapter->subchapter_title ?: 'SUBCAPÍTULO ' . $subchapter->subchapter_number,
            'articles' => $this->formatArticles(
                LegalOrderService::sortArticles($subchapter->articles),
                $includeContent
            ),
        ];
    }

    private function formatArticles($articles, bool $includeContent): array
    {
        return LegalOrderService::sortFormattedArticles(
            LegalOrderService::sortArticles(collect($articles))
                ->map(fn (Article $article) => $this->formatArticle($article, $includeContent))
                ->values()
                ->all()
        );
    }

    private function formatArticle(Article $article, bool $includeContent): array
    {
        $data = [
            'id' => $article->id,
            'name' => $article->article_title,
        ];

        if ($includeContent) {
            $data['content'] = $article->article_content ?? '';
        }

        return array_merge($data, $this->formatAttachmentCounts($article));
    }

    private function formatAttachmentCounts(Article $article): array
    {
        $resolutions = (int) ($article->resolutions_count ?? 0);
        $videos = (int) ($article->videos_count ?? 0);
        $files = (int) ($article->files_count ?? 0);
        $opinions = (int) ($article->opinions_count ?? 0);
        $hasAttachments = ($resolutions + $videos + $files + $opinions) > 0;

        return [
            'has_attachments' => $hasAttachments,
            'attachments_count' => $hasAttachments
                ? [
                    'resolutions' => $resolutions,
                    'videos' => $videos,
                    'files' => $files,
                    'opinions' => $opinions,
                ]
                : null,
        ];
    }
}
