<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Law;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LawController extends Controller
{
    /**
     * Get all laws
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $laws = Law::with(['titles' => function ($query) {
            $query->withCount('chapters');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => [
                'laws' => $laws->map(function ($law) {
                    return [
                        'id' => $law->id,
                        'name' => $law->name,
                        'titles_count' => $law->titles->count(),
                        'chapters_count' => $law->titles->sum('chapters_count'),
                        'created_at' => $law->created_at,
                        'updated_at' => $law->updated_at,
                    ];
                }),
            ],
        ], 200);
    }

    /**
     * Get law details with titles, chapters, subchapters and articles
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $law = Law::with([
            'titles.chapters.subchapters.articles' => function ($query) {
                $query->orderBy('article_number', 'asc');
            }
        ])->find($id);

        if (!$law) {
            return response()->json([
                'success' => false,
                'message' => 'Ley no encontrada',
            ], 404);
        }

        $formattedLaw = [
            'id' => $law->id,
            'name' => $law->name,
            'titles' => $law->titles->map(function ($title) {
                return [
                    'id' => $title->id,
                    'title' => $title->title,
                    'chapters' => $title->chapters->map(function ($chapter) {
                        return [
                            'id' => $chapter->id,
                            'chapter' => $chapter->chapter,
                            'subchapters' => $chapter->subchapters->map(function ($subchapter) {
                                return [
                                    'id' => $subchapter->id,
                                    'subchapter' => $subchapter->subchapter_title,
                                    'articles' => $subchapter->articles->map(function ($article) {
                                        return [
                                            'id' => $article->id,
                                            'number' => $article->article_number,
                                            'title' => $article->article_title,
                                            'content' => $article->article_content,
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $formattedLaw,
        ], 200);
    }
}
