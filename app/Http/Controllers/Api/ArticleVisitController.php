<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleVisitController extends Controller
{
    /**
     * Register a visit to an article
     *
     * @param Request $request
     * @param Article $article
     * @return JsonResponse
     */
    public function store(Request $request, Article $article): JsonResponse
    {
        $user = $request->user();
        $ipAddress = $request->ip();

        // Check if this user has already visited this article recently (within 24 hours)
        $recentVisit = ArticleVisit::where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($recentVisit) {
            return response()->json([
                'success' => true,
                'message' => 'Visita ya registrada recientemente',
                'data' => [
                    'article_id' => $article->id,
                    'visit_id' => $recentVisit->id,
                    'visited_at' => $recentVisit->created_at,
                ],
            ], 200);
        }

        // Create new visit record
        $visit = ArticleVisit::create([
            'article_id' => $article->id,
            'user_id' => $user->id,
            'ip_address' => $ipAddress,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visita registrada exitosamente',
            'data' => [
                'article_id' => $article->id,
                'visit_id' => $visit->id,
                'visited_at' => $visit->created_at,
            ],
        ], 201);
    }
}
