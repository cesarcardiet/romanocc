<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use App\Models\Regulation;
use App\Services\LawStructureService;
use App\Services\LegalOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected LawStructureService $lawStructureService;

    public function __construct(LawStructureService $lawStructureService)
    {
        $this->lawStructureService = $lawStructureService;
    }

    /**
     * Buscar en leyes y reglamentos
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'type' => 'nullable|string|in:ley,reglamento,ambos',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->input('query');
        $type = $request->input('type', 'ambos');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        // La normalización ahora se maneja en el servicio

        $results = collect();

        // Buscar en leyes (que incluye tanto leyes como reglamentos)
        if ($type === 'ley' || $type === 'reglamento' || $type === 'ambos') {
            // Aplicar filtro por tipo si se especifica
            $queryBuilder = Law::query();
            if ($type !== 'ambos') {
                $queryBuilder->where('type', $type);
            }

            $laws = $queryBuilder->with([
                'titles' => function ($q) {
                    LegalOrderService::applyTitleOrder($q)->with([
                        'articles' => fn ($qa) => LegalOrderService::applyArticleOrder($qa),
                        'chapters' => function ($qc) {
                            LegalOrderService::applyChapterOrder($qc)->with([
                                'articles' => fn ($qa) => LegalOrderService::applyArticleOrder($qa),
                                'subchapters' => function ($qs) {
                                    LegalOrderService::applySubchapterOrder($qs)
                                        ->with(['articles' => fn ($qsa) => LegalOrderService::applyArticleOrder($qsa)]);
                                },
                            ]);
                        }
                    ]);
                },
            ])
            ->where(function ($q) use ($query) {
                $q->whereHas('titles.articles', function ($subQ) use ($query) {
                    $subQ->where('article_title', 'like', "%{$query}%")
                         ->orWhere('article_content', 'like', "%{$query}%");
                })
                ->orWhereHas('titles.chapters.articles', function ($subQ) use ($query) {
                    $subQ->where('article_title', 'like', "%{$query}%")
                         ->orWhere('article_content', 'like', "%{$query}%");
                })
                ->orWhereHas('titles.chapters.subchapters.articles', function ($subQ) use ($query) {
                    $subQ->where('article_title', 'like', "%{$query}%")
                         ->orWhere('article_content', 'like', "%{$query}%");
                });
            })
            ->orderBy('id', 'asc')
            ->get()
            ->flatMap(function ($law) use ($type, $query) {
                return $this->formatLawForSearch($law, $type, $query);
            });

            $results = $results->merge($laws);
        }

        // Aplicar paginación
        $totalItems = $results->count();
        $totalPages = ceil($totalItems / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $results->slice($offset, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedResults->values(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1,
            ],
            'search_info' => [
                'query' => $query,
                'type' => $type,
                'total_results' => $totalItems,
            ]
        ]);
    }

    /**
     * Formatear ley para búsqueda
     */
    private function formatLawForSearch($law, $type, $query)
    {
        $formattedTitles = [];

        // Mantener la estructura jerárquica original
        foreach (LegalOrderService::sortTitles($law->titles) as $title) {
            $formattedTitle = $this->lawStructureService->formatTitleForSearch($title, $query);
            
            // Solo incluir títulos que tengan capítulos con artículos
            if (!empty($formattedTitle['chapters'])) {
                $formattedTitles[] = [
                    'id' => $law->id,
                    'title' => $formattedTitle['title'],
                    'type' => $type,
                    'chapters' => $formattedTitle['chapters'],
                ];
            }
        }

        // Devolver múltiples estructuras jerárquicas (una por cada título)
        return $formattedTitles;
    }

}
