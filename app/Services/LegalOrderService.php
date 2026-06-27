<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class LegalOrderService
{
    public static function applyTitleOrder(Builder|Relation $query): Builder|Relation
    {
        return $query;
    }

    public static function applyChapterOrder(Builder|Relation $query): Builder|Relation
    {
        return $query;
    }

    public static function applySubchapterOrder(Builder|Relation $query): Builder|Relation
    {
        return $query;
    }

    public static function applyArticleOrder(Builder|Relation $query): Builder|Relation
    {
        return $query;
    }

    public static function sortTitles(Collection $titles): Collection
    {
        return $titles->sort(fn ($a, $b) => self::compareTitles($a, $b))->values();
    }

    public static function sortChapters(Collection $chapters): Collection
    {
        return $chapters->sort(fn ($a, $b) => self::compareChapters($a, $b))->values();
    }

    public static function sortSubchapters(Collection $subchapters): Collection
    {
        return $subchapters->sort(fn ($a, $b) => self::compareSubchapters($a, $b))->values();
    }

    public static function sortArticles(Collection $articles): Collection
    {
        return $articles->sort(fn ($a, $b) => self::compareArticles($a, $b))->values();
    }

    /**
     * Orden final del árbol JSON (títulos, capítulos, subcapítulos, artículos) ascendente.
     */
    public static function sortFormattedHierarchy(array $data): array
    {
        if (! isset($data['titles']) || ! is_array($data['titles'])) {
            return $data;
        }

        $data['titles'] = collect($data['titles'])
            ->sort(fn ($a, $b) => self::compareFormattedTitles($a, $b))
            ->values()
            ->map(fn (array $title) => self::sortFormattedTitle($title))
            ->all();

        return $data;
    }

    public static function sortFormattedTitle(array $title): array
    {
        if (isset($title['chapters']) && is_array($title['chapters'])) {
            $title['chapters'] = collect($title['chapters'])
                ->sort(fn ($a, $b) => self::compareFormattedChapters($a, $b))
                ->values()
                ->map(fn (array $chapter) => self::sortFormattedChapter($chapter))
                ->all();
        }

        if (isset($title['articles']) && is_array($title['articles'])) {
            $title['articles'] = self::sortFormattedArticles($title['articles']);
        }

        return $title;
    }

    public static function sortFormattedChapter(array $chapter): array
    {
        if (isset($chapter['subchapters']) && is_array($chapter['subchapters'])) {
            $chapter['subchapters'] = collect($chapter['subchapters'])
                ->sort(fn ($a, $b) => self::compareFormattedSubchapters($a, $b))
                ->values()
                ->map(fn (array $subchapter) => self::sortFormattedSubchapter($subchapter))
                ->all();
        }

        if (isset($chapter['articles']) && is_array($chapter['articles'])) {
            $chapter['articles'] = self::sortFormattedArticles($chapter['articles']);
        }

        return $chapter;
    }

    public static function sortFormattedSubchapter(array $subchapter): array
    {
        if (isset($subchapter['articles']) && is_array($subchapter['articles'])) {
            $subchapter['articles'] = self::sortFormattedArticles($subchapter['articles']);
        }

        return $subchapter;
    }

    public static function sortFormattedArticles(array $articles): array
    {
        return collect($articles)
            ->sort(fn ($a, $b) => self::compareFormattedArticles($a, $b))
            ->values()
            ->all();
    }

    public static function compareTitles(object $a, object $b): int
    {
        $order = self::effectiveTitleOrder($a) <=> self::effectiveTitleOrder($b);

        return $order !== 0 ? $order : ($a->id <=> $b->id);
    }

    public static function compareChapters(object $a, object $b): int
    {
        $order = self::chapterOrderValue($a) <=> self::chapterOrderValue($b);

        return $order !== 0 ? $order : ($a->id <=> $b->id);
    }

    public static function compareSubchapters(object $a, object $b): int
    {
        $order = self::subchapterOrderValue($a) <=> self::subchapterOrderValue($b);

        return $order !== 0 ? $order : ($a->id <=> $b->id);
    }

    public static function compareArticles(object $a, object $b): int
    {
        $order = self::articleOrderValue($a) <=> self::articleOrderValue($b);

        return $order !== 0 ? $order : ($a->id <=> $b->id);
    }

    public static function compareFormattedTitles(array $a, array $b): int
    {
        $order = self::orderFromTitleName($a['name'] ?? '') <=> self::orderFromTitleName($b['name'] ?? '');

        return $order !== 0 ? $order : (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
    }

    public static function compareFormattedChapters(array $a, array $b): int
    {
        $order = self::orderFromLabel($a['name'] ?? '', 'chapter') <=> self::orderFromLabel($b['name'] ?? '', 'chapter');

        return $order !== 0 ? $order : (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
    }

    public static function compareFormattedSubchapters(array $a, array $b): int
    {
        $order = self::orderFromLabel($a['name'] ?? '', 'subchapter') <=> self::orderFromLabel($b['name'] ?? '', 'subchapter');

        return $order !== 0 ? $order : (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
    }

    public static function compareFormattedArticles(array $a, array $b): int
    {
        $order = self::orderFromLabel($a['name'] ?? '', 'article') <=> self::orderFromLabel($b['name'] ?? '', 'article');

        return $order !== 0 ? $order : (($a['id'] ?? 0) <=> ($b['id'] ?? 0));
    }

    public static function orderFromTitleName(string $name): int
    {
        return self::effectiveTitleOrder((object) ['title' => $name]);
    }

    public static function orderFromLabel(string $label, string $type): int
    {
        if ($type === 'title') {
            return self::orderFromTitleName($label);
        }

        return self::numericOrderValue(null, $label);
    }

    public static function chapterOrderValue(object $chapter): int
    {
        return self::numericOrderValue(
            isset($chapter->chapter_number) ? (string) $chapter->chapter_number : null,
            $chapter->chapter_title ?? null
        );
    }

    public static function subchapterOrderValue(object $subchapter): int
    {
        return self::numericOrderValue(
            isset($subchapter->subchapter_number) ? (string) $subchapter->subchapter_number : null,
            $subchapter->subchapter_title ?? null
        );
    }

    public static function articleOrderValue(object $article): int
    {
        $number = $article->article_number ?? null;

        if ($number !== null && $number !== '') {
            $fromNumber = self::numericOrderValue((string) $number, null);
            if ($fromNumber < PHP_INT_MAX) {
                return $fromNumber;
            }
        }

        return self::numericOrderValue(null, $article->article_title ?? null);
    }

    public static function parseOrderFromText(string $text): ?int
    {
        if (preg_match('/(?:T[ÍI]TULO|CAP[ÍI]TULO|SUBCAP[ÍI]TULO|ART[ÍI]CULO)\s+([IVXLCDM]+|\d+)/iu', $text, $matches)) {
            return self::toInt($matches[1]);
        }

        if (preg_match('/\b(\d+)\b/u', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public static function numericOrderValue(?string $number, ?string $label = null): int
    {
        if ($number !== null && $number !== '') {
            $parsed = self::toInt($number);
            if ($parsed !== null) {
                return $parsed;
            }

            $ordinal = self::parseSpanishOrdinal($number);
            if ($ordinal !== null) {
                return $ordinal;
            }
        }

        if ($label !== null && $label !== '') {
            $parsed = self::parseOrderFromText($label);
            if ($parsed !== null) {
                return $parsed;
            }

            $ordinal = self::parseSpanishOrdinal($label);
            if ($ordinal !== null) {
                return $ordinal;
            }
        }

        return PHP_INT_MAX;
    }

    public static function effectiveTitleOrder(object $title): int
    {
        $name = (string) ($title->title ?? '');

        if (self::isComplementarySection($name)) {
            return 800 + self::complementarySectionOrder($name);
        }

        $parsed = self::parseOrderFromText($name);
        if ($parsed !== null) {
            return $parsed;
        }

        $fromDb = (int) ($title->sort_order ?? 0);
        if ($fromDb > 0 && $fromDb < 800) {
            return $fromDb;
        }

        return 750;
    }

    public static function isComplementarySection(string $text): bool
    {
        return (bool) preg_match('/DISPOSICI(Ó|O)N(?:ES)?\s+COMPLEMENTARIA/iu', $text);
    }

    public static function complementarySectionOrder(string $text): int
    {
        if (preg_match('/DEROGATORIA/iu', $text)) {
            return 40;
        }
        if (preg_match('/MODIFICATORIA/iu', $text)) {
            return 30;
        }
        if (preg_match('/TRANSITORIA/iu', $text)) {
            return 20;
        }
        if (preg_match('/FINALES/iu', $text)) {
            return 10;
        }

        return 50;
    }

    public static function parseSpanishOrdinal(string $text): ?int
    {
        $map = [
            'única' => 1, 'unica' => 1,
            'único' => 1, 'unico' => 1,
            'primera' => 1, 'primero' => 1,
            'segunda' => 2, 'segundo' => 2,
            'tercera' => 3, 'tercero' => 3,
            'cuarta' => 4, 'cuarto' => 4,
            'quinta' => 5, 'quinto' => 5,
        ];

        $normalized = mb_strtolower(trim($text));

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        if (preg_match('/\b(única|unica|primera|primero|segunda|segundo|tercera|tercero)\b/iu', $text, $matches)) {
            $key = mb_strtolower($matches[1]);

            return $map[$key] ?? null;
        }

        return null;
    }

    public static function toInt(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (preg_match('/^[IVXLCDM]+$/i', $value)) {
            return self::romanToInt(strtoupper($value));
        }

        return null;
    }

    private static function romanToInt(string $roman): int
    {
        $map = ['M' => 1000, 'D' => 500, 'C' => 100, 'L' => 50, 'X' => 10, 'V' => 5, 'I' => 1];
        $result = 0;
        $length = strlen($roman);

        for ($i = 0; $i < $length; $i++) {
            $current = $map[$roman[$i]] ?? 0;
            $next = $map[$roman[$i + 1] ?? ''] ?? 0;
            $result += $current < $next ? -$current : $current;
        }

        return $result;
    }
}
