<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use App\Services\LawStructureService;
use App\Services\LawHierarchyService;
use App\Services\LegalOrderService;
use App\Services\IndexCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegulationController extends Controller
{
    protected LawStructureService $lawStructureService;
    protected LawHierarchyService $lawHierarchyService;

    public function __construct(
        LawStructureService $lawStructureService,
        LawHierarchyService $lawHierarchyService
    ) {
        $this->lawStructureService = $lawStructureService;
        $this->lawHierarchyService = $lawHierarchyService;
    }

    /**
     * GET /api/v2/regulations
     * Retorna la estructura jerárquica paginada para la app móvil
     * Nota: Usamos el mismo modelo Law pero filtramos por tipo 'reglamento'
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5); // Cargar 5 títulos por página
        
        $regulations = Law::where('type', 'reglamento')
            ->with([
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
            ])->orderBy('id', 'asc')->get();

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $allFormattedData = $regulations->flatMap(function ($regulation) {
            return LegalOrderService::sortTitles($regulation->titles)->map(function ($title) use ($regulation) {
                if ($title->chapters->isNotEmpty()) {
                    $chapters = $title->chapters->map(function ($chapter) {
                        return $this->lawStructureService->formatChapterForShow($chapter);
                    });
                } else {
                    $chapters = $this->lawStructureService->createVirtualChapterForShow($title);
                }

                return [
                    'title' => (string) $title->title,
                    'chapters' => $chapters->values(),
                    'law_id' => $regulation->id,
                    'law_type' => $regulation->type,
                    'law_name' => $regulation->name,
                ];
            });
        })->values();

        // Aplicar paginación
        $totalItems = $allFormattedData->count();
        $offset = ($page - 1) * $perPage;
        $paginatedData = $allFormattedData->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => ceil($totalItems / $perPage),
                'has_next_page' => ($page * $perPage) < $totalItems,
                'has_previous_page' => $page > 1
            ]
        ], 200);
    }

    /**
     * GET /api/v2/regulations/{id}
     * Retorna un reglamento específico con estructura jerárquica
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $regulation = Law::where('type', 'reglamento')
            ->with([
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
            ])->orderBy('id', 'asc')->find($id);

        if (!$regulation) {
            return response()->json([
                'success' => false, 
                'message' => 'Reglamento no encontrado'
            ], 404);
        }

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $formattedData = LegalOrderService::sortTitles($regulation->titles)->map(function ($title) use ($regulation) {
            if ($title->chapters->isNotEmpty()) {
                $chapters = $title->chapters->map(function ($chapter) {
                    return $this->lawStructureService->formatChapterForShow($chapter);
                });
            } else {
                $chapters = $this->lawStructureService->createVirtualChapterForShow($title);
            }

            return [
                'title' => (string) $title->title,
                'chapters' => $chapters->values(),
                'law_id' => $regulation->id,
                'law_type' => $regulation->type,
                'law_name' => $regulation->name,
            ];
        })->values();

        return response()->json([
            'success' => true, 
            'data' => $formattedData
        ], 200);
    }

    /**
     * GET /api/v2/regulations/{id}/detail
     * Retorna información plana del reglamento con opiniones, resoluciones, videos y archivos
     */
    public function detail(Request $request, int $id): JsonResponse
    {
        $regulation = Law::where('type', 'reglamento')->with([
            'articles.opinions',
            'articles.resolutions',
            'articles.videos',
            'articles.files',
            'titles.chapters.articles.opinions',
            'titles.chapters.articles.resolutions',
            'titles.chapters.articles.videos',
            'titles.chapters.articles.files',
            'titles.chapters.subchapters.articles.opinions',
            'titles.chapters.subchapters.articles.resolutions',
            'titles.chapters.subchapters.articles.videos',
            'titles.chapters.subchapters.articles.files'
        ])->orderBy('id', 'asc')->find($id);

        if (!$regulation) {
            return response()->json([
                'success' => false, 
                'message' => 'Reglamento no encontrado'
            ], 404);
        }

        // Recopilar todos los artículos con sus datos relacionados
        $articlesData = collect();

        // Artículos directos del reglamento (sin título/capítulo)
        foreach ($regulation->articles as $article) {
            $articlesData->push([
                'id' => $article->id,
                'law_id' => $regulation->id,
                'law_name' => $regulation->name,
                'title' => $article->article_title,
                'content' => $article->article_content,
                'number' => $article->article_number,
                'chapter' => null,
                'subchapter' => null,
                'opinions' => $article->opinions->map(function ($opinion) {
                    return [
                        'id' => $opinion->id,
                        'opinion' => $opinion->opinion,
                        'url_file' => $opinion->url_file_full,
                        'user_name' => $opinion->user ? $opinion->user->name : 'Usuario',
                        'created_at' => $opinion->created_at,
                        'updated_at' => $opinion->updated_at,
                    ];
                }),
                'resolutions' => $article->resolutions->map(function ($resolution) {
                    return [
                        'id' => $resolution->id,
                        'name' => $resolution->name,
                        'url' => $resolution->url,
                        'url_pdf' => $resolution->url_pdf_full,
                        'user_name' => $resolution->user ? $resolution->user->name : 'Usuario',
                        'created_at' => $resolution->created_at,
                        'updated_at' => $resolution->updated_at,
                    ];
                }),
                'videos' => $article->videos->map(function ($video) {
                    return [
                        'id' => $video->id,
                        'name' => $video->name,
                        'url' => $video->url,
                        'user_name' => $video->user ? $video->user->name : 'Usuario',
                        'created_at' => $video->created_at,
                        'updated_at' => $video->updated_at,
                    ];
                }),
                'files' => $article->files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_url' => $file->file_url,
                        'file_path' => $file->file_path,
                        'created_at' => $file->created_at,
                        'updated_at' => $file->updated_at,
                    ];
                }),
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
            ]);
        }

        foreach ($regulation->titles as $title) {
            foreach ($title->chapters as $chapter) {
                // Artículos directos del capítulo
                foreach ($chapter->articles as $article) {
                    $articlesData->push([
                        'id' => $article->id,
                        'law_id' => $regulation->id,
                        'law_name' => $regulation->name,
                        'title' => $article->article_title,
                        'content' => $article->article_content,
                        'number' => $article->article_number,
                        'chapter' => $chapter->chapter_title ?: 'CAPÍTULO ' . $chapter->chapter_number,
                        'subchapter' => null,
                        'opinions' => $article->opinions->map(function ($opinion) {
                            return [
                                'id' => $opinion->id,
                                'opinion' => $opinion->opinion,
                                'url_file' => $opinion->url_file_full,
                                'user_name' => $opinion->user ? $opinion->user->name : 'Usuario',
                                'created_at' => $opinion->created_at,
                                'updated_at' => $opinion->updated_at,
                            ];
                        }),
                        'resolutions' => $article->resolutions->map(function ($resolution) {
                            return [
                                'id' => $resolution->id,
                                'name' => $resolution->name,
                                'url' => $resolution->url,
                                'url_pdf' => $resolution->url_pdf_full,
                                'user_name' => $resolution->user ? $resolution->user->name : 'Usuario',
                                'created_at' => $resolution->created_at,
                                'updated_at' => $resolution->updated_at,
                            ];
                        }),
                        'videos' => $article->videos->map(function ($video) {
                            return [
                                'id' => $video->id,
                                'name' => $video->name,
                                'url' => $video->url,
                                'user_name' => $video->user ? $video->user->name : 'Usuario',
                                'created_at' => $video->created_at,
                                'updated_at' => $video->updated_at,
                            ];
                        }),
                        'files' => $article->files->map(function ($file) {
                            return [
                                'id' => $file->id,
                                'file_name' => $file->file_name,
                                'file_url' => $file->file_url,
                                'file_path' => $file->file_path,
                                'created_at' => $file->created_at,
                                'updated_at' => $file->updated_at,
                            ];
                        }),
                        'created_at' => $article->created_at,
                        'updated_at' => $article->updated_at,
                    ]);
                }

                // Artículos de subcapítulos
                foreach ($chapter->subchapters as $subchapter) {
                    foreach ($subchapter->articles as $article) {
                        $articlesData->push([
                            'id' => $article->id,
                            'law_id' => $regulation->id,
                            'law_name' => $regulation->name,
                            'title' => $article->article_title,
                            'content' => $article->article_content,
                            'number' => $article->article_number,
                            'chapter' => $chapter->chapter_title ?: 'CAPÍTULO ' . $chapter->chapter_number,
                            'subchapter' => $subchapter->subchapter_title,
                            'opinions' => $article->opinions->map(function ($opinion) {
                                return [
                                    'id' => $opinion->id,
                                    'opinion' => $opinion->opinion,
                                    'url_file' => $opinion->url_file_full,
                                    'user_name' => $opinion->user ? $opinion->user->name : 'Usuario',
                                    'created_at' => $opinion->created_at,
                                    'updated_at' => $opinion->updated_at,
                                ];
                            }),
                            'resolutions' => $article->resolutions->map(function ($resolution) {
                                return [
                                    'id' => $resolution->id,
                                    'name' => $resolution->name,
                                    'url' => $resolution->url,
                                    'url_pdf' => $resolution->url_pdf_full,
                                    'user_name' => $resolution->user ? $resolution->user->name : 'Usuario',
                                    'created_at' => $resolution->created_at,
                                    'updated_at' => $resolution->updated_at,
                                ];
                            }),
                            'videos' => $article->videos->map(function ($video) {
                                return [
                                    'id' => $video->id,
                                    'name' => $video->name,
                                    'url' => $video->url,
                                    'user_name' => $video->user ? $video->user->name : 'Usuario',
                                    'created_at' => $video->created_at,
                                    'updated_at' => $video->updated_at,
                                ];
                            }),
                            'files' => $article->files->map(function ($file) {
                                return [
                                    'id' => $file->id,
                                    'file_name' => $file->file_name,
                                    'file_url' => $file->file_url,
                                    'file_path' => $file->file_path,
                                    'created_at' => $file->created_at,
                                    'updated_at' => $file->updated_at,
                                ];
                            }),
                            'created_at' => $article->created_at,
                            'updated_at' => $article->updated_at,
                        ]);
                    }
                }
            }
        }

        // Ordenar los artículos por número de forma numérica
        $articlesData = $articlesData->sortBy(function ($article) {
            return (int) $article['number'];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $regulation->id,
                'title' => $regulation->name,
                'description' => 'Reglamento de la Ley General de Contrataciones Públicas',
                'category' => 'reglamento',
                'articles' => $articlesData->values(),
                'created_at' => $regulation->created_at,
                'updated_at' => $regulation->updated_at,
            ]
        ], 200);
    }

    /**
     * GET /api/v2/regulations/{id}/hierarchy
     * Retorna la jerarquía de un reglamento con id y nombre (para índice)
     */
    public function hierarchy(int $id): JsonResponse
    {
        $regulation = $this->lawHierarchyService->findLaw($id, 'reglamento');

        if (!$regulation) {
            return response()->json([
                'success' => false,
                'message' => 'Reglamento no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->lawHierarchyService->buildIndex($regulation),
        ], 200);
    }

    /**
     * GET /api/v2/regulations/{id}/hierarchy/content
     * Retorna la jerarquía de un reglamento con el contenido de los artículos
     */
    public function hierarchyContent(int $id): JsonResponse
    {
        $regulation = $this->lawHierarchyService->findLaw($id, 'reglamento');

        if (!$regulation) {
            return response()->json([
                'success' => false,
                'message' => 'Reglamento no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->lawHierarchyService->buildWithContent($regulation),
        ], 200);
    }

    /**
     * GET /api/v2/regulations/index
     * Retorna la estructura completa del índice para reglamentos (sin contenido de artículos)
     */
    public function getIndex(Request $request): JsonResponse
    {
        try {
            // Usar cache para mejorar rendimiento
            return IndexCacheService::remember('reglamento', function () {
                $regulations = Law::where('type', 'reglamento')
                ->with([
                    'titles' => function ($q) {
                        LegalOrderService::applyTitleOrder($q)->with([
                            'chapters' => function ($qc) {
                                LegalOrderService::applyChapterOrder($qc)->with([
                                    'articles' => function ($qa) {
                                        LegalOrderService::applyArticleOrder(
                                            $qa->whereNull('subchapter_id')
                                                ->select('id', 'chapter_id', 'subchapter_id', 'article_number')
                                        );
                                    },
                                    'subchapters' => function ($qs) {
                                        LegalOrderService::applySubchapterOrder($qs)->with(['articles' => function ($qsa) {
                                            LegalOrderService::applyArticleOrder(
                                                $qsa->select('id', 'subchapter_id', 'article_number')
                                            );
                                        }]);
                                    }
                                ]);
                            },
                            'articles' => function ($qa) {
                                LegalOrderService::applyArticleOrder(
                                    $qa->whereNull('chapter_id')
                                        ->select('id', 'title_id', 'chapter_id', 'article_number')
                                );
                            }
                        ]);
                    }
                ])->orderBy('id', 'asc')->get();

            // Construir estructura del índice
            $indexData = [];
            $totalTitles = 0;
            $totalChapters = 0;
            $totalArticles = 0;

            foreach ($regulations as $regulation) {
                foreach (LegalOrderService::sortTitles($regulation->titles) as $title) {
                    $totalTitles++;
                    $chaptersData = [];

                    if ($title->chapters->isNotEmpty()) {
                        // Título con capítulos
                        foreach (LegalOrderService::sortChapters($title->chapters) as $chapter) {
                            $totalChapters++;
                            
                            // Contar artículos directos del capítulo
                            $directArticlesCount = $chapter->articles->count();
                            
                            // Procesar subcapítulos
                            $subchaptersData = [];
                            $subchapterArticlesTotal = 0;
                            
                            if ($chapter->subchapters->isNotEmpty()) {
                                foreach (LegalOrderService::sortSubchapters($chapter->subchapters) as $subchapter) {
                                    $subchapterArticlesCount = $subchapter->articles->count();
                                    $subchapterArticlesTotal += $subchapterArticlesCount;
                                    $totalArticles += $subchapterArticlesCount;
                                    
                                    $subchaptersData[] = [
                                        'subchapter' => $subchapter->subchapter_title ?: "SUBCAPÍTULO " . $subchapter->subchapter_number,
                                        'articles_count' => $subchapterArticlesCount
                                    ];
                                }
                            }
                            
                            // Total de artículos del capítulo = directos + de subcapítulos
                            $chapterArticlesCount = $directArticlesCount + $subchapterArticlesTotal;
                            $totalArticles += $directArticlesCount;
                            
                            $chaptersData[] = [
                                'chapter' => $chapter->chapter_title ?: "CAPÍTULO " . $chapter->chapter_number,
                                'articles_count' => $chapterArticlesCount,
                                'subchapters' => !empty($subchaptersData) ? $subchaptersData : null
                            ];
                        }
                    } else {
                        // Título sin capítulos (artículos directos del título)
                        $titleArticlesCount = $title->articles->count();
                        $totalArticles += $titleArticlesCount;
                        
                        // Crear un capítulo virtual
                        $chaptersData[] = [
                            'chapter' => 'ARTÍCULOS',
                            'articles_count' => $titleArticlesCount,
                            'subchapters' => null
                        ];
                        $totalChapters++;
                    }

                    $indexData[] = [
                        'title' => (string) $title->title,
                        'law_id' => $regulation->id,
                        'law_type' => 'reglamento',
                        'chapters' => $chaptersData
                    ];
                }
            }

                return response()->json([
                    'success' => true,
                    'data' => $indexData,
                    'meta' => [
                        'total_titles' => $totalTitles,
                        'total_chapters' => $totalChapters,
                        'total_articles' => $totalArticles,
                        'law_type' => 'reglamento'
                    ]
                ], 200);
            });

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al generar el índice',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
