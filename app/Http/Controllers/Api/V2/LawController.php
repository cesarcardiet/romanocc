<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use App\Models\Article;
use App\Models\ArticleOpinion;
use App\Models\ArticleResolution;
use App\Models\ArticleVideo;
use App\Services\LawStructureService;
use App\Services\LawHierarchyService;
use App\Services\LegalOrderService;
use App\Services\IndexCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LawController extends Controller
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
     * GET /api/v2/laws
     * Retorna la estructura jerárquica paginada para la app móvil
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5); // Cargar 5 títulos por página
        $type = $request->get('type'); // Filtrar por tipo: 'ley' o 'reglamento'
        
        // Incluir siempre contenido completo e indicadores de adjuntos (plan migración backend)
        $includeContent = true;

        $articleWithCount = function ($q) {
            LegalOrderService::applyArticleOrder($q)
                ->withCount(['opinions', 'resolutions', 'videos', 'files']);
        };

        $query = Law::with([
            'titles' => function ($q) use ($articleWithCount) {
                LegalOrderService::applyTitleOrder($q)->with([
                    'articles' => $articleWithCount,
                    'chapters' => function ($qc) use ($articleWithCount) {
                        LegalOrderService::applyChapterOrder($qc)->with([
                            'articles' => $articleWithCount,
                            'subchapters' => function ($qs) use ($articleWithCount) {
                                LegalOrderService::applySubchapterOrder($qs)
                                    ->with(['articles' => $articleWithCount]);
                            },
                        ]);
                    }
                ]);
            },
        ])->orderBy('id', 'asc');
        
        // Aplicar filtro por tipo si se especifica
        if ($type) {
            $query->where('type', $type);
        }
        
        $laws = $query->get();

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $allFormattedData = $laws->flatMap(function ($law) use ($includeContent) {
            return LegalOrderService::sortTitles($law->titles)->map(function ($title) use ($law, $includeContent) {
                $formattedTitle = $this->lawStructureService->formatTitleWithChapters($title, $includeContent);
                $formattedTitle['law_id'] = $law->id;
                $formattedTitle['law_type'] = $law->type;
                $formattedTitle['law_name'] = $law->name;
                return $formattedTitle;
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
     * GET /api/v2/laws/{id}
     * Retorna una ley específica con estructura jerárquica
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $law = Law::with([
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

        if (!$law) {
            return response()->json([
                'success' => false, 
                'message' => 'Ley no encontrada'
            ], 404);
        }

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $formattedData = LegalOrderService::sortTitles($law->titles)->map(function ($title) use ($law) {
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
                'law_id' => $law->id,
                'law_type' => $law->type,
                'law_name' => $law->name,
            ];
        })->values();

        return response()->json([
            'success' => true, 
            'data' => $formattedData
        ], 200);
    }

    /**
     * GET /api/v2/laws/{id}/detail
     * Retorna información plana de la ley con opiniones, resoluciones, videos y adiciones
     */
    public function detail(Request $request, int $id): JsonResponse
    {
        $law = Law::with([
            'articles.opinions.user',
            'articles.resolutions',
            'articles.videos',
            'articles.files',
            'articles.comments.user',
            'titles.chapters.articles.opinions.user',
            'titles.chapters.articles.resolutions',
            'titles.chapters.articles.videos',
            'titles.chapters.articles.files',
            'titles.chapters.articles.comments.user',
            'titles.chapters.subchapters.articles.opinions.user',
            'titles.chapters.subchapters.articles.resolutions',
            'titles.chapters.subchapters.articles.videos',
            'titles.chapters.subchapters.articles.files',
            'titles.chapters.subchapters.articles.comments.user'
        ])->orderBy('id', 'asc')->find($id);

        if (!$law) {
            return response()->json([
                'success' => false, 
                'message' => 'Ley no encontrada'
            ], 404);
        }

        // Recopilar todos los artículos con sus datos relacionados
        $articlesData = collect();
        
        foreach ($law->articles as $article) {
            $articlesData->push([
                'id' => $article->id,
                'law_id' => $law->id,
                'law_name' => $law->name,
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
                        'url' => $resolution->url, // Enlace directo
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
                'foro' => $article->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'user_name' => $comment->user ? $comment->user->name : 'Usuario',
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                    ];
                }),
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
            ]);
        }

        foreach ($law->titles as $title) {
            foreach ($title->chapters as $chapter) {
                // Artículos directos del capítulo
                foreach ($chapter->articles as $article) {
                    $articlesData->push([
                        'id' => $article->id,
                        'law_id' => $law->id,
                        'law_name' => $law->name,
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
                                'url' => $resolution->url, // Enlace directo
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
                        'foro' => $article->comments->map(function ($comment) {
                            return [
                                'id' => $comment->id,
                                'comment' => $comment->comment,
                                'user_name' => $comment->user ? $comment->user->name : 'Usuario',
                                'created_at' => $comment->created_at,
                                'updated_at' => $comment->updated_at,
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
                            'law_id' => $law->id,
                            'law_name' => $law->name,
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
                            'foro' => $article->comments->map(function ($comment) {
                                return [
                                    'id' => $comment->id,
                                    'comment' => $comment->comment,
                                    'user_name' => $comment->user ? $comment->user->name : 'Usuario',
                                    'created_at' => $comment->created_at,
                                    'updated_at' => $comment->updated_at,
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
                'id' => $law->id,
                'title' => $law->name,
                'description' => 'Ley General de Contrataciones Públicas',
                'category' => 'ley',
                'articles' => $articlesData->values(),
                'created_at' => $law->created_at,
                'updated_at' => $law->updated_at,
            ]
        ], 200);
    }

    /**
     * GET /api/v2/articles/{id}
     * Retorna un único artículo con toda su información (opiniones, resoluciones, videos, archivos, foro)
     */
    public function articleDetail(Request $request, int $id): JsonResponse
    {
        $article = Article::with([
            'law',
            'opinions.user',
            'resolutions.user',
            'videos.user',
            'files',
            'comments.user',
            'chapter',
            'subchapter',
        ])->find($id);

        if (!$article || !$article->law) {
            return response()->json([
                'success' => false,
                'message' => 'Artículo no encontrado',
            ], 404);
        }

        $law = $article->law;
        $chapterLabel = $article->chapter
            ? ($article->chapter->chapter_title ?: 'CAPÍTULO ' . $article->chapter->chapter_number)
            : null;
        $subchapterLabel = $article->subchapter
            ? ($article->subchapter->subchapter_title ?: 'SUBCAPÍTULO ' . $article->subchapter->subchapter_number)
            : null;

        $data = [
            'id' => $article->id,
            'law_id' => $law->id,
            'law_name' => $law->name,
            'title' => $article->article_title,
            'content' => $article->article_content,
            'number' => $article->article_number,
            'chapter' => $chapterLabel,
            'subchapter' => $subchapterLabel,
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
            'foro' => $article->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $comment->user ? $comment->user->name : 'Usuario',
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];
            }),
            'created_at' => $article->created_at,
            'updated_at' => $article->updated_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * GET /api/v2/laws/{id}/hierarchy
     * Retorna la jerarquía de una ley con id y nombre (para índice)
     */
    public function hierarchy(int $id): JsonResponse
    {
        $law = $this->lawHierarchyService->findLaw($id, 'ley');

        if (!$law) {
            return response()->json([
                'success' => false,
                'message' => 'Ley no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->lawHierarchyService->buildIndex($law),
        ], 200);
    }

    /**
     * GET /api/v2/laws/{id}/hierarchy/content
     * Retorna la jerarquía de una ley con el contenido de los artículos
     */
    public function hierarchyContent(int $id): JsonResponse
    {
        $law = $this->lawHierarchyService->findLaw($id, 'ley');

        if (!$law) {
            return response()->json([
                'success' => false,
                'message' => 'Ley no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->lawHierarchyService->buildWithContent($law),
        ], 200);
    }

    /**
     * GET /api/v2/laws/index
     * Retorna la estructura completa del índice (sin contenido de artículos)
     */
    public function getIndex(Request $request): JsonResponse
    {
        $type = $request->get('type', 'ley'); // Por defecto solo leyes

        // Validar parámetro type
        if (!in_array($type, ['ley', 'reglamento', 'ambos'])) {
            return response()->json([
                'success' => false,
                'message' => "Parámetro 'type' inválido. Valores permitidos: 'ley', 'reglamento', 'ambos'",
                'errors' => [
                    'type' => ['El valor debe ser uno de: ley, reglamento, ambos']
                ]
            ], 400);
        }

        try {
            // Usar cache para mejorar rendimiento
            return IndexCacheService::remember($type, function () use ($type) {
                // Construir query según el tipo
                $query = Law::with([
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
            ]);

            // Aplicar filtro por tipo
            if ($type === 'ambos') {
                // No filtrar, traer todo
            } elseif ($type === 'ley') {
                $query->where('type', 'ley');
            } else {
                $query->where('type', 'reglamento');
            }

            $laws = $query->get();

            // Construir estructura del índice
            $indexData = [];
            $totalTitles = 0;
            $totalChapters = 0;
            $totalArticles = 0;
            $lawsCount = 0;
            $regulationsCount = 0;

            foreach ($laws as $law) {
                if ($law->type === 'ley') {
                    $lawsCount++;
                } else {
                    $regulationsCount++;
                }

                foreach (LegalOrderService::sortTitles($law->titles) as $title) {
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
                        'law_id' => $law->id,
                        'law_type' => $law->type,
                        'chapters' => $chaptersData
                    ];
                }
            }

            // Construir meta
            $meta = [
                'total_titles' => $totalTitles,
                'total_chapters' => $totalChapters,
                'total_articles' => $totalArticles,
                'law_type' => $type
            ];

            if ($type === 'ambos') {
                $meta['laws_count'] = $lawsCount;
                $meta['regulations_count'] = $regulationsCount;
            }

                return response()->json([
                    'success' => true,
                    'data' => $indexData,
                    'meta' => $meta
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
